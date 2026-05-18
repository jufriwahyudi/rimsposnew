<?php

namespace App\Services;

use App\Models\StockAdjustment;
use App\Models\StockBatch;
use App\Models\StockMovement;

class StockAdjustmentPostingService
{
    public function post(StockAdjustment $adjustment): array
    {
        $totalIncrease = 0; // nilai
        $totalDecrease = 0;

        foreach ($adjustment->items as $item) {

            $value = abs($item->qty) * $item->cost;

            if ($item->qty > 0) {
                $this->increaseStock($adjustment, $item);
                $totalIncrease += $value;
            }

            if ($item->qty < 0) {
                $this->decreaseStock($adjustment, $item);
                $totalDecrease += $value;
            }
        }

        $adjustment->update([
            'status'    => 'POSTED',
            'posted_at' => now(),
        ]);

        return [
            'increase' => $totalIncrease,
            'decrease' => $totalDecrease,
        ];
    }

    protected function increaseStock($adjustment, $item)
    {
        if ($item->qty <= 0) {
            return;
        }

        if ($item->stock_batch_id) {

            // 🔒 Lock batch
            $stockbatch = StockBatch::lockForUpdate()
                ->findOrFail($item->stock_batch_id);

            // ❗ qty_awal TIDAK diubah
            $stockbatch->qty_sisa += $item->qty;
            $stockbatch->save();
        } else {

            // 🆕 Batch baru khusus opname
            $stockbatch = StockBatch::create([
                'product_variant_id' => $item->product_variant_id,
                'posisi'             => $adjustment->posisi,
                'tanggal_masuk'      => $adjustment->effective_date,
                'qty_awal'           => $item->qty,
                'qty_sisa'           => $item->qty,
                'harga_beli'         => $item->cost,
                'sumber'             => 'opname',
            ]);

            $item->update([
                'stock_batch_id' => $stockbatch->id,
            ]);
        }

        // 📦 Stock movement
        StockMovement::create([
            'product_variant_id' => $item->product_variant_id,
            'stock_batch_id'     => $stockbatch->id,
            'posisi'             => $adjustment->posisi,
            'tanggal'            => $adjustment->effective_date,
            'tipe'               => 'adjust',
            'direction'          => 'in',
            'qty'                => $item->qty,
            'ref_type'           => 'StockAdjustment',
            'ref_id'             => $adjustment->id,
        ]);
    }

    protected function decreaseStock($adjustment, $item)
    {
        if ($item->qty >= 0) {
            return;
        }

        if (!$item->stock_batch_id) {
            throw new \Exception('Pengurangan stok wajib memiliki batch.');
        }

        $qty = abs($item->qty);

        // 🔒 Lock batch
        $stockbatch = StockBatch::lockForUpdate()
            ->findOrFail($item->stock_batch_id);

        if ($stockbatch->qty_sisa < $qty) {
            throw new \Exception('Stok batch tidak mencukupi untuk adjustment.');
        }

        // qty_awal TIDAK diubah
        $stockbatch->qty_sisa -= $qty;
        $stockbatch->save();

        StockMovement::create([
            'product_variant_id' => $item->product_variant_id,
            'stock_batch_id'     => $stockbatch->id,
            'posisi'             => $adjustment->posisi,
            'tanggal'            => $adjustment->effective_date,
            'tipe'               => 'adjust',
            'direction'          => 'out',
            'qty'                => $qty,
            'ref_type'           => 'StockAdjustment',
            'ref_id'             => $adjustment->id,
        ]);
    }
}
