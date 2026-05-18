<?php

namespace App\Services;

use App\Models\Voucher;
use App\Models\Jurnal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JournalEntryService
{
    /**
     * Generate nomor voucher
     */
    public function generateVoucherNumber(string $tanggal, string $jnsTrx): array
    {
        $date = Carbon::parse($tanggal);

        $bulan = $date->month;
        $tahun = $date->year;

        $last = Voucher::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->where('jns_trx', $jnsTrx)
            ->max('no_voucer');

        $num = $last ? ((int) substr($last, 0, 4)) + 1 : 1;

        return ['voucer' => str_pad($num, 4, '0', STR_PAD_LEFT) . '.' . $jnsTrx . '.' . $date->format('m') . '.' . substr($tahun, 2), 'num' => $num];
    }

    /**
     * Create voucher + jurnal (double entry)
     */
    public function create(array $data, array $entries)
    {
        /**
         * $data = header voucher
         * $entries = detail jurnal (debit & kredit)
         */

        return DB::transaction(function () use ($data, $entries) {

            // 1️⃣ Generate voucher number
            $noVoucher = $this->generateVoucherNumber(
                $data['tanggal'],
                $data['jns_trx'] ?? 4
            );

            // 2️⃣ Hitung total debit & kredit
            $totalDebet  = collect($entries)->where('type', 'debet')->sum('amount');
            $totalKredit = collect($entries)->where('type', 'kredit')->sum('amount');

            if ($totalDebet !== $totalKredit) {
                throw new \Exception('Jurnal tidak balance (total debet: ' . $totalDebet . ', total kredit: ' . $totalKredit . ').');
            }
            $userfinance = auth()->user()?->id_user_finance ?? null;
            if (!$userfinance) {
                throw new \Exception('User anda tidak memiliki akses ke Finara');
            }
            // 3️⃣ Simpan Voucher
            $voucher = Voucher::create([
                'tanggal'     => $data['tanggal'],
                'no_voucer'  => $noVoucher['num'],
                'voucer'      => $noVoucher['voucer'],
                'referensi'   => $noVoucher['voucer'],
                'uraian'      => $data['uraian'],
                'jlh_debet'   => $totalDebet,
                'jlh_kredit'  => $totalKredit,
                'jlh_bayar'   => $data['jlh_bayar'] ?? 0,
                'jns_trx'     => $data['jns_trx'] ?? 4,
                'user_input'  => $userfinance,
                'supplier'    => $data['supplier'] ?? '1',
                'unit'        => $data['unit'] ?? '0101',
                'stts'        => '0',
                'dtinput'     => now(),
                'jns'         => $data['jns'] ?? 'J',
                'ref_tagihan' => $data['ref_tagihan'] ?? 0,
                'divisi'      => $data['divisi'] ?? 6,
                'can_edit'    => 'N',
            ]);

            // 4️⃣ Simpan detail jurnal
            foreach ($entries as $entry) {
                // Log::info('Membuat jurnal', ['entry' => $entry]);
                $jurnal = Jurnal::create([
                    'tanggal'    => $data['tanggal'],
                    'no_voucer' => $noVoucher['num'],
                    'voucer'     => $noVoucher['voucer'],
                    'referensi'  => $noVoucher['voucer'],
                    'uraian'     => $data['uraian'],
                    'kode'       => $entry['kode_akun'],
                    'amount'     => $entry['amount'],
                    'post'       => $entry['type'] === 'debet' ? 'D' : 'K',
                    'jns_trx'    => $data['jns_trx'] ?? 4,
                    'user_input' => $userfinance,
                    'tgl_input'  => now(),
                    'supplier'   => $data['supplier'] ?? '1',
                    'unit'       => $data['unit'] ?? '0101',
                    'stts'       => '1',
                    'ref'        => $voucher->id,
                    'divisi'     => $data['divisi'] ?? 6,
                ]);
                // Log::info('Jurnal dibuat', ['jurnal' => $jurnal]);
            }

            return $voucher;
        });
    }
    public function createSale(array $data, array $entries)
    {
        /**
         * $data = header voucher
         * $entries = detail jurnal (debit & kredit)
         */

        return DB::transaction(function () use ($data, $entries) {

            // 1️⃣ Generate voucher number
            $noVoucher = $this->generateVoucherNumber(
                $data['tanggal'],
                $data['jns_trx'] ?? 4
            );

            // 2️⃣ Hitung total debit & kredit
            $totalDebet  = $data['amount']; // untuk kasus penjualan, total debet diambil dari amount transaksi
            $totalKredit = $data['amount']; // untuk kasus penjualan, total kredit diambil dari amount transaksi

            if ($totalDebet !== $totalKredit) {
                throw new \Exception('Jurnal tidak balance');
            }
            $userfinance = User::find($data['user_id'])?->id_user_finance ?? null;
            if (!$userfinance) {
                throw new \Exception('User anda tidak memiliki akses ke Finara');
            }
            // 3️⃣ Simpan Voucher
            $voucher = Voucher::create([
                'tanggal'     => $data['tanggal'],
                'no_voucer'  => $noVoucher['num'],
                'voucer'      => $noVoucher['voucer'],
                'referensi'   => $noVoucher['voucer'],
                'uraian'      => $data['uraian'],
                'jlh_debet'   => $totalDebet,
                'jlh_kredit'  => $totalKredit,
                'jlh_bayar'   => $data['jlh_bayar'] ?? 0,
                'jns_trx'     => $data['jns_trx'] ?? 4,
                'user_input'  => $userfinance,
                'supplier'    => $data['supplier'] ?? '1',
                'unit'        => $data['unit'] ?? '0101',
                'stts'        => '0',
                'dtinput'     => now(),
                'jns'         => $data['jns'] ?? 'J',
                'ref_tagihan' => $data['ref_tagihan'] ?? 0,
                'divisi'      => $data['divisi'] ?? 6,
                'can_edit'    => 'N',
            ]);

            // 4️⃣ Simpan detail jurnal
            foreach ($entries as $entry) {
                // Log::info('Membuat jurnal', ['entry' => $entry]);
                $jurnal = Jurnal::create([
                    'tanggal'    => $data['tanggal'],
                    'no_voucer' => $noVoucher['num'],
                    'voucer'     => $noVoucher['voucer'],
                    'referensi'  => $noVoucher['voucer'],
                    'uraian'     => $data['uraian'],
                    'kode'       => $entry['kode_akun'],
                    'amount'     => $entry['amount'],
                    'post'       => $entry['type'] === 'debet' ? 'D' : 'K',
                    'jns_trx'    => $data['jns_trx'] ?? 4,
                    'user_input' => $userfinance,
                    'tgl_input'  => now(),
                    'supplier'   => $data['supplier'] ?? '1',
                    'unit'       => $data['unit'] ?? '0101',
                    'stts'       => '1',
                    'ref'        => $voucher->id,
                    'divisi'     => $data['divisi'] ?? 6,
                ]);
                // Log::info('Jurnal dibuat', ['jurnal' => $jurnal]);
            }

            return $voucher;
        });
    }
    public function delete(int $voucherId): void
    {
        DB::transaction(function () use ($voucherId) {
            // Hapus detail jurnal terkait
            Jurnal::where('ref', $voucherId)->delete();

            // Hapus voucher
            Voucher::where('id', $voucherId)->delete();
        });
    }
}

/* Cara panggilnya
$journalService = new JournalEntryService();
$voucher = $journalService->create(
    [
        'tanggal' => '2024-06-20',
        'uraian'  => 'Pembelian barang',
        'jns_trx' => 4,
        'ref_tagihan' => 0,
        'divisi' => 8,
        // ...data voucher lainnya
    ],
    [
        [
            'kode_akun' => '5110',
            'amount'    => 1000000,
            'type'      => 'debet',
        ],
        [
            'kode_akun' => '1110',
            'amount'    => 1000000,
            'type'      => 'kredit',
        ],
        // ...entri jurnal lainnya
    ]
);
*/