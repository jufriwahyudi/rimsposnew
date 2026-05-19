<?php

namespace App\Exports;

use App\Models\Expense;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class LaporanBiayaExport implements
    FromCollection,
    WithHeadings,
    WithTitle,
    WithStyles,
    WithColumnWidths,
    WithColumnFormatting
{
    public function __construct(
        protected string $mulai,
        protected string $akhir,
        protected string $jenis = 'rekap',
        protected ?string $metode = null,
        protected ?int $categoryId = null,
    ) {}

    public function collection()
    {
        $expenses = Expense::with(['category', 'user'])
            ->whereBetween('transaction_date', [$this->mulai, $this->akhir])
            ->when($this->metode && $this->metode !== 'semua', fn($q) => $q->where('payment_method', $this->metode))
            ->when($this->categoryId, fn($q) => $q->where('expense_category_id', $this->categoryId))
            ->orderBy('transaction_date')
            ->get();

        if ($this->jenis === 'rekap') {
            return $expenses
                ->groupBy('expense_category_id')
                ->map(function ($items) {
                    return [
                        $items->first()->category->name ?? '-',
                        $items->count(),
                        $items->where('payment_method', 'cash')->sum('amount'),
                        $items->where('payment_method', 'transfer')->sum('amount'),
                        $items->sum('amount'),
                    ];
                })
                ->sortByDesc(fn($row) => $row[4])
                ->values();
        }

        // Detail
        return $expenses->values()->map(function ($e, $i) {
            return [
                $i + 1,
                $e->transaction_date->format('d/m/Y'),
                $e->category->name ?? '-',
                $e->description,
                ucfirst($e->payment_method),
                $e->amount,
                optional($e->user)->name ?? '-',
                $e->notes ?? '',
            ];
        });
    }

    public function headings(): array
    {
        if ($this->jenis === 'rekap') {
            return ['Kategori', 'Jml Transaksi', 'Total Cash', 'Total Transfer', 'Total'];
        }

        return ['No', 'Tanggal', 'Kategori', 'Keterangan', 'Metode', 'Jumlah', 'Dicatat Oleh', 'Catatan'];
    }

    public function title(): string
    {
        return $this->jenis === 'rekap' ? 'Rekapitulasi' : 'Detail';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'size' => 11],
                'fill'      => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF7C3AED']],
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }

    public function columnWidths(): array
    {
        if ($this->jenis === 'rekap') {
            return ['A' => 28, 'B' => 16, 'C' => 18, 'D' => 18, 'E' => 18];
        }

        return ['A' => 5, 'B' => 14, 'C' => 22, 'D' => 36, 'E' => 12, 'F' => 18, 'G' => 22, 'H' => 30];
    }

    public function columnFormats(): array
    {
        if ($this->jenis === 'rekap') {
            return [
                'C' => '#,##0',
                'D' => '#,##0',
                'E' => '#,##0',
            ];
        }

        return ['F' => '#,##0'];
    }
}
