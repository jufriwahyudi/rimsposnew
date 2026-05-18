<?php

namespace App\Http\Controllers;

use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Services\JournalEntryService;
use App\Services\StockAdjustmentPostingService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockAdjustmentController extends Controller
{
    public function index()
    {
        $adjustments = StockAdjustment::with('opname')
            ->where('status', 'DRAFT')
            ->orderByDesc('id')
            ->paginate(20);
        $status = 'DRAFT';

        return view('stock-adjustments.index', compact('adjustments', 'status'));
    }

    public function historyPosted()
    {
        $adjustments = StockAdjustment::with('opname')
            ->where('status', 'POSTED')
            ->where('created_by', auth()->id())
            ->orderByDesc('id')
            ->paginate(20);
        $status = 'POSTED';

        return view('stock-adjustments.index', compact('adjustments', 'status'));
    }

    public function show(StockAdjustment $stockAdjustment)
    {
        $stockAdjustment->load([
            'items.productVariant.product',
            'items.productVariant.variantAttributes.value',
            'items.adjustment'
        ]);

        $hasZeroCost = $stockAdjustment->items()
            ->where('cost', '<=', 0)
            ->exists();

        return view('stock-adjustments.show', compact('stockAdjustment', 'hasZeroCost'));
    }

    public function post(StockAdjustment $stockAdjustment, StockAdjustmentPostingService $service)
    {
        // VALIDASI
        $invalidItems = $stockAdjustment->items()
            ->where('cost', '<=', 0)
            ->count();

        if ($invalidItems > 0) {
            return back()->withErrors('Masih ada item dengan cost = 0. Lengkapi dulu sebelum posting.');
        }
        try {
            DB::transaction(function () use ($stockAdjustment, $service) {
                // 1️⃣ Posting stok (batch + movement)
                $result = $service->post($stockAdjustment);

                // 2️⃣ Generate jurnal akuntansi
                $this->generateJournal($stockAdjustment, $result);
            });

            return back()->with('success', 'Stock Adjustment selesai diposting.');
        } catch (Exception $e) {
            Log::error('Gagal posting stock adjustment', [
                'stock_adjustment_id' => $stockAdjustment->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors('Gagal posting stock adjustment: ' . $e->getMessage());
        }
    }

    public function updateItemCost(Request $request, StockAdjustmentItem $stockAdjustmentItem)
    {
        $request->validate([
            'cost'    => 'required|numeric|min:0'
        ]);

        $stockAdjustmentItem->update([
            'cost' => $request->cost,
            'total_value' => $stockAdjustmentItem->qty * $request->cost
        ]);

        return redirect()->back()->with('success', 'Cost item berhasil diperbarui.');
    }

    protected function generateJournal(StockAdjustment $adjustment, array $result)
    {
        // SAFETY CHECK
        if (($result['increase'] ?? 0) <= 0 && ($result['decrease'] ?? 0) <= 0) {
            throw new Exception('Tidak ada nilai adjustment untuk dijurnal.');
        }

        if ($adjustment->nojurnal) {
            throw new Exception('Jurnal sudah pernah dibuat.');
        }

        if ($adjustment->posisi === 'store') {
            $inventoryAccount = '11.04.13'; // Persediaan Toko
        } else {
            $inventoryAccount = '11.04.14'; // Persediaan Gudang
        }
        $diffAccount = '11.04.15'; // Selisih Opname Persediaan

        $entries = [];

        /**
         * SELISIH LEBIH
         * Dr Persediaan
         * Cr Selisih Opname
         */
        if (($result['increase'] ?? 0) > 0) {
            $entries[] = [
                'kode_akun' => $inventoryAccount,
                'amount'    => $result['increase'],
                'type'      => 'debet',
            ];

            $entries[] = [
                'kode_akun' => $diffAccount,
                'amount'    => $result['increase'],
                'type'      => 'kredit',
            ];
        }

        /**
         * SELISIH KURANG
         * Dr Selisih Opname
         * Cr Persediaan
         */
        if (($result['decrease'] ?? 0) > 0) {
            $entries[] = [
                'kode_akun' => $diffAccount,
                'amount'    => $result['decrease'],
                'type'      => 'debet',
            ];

            $entries[] = [
                'kode_akun' => $inventoryAccount,
                'amount'    => $result['decrease'],
                'type'      => 'kredit',
            ];
        }

        // FINAL CHECK
        if (count($entries) < 2) {
            throw new Exception('Jurnal tidak balance atau data tidak valid.');
        }
        // dd($entries);
        try {
            $journalService = new JournalEntryService();

            $voucher = $journalService->create(
                [
                    'tanggal'     => $adjustment->effective_date,
                    'uraian'      => 'Stock Opname ' . $adjustment->code,
                    'jns_trx'     => 12, // kode transaksi Stock Opname
                    'ref_tagihan' => $adjustment->id,
                    'divisi'      => 8, // gudang / koperasi
                ],
                $entries
            );

            $adjustment->update([
                'nojurnal' => $voucher->id,
            ]);

            return $voucher;
        } catch (\Throwable $e) {
            Log::error('Gagal generate jurnal stock opname', [
                'stock_adjustment_id' => $adjustment->id,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Gagal membuat jurnal stock opname.');
        }
    }
}
