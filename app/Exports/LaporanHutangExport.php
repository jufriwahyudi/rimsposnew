<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class LaporanHutangExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithEvents
{
    protected $totalHutang = 0;
    protected $totalTerbayar = 0;
    protected $totalSisaHutang = 0;
    protected $rowCount = 0;

    public function __construct(
        protected ?string $mulai,
        protected ?string $akhir
    ) {}

    public function headings(): array
    {
        $periode = 'Semua Periode';
        if ($this->mulai && $this->akhir) {
            $periode = 'Periode: ' . date('d/m/Y', strtotime($this->mulai)) . ' s/d ' . date('d/m/Y', strtotime($this->akhir));
        }

        return [
            ['Laporan Hutang per Mitra/Pelanggan'],
            [$periode],
            [],
            [
                'No',
                'Nama Pelanggan',
                'Nomor Telepon',
                'Alamat',
                'Jumlah Nota Hutang',
                'Total Hutang (Gross)',
                'Total Terbayar',
                'Sisa Hutang (Outstanding)',
            ],
        ];
    }

    public function array(): array
    {
        $mulai = $this->mulai;
        $akhir = $this->akhir;

        $query = Customer::query()
            ->with(['sales' => function ($q) use ($mulai, $akhir) {
                $q->where('payment_status', 'hutang');
                if ($mulai && $akhir) {
                    $q->whereBetween('sale_date', [$mulai . ' 00:00:00', $akhir . ' 23:59:59']);
                }
            }]);

        $customers = $query->get()->map(function ($customer) {
            $sales = $customer->sales;
            $totalInvoices = $sales->count();
            $totalDebt = $sales->sum('grand_total');
            $totalPaid = $sales->sum('paid_amount');
            $remaining = $totalDebt - $totalPaid;

            return (object) [
                'name' => $customer->name,
                'phone' => $customer->phone ?? '-',
                'alamat' => $customer->alamat ?? '-',
                'total_invoices' => $totalInvoices,
                'total_debt' => $totalDebt,
                'total_paid' => $totalPaid,
                'remaining' => $remaining,
            ];
        });

        // Filter: Hanya yang punya invoice hutang
        $rowsData = $customers->filter(function ($item) {
            return $item->total_invoices > 0;
        })->values();

        $rows = [];
        foreach ($rowsData as $i => $item) {
            $this->totalHutang += $item->total_debt;
            $this->totalTerbayar += $item->total_paid;
            $this->totalSisaHutang += $item->remaining;

            $rows[] = [
                $i + 1,
                $item->name,
                $item->phone,
                $item->alamat,
                $item->total_invoices,
                $item->total_debt,
                $item->total_paid,
                $item->remaining,
            ];
        }

        $this->rowCount = count($rows);

        // Append Total Row
        $rows[] = [
            '',
            '',
            '',
            'TOTAL',
            $rowsData->sum('total_invoices'),
            $this->totalHutang,
            $this->totalTerbayar,
            $this->totalSisaHutang,
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

                // Merge title & subtitle
                $sheet->mergeCells('A1:H1');
                $sheet->mergeCells('A2:H2');
                $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Format number columns (F, G, H)
                $totalRow = 4 + $this->rowCount + 1;
                
                // Alignments
                $sheet->getStyle("A5:A{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E5:E{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                $sheet->getStyle("F5:H{$totalRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');

                // Bold total row
                $sheet->getStyle("A{$totalRow}:H{$totalRow}")->getFont()->setBold(true);
            },
        ];
    }
}
