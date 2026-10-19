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

class LaporanHarianCategorySheet implements
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
        return 'Per Kategori';
    }

    public function headings(): array
    {
        $tanggalText = $this->mulai === $this->akhir ? $this->mulai : $this->mulai . ' s/d ' . $this->akhir;
        return [
            ['Laporan Harian - Penjualan Berdasarkan Kategori'],
            ['Tanggal: ' . $tanggalText],
            [],
            [
                'No',
                'Nama Kategori',
                'Jml Jenis Produk',
                'Qty Terjual',
                'Diskon',
                'Total Penjualan',
                'Modal (HPP)',
                'Laba / Rugi',
                'Kontribusi (%)',
                'Jml Trx',
            ],
        ];
    }

    public function array(): array
    {
        $storeId = session('store_id');
        $store = $storeId ? Store::find($storeId) : null;
        $isFnB = $store && $store->business_type === 'fnb';

        $startDateTime = str_contains($this->mulai, ':') ? (substr_count($this->mulai, ':') === 1 ? $this->mulai . ':00' : $this->mulai) : ($this->mulai . ' 00:00:00');
        $endDateTime = str_contains($this->akhir, ':') ? (substr_count($this->akhir, ':') === 1 ? $this->akhir . ':59' : $this->akhir) : ($this->akhir . ' 23:59:59');

        $query = SaleItem::with(['variant.product.category', 'fnbDetail', 'batches', 'sale'])
            ->whereHas('sale', function ($q) use ($storeId, $startDateTime, $endDateTime) {
                $q->whereNull('ref_sale_id')
                    ->whereDoesntHave('refunds')
                    ->where('status', 'paid')
                    ->when($storeId, fn($sq) => $sq->where('store_id', $storeId))
                    ->whereBetween('sale_date', [$startDateTime, $endDateTime]);
            })
            ->whereIn('status', ['sold', 'exchanged_in']);

        if (!empty($this->categoryId)) {
            $query->whereHas('variant.product', function ($p) {
                $p->where('category_id', $this->categoryId);
            });
        }

        $rawItems = $query->get();

        foreach ($rawItems as $item) {
            $variant = $item->variant;
            if ($isFnB) {
                $tenantId = $variant && $variant->product ? $variant->product->tenant_id : null;
                $costPriceManual = $item->cost_price ?? ($variant->cost_price_manual ?? 0);
                if ($tenantId) {
                    $commissionAmount = $item->commission_amount ?? ($variant ? $variant->calculateCommission($item->price) : 0);
                    $costPrice = ($item->price - $commissionAmount) + $costPriceManual;
                } else {
                    $costPrice = $costPriceManual;
                }
                $item->calculated_modal = $item->qty * $costPrice;
            } else {
                $itemModal = 0;
                foreach ($item->batches as $batch) {
                    $itemModal += ($batch->qty * $batch->cost_price);
                }
                $item->calculated_modal = $itemModal;
            }
        }

        $allSubtotal = $rawItems->sum('subtotal');

        $categories = $rawItems->groupBy(function ($item) {
            $category = optional(optional($item->variant)->product)->category;
            return $category ? $category->id : 0;
        })->map(function ($group) use ($allSubtotal) {
            $first = $group->first();
            $category = optional(optional($first->variant)->product)->category;

            $totalQty = $group->sum('qty');
            $totalSubtotal = $group->sum('subtotal');
            $totalDiscount = $group->sum('discount_amount');
            $totalModal = $group->sum('calculated_modal');
            $totalLaba = $totalSubtotal - $totalModal;
            $kontribusiPct = $allSubtotal > 0 ? round(($totalSubtotal / $allSubtotal) * 100, 1) : 0;
            $jumlahTrx = $group->pluck('sale_id')->unique()->count();
            $jumlahProduk = $group->pluck('variant.product_id')->filter()->unique()->count();

            return (object) [
                'category_name' => $category ? $category->name : 'Tanpa Kategori',
                'jumlah_produk' => $jumlahProduk ?: 1,
                'total_qty' => $totalQty,
                'total_diskon' => $totalDiscount,
                'total_subtotal' => $totalSubtotal,
                'total_modal' => $totalModal,
                'laba_rugi' => $totalLaba,
                'kontribusi_pct' => $kontribusiPct,
                'jumlah_trx' => $jumlahTrx,
            ];
        })->sortByDesc('total_subtotal')->values();

        $rows = [];
        foreach ($categories as $i => $row) {
            $this->sumQty += $row->total_qty;
            $this->sumDiskon += $row->total_diskon;
            $this->sumSubtotal += $row->total_subtotal;
            $this->sumModal += $row->total_modal;
            $this->sumLaba += $row->laba_rugi;
            $this->sumTrx += $row->jumlah_trx;

            $rows[] = [
                $i + 1,
                $row->category_name,
                $row->jumlah_produk,
                $row->total_qty,
                $row->total_diskon,
                $row->total_subtotal,
                $row->total_modal,
                $row->laba_rugi,
                $row->kontribusi_pct . '%',
                $row->jumlah_trx,
            ];
        }

        $this->rowCount = count($rows);

        $salesQuery = Sale::whereNull('ref_sale_id')
            ->whereDoesntHave('refunds')
            ->where('status', 'paid')
            ->when($storeId, fn($sq) => $sq->where('store_id', $storeId))
            ->whereBetween('sale_date', [$startDateTime, $endDateTime]);

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
            '', '', 'SUBTOTAL ITEM', $this->sumQty, $this->sumDiskon, $this->sumSubtotal, $this->sumModal, $this->sumLaba, '100%', $this->sumTrx,
        ];

        // Diskon Transaksi
        $rows[] = [
            '', '', 'DISKON TRANSAKSI', '', '', -$this->totalTransDiscount, '', '', '', '',
        ];

        // Diskon Voucher
        if ($this->totalVoucherDiscount > 0) {
            $rows[] = [
                '', '', 'DISKON VOUCHER', '', '', -$this->totalVoucherDiscount, '', '', '', '',
            ];
        }

        // Diskon Point
        if ($this->totalPointDiscount > 0) {
            $rows[] = [
                '', '', 'DISKON POINT', '', '', -$this->totalPointDiscount, '', '', '', '',
            ];
        }

        $grandTotal = $this->sumSubtotal - $this->totalTransDiscount - $this->totalVoucherDiscount - $this->totalPointDiscount;

        // Grand Total Omset
        $rows[] = [
            '', '', 'GRAND TOTAL (OMSET)', '', '', $grandTotal, '', '', '', '',
        ];

        // Total Modal HPP
        $rows[] = [
            '', '', 'TOTAL MODAL (HPP)', '', '', '', $this->sumModal, '', '', '',
        ];

        // Laba Bersih
        $labaBersih = $grandTotal - $this->sumModal;
        $rows[] = [
            '', '', 'LABA / RUGI BERSIH', '', '', '', '', $labaBersih, '', '',
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

                $sheet->mergeCells('A1:J1');
                $sheet->mergeCells('A2:J2');
                $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle('A4:J4')->applyFromArray([
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
                    $sheet->getStyle('A5:J' . $lastDataRow)->applyFromArray([
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

                $sheet->getStyle('A' . $summaryStart . ':J' . $summaryEnd)->applyFromArray([
                    'font' => ['bold' => true],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);

                $grandTotalRow = $summaryStart + 1 + ($this->totalVoucherDiscount > 0 ? 1 : 0) + ($this->totalPointDiscount > 0 ? 1 : 0);
                $sheet->getStyle('A' . $grandTotalRow . ':J' . $grandTotalRow)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFF2CC'],
                    ],
                ]);

                $sheet->getStyle('A' . $summaryEnd . ':J' . $summaryEnd)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D1E7DD'],
                    ],
                ]);

                $sheet->getStyle('D5:H' . $summaryEnd)->getNumberFormat()->setFormatCode('#,##0');
            },
        ];
    }
}
