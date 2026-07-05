<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SaleItemBatch;
use App\Models\StockBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockBatchController extends Controller
{
    public function index(Request $request)
    {
        $query = StockBatch::whereHas('variant')->with([
            'variant' => fn($q) => $q->withoutGlobalScopes(),
            'variant.product' => fn($q) => $q->withoutGlobalScopes()->withTrashed()
        ]);
        
        // Filter: Date Range
        if ($request->filled('date_range')) {
            $parts = explode(' - ', $request->date_range);
            if (count($parts) === 2) {
                $dateFrom = \Carbon\Carbon::createFromFormat('d/m/Y', trim($parts[0]))->startOfDay();
                $dateTo   = \Carbon\Carbon::createFromFormat('d/m/Y', trim($parts[1]))->endOfDay();
                $query->whereBetween('tanggal_masuk', [$dateFrom, $dateTo]);
            }
        }

        if ($request->filled('product_name')) {
            $productName = $request->product_name;
            $query->where(function ($q) use ($productName) {
                $q->whereHas('variant.product', function ($q2) use ($productName) {
                    $q2->withoutGlobalScopes()->where('nama_produk', 'like', '%' . $productName . '%');
                })->orWhereHas('variant', function ($q2) use ($productName) {
                    $q2->withoutGlobalScopes()->where('variant_name', 'like', '%' . $productName . '%');
                });
            });
        }

        // Filter: Posisi
        if ($request->filled('posisi')) {
            $query->where('posisi', $request->posisi);
        }

        // Filter: Sumber
        if ($request->filled('sumber')) {
            $query->where('sumber', $request->sumber);
        }

        $batches = $query->latest('tanggal_masuk')
            ->latest('id')
            ->paginate(15)
            ->appends($request->query());

        return view('stock_batches.index', compact('batches'));
    }

    public function show(StockBatch $batch)
    {
        $batch->load([
            'variant' => fn($q) => $q->withoutGlobalScopes(),
            'variant.product' => fn($q) => $q->withoutGlobalScopes()->withTrashed(),
            'purchaseOrderItem.purchaseOrder',
            'movements'
        ]);
        
        $saleItemBatches = SaleItemBatch::with(['saleItem.sale'])
            ->where('stock_batch_id', $batch->id)
            ->whereHas('saleItem.sale', function ($query) {
                $query->where('store_id', session('store_id'));
            })
            ->latest('id')
            ->paginate(15);
            
        return view('stock_batches.show', compact('batch', 'saleItemBatches'));
    }

    public function updateHarga(Request $request, StockBatch $batch)
    {
        $request->validate([
            'harga_beli' => 'required|numeric|min:0'
        ]);

        try {
            DB::transaction(function () use ($batch, $request) {
                $storeId = session('store_id');
                $newHarga = $request->harga_beli;

                // 1. Update harga di stock_batch
                $batch->update(['harga_beli' => $newHarga]);

                // 2. Update cost_price di sale_item_batches dengan scope store_id yang ketat
                SaleItemBatch::where('stock_batch_id', $batch->id)
                    ->whereHas('saleItem.sale', function ($query) use ($storeId) {
                        $query->where('store_id', $storeId);
                    })
                    ->update(['cost_price' => $newHarga]);
            });

            return back()->with('success', 'Harga modal berhasil diperbarui beserta transaksi penjualannya.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui harga: ' . $e->getMessage());
        }
    }
}
