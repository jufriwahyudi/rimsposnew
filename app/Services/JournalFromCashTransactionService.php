<?php

namespace App\Services;

use App\Models\CashTransaction;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\JournalEntryService;
use Illuminate\Support\Facades\Log;

class JournalFromCashTransactionService
{
    /**
     * Buat jurnal berdasarkan SALE ID
     */
    public function createForSale(int $saleId)
    {
        Log::info("JournalService: Mulai proses jurnal untuk Sale #{$saleId}");

        // Ambil data Sale
        $sale = Sale::with('items.batches')->find($saleId);
        if (!$sale) {
            Log::warning("JournalService: Sale #{$saleId} tidak ditemukan.");
            return null;
        }

        // Ambil semua transaksi split payment
        $transactions = CashTransaction::where('ref_id', $saleId)
            ->where('transaction_type', 'sale')
            ->where('direction', 'in')
            ->get();

        if ($transactions->isEmpty()) {
            Log::warning("JournalService: Tidak ada CashTransaction untuk Sale #{$saleId}.");
            return null;
        }

        // Jika semua transaksi sudah memiliki jurnal, skip
        if ($transactions->every(fn($trx) => $trx->nojurnal !== null)) {
            Log::info("JournalService: Jurnal untuk Sale #{$saleId} sudah dibuat sebelumnya.");
            return $transactions->first()->nojurnal;
        }

        // Hitung diskon
        $discount = $sale->discount_total ?? 0;

        // Hitung HPP (modal)
        $modal = 0;
        foreach ($sale->items as $item) {
            foreach ($item->batches as $batch) {
                $modal += ($batch->qty * $batch->cost_price);
            }
        }

        // ============================
        // DETAIL JURNAL
        // ============================
        $details = [];

        // (1) DEBIT KAS/BANK (split payment)
        foreach ($transactions as $trx) {
            $details[] = [
                'kode_akun' => $trx->account_code,
                'amount'    => $trx->amount,
                'type'      => 'debet',
            ];
        }

        // Total pembayaran
        $totalPayment = $transactions->sum('amount');

        // (2) Debit Diskon Penjualan (jika ada)
        if ($discount > 0) {
            $details[] = [
                'kode_akun' => '41.02.12',
                'amount'    => $discount,
                'type'      => 'debet',
            ];
        }

        // (3) Kredit Pendapatan
        $details[] = [
            'kode_akun' => '41.02.10',
            'amount'    => $totalPayment + $discount, // total kredit adalah total pembayaran + diskon
            'type'      => 'kredit',
        ];

        // (4) Debit HPP
        $details[] = [
            'kode_akun' => '41.02.11',
            'amount'    => $modal,
            'type'      => 'debet',
        ];

        // (5) Kredit Persediaan
        $details[] = [
            'kode_akun' => '11.04.13',
            'amount'    => $modal,
            'type'      => 'kredit',
        ];

        // ============================
        // Simpan jurnal
        // ============================
        $journalService = new JournalEntryService();

        $voucher = $journalService->createSale(
            [
                'tanggal'    => date('Y-m-d', strtotime($sale->sale_date)),
                'uraian'     => "Penjualan POS #" . $sale->invoice_number,
                'jns_trx'    => 10,
                'ref_tagihan' => $saleId,
                'divisi'     => 8,
                'amount'     => $totalPayment + $discount, // total amount adalah total pembayaran + diskon
                'user_id'    => $sale->user_id,
            ],
            $details
        );

        // Update semua transaksi split
        CashTransaction::where('ref_id', $saleId)
            ->where('transaction_type', 'sale')
            ->where('direction', 'in')
            ->update(['nojurnal' => $voucher->id]);

        Log::info("JournalService: Jurnal berhasil dibuat untuk Sale #{$saleId}, voucher #{$voucher->id}.");

        return $voucher->id;
    }

