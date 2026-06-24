<?php

namespace App\Exports;

use App\Models\SaleItem;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class LaporanHarianExport implements
    FromArray,
    WithHeadings,
    WithStyles,
    ShouldAutoSize,
    WithEvents
{
    protected $mulai;
    protected $akhir;
    protected $rowCount = 0;
    protected $sumQty = 0;
    protected $sumDiskon = 0;
    protected $sumSubtotal = 0;
    protected $sumModal = 0;
    protected $sumLaba = 0;
    protected $sumTrx = 0;

    public function __construct($mulai, $akhir)
    {
        $this->mulai = $mulai;
        $this->akhir = $akhir;
    }

    public function headings(): array
    {
        $tanggalText = $this->mulai === $this->akhir ? $this->mulai : $this->mulai . ' s/d ' . $this->akhir;
        return [
            ['Laporan Harian - Stok Terjual'],
            ['Tanggal: ' . $tanggalText],
            [],
            [
                'No',
                'SKU',
                'Produk',
                'Varian',
                'Harga Jual',
                'Qty Terjual',
                'Diskon',
                'Total Penjualan',
                'Modal',
                'Laba / Rugi',
                'Jml Trx',
            ],
        ];
    }

    public function array(): array
    {
        $store = \App\Models\Store::find(session('store_id'));
        $isFnB = $store && $store->business_type === 'fnb';

        $items = SaleItem::with(['variant.product', 'fnbDetail', 'batches', 'sale'])
            ->whereHas('sale', function ($q) {
                $q->whereNull('ref_sale_id')
                    ->whereDoesntHave('refunds')
                    ->whereBetween('sale_date', [$this->mulai . ' 00:00:00', $this->akhir . ' 23:59:59']);
            })
            ->whereIn('status', ['sold', 'exchanged_in'])
            ->get()
            ->groupBy('product_variant_id')
            ->map(function ($items) use ($isFnB) {
                $first = $items->first();
                $totalModal = 0;
                if ($isFnB) {
                    foreach ($items as $item) {
                        $variant = $item->variant;
                        $tenantId = $variant && $variant->product ? $variant->product->tenant_id : null;
                        $costPriceManual = $item->cost_price ?? ($variant->cost_price_manual ?? 0);
                        if ($tenantId) {
                            $commissionAmount = $item->commission_amount ?? ($variant ? $variant->calculateCommission($item->price) : 0);
                            $costPrice = ($item->price - $commissionAmount) + $costPriceManual;
                        } else {
                            $costPrice = $costPriceManual;
                        }
                        $totalModal += ($item->qty * $costPrice);
                    }
                } else {
                    foreach ($items as $item) {
                        foreach ($item->batches as $batch) {
                            $totalModal += ($batch->qty * $batch->cost_price);
                        }
                    }
                }

                return (object) [
                    'sku' => $first->sku,
                    'product_name' => $first->variant ? $first->variant->product->nama_produk : ($first->product_name ?? '-'),
                    'variant_label' => optional($first->variant)->variant_label ?? '',
                    'harga_jual' => $first->price,
                    'total_qty' => $items->sum('qty'),
                    'total_diskon' => $items->sum('discount_amount'),
                    'total_subtotal' => $items->sum('subtotal'),
                    'total_modal' => $totalModal,
                    'laba_rugi' => $items->sum('subtotal') - $totalModal,
                    'jumlah_trx' => $items->count(),
                ];
            })
            ->sortByDesc('total_qty')
            ->values();

        $rows = [];
        foreach ($items as $i => $row) {
            $this->sumQty += $row->total_qty;
            $this->sumDiskon += $row->total_diskon;
            $this->sumSubtotal += $row->total_subtotal;
            $this->sumModal += $row->total_modal;
            $this->sumLaba += $row->laba_rugi;
            $this->sumTrx += $row->jumlah_trx;

            $rows[] = [
                $i + 1,
                $row->sku,
                $row->product_name,
                $row->variant_label ?: '-',
                $row->harga_jual,
                $row->total_qty,
                $row->total_diskon,
                $row->total_subtotal,
                $row->total_modal,
                $row->laba_rugi,
                $row->jumlah_trx,
            ];
        }

        $this->rowCount = count($rows);

        // Total row
        $rows[] = [
            '',
            '',
            '',
            'TOTAL',
            '',
            $this->sumQty,
            $this->sumDiskon,
            $this->sumSubtotal,
            $this->sumModal,
            $this->sumLaba,
            $this->sumTrx,
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['italic' => true, 'size' => 11]],
            4 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Merge title & subtitle
                $sheet->mergeCells('A1:K1');
                $sheet->mergeCells('A2:K2');
                $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Header row style
                $sheet->getStyle('A4:K4')->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E9ECEF'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // Data rows border
                $lastDataRow = 4 + $this->rowCount;
                if ($this->rowCount > 0) {
                    $sheet->getStyle('A5:K' . $lastDataRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            ],
                        ],
                    ]);
                }

                // Total row
                $totalRow = $lastDataRow + 1;
                $sheet->getStyle('A' . $totalRow . ':K' . $totalRow)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D1E7DD'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // Number format for amount columns (E, G, H, I, J)
                $sheet->getStyle('E5:E' . $totalRow)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('G5:J' . $totalRow)->getNumberFormat()->setFormatCode('#,##0');
            },
        ];
    }
}
