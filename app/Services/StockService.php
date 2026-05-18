<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\StockBatch;
use App\Models\StockMovement;
use Exception;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * STOCK MASUK (Goods Receipt)
     */
    public static function receiveFromPurchase(
        int $productVariantId,
        int $purchaseItemId,
        string $posisi, // warehouse / store
        string $tanggalMasuk,
        int $qty,
        float $hargaBeli,
        int $refId
    ) {
        DB::transaction(function () use (
            $productVariantId,
            $purchaseItemId,
            $posisi,
            $tanggalMasuk,
            $qty,
            $hargaBeli,
            $refId
        ) {

            // 1️⃣ Buat batch baru
            $batch = StockBatch::create([
                'product_variant_id' => $productVariantId,
                'purchase_item_id'   => $purchaseItemId,
                'posisi'             => $posisi,
                'tanggal_masuk'      => $tanggalMasuk,
                'qty_awal'           => $qty,
                'qty_sisa'           => $qty,
                'harga_beli'         => $hargaBeli,
                'sumber'             => 'purchase',
            ]);

            // 2️⃣ Movement IN
            StockMovement::create([
                'product_variant_id' => $productVariantId,
                'stock_batch_id'     => $batch->id,
                'posisi'             => $posisi,
                'tanggal'            => now(),
                'tipe'               => 'in',
                'direction'          => 'in',
                'qty'                => $qty,
                'ref_type'           => 'GoodsReceipt',
                'ref_id'             => $refId,
            ]);
        });
    }

    /**
     * STOCK KELUAR (FIFO)
     */
    public static function issueFIFO(
        int $productVariantId,
        string $posisi,
        int $qtyKeluar,
        string $refType,
        int $refId
    ): array {
        $result = [];

        $batches = StockBatch::where('product_variant_id', $productVariantId)
            ->where('posisi', $posisi)
            ->where('qty_sisa', '>', 0)
            ->orderBy('tanggal_masuk')
            ->lockForUpdate()
            ->get();

        $sisa = $qtyKeluar;

        foreach ($batches as $batch) {
            if ($sisa <= 0) break;

            $ambil = min($batch->qty_sisa, $sisa);

            $batch->decrement('qty_sisa', $ambil);

            StockMovement::create([
                'product_variant_id' => $productVariantId,
                'stock_batch_id'     => $batch->id,
                'posisi'             => $posisi,
                'tanggal'            => now(),
                'tipe'               => 'out',
                'direction'          => 'out',
                'qty'                => $ambil,
                'ref_type'           => $refType,
                'ref_id'             => $refId,
            ]);

            $result[] = [
                'harga_beli' => $batch->harga_beli,
                'qty'        => $ambil,
            ];

            $sisa -= $ambil;
        }

        if ($sisa > 0) {
            throw new Exception('Stok tidak mencukupi (FIFO)');
        }

        return $result;
    }


    /**
     * Cek apakah Goods Receipt masih aman untuk di-rollback
     * (belum ada stok terpakai)
     */
    public static function canRollbackGoodsReceipt(GoodsReceipt $gr): bool
    {
        $movements = StockMovement::where('ref_type', 'GoodsReceipt')
            ->where('ref_id', $gr->id)
            ->with('batch')
            ->get();

        foreach ($movements as $movement) {
            $batch = $movement->batch;

            // Jika batch tidak ditemukan, anggap tidak aman
            if (! $batch) {
                return false;
            }

            // Jika qty_sisa != qty_awal → stok sudah terpakai
            if ((int) $batch->qty_sisa !== (int) $batch->qty_awal) {
                return false;
            }
        }

        return true;
    }

    /**
     * Optional: ambil alasan kenapa tidak bisa rollback
     */
    public static function rollbackBlockReason(GoodsReceipt $gr): ?string
    {
        $movements = StockMovement::where('ref_type', 'GoodsReceipt')
            ->where('ref_id', $gr->id)
            ->with('batch')
            ->get();

        foreach ($movements as $movement) {
            $batch = $movement->batch;

            if (! $batch) {
                return 'Batch stok tidak ditemukan';
            }

            if ($batch->qty_sisa < $batch->qty_awal) {
                return 'Sebagian stok dari penerimaan ini sudah digunakan';
            }

            if ($batch->qty_sisa > $batch->qty_awal) {
                return 'Data stok tidak valid (qty_sisa melebihi qty_awal)';
            }
        }

        return null;
    }
}