    public function createForRefund($refundId)
    {
        // Ambil transaksi refund (harus 1 baris)
        $trx = CashTransaction::where('ref_type', 'SalePosRefund')
            ->where('ref_id', $refundId)
            ->where('transaction_type', 'refund')
            ->where('direction', 'out')
            ->first();

        if (!$trx) {
            Log::warning("JournalService: Tidak ada CashTransaction refund untuk #{$refundId}.");
            return null;
        }

        // Refund Sale
        $saleRefund = Sale::with('items')->find($refundId);
        if (!$saleRefund) {
            Log::warning("JournalService: Sale Refund #{$refundId} tidak ditemukan.");
            return null;
        }

        // Sale asli
        $saleOriginal = Sale::with('items.batches')->find($saleRefund->ref_sale_id);
        if (!$saleOriginal) {
            Log::warning("JournalService: Sale Referensi tidak ditemukan untuk refund #{$refundId}.");
            return null;
        }

        // Hitung modal item yg direfund (hanya item yg status 'refunded')
        $modalRefund = 0;
        foreach ($saleOriginal->items as $refundItem) {
            foreach ($refundItem->batches as $batch) {
                $modalRefund += ($batch->qty * $batch->cost_price);
            }
        }

        // Total refund (nilai kas keluar)
        $totalRefund = abs($saleRefund->grand_total);

        // Diskon (negatif di tabel, jadi ambil abs)
        $discountRefund = abs($saleRefund->discount_total);

        // ============================
        // DETAIL JURNAL
        // ============================
        $details = [];

        // 1. Kredit Kas/Bank
        $details[] = [
            'kode_akun' => $trx->account_code,
            'amount'    => $trx->amount,
            'type'      => 'kredit',
        ];

        // 2. Debit Pendapatan Dikembalikan
        $details[] = [
            'kode_akun' => '41.02.10',
            'amount'    => $totalRefund + $discountRefund,
            'type'      => 'debet',
        ];

        // 3. Debit Diskon Dikembalikan (jika ada)
        if ($discountRefund > 0) {
            $details[] = [
                'kode_akun' => '41.02.12',
                'amount'    => $discountRefund,
                'type'      => 'kredit',
            ];
        }

        // 4. Kredit HPP Dikembalikan
        $details[] = [
            'kode_akun' => '41.02.11',
            'amount'    => $modalRefund,
            'type'      => 'kredit',
        ];

        // 5. Debit Persediaan
        $details[] = [
            'kode_akun' => '11.04.13',
            'amount'    => $modalRefund,
            'type'      => 'debet',
        ];

        // ============================
        // SIMPAN JURNAL
        // ============================
        $journalService = new JournalEntryService();

        $voucher = $journalService->create([
            'tanggal'     => $saleRefund->sale_date ?: now()->format('Y-m-d'),
            'uraian'      => "Refund Penjualan POS #{$saleRefund->invoice_number}",
            'jns_trx'     => 10,
            'ref_tagihan' => $refundId,
            'divisi'      => 8,
            'amount'      => $totalRefund + $discountRefund,
            'user_id'     => $saleRefund->user_id,
        ], $details);

        // Update transaksi refund
        $trx->update(['nojurnal' => $voucher->id]);

        Log::info("JournalService: Jurnal refund berhasil dibuat untuk Refund #{$refundId}, voucher #{$voucher->id}.");

        return $voucher->id;
    }

