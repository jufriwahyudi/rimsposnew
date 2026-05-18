<?php

namespace App\Exports;

use App\Models\Sale;
use App\Models\CashTransaction;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Facades\DB;

class LaporanPenjualanNSEExport implements FromArray, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $mulai;
    protected $akhir;
    protected $id_divisi;

    public function __construct($mulai, $akhir, $id_divisi = null)
    {
        $this->mulai = $mulai;
        $this->akhir = $akhir;
        $this->id_divisi = $id_divisi;
    }
    public function headings(): array
    {
        return [
            ['Laporan Penjualan NSE'],
            ['Periode: ' . $this->mulai . ' s/d ' . $this->akhir],
            [],
            ['No', 'Tanggal', 'Divisi', 'Nama', 'Gender', 'Total', 'Modal', 'Laba', 'Metode', 'No POS', 'Kasir', 'Status']
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                // Merge title
                $event->sheet->mergeCells('A1:L1');

                // Merge periode
                $event->sheet->mergeCells('A2:L2');

                // Optional: styling
                $event->sheet->getStyle('A1:L2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14
                    ],
                    'alignment' => [
                        'horizontal' => 'center',
                    ]
                ]);
            },
        ];
    }

    public function array(): array
    {
        $divisis = DB::connection('nsedb')
            ->table('master_divisi')
            ->where('kelompok', $this->id_divisi)
            ->get()
            ->pluck('id')
            ->toArray();
        $sales = Sale::with([
            'biodata.divisi',
            'items' => function ($query) {
                $query->with('batches')
                    ->whereIn('status', ['sold', 'exchanged_in']);
            },
            'cashier'
        ])
            ->whereNull('ref_sale_id')
            ->whereDoesntHave('refunds')
            ->where('sale_type', 'nse')
            ->where('status', 'paid')
            ->whereBetween('sale_date', [
                $this->mulai . " 00:00:00",
                $this->akhir . " 23:59:59"
            ])
            ->orderBy('sale_date', 'asc')
            ->get();

        // ✅ FILTER DIVISI (SAMA PERSIS)
        if ($this->id_divisi) {
            $sales = $sales->filter(function ($sale) use ($divisis) {
                return in_array(optional(optional($sale->biodata)->divisi)->id, $divisis);
            })->values();
        }

        $rows = [];

        $totalPenjualan = 0;
        $totalModal = 0;
        $totalLaba = 0;

        foreach ($sales as $i => $sale) {
            $cost = 0;

            foreach ($sale->items as $item) {
                foreach ($item->batches as $batch) {
                    $qty = $batch->qty ?? 0;
                    $costPrice = $batch->cost_price ?? ($batch->cost ?? 0);
                    $cost += ($qty * $costPrice);
                }
            }

            $jumlah = $sale->grand_total ?? 0;
            $laba = $jumlah - $cost;

            $cash = CashTransaction::where('transaction_type', 'sale')
                ->where('ref_id', $sale->id)
                ->first();

            $metode = $cash->payment_method ?? null;

            $totalPenjualan += $jumlah;
            $totalModal += $cost;
            $totalLaba += $laba;

            $rows[] = [
                $i + 1,
                optional($sale->sale_date)->format('Y-m-d H:i') ?? '-',
                optional(optional($sale->biodata)->divisi)->nama ?? '-',
                optional($sale->biodata)->nama_lengkap ?? ($sale->customer_name ?? '-'),
                optional($sale->biodata)->jk ?? '-',
                $jumlah,
                $cost,
                $laba,
                $metode ?? '-',
                $sale->invoice_number ?? '-',
                optional($sale->cashier)->name ?? '-',
                ucfirst($sale->status ?? '-'),
            ];
        }

        // TOTAL
        $rows[] = [
            '',
            '',
            '',
            '',
            'TOTAL',
            $totalPenjualan,
            $totalModal,
            $totalLaba,
            '',
            '',
            ''
        ];

        return $rows;
    }
}
