<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class KasKeluarExport implements
    FromArray,
    WithHeadings,
    WithStyles,
    ShouldAutoSize,
    WithEvents
{
    protected $storeId;
    protected $startDateTime;
    protected $endDateTime;
    protected $shiftId;
    protected $userId;
    protected $search;
    protected $storeName;
    protected $rowCount = 0;
    protected $totalAmount = 0;

    public function __construct($storeId, $startDateTime, $endDateTime, $shiftId = null, $userId = null, $search = null, $storeName = '')
    {
        $this->storeId = $storeId;
        $this->startDateTime = $startDateTime;
        $this->endDateTime = $endDateTime;
        $this->shiftId = $shiftId;
        $this->userId = $userId;
        $this->search = $search;
        $this->storeName = $storeName;
    }

    public function headings(): array
    {
        return [
            ['LAPORAN KAS KELUAR'],
            ['Toko: ' . ($this->storeName ?: ('Store #' . $this->storeId))],
            ['Periode: ' . Carbon::parse($this->startDateTime)->format('d/m/Y H:i') . ' s/d ' . Carbon::parse($this->endDateTime)->format('d/m/Y H:i')],
            [''], // empty row
            [
                'No',
                'ID Transaksi',
                'Tanggal & Waktu',
                'Shift / Sesi Kasir',
                'Petugas Shift',
                'Kasir Input',
                'Keperluan / Keterangan',
                'Nominal Kas Keluar (Rp)',
            ],
        ];
    }

    public function array(): array
    {
        $liveDb = DB::connection('mysql_live');

        $query = $liveDb->table('cash_transactions as ct')
            ->leftJoin('users as u', 'ct.user_id', '=', 'u.id')
            ->leftJoin('cash_registers as cr', 'ct.cash_register_id', '=', 'cr.id')
            ->leftJoin('users as cru', 'cr.user_id', '=', 'cru.id')
            ->where('ct.store_id', $this->storeId)
            ->where('ct.transaction_type', 'cash_out')
            ->whereBetween('ct.transaction_date', [$this->startDateTime, $this->endDateTime]);

        if (!empty($this->shiftId)) {
            $query->where('ct.cash_register_id', $this->shiftId);
        }
        if (!empty($this->userId)) {
            $query->where('ct.user_id', $this->userId);
        }
        if (!empty($this->search)) {
            $query->where('ct.notes', 'like', "%{$this->search}%");
        }

        $items = $query->select(
            'ct.id',
            'ct.transaction_date',
            'ct.amount',
            'ct.notes',
            'u.name as cashier_name',
            'ct.cash_register_id',
            'cru.name as shift_opened_by',
            'cr.opened_at as shift_opened_at',
            'cr.closed_at as shift_closed_at',
            'cr.status as shift_status'
        )
        ->orderBy('ct.transaction_date', 'asc')
        ->get();

        $rows = [];
        $no = 1;
        $this->totalAmount = 0;

        foreach ($items as $item) {
            $this->totalAmount += (float)$item->amount;
            $shiftLabel = $item->cash_register_id ? "Shift #{$item->cash_register_id}" : "-";
            if ($item->shift_opened_at) {
                $shiftLabel .= ' (' . Carbon::parse($item->shift_opened_at)->format('d/m H:i') . ')';
            }

            $rows[] = [
                $no++,
                '#' . $item->id,
                Carbon::parse($item->transaction_date)->format('d/m/Y H:i:s'),
                $shiftLabel,
                $item->shift_opened_by ?: '-',
                $item->cashier_name ?: '-',
                $item->notes ?: '-',
                (float)$item->amount,
            ];
        }

        $this->rowCount = count($rows);

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');
        $sheet->mergeCells('A3:H3');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10);

        // Header table at row 5
        $sheet->getStyle('A5:H5')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFDC2626'], // Danger Red theme
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $startRow = 6;
                $lastRow = 5 + $this->rowCount;

                // Center align specific columns
                if ($this->rowCount > 0) {
                    $sheet->getStyle("A{$startRow}:C{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("D{$startRow}:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("H{$startRow}:H{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');

                    // Borders
                    $sheet->getStyle("A5:H{$lastRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['argb' => 'FFD1D5DB'],
                            ],
                        ],
                    ]);
                }

                // Summary Row
                $summaryRow = $lastRow + 1;
                $sheet->mergeCells("A{$summaryRow}:G{$summaryRow}");
                $sheet->setCellValue("A{$summaryRow}", 'TOTAL KAS KELUAR');
                $sheet->setCellValue("H{$summaryRow}", $this->totalAmount);

                $sheet->getStyle("A{$summaryRow}:H{$summaryRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFEE2E2'],
                    ],
                    'borders' => [
                        'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FFDC2626']],
                        'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['argb' => 'FFDC2626']],
                    ],
                ]);

                $sheet->getStyle("A{$summaryRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("H{$summaryRow}")->getNumberFormat()->setFormatCode('#,##0');
            },
        ];
    }
}