    public function createForExchange($exchangeId)
    {
        // Ambil transaksi refund (harus 1 baris)
        $trx = CashTransaction::with('user')->where('ref_type', 'Exchange')
            ->where('ref_id', $exchangeId)
            ->first();

        if (!$trx) {
            Log::warning("JournalService: Tidak ada CashTransaction refund untuk #{$exchangeId}.");
            return null;
        }

        // Hitung selisih harga modal
        $exchangeItem = SaleItem::with(['batches', 'sale'])->find($exchangeId);
        if (!$exchangeItem) {
            Log::warning("JournalService: SaleItem untuk Exchange #{$exchangeId} tidak ditemukan.");
            return null;
        }
        $modalRefund = 0;
        foreach ($exchangeItem->batches as $batch) {
            $modalRefund += ($batch->qty * $batch->cost_price);
        }
        // Harga modal lama
        $prevItem = SaleItem::with('batches')->find($exchangeItem->ref_sale_item_id);
        $modalPrevItem = 0;
        foreach ($prevItem->batches as $batch) {
            $modalPrevItem += ($batch->qty * $batch->cost_price);
        }
        $modalRefund = $modalRefund - $modalPrevItem;
        // ============================
        // DETAIL JURNAL
        // ============================
        $details = [];

        if ($trx->amount != 0) {
            $details[] = [
                'kode_akun' => $trx->account_code,
                'amount'    => $trx->amount,
                'type'      => ($trx->transaction_type === 'exchange_additional') ? 'debet' : 'kredit', // jika exchange in = debet, jika exchange out = kredit
            ];

            $details[] = [
                'kode_akun' => '41.02.10',
                'amount'    => $trx->amount,
                'type'      => ($trx->transaction_type === 'exchange_additional') ? 'kredit' : 'debet', // jika exchange in = kredit, jika exchange out = debet
            ];
        }


        if ($modalRefund != 0) {
            $details[] = [
                'kode_akun' => '41.02.11',
                'amount'    => abs($modalRefund),
                'type'      => ($modalRefund > 0) ? 'debet' : 'kredit', // jika selisih modal positif = debet, jika negatif = kredit
            ];

            // 5. Debit Persediaan
            $details[] = [
                'kode_akun' => '11.04.13',
                'amount'    => abs($modalRefund),
                'type'      => ($modalRefund > 0) ? 'kredit' : 'debet', // jika selisih modal positif = kredit, jika negatif = debet
            ];
        }

        // ============================
        // SIMPAN JURNAL
        // ============================
        if ($details == []) {
            Log::info("JournalService: Tidak ada selisih harga maupun modal untuk Exchange #{$exchangeId}, jurnal tidak dibuat.");
            return null;
        } else {
            $journalService = new JournalEntryService();

            $voucher = $journalService->create([
                'tanggal'     => now()->format('Y-m-d'),
                'uraian'      => "Penukaran Penjualan POS #{$exchangeItem->sale->invoice_number}",
                'jns_trx'     => 10,
                'ref_tagihan' => $exchangeId,
                'divisi'      => 8,
                'amount'      => $trx->amount == 0 ? abs($modalRefund) : abs($trx->amount), // jika tidak ada selisih harga, maka amount jurnal diambil dari selisih modal
                'user_id'     => $trx->user->id_user_finance,
            ], $details);

            // Update transaksi refund
            $trx->update(['nojurnal' => $voucher->id]);

            Log::info("JournalService: Jurnal refund berhasil dibuat untuk Exchange #{$exchangeId}, voucher #{$voucher->id}.");

            return $voucher->id;
        }
    }

    public function createForNseExchange($exchangeId, $akun)
    {
        // Ambil transaksi refund (harus 1 baris)
        $trx = CashTransaction::with('user')->where('ref_type', 'Exchange')
            ->where('ref_id', $exchangeId)
            ->first();

        if (!$trx) {
            Log::warning("JournalService: Tidak ada CashTransaction refund untuk #{$exchangeId}.");
            return null;
        }

        // Hitung selisih harga modal
        $exchangeItem = SaleItem::with(['batches', 'sale'])->find($exchangeId);
        if (!$exchangeItem) {
            Log::warning("JournalService: SaleItem untuk Exchange #{$exchangeId} tidak ditemukan.");
            return null;
        }
        $modalRefund = 0;
        foreach ($exchangeItem->batches as $batch) {
            $modalRefund += ($batch->qty * $batch->cost_price);
        }
        // Harga modal lama
        $prevItem = SaleItem::with('batches')->find($exchangeItem->ref_sale_item_id);
        $modalPrevItem = 0;
        foreach ($prevItem->batches as $batch) {
            $modalPrevItem += ($batch->qty * $batch->cost_price);
        }
        $modalRefund = $modalRefund - $modalPrevItem;
        // ============================
        // DETAIL JURNAL
        // ============================
        $details = [];

        if ($modalRefund != 0) {
            $details[] = [
                'kode_akun' => $akun ?? '52.01.15',
                'amount'    => abs($modalRefund),
                'type'      => ($modalRefund > 0) ? 'debet' : 'kredit', // jika selisih modal positif = debet, jika negatif = kredit
            ];

            // 5. Debit Persediaan
            $details[] = [
                'kode_akun' => '11.04.13',
                'amount'    => abs($modalRefund),
                'type'      => ($modalRefund > 0) ? 'kredit' : 'debet', // jika selisih modal positif = kredit, jika negatif = debet
            ];
        }

        // ============================
        // SIMPAN JURNAL
        // ============================
        if ($details == []) {
            Log::info("JournalService: Tidak ada selisih harga maupun modal untuk Exchange #{$exchangeId}, jurnal tidak dibuat.");
            return null;
        } else {
            $journalService = new JournalEntryService();

            $voucher = $journalService->create([
                'tanggal'     => now()->format('Y-m-d'),
                'uraian'      => "Penukaran Penjualan POS #{$exchangeItem->sale->invoice_number}",
                'jns_trx'     => 10,
                'ref_tagihan' => $exchangeId,
                'divisi'      => 8,
                'amount'      => abs($modalRefund), // jika tidak ada selisih harga, maka amount jurnal diambil dari selisih modal
                'user_id'     => $trx->user->id_user_finance,
            ], $details);

            // Update transaksi refund
            $trx->update(['nojurnal' => $voucher->id]);

            Log::info("JournalService: Jurnal refund berhasil dibuat untuk Exchange #{$exchangeId}, voucher #{$voucher->id}.");

            return $voucher->id;
        }
    }

