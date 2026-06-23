<?php

namespace App\Exports;

use App\Models\Customer;
use App\Models\CustomerCustomField;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class LaporanCustomerExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithEvents
{
    protected int $rowCount = 0;
    protected $customFields;
    protected int $columnCount = 5;

    public function __construct(
        protected ?string $mulai,
        protected ?string $akhir,
        protected ?string $search
    ) {
        $storeId = session('store_id');
        $this->customFields = CustomerCustomField::where('store_id', $storeId)->get();
        $this->columnCount = 5 + $this->customFields->count();
    }

    public function headings(): array
    {
        $periode = 'Semua Periode';
        if ($this->mulai && $this->akhir) {
            $periode = 'Periode Registrasi: ' . date('d/m/Y', strtotime($this->mulai)) . ' s/d ' . date('d/m/Y', strtotime($this->akhir));
        }

        $headers = [
            'No',
            'Nama Customer',
            'No. Telepon',
            'Alamat',
            'Tanggal Registrasi'
        ];

        foreach ($this->customFields as $field) {
            $headers[] = $field->label;
        }

        return [
            ['Laporan Customer / Mitra'],
            [$periode],
            [],
            $headers,
        ];
    }

    public function array(): array
    {
        $storeId = session('store_id');
        $query = Customer::where('store_id', $storeId);

        if ($this->mulai && $this->akhir) {
            $query->whereBetween('created_at', [$this->mulai . ' 00:00:00', $this->akhir . ' 23:59:59']);
        }

        if ($this->search) {
            $search = $this->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('alamat', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('name', 'asc')->get();

        $rows = [];
        foreach ($customers as $i => $item) {
            $row = [
                $i + 1,
                $item->name,
                $item->phone ?? '-',
                $item->alamat ?? '-',
                $item->created_at ? $item->created_at->format('d-m-Y H:i') : '-',
            ];

            $customValues = $item->custom_values ?? [];
            foreach ($this->customFields as $field) {
                $row[] = $customValues[$field->name] ?? '-';
            }

            $rows[] = $row;
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
                $endColumn = Coordinate::stringFromColumnIndex($this->columnCount);

                // Merge title & subtitle
                $sheet->mergeCells("A1:{$endColumn}1");
                $sheet->mergeCells("A2:{$endColumn}2");
                $sheet->getStyle("A1:{$endColumn}2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $totalRow = 4 + $this->rowCount;

                // Center align the 'No' and 'Tanggal Registrasi' columns
                $sheet->getStyle("A5:A{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E5:E{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}
