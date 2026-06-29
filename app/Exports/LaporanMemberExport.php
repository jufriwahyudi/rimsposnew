<?php

namespace App\Exports;

use App\Models\Member;
use App\Models\Store;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class LaporanMemberExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithEvents
{
    protected int $rowCount = 0;

    public function __construct(
        protected ?string $mulai,
        protected ?string $akhir,
        protected ?string $search
    ) {}

    public function headings(): array
    {
        $periode = 'Semua Periode';
        if ($this->mulai && $this->akhir) {
            $periode = 'Periode Registrasi: ' . date('d/m/Y', strtotime($this->mulai)) . ' s/d ' . date('d/m/Y', strtotime($this->akhir));
        }

        return [
            ['Laporan Member & Total Poin'],
            [$periode],
            [],
            [
                'No',
                'Nama Member',
                'No. Telepon',
                'Email',
                'Tanggal Lahir',
                'Total Poin',
                'Status',
                'Tanggal Terdaftar',
            ],
        ];
    }

    public function array(): array
    {
        $storeId = session('store_id');
        $store = Store::find($storeId);
        $businessId = $store ? ($store->business_id ?: 1) : 1;

        $query = Member::where('business_id', $businessId);

        if ($this->mulai && $this->akhir) {
            $query->whereBetween('created_at', [$this->mulai . ' 00:00:00', $this->akhir . ' 23:59:59']);
        }

        if ($this->search) {
            $search = $this->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $members = $query->orderBy('name', 'asc')->get();

        $rows = [];
        foreach ($members as $i => $item) {
            $rows[] = [
                $i + 1,
                $item->name,
                $item->phone ?? '-',
                $item->email ?? '-',
                $item->birth_date ? $item->birth_date->format('d-m-Y') : '-',
                $item->total_points ?? 0,
                $item->is_active ? 'Aktif' : 'Nonaktif',
                $item->created_at ? $item->created_at->format('d-m-Y H:i') : '-',
            ];
        }

        $this->rowCount = count($rows);

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

                // Merge title & subtitle
                $sheet->mergeCells("A1:H1");
                $sheet->mergeCells("A2:H2");
                $sheet->getStyle("A1:H2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $totalRow = 4 + $this->rowCount;

                // Alignment styling
                $sheet->getStyle("A5:A{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E5:E{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("F5:F{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("G5:H{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}
