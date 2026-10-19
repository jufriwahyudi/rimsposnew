<?php

namespace App\Exports\Sheets;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Store;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class LaporanHarianVariantSheet implements
    FromArray,
    WithHeadings,
    WithStyles,
    ShouldAutoSize,
    WithTitle,
    WithEvents
{
    protected $mulai;
    protected $akhir;
    protected $categoryId;
    protected $rowCount = 0;
    protected $sumQty = 0;
    protected $sumDiskon = 0;
    protected $sumSubtotal = 0;
    protected $sumModal = 0;
    protected $sumLaba = 0;
    protected $sumTrx = 0;
    protected $totalTransDiscount = 0;
    protected $totalPointDiscount = 0;
    protected $totalVoucherDiscount = 0;

    public function __construct($mulai, $akhir, $categoryId = null)
    {
        $this->mulai = $mulai;
        $this->akhir = $akhir;
        $this->categoryId = $categoryId;
    }

    public function title(): string
    {
        return 'Per Varian';
    }

    public function headings(): array
    {
        $tanggalText = $this->mulai === $this->akhir ? $this->mulai : $this->mulai . ' s/d ' . $this->akhir;
        return [
            ['Laporan Harian - Penjualan Berdasarkan Varian (Detail)'],
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
                'Modal (HPP)',
                'Laba / Rugi',
                'Jml Trx',
            ],
        ];
    }

    public function array(): array
    {
        $storeId = session('store_id');
        $store = $storeId ? Store::find($storeId) : null;
        $isFnB = $store && $store->business_type === 'fnb';

        $query = SaleItem::with(['variant.product', 'fnbDetail', 'batches', 'sale'])
            ->whereHas('sale', function ($q) use ($storeId) {
                $q->whereNull('ref_sale_id')
                    ->whereDoesntHave('refunds')
                    ->where('status', 'paid')
                    ->when($storeId, fn($sq) => $sq->where('store_id', $storeId))
                    ->whereBetween('sale_date', [$this->mulai . ' 00:00:00', $this->akhir . ' 23:59:59']);
            })
            ->whereIn('status', ['sold', 'exchanged_in']);

        if (!empty($this->categoryId)) {
            $query->whereHas('variant.product', function ($p) {
                $p->where('category_id', $this->categoryId);
            });
        }

        $items = $query->get()
            ->groupBy('product_variant_id')
            ->map(function ($group) use ($isFnB) {
                $first = $group->first();
                $totalModal = 0;
                if ($isFnB) {
                    foreach ($group as $item) {
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
                    foreach ($group as $item) {
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
                    'total_qty' => $group->sum('qty'),
                    'total_diskon' => $group->sum('discount_amount'),
                    'total_subtotal' => $group->sum('subtotal'),
                    'total_modal' => $totalModal,
                    'laba_rugi' => $group->sum('subtotal') - $totalModal,
                    'jumlah_trx' => $group->pluck('sale_id')->unique()->count(),
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

        $salesQuery = Sale::whereNull('ref_sale_id')
            ->whereDoesntHave('refunds')
            ->where('status', 'paid')
            ->when($storeId, fn($sq) => $sq->where('store_id', $storeId))
            ->whereBetween('sale_date', [$this->mulai . ' 00:00:00', $this->akhir . ' 23:59:59']);

        if (!empty($this->categoryId)) {
            $salesQuery->whereHas('items.variant.product', function ($p) {
                $p->where('category_id', $this->categoryId);
            });
        }

        $sales = $salesQuery->get();
        $this->totalTransDiscount = $sales->sum('trans_discount');
        $this->totalPointDiscount = $sales->sum('point_discount_amount');
        $this->totalVoucherDiscount = $sales->sum('voucher_discount_amount');

        // Subtotal Item
        $rows[] = [
            '', '', '', 'SUBTOTAL ITEM', '', $this->sumQty, $this->sumDiskon, $this->sumSubtotal, $this->sumModal, $this->sumLaba, $this->sumTrx,
        ];

        // Diskon Transaksi
        $rows[] = [
            '', '', '', 'DISKON TRANSAKSI', '', '', '', -$this->totalTransDiscount, '', '', '',
        ];

        // Diskon Voucher
        if ($this->totalVoucherDiscount > 0) {
            $rows[] = [
                '', '', '', 'DISKON VOUCHER', '', '', '', -$this->totalVoucherDiscount, '', '', '',
            ];
        }

        // Diskon Point
        if ($this->totalPointDiscount > 0) {
            $rows[] = [
                '', '', '', 'DISKON POINT', '', '', '', -$this->totalPointDiscount, '', '', '',
            ];
        }

        $grandTotal = $this->sumSubtotal - $this->totalTransDiscount - $this->totalVoucherDiscount - $this->totalPointDiscount;

        // Grand Total Omset
        $rows[] = [
            '', '', '', 'GRAND TOTAL (OMSET)', '', '', '', $grandTotal, '', '', '',
        ];

        // Total Modal HPP
        $rows[] = [
            '', '', '', 'TOTAL MODAL (HPP)', '', '', '', '', $this->sumModal, '', '',
        ];

        // Laba Bersih
        $labaBersih = $grandTotal - $this->sumModal;
        $rows[] = [
            '', '', '', 'LABA / RUGI BERSIH', '', '', '', '', '', $labaBersih, '',
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

                $sheet->mergeCells('A1:K1');
                $sheet->mergeCells('A2:K2');
                $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

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

                $summaryStart = $lastDataRow + 1;
                $summaryCount = 4 + ($this->totalVoucherDiscount > 0 ? 1 : 0) + ($this->totalPointDiscount > 0 ? 1 : 0);
                $summaryEnd = $summaryStart + $summaryCount;

                $sheet->getStyle('A' . $summaryStart . ':K' . $summaryEnd)->applyFromArray([
                    'font' => ['bold' => true],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);

                $grandTotalRow = $summaryStart + 1 + ($this->totalVoucherDiscount > 0 ? 1 : 0) + ($this->totalPointDiscount > 0 ? 1 : 0);
                $sheet->getStyle('A' . $grandTotalRow . ':K' . $grandTotalRow)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFF2CC'],
                    ],
                ]);

                $sheet->getStyle('A' . $summaryEnd . ':K' . $summaryEnd)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D1E7DD'],
                    ],
                ]);

                $sheet->getStyle('E5:E' . $summaryEnd)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('G5:J' . $summaryEnd)->getNumberFormat()->setFormatCode('#,##0');
            },
        ];
    }
}
