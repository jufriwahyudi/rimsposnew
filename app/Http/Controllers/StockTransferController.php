<?php

namespace App\Http\Controllers;

use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\ProductVariant;
use App\Services\StockTransferService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    protected StockTransferService $service;

    public function __construct(StockTransferService $service)
    {
        $this->service = $service;
    }

    /**
     * List transfer
     */
    public function index(Request $request)
    {
        $query = StockTransfer::with('items.variant')
            ->orderByDesc('id');

        if(isStore()){
            $query->whereIn('status', ['REQUESTED', 'RECEIVED', 'APPROVED', 'PARTIALLY_RECEIVED', 'REJECTED', 'CANCELLED']);
        }
        if(isWarehouse()){
            $query->whereIn('status', ['REQUESTED', 'APPROVED', 'REJECTED','RECEIVED']);
        }

        return view('stock_transfers.index', [
            'transfers' => $query->paginate(15)
        ]);
    }

    /**
     * Form create transfer
     */
    public function create()
    {
        $products = Product::with([
            'variants.variantAttributes.attribute'
        ])->get();
        $attributeValues = AttributeValue::pluck('nama', 'id');
        return view('stock_transfers.create', [
            'products' => $products,
            'attributeValues' => $attributeValues,
        ]);
    }

    /**
     * Store request transfer
     */
    public function store(Request $request, StockTransferService $service)
    {
        $request->validate([
            'from_position' => 'required|in:warehouse,store',
            'to_position'   => 'required|in:warehouse,store|different:from_position',
            'items'         => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.qty'        => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            /* ==========================================
             * 1️⃣ VALIDASI STOK (DEFENSIVE)
             * ========================================== */
            foreach ($request->items as $row) {

                $variant = ProductVariant::findOrFail($row['variant_id']);

                $stokTersedia = $request->from_position === 'warehouse'
                    ? $variant->stok_warehouse
                    : $variant->stok_store;

                if ($row['qty'] > $stokTersedia) {
                    throw new Exception(
                        "Stok {$variant->sku} tidak mencukupi (tersedia: {$stokTersedia})"
                    );
                }
            }

            /* ==========================================
             * 2️⃣ PREPARE PAYLOAD UNTUK SERVICE
             * ========================================== */
            $payload = [
                'from_position' => $request->from_position,
                'to_position'   => $request->to_position,
                'notes'         => $request->notes ?? null,
                'items'         => collect($request->items)->map(function ($item) {
                    return [
                        'product_variant_id' => $item['variant_id'],
                        'qty' => $item['qty'],
                    ];
                })->values()->toArray(),
            ];

            /* ==========================================
             * 3️⃣ CALL SERVICE
             * ========================================== */
            $service->request($payload, auth()->id());

            DB::commit();

            return redirect()
                ->route('stock-transfers.index')
                ->with('success', 'Request transfer stok berhasil dibuat');
        } catch (\Throwable $e) {

            DB::rollBack();
            report($e);

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Detail transfer
     */
    public function show(StockTransfer $stockTransfer)
    {
        $stockTransfer->load([
            'items.variant.product',
            'stockBatches.variant.product',
        ]);

        return view('stock_transfers.show', [
            'transfer' => $stockTransfer
        ]);
    }

    /**
     * Approve transfer (Gudang)
     */
    public function approve(
        Request $request,
        StockTransfer $stockTransfer,
        StockTransferService $service
    ) {
        $request->validate([
            'items' => 'required|array',
            'items.*' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();

        try {
            $service->approve(
                $stockTransfer,
                $request->items, // [transfer_item_id => qty_approved]
                auth()->id()
            );

            DB::commit();

            return redirect()
                ->route('stock-transfers.show', $stockTransfer->id)
                ->with('success', 'Transfer berhasil di-approve');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function updateStatus(Request $request, StockTransfer $stockTransfer, StockTransferService $service)
    {
        $status = $request->status;

        match ($status) {
            'APPROVED', 'REJECTED' => $this->authorizeWarehouse($stockTransfer),
            'CANCELLED' => $this->authorizeStore($stockTransfer),
            default => abort(400, 'Status tidak valid'),
        };

        if ($status === 'APPROVED') {
            $request->validate([
                'items' => 'required|array',
                'items.*' => 'required|integer|min:0',
            ]);

            DB::beginTransaction();

            try {
                $service->approve(
                    $stockTransfer,
                    $request->items, // [transfer_item_id => qty_approved]
                    auth()->id()
                );

                DB::commit();

                return redirect()
                    ->route('stock-transfers.show', $stockTransfer->id)
                    ->with('success', 'Transfer berhasil di-approve');
            } catch (\Throwable $e) {
                DB::rollBack();

                return back()
                    ->withInput()
                    ->with('error', $e->getMessage());
            }
        }

        if ($status === 'REJECTED') {
            if ($stockTransfer->status !== 'REQUESTED') {
                return back()->with('error', 'Transfer sudah diproses.');
            }

            $request->validate([
                'reason' => 'required|string'
            ]);

            $stockTransfer->update([
                'status' => 'REJECTED',
                'notes' => $request->reason,
                'approved_date' => now(),
                'approved_by' => auth()->id()
            ]);

            return redirect()
                ->route('stock-transfers.index')
                ->with('success', 'Stock transfer ditolak.');
        }

        if ($status === 'CANCELLED') {
            if ($stockTransfer->status !== 'REQUESTED') {
                return back()->with('error', 'Transfer tidak bisa dibatalkan.');
            }

            $stockTransfer->update([
                'status' => 'CANCELLED',
                'approve_date' => now(),
                'approved_by' => auth()->id()
            ]);

            return redirect()
                ->route('stock-transfers.index')
                ->with('success', 'Stock transfer dibatalkan.');
        }
    }

    private function authorizeWarehouse()
    {
        if (! isWarehouse() && ! isAdmin()) {
            abort(403, 'Akses hanya untuk warehouse atau admin');
        }
    }

    private function authorizeStore()
    {
        if (! isStore()) {
            abort(403, 'Akses hanya untuk store');
        }
    }

    /**
     * Ship transfer (keluar stok asal)
     */
    public function ship(StockTransfer $stockTransfer)
    {
        DB::transaction(function () use ($stockTransfer) {
            $this->service->ship($stockTransfer, auth()->id());
        });

        return back()->with('success', 'Barang dikirim');
    }

    /**
     * Receive transfer (partial / full)
     */
    public function receive(
        Request $request,
        StockTransfer $stockTransfer,
        StockTransferService $service
    ) {
        $request->validate([
            'items' => 'required|array',
            'items.*' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();

        try {
            $service->receive(
                $stockTransfer,
                $request->items, // [transfer_item_id => qty_received]
                auth()->id()
            );

            DB::commit();

            return redirect()
                ->route('stock-transfers.show', $stockTransfer->id)
                ->with('success', 'Transfer berhasil diterima & stok diperbarui');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }


    /**
     * Reject transfer (belum kirim)
     */
    public function reject(Request $request, StockTransfer $stockTransfer)
    {
        if ($stockTransfer->status !== 'REQUESTED') {
            return back()->with('error', 'Transfer sudah diproses.');
        }

        $request->validate([
            'reason' => 'required|string'
        ]);

        $stockTransfer->update([
            'status' => 'REJECTED',
            'notes' => $request->reason,
            'approved_date' => now(),
            'approved_by' => auth()->id()
        ]);

        return redirect()
            ->route('stock-transfers.show', $stockTransfer->id)
            ->with('success', 'Stock transfer ditolak.');
    }
    /**
     * Cancel transfer (belum kirim)
     */
    public function cancel(StockTransfer $stockTransfer)
    {
        if ($stockTransfer->status !== 'REQUESTED') {
            return back()->with('error', 'Transfer tidak bisa dibatalkan.');
        }

        $stockTransfer->update([
            'status' => 'CANCELLED',
            'approve_date' => now(),
            'approved_by' => auth()->id()
        ]);

        return redirect()
            ->route('stock-transfers.show', $stockTransfer->id)
            ->with('success', 'Stock transfer dibatalkan.');
    }


    /**
     * Rollback transfer (sudah kirim)
     */
    public function rollback(StockTransfer $stockTransfer)
    {
        DB::transaction(function () use ($stockTransfer) {
            if (!in_array($stockTransfer->status, ['RECEIVED', 'PARTIAL_RECEIVED'])) {
                throw new Exception('Transfer belum diterima');
            }
            $this->service->rollbackReceive($stockTransfer, auth()->id());
        });

        return back()->with('warning', 'Transfer berhasil di-rollback');
    }
}
