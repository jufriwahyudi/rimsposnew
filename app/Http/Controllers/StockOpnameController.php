<?php

namespace App\Http\Controllers;

use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\StockOpnamePeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockOpnameController extends Controller
{
    public function create(StockOpnamePeriod $period)
    {
        return view('stock-opnames.create', compact('period'));
    }

    public function store(Request $request, StockOpnamePeriod $period)
    {
        try {
            DB::transaction(function () use ($request, $period, &$opname) {

                // handle apabila stock opname duplikat posisi pada periode yang sama
                $existingOpname = StockOpname::where('stock_opname_period_id', $period->id)
                    ->where('posisi', $request->posisi)
                    ->first();
                if ($existingOpname) {
                    throw new \Exception('Stock Opname untuk posisi "' . $request->posisi . '" pada periode ini sudah ada.');
                }
                $opname = StockOpname::create([
                    'store_id' => session('store_id'),
                    'code' => 'SO-' . time(),
                    'stock_opname_period_id' => $period->id,
                    'posisi' => $request->posisi, // warehouse / store
                    'input_date' => now()->toDateString(),
                    'created_by' => auth()->id()
                ]);



                /**
                 * Hitung stok sistem per VARIANT
                 * berdasarkan stock_movements
                 */
                $stocks = DB::table('product_variants as pv')
                    ->leftJoin('stock_movements as sm', function ($join) use ($request, $period) {
                        $join->on('pv.id', '=', 'sm.product_variant_id')
                            ->where('sm.posisi', '=', $request->posisi)
                            ->where('sm.tanggal', '<=', $period->period_date . ' 23:59:59');
                    })
                    ->where('pv.store_id', session('store_id'))
                    ->select(
                        'pv.id as product_variant_id',
                        DB::raw("
                            COALESCE(
                                SUM(
                                    CASE
                                        WHEN sm.direction = 'in' THEN sm.qty
                                        WHEN sm.direction = 'out' THEN -sm.qty
                                    END
                                ),
                                0
                            ) AS system_qty
                        ")
                    )
                    ->where('pv.is_active', 'Y')
                    ->groupBy('pv.id')
                    ->get();

                $items = [];

                foreach ($stocks as $stock) {
                    $items[] = [
                        'stock_opname_id'   => $opname->id,
                        'product_variant_id' => $stock->product_variant_id,
                        'system_qty'        => $stock->system_qty,
                        'physical_qty'      => $stock->system_qty, // default
                        'difference_qty'    => 0,
                        'status'            => 'MATCH',
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ];
                }

                if (!empty($items)) {
                    StockOpnameItem::insert($items);
                }
            });

            return redirect()->route('stock-opnames.edit', $opname);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal membuat Stock Opname: ' . $e->getMessage()]);
        }
    }


    public function edit(StockOpname $stockOpname)
    {
        $stockOpname->load('items.productVariant.product');
        $stockOpname->load('items.productVariant.variantAttributes.value');
        $stockOpname->load('period');
        // dd(json_encode($stockOpname->take(10), JSON_PRETTY_PRINT));
        return view('stock-opnames.edit', compact('stockOpname'));
    }

    public function update(Request $request, StockOpnameItem $stockOpnameItem)
    {
        $physical = $request->input('physical_qty');
        $hargaBeli = $request->input('harga_beli');
        // dd($stockOpnameItem);
        $difference = $physical - $stockOpnameItem->system_qty;

        $stockOpnameItem->update([
            'physical_qty' => $physical,
            'difference_qty' => $difference,
            'harga_beli' => $hargaBeli,
            'status' => $difference == 0 ? 'MATCH' : ($difference > 0 ? 'EXCESS' : 'SHORTAGE')
        ]);

        $stockOpnameItem->opname()->update(['status' => 'COUNTED']);

        // check apakah update berhasil
        if ($stockOpnameItem->wasChanged()) {
            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false], 400);
        }
    }

    public function show(StockOpname $stockOpname)
    {
        return view('stock-opnames.show', compact('stockOpname'));
    }

    public function approve(StockOpname $stockOpname)
    {
        DB::transaction(function () use ($stockOpname) {

            // 1. Approve stock opname
            $stockOpname->update([
                'status'       => 'APPROVED',
                'approved_by'  => auth()->id(),
                'approved_at'  => now(),
            ]);

            // 2. Buat Stock Adjustment (DRAFT)
            $adjustment = StockAdjustment::create([
                'code'             => $this->generateCode('SA'), // helper kamu sendiri
                'effective_date'   => $stockOpname->period->period_date,
                'posisi'           => $stockOpname->posisi,
                'reason_type'      => 'OPNAME',
                'notes'            => 'Adjustment from stock opname #' . $stockOpname->code,
                'status'           => 'DRAFT',
                'stock_opname_id'  => $stockOpname->id,
                'created_by'       => auth()->id(),
            ]);

            // 3. Loop item opname
            foreach ($stockOpname->items as $opnameItem) {

                $diffQty = (int)$opnameItem->difference_qty;

                if ($diffQty == 0) {
                    continue;
                }

                /**
                 * 4. Ambil batch sesuai kebutuhan
                 * - Jika minus → kurangi dari batch (FIFO / LIFO tergantung rule)
                 * - Jika plus  → tambahkan batch baru / batch virtual
                 */
                $this->generateAdjustmentItems(
                    adjustment: $adjustment,
                    opnameItem: $opnameItem,
                    diffQty: $diffQty,
                    posisi: $stockOpname->posisi
                );
            }
        });

        return back()->with('success', 'Stock Opname approved & draft adjustment created.');
    }

    public function cancel(StockOpname $stockOpname)
    {
        $stockOpname->update([
            'status' => 'CANCELLED',
            'approved_by' => auth()->id(),
            'approved_at' => now()
        ]);

        return back()->with('success', 'Stock Opname cancelled successfully.');
    }

    protected function generateAdjustmentItems(
        StockAdjustment $adjustment,
        $opnameItem,
        int $diffQty,
        string $posisi
    ) {
        if ($diffQty > 0) {
            $this->handlePositiveAdjustment($adjustment, $opnameItem, $diffQty, $posisi);
        } else {
            $this->handleNegativeAdjustment($adjustment, $opnameItem, abs($diffQty), $posisi);
        }
    }

    protected function handlePositiveAdjustment($adjustment, $opnameItem, $qty, $posisi)
    {
        $lastBatch = StockBatch::where('product_variant_id', $opnameItem->product_variant_id)
            ->where('posisi', $posisi)
            ->orderByDesc('id')
            ->first();

        if (!$lastBatch) {
            $batch = StockBatch::create([
                'product_variant_id' => $opnameItem->product_variant_id,
                'posisi'             => $posisi,
                'tanggal_masuk'      => $adjustment->effective_date,
                'qty_awal'           => $qty,
                'qty_sisa'           => $qty,
                'harga_beli'         => $opnameItem->harga_beli, // atau nullable
                'sumber'             => 'opname',
            ]);
            $lastBatch = $batch;
            StockMovement::create([
                'product_variant_id' => $opnameItem->product_variant_id,
                'stock_batch_id'     => $batch->id,
                'posisi'             => $posisi,
                'tanggal'            => $adjustment->effective_date,
                'tipe'               => 'adjust',
                'direction'          => 'in',
                'qty'                => $qty,
                'ref_type'           => 'StockAdjustment', // atau nullable
                'ref_id'             => $adjustment->id,
            ]);
        }

        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'stock_batch_id'      => $lastBatch->id,
            'product_variant_id'  => $opnameItem->product_variant_id,
            'qty'                 => $qty,
            'cost'                => $lastBatch->harga_beli,
            'total_value'         => $qty * $lastBatch->harga_beli,
        ]);
    }

    protected function handleNegativeAdjustment($adjustment, $opnameItem, $qty, $posisi)
    {
        // dd($opnameItem);
        $batches = StockBatch::where('product_variant_id', $opnameItem->product_variant_id)
            ->where('posisi', $posisi)
            ->where('qty_sisa', '>', 0)
            ->orderBy('created_at') // FIFO
            ->get();

        foreach ($batches as $batch) {

            if ($qty <= 0) {
                break;
            }

            $takeQty = min($batch->qty_sisa, $qty);
            StockMovement::create([
                'product_variant_id' => $opnameItem->product_variant_id,
                'stock_batch_id'     => $batch->id,
                'posisi'             => $posisi,
                'tanggal'            => $adjustment->effective_date,
                'tipe'               => 'adjust',
                'direction'          => 'out',
                'qty'                => $takeQty,
                'ref_type'           => 'StockAdjustment', // atau nullable
                'ref_id'             => $adjustment->id,
            ]);

            StockAdjustmentItem::create([
                'stock_adjustment_id' => $adjustment->id,
                'product_variant_id'  => $opnameItem->product_variant_id,
                'stock_batch_id'      => $batch->id,
                'qty'                 => -$takeQty,
                'cost'                => $batch->harga_beli,
                'total_value'         => - ($takeQty * $batch->harga_beli),
            ]);

            $qty -= $takeQty;
        }
    }


    private function generateCode($prefix)
    {
        $datePart = date('Ymd');
        $storeId = session('store_id');
        $count = StockAdjustment::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->whereDate('created_at', now()->toDateString())
            ->count() + 1;

        do {
            $code = sprintf("%s-%s-%04d", $prefix, $datePart, $count);
            $exists = StockAdjustment::withoutGlobalScopes()
                ->where('store_id', $storeId)
                ->where('code', $code)
                ->exists();
            if ($exists) {
                $count++;
            }
        } while ($exists);

        return $code;
    }
}
