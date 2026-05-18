<?php

namespace App\Services;

use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\StokTransferJurnal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class StockTransferService
{
    /* =====================================================
     * CREATE REQUEST
     * ===================================================== */
    public function request(array $payload, int $userId): StockTransfer
    {
        return DB::transaction(function () use ($payload, $userId) {

            if ($payload['from_position'] === $payload['to_position']) {
                throw new Exception('Posisi asal dan tujuan tidak boleh sama');
            }
            $transferType = 'REQUEST';
            if ($payload['from_position'] === 'store') {
                $transferType = 'RETURN';
            }

            $transfer = StockTransfer::create([
                'transfer_code' => $this->generateCode(),
                'from_position' => $payload['from_position'],
                'to_position'   => $payload['to_position'],
                'transfer_type' => $transferType,
                'requested_by'  => $userId,
                'request_date'  => now(),
                'notes'         => $payload['notes'] ?? null,
                'status'        => 'REQUESTED',
            ]);

            foreach ($payload['items'] as $item) {
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_variant_id' => $item['product_variant_id'],
                    'qty_requested'     => $item['qty'],
                ]);
            }

            return $transfer;
        });
    }

    /* =====================================================
     * APPROVE
     * ===================================================== */
    public function approve(
        StockTransfer $transfer,
        array $approvedItems,
        int $approverId
    ): StockTransfer {
        return DB::transaction(function () use ($transfer, $approvedItems, $approverId) {

            if ($transfer->status !== 'REQUESTED') {
                throw new Exception('Transfer tidak bisa di-approve ==' . $transfer->status . '==');
            }

            foreach ($transfer->items as $item) {
                $qtyApprove = $approvedItems[$item->id]
                    ?? $item->qty_requested;

                if ($qtyApprove <= 0) {
                    throw new Exception('Qty approve tidak valid');
                }

                // VALIDASI STOK (cek total qty_sisa)
                $stokTersedia = StockBatch::where(
                    'product_variant_id',
                    $item->product_variant_id
                )
                    ->where('posisi', $transfer->from_position)
                    ->sum('qty_sisa');

                if ($stokTersedia < $qtyApprove) {
                    throw new Exception(
                        'Stok tidak mencukupi untuk variant ID '
                            . $item->product_variant_id
                    );
                }

                $item->update([
                    'qty_approved' => $qtyApprove
                ]);
            }

            $transfer->update([
                'status'       => 'APPROVED',
                'approved_by'  => $approverId,
                'approve_date' => now(),
            ]);

            return $transfer;
        });
    }

    /* =====================================================
     * RECEIVE (EKSEKUSI STOK)
     * ===================================================== */
    public function receive(
        StockTransfer $transfer,
        array $receivedItems,
        int $userId
    ): StockTransfer {
        return DB::transaction(function () use ($transfer, $receivedItems, $userId) {

            if (!in_array($transfer->status, ['APPROVED', 'PARTIAL_RECEIVED'])) {
                throw new Exception('Transfer tidak bisa diterima');
            }

            $totalApproved = 0;
            $totalReceived = 0;

            foreach ($transfer->items as $item) {

                $qtyApprove  = $item->qty_approved;
                $qtyReceived = $item->qty_received ?? 0;
                $sisa        = $qtyApprove - $qtyReceived;

                if ($sisa <= 0) continue;

                $qty = $receivedItems[$item->id] ?? $sisa;

                if ($qty <= 0) {
                    throw new Exception('Qty receive tidak valid');
                }

                if ($qty > $sisa) {
                    throw new Exception('Qty receive melebihi sisa approve');
                }

                /* 1️⃣ OUT (FIFO) */
                $fifoBatches = StockService::issueFIFO(
                    $item->product_variant_id,
                    $transfer->from_position,
                    $qty,
                    'StockTransfer',
                    $transfer->id
                );

                /* 2️⃣ IN */
                $totalNilaiTransfer = 0;
                foreach ($fifoBatches as $fifo) {
                    $lineValue = $fifo['qty'] * $fifo['harga_beli'];
                    $totalNilaiTransfer += $lineValue;

                    $batch = StockBatch::create([
                        'product_variant_id' => $item->product_variant_id,
                        'stock_transfer_id'  => $transfer->id,
                        'posisi'             => $transfer->to_position,
                        'tanggal_masuk'      => now()->toDateString(),
                        'qty_awal'           => $fifo['qty'],
                        'qty_sisa'           => $fifo['qty'],
                        'harga_beli'         => $fifo['harga_beli'],
                        'sumber'             => 'transfer',
                    ]);

                    StockMovement::create([
                        'product_variant_id' => $item->product_variant_id,
                        'stock_batch_id'     => $batch->id,
                        'posisi'             => $transfer->to_position,
                        'tanggal'            => now(),
                        'tipe'               => 'in',
                        'direction'          => 'in',
                        'qty'                => $fifo['qty'],
                        'ref_type'           => 'StockTransfer',
                        'ref_id'             => $transfer->id,
                    ]);
                }
                $item->increment('qty_received', $qty);

                $totalApproved += $qtyApprove;
                $totalReceived += ($qtyReceived + $qty);
            }

            /* ===========================
            * SET STATUS
            * =========================== */
            if ($totalReceived < $totalApproved) {
                $transfer->update([
                    'status' => 'PARTIAL_RECEIVED'
                ]);
            } else {
                $transfer->update([
                    'status'       => 'RECEIVED',
                    'receive_date' => now(),
                ]);
            }

            // Jurnal Akan di buat disini
            $journalService = new JournalEntryService();

            $voucher = $journalService->create(
                [
                    'tanggal' => now(),
                    'uraian'  => 'Transfer stok ' . $transfer->transfer_code,
                    'jns_trx' => 4, // misal: kode khusus transfer stok
                    'ref_tagihan' => $transfer->id,
                    'divisi'  => 8, // sesuaikan
                ],
                [
                    [
                        'kode_akun' => $transfer->from_position == 'store' ? '11.04.13' : '11.04.14', // Persediaan Store or Gudang
                        'amount'    => $totalNilaiTransfer,
                        'type'      => 'debet',
                    ],
                    [
                        'kode_akun' => $transfer->to_position == 'store' ? '11.04.13' : '11.04.14', // Persediaan Store or Gudang
                        'amount'    => $totalNilaiTransfer,
                        'type'      => 'kredit',
                    ],
                ]
            );

            StokTransferJurnal::create([
                'stock_transfer_id' => $transfer->id,
                'nojurnal' => $voucher->id,
                'jumlah' => $totalNilaiTransfer,
            ]);

            return $transfer;
        });
    }

    /* =====================================================
     * Rollback RECEIVE
     * ===================================================== */
    public function rollbackReceive(StockTransfer $transfer, int $userId): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $userId) {
            $stockMovement = StockMovement::where('ref_type', 'StockTransfer')
                ->where('ref_id', $transfer->id)
                ->get();
            foreach ($stockMovement as $movement) {
                if ($movement->direction === 'in') {
                    // rollback stock in
                    $batch = StockBatch::find($movement->stock_batch_id);
                    if ($batch->qty_sisa < $batch->qty_awal) {
                        throw new Exception('Tidak bisa rollback, stok sudah terpakai');
                    }
                    $movement->delete();
                    $batch->delete();
                } elseif ($movement->direction === 'out') {
                    // rollback stock out
                    $batch = StockBatch::find($movement->stock_batch_id);
                    if ($batch) {
                        $batch->increment('qty_sisa', $movement->qty);
                    }
                    $movement->delete();
                }
            }
            // update stock transfer status
            $transfer->update([
                'status'       => 'APPROVED',
                'receive_date' => null,
            ]);
            // hapus jurnal
            $jurnal = StokTransferJurnal::where('stock_transfer_id', $transfer->id)->get();
            foreach ($jurnal as $j) {
                $journalService = new JournalEntryService();
                $journalService->delete($j->nojurnal);
                $j->delete();
            }
            // kosongkan qty_received di item
            foreach ($transfer->items as $item) {
                $item->update([
                    'qty_received' => 0,
                ]);
            }
            return $transfer;
        });
    }

    /* =====================================================
     * HELPER
     * ===================================================== */

    protected function generateCode(): string
    {
        return 'TRF-' . date('Ymd') . '-' . Str::upper(Str::random(5));
    }

    protected function getLastPurchasePrice(int $productVariantId): float
    {
        return (float) StockBatch::where(
            'product_variant_id',
            $productVariantId
        )
            ->whereNotNull('purchase_item_id')
            ->orderByDesc('tanggal_masuk')
            ->value('harga_beli') ?? 0;
    }
}
