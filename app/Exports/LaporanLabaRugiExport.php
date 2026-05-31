<?php

namespace App\Exports;

use App\Models\Expense;
use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LaporanLabaRugiExport implements FromArray, ShouldAutoSize, WithEvents
{
    public function __construct(
        protected string $mulai,
        protected string $akhir,
        protected string $storeName = '',
    ) {}

    public function array(): array
    {
        // ===== PENJUALAN =====
        $sales = Sale::with([
            'items' => fn($q) => $q->with(['batches', 'variant.product.tenant', 'fnbDetail'])->whereIn('status', ['sold', 'exchanged_in']),
        ])
            ->whereNull('ref_sale_id')
            ->whereDoesntHave('refunds')
            ->whereBetween('sale_date', [$this->mulai . ' 00:00:00', $this->akhir . ' 23:59:59'])
            ->get();

        $store = \App\Models\Store::find(session('store_id'));
        $isFnB = $store && $store->business_type === 'fnb';

        $omset = $sales->sum('grand_total');
        $hpp   = 0;
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                if ($isFnB) {
                    $variant = $item->variant;
                    $tenant = $variant->product->tenant ?? null;
                    $costPriceManual = $item->cost_price ?? ($variant->cost_price_manual ?? 0);
                    if ($tenant) {
                        $commissionAmount = $item->commission_amount ?? ($variant ? $variant->calculateCommission($item->price) : 0);
                        $commission = $commissionAmount * $item->qty;
                        $tenantShare = ($item->price * $item->qty) - $commission;
                        $hpp += $tenantShare + $costPriceManual * $item->qty;
                    } else {
                        $hpp += $costPriceManual * $item->qty;
                    }
                } else {
                    foreach ($item->batches as $batch) {
                        $hpp += $batch->qty * $batch->cost_price;
                    }
                }
            }
        }

        $pendapatanKotor = $omset - $hpp;

        // ===== BIAYA OPERASIONAL =====
        $expenses = Expense::with('category')
            ->whereBetween('transaction_date', [$this->mulai, $this->akhir])
            ->get();

        $biayaPerKategori = $expenses
            ->groupBy('expense_category_id')
            ->map(fn($items) => [
                $items->first()->category->name ?? 'Lainnya',
                $items->sum('amount'),
            ])
            ->sortByDesc(fn($r) => $r[1])
            ->values();

        $totalBiaya = $expenses->sum('amount');
        $labaRugi   = $pendapatanKotor - $totalBiaya;

        // ===== BUILD ROWS =====
        $rows = [];

        $rows[] = ['LAPORAN LABA / RUGI', ''];
        $rows[] = [$this->storeName, ''];
        $rows[] = [
            'Periode: ' . date('d/m/Y', strtotime($this->mulai))
                . ' s/d ' . date('d/m/Y', strtotime($this->akhir)),
            '',
        ];
        $rows[] = ['', ''];

        // Pendapatan
        $rows[] = ['PENDAPATAN', ''];
        $rows[] = ['Total Omset Penjualan', $omset];
        $rows[] = ['', ''];

        // HPP
        $rows[] = ['BEBAN POKOK PENJUALAN (HPP)', ''];
        $rows[] = ['Modal / HPP', $hpp];
        $rows[] = ['', ''];

        // Pendapatan Kotor
        $rows[] = ['PENDAPATAN KOTOR', $pendapatanKotor];
        $rows[] = ['', ''];

        // Biaya Operasional
        $rows[] = ['BIAYA OPERASIONAL', ''];
        foreach ($biayaPerKategori as $biaya) {
            $rows[] = ['  ' . $biaya[0], $biaya[1]];
        }
        $rows[] = ['Total Biaya Operasional', $totalBiaya];
        $rows[] = ['', ''];

        // Laba / Rugi
        $rows[] = ['LABA / RUGI BERSIH', $labaRugi];

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet  = $event->sheet->getDelegate();
                $rows   = $this->array();
                $total  = count($rows);

                // Column widths
                $sheet->getColumnDimension('A')->setWidth(42);
                $sheet->getColumnDimension('B')->setWidth(22);

                // Number format column B (currency)
                $sheet->getStyle('B1:B' . ($total + 1))
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');

                // ---- Row 1: Title ----
                $sheet->mergeCells('A1:B1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF5B21B6']],
                    'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
                ]);

                // ---- Row 2: Store name ----
                $sheet->mergeCells('A2:B2');
                $sheet->getStyle('A2')->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'font'      => ['bold' => false, 'size' => 11, 'color' => ['argb' => 'FF374151']],
                ]);

                // ---- Row 3: Period ----
                $sheet->mergeCells('A3:B3');
                $sheet->getStyle('A3')->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'font'      => ['italic' => true, 'color' => ['argb' => 'FF6B7280']],
                ]);

                // Section header rows: detect by empty B value
                $sectionHeaders = ['PENDAPATAN', 'BEBAN POKOK PENJUALAN (HPP)', 'BIAYA OPERASIONAL'];
                $subtotalRows   = ['PENDAPATAN KOTOR', 'Total Biaya Operasional'];
                $bottomLine     = 'LABA / RUGI BERSIH';

                for ($r = 1; $r <= $total; $r++) {
                    $cellA = $sheet->getCell("A{$r}")->getValue();
                    $cellB = $sheet->getCell("B{$r}")->getValue();

                    if (in_array($cellA, $sectionHeaders)) {
                        $sheet->mergeCells("A{$r}:B{$r}");
                        $sheet->getStyle("A{$r}")->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['argb' => 'FF5B21B6']],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF3F0FF']],
                        ]);
                    } elseif (in_array($cellA, $subtotalRows)) {
                        $sheet->getStyle("A{$r}:B{$r}")->applyFromArray([
                            'font' => ['bold' => true],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEDE9FE']],
                            'borders' => [
                                'top'    => ['borderStyle' => Border::BORDER_THIN],
                                'bottom' => ['borderStyle' => Border::BORDER_THIN],
                            ],
                        ]);
                        $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    } elseif ($cellA === $bottomLine) {
                        $isLaba = ($cellB >= 0);
                        $bgColor = $isLaba ? 'FF065F46' : 'FF991B1B';
                        $sheet->getStyle("A{$r}:B{$r}")->applyFromArray([
                            'font'    => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FFFFFFFF']],
                            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgColor]],
                            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]],
                        ]);
                        $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    } elseif ($cellB !== '' && $cellB !== null && $cellA !== '') {
                        $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }
                }
            },
        ];
    }
}