    public function createForNse(int $saleId)
    {
        Log::info("JournalService: Mulai proses jurnal untuk Sale #{$saleId}");

        // Ambil data Sale
        $sale = Sale::with('items.batches')->find($saleId);
        if (!$sale) {
            Log::warning("JournalService: Sale #{$saleId} tidak ditemukan.");
            return null;
        }

        // Ambil semua transaksi split payment
        $transactions = CashTransaction::where('ref_id', $saleId)
            ->where('transaction_type', 'nse') // khusus transaksi NSE
            ->where('direction', 'in')
            ->first();

        if (!$transactions) {
            Log::warning("JournalService: Tidak ada CashTransaction untuk Sale #{$saleId}.");
            return null;
        }

        // Jika semua transaksi sudah memiliki jurnal, skip
        if ($transactions->nojurnal !== null) {
            Log::info("JournalService: Jurnal untuk Sale #{$saleId} sudah dibuat sebelumnya.");
            return $transactions->nojurnal;
        }

        // Hitung diskon
        $discount = $sale->discount_total ?? 0;

        // Hitung HPP (modal)
        $modal = 0;
        foreach ($sale->items as $item) {
            foreach ($item->batches as $batch) {
                $modal += ($batch->qty * $batch->cost_price);
            }
        }

        // ============================
        // DETAIL JURNAL
        // ============================
        $details = [];
        // Untuk harga jual belum di jurnal
        // hanya harga modal yang di jurnal
        // (1) Masuk ke Beban Seragam NSE tahun ajaran bersangkutan
        $details[] = [
            'kode_akun' => $transactions->account_code,
            'amount'    => $transactions->amount,
            'type'      => 'debet',
        ];

        // (2) Kredit Persediaan
        $details[] = [
            'kode_akun' => '11.04.13',
            'amount'    => $transactions->amount,
            'type'      => 'kredit',
        ];


        // ============================
        // Simpan jurnal
        // ============================
        $journalService = new JournalEntryService();

        $voucher = $journalService->createSale(
            [
                'tanggal'    => date('Y-m-d', strtotime($sale->sale_date)),
                'uraian'     => "Penjualan POS #" . $sale->invoice_number,
                'jns_trx'    => 10,
                'ref_tagihan' => $saleId,
                'divisi'     => 8,
                'amount'     => $transactions->amount, // total amount adalah total pembayaran + diskon
                'user_id'    => $sale->user_id,
            ],
            $details
        );

        // Update semua transaksi split
        CashTransaction::where('ref_id', $saleId)
            ->where('transaction_type', 'nse') // khusus transaksi NSE
            ->where('direction', 'in')
            ->update(['nojurnal' => $voucher->id]);

        Log::info("JournalService: Jurnal berhasil dibuat untuk Sale #{$saleId}, voucher #{$voucher->id}.");

        return $voucher->id;
    }
}
