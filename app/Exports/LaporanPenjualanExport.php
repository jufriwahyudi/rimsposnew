<?php

namespace App\Exports;

use App\Models\Sale;
use App\Models\CashTransaction;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class LaporanPenjualanExport implements
    FromArray,
    WithHeadings,
    WithStyles,
    ShouldAutoSize,
    WithEvents
{
    protected $totalPenjualan = 0;
    protected $totalModal = 0;
    protected $totalLaba = 0;
    protected $rowCount = 0;
    protected $mulai;
    protected $akhir;

    public function __construct($mulai, $akhir)
    {
        $this->mulai = $mulai;
        $this->akhir = $akhir;
    }

    public function headings(): array
    {
        return [
            ['Laporan Penjualan'],
            ['Periode: ' . $this->mulai . ' s/d ' . $this->akhir],
            [],
            [
                'No',
                'Jenis',
                'Tanggal',
                'Nama Pelanggan',
                'Total',
                'Modal',
                'Laba / Rugi',
                'Metode Pembayaran',
                'Petugas',
                'Status',
            ],
        ];
    }

    public function array(): array
    {
        $sales = Sale::with(['items' => function ($query) {
            $query->with(['batches', 'variant.product', 'fnbDetail'])
                ->whereIn('status', ['sold', 'exchanged_in']);
        }, 'cashier'])
            ->whereNull('ref_sale_id')
            ->whereDoesntHave('refunds')
            ->whereBetween('sale_date', [$this->mulai . " 00:00:00", $this->akhir . " 23:59:59"])
            ->orderBy('sale_date', 'asc')
            ->get();

        $store = \App\Models\Store::find(session('store_id'));
        $isFnB = $store && $store->business_type === 'fnb';

        $rows = [];
        foreach ($sales as $i => $sale) {
            $cost = 0;
            if ($isFnB) {
                foreach ($sale->items as $item) {
                    $variant = $item->variant;
                    $tenantId = $variant && $variant->product ? $variant->product->tenant_id : null;
                    $costPriceManual = $item->cost_price ?? ($variant->cost_price_manual ?? 0);
                    if ($tenantId) {
                        $commissionAmount = $item->commission_amount ?? ($variant ? $variant->calculateCommission($item->price) : 0);
                        $costPrice = ($item->price - $commissionAmount) + $costPriceManual;
                    } else {
                        $costPrice = $costPriceManual;
                    }
                    $cost += ($item->qty * $costPrice);
                }
            } else {
                foreach ($sale->items as $item) {
                    foreach ($item->batches as $batch) {
                        $qty = $batch->qty ?? 0;
                        $costPrice = $batch->cost_price ?? ($batch->cost ?? 0);
                        $cost += ($qty * $costPrice);
                    }
                }
            }

            $jumlah = $sale->grand_total ?? 0;
            $laba = $jumlah - $cost;

            $cash = CashTransaction::where('transaction_type', 'sale')
                ->where('ref_id', $sale->id)
                ->first();

            $metode = $cash->payment_method ?? '-';

            $this->totalPenjualan += $jumlah;
            $this->totalModal += $cost;
            $this->totalLaba += $laba;

            $rows[] = [
                $i + 1,
                ucfirst($sale->sale_type ?? '-'),
                optional($sale->sale_date)->format('Y-m-d H:i') ?? '-',
                $sale->customer_name ?: ($sale->customer_id ?: '-'),
                $jumlah,
                $cost,
                $laba,
                ucfirst($metode),
                optional($sale->cashier)->name ?? '-',
                ucfirst($sale->status ?? '-'),
            ];
        }

        $this->rowCount = count($rows);

        // Baris total
        $rows[] = [
            '',
            '',
            '',
            'TOTAL',
            $this->totalPenjualan,
            $this->totalModal,
            $this->totalLaba,
            '',
            '',
            '',
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['italic' => true]],
            4 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Merge title row
                $sheet->mergeCells('A1:J1');
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Merge periode row
                $sheet->mergeCells('A2:J2');
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Style baris total (heading 4 baris + data rows + 1 total)
                $totalRow = 4 + $this->rowCount + 1;
                $sheet->getStyle("A{$totalRow}:J{$totalRow}")->getFont()->setBold(true);
            },
        ];
    }
}
