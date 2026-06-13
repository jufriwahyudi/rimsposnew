<?php

namespace App\Exports;

use App\Models\MemberRedemption;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class LaporanPenukaranPoinExport implements
    FromArray,
    WithHeadings,
    WithStyles,
    ShouldAutoSize,
    WithEvents
{
    protected $mulai;
    protected $akhir;
    protected $totalPointsSpent = 0;
    protected $rowCount = 0;

    public function __construct($mulai, $akhir)
    {
        $this->mulai = $mulai;
        $this->akhir = $akhir;
    }

    public function headings(): array
    {
        return [
            ['Laporan Penukaran Poin Member'],
            ['Periode: ' . $this->mulai . ' s/d ' . $this->akhir],
            [],
            [
                'No',
                'Tanggal Penukaran',
                'Nama Member',
                'No. Telepon',
                'Hadiah / Voucher',
                'Tipe Hadiah',
                'Poin Ditukar',
                'Kode Voucher',
                'Status Voucher',
                'Digunakan Pada Tanggal',
                'Invoice Penjualan',
            ],
        ];
    }

    public function array(): array
    {
        $storeId = session('store_id');
        $redemptions = MemberRedemption::with(['member', 'rewardItem', 'sale'])
            ->where('store_id', $storeId)
            ->whereBetween('created_at', [$this->mulai . " 00:00:00", $this->akhir . " 23:59:59"])
            ->orderBy('created_at', 'asc')
            ->get();

        $rows = [];
        foreach ($redemptions as $i => $r) {
            $typeStr = match ($r->rewardItem->reward_type) {
                'physical' => 'Barang Fisik',
                'voucher_percent' => 'Voucher Diskon (%)',
                'voucher_nominal' => 'Voucher Potongan (Rp)',
                default => $r->rewardItem->reward_type
            };

            $statusStr = '';
            if ($r->rewardItem->reward_type === 'physical') {
                $statusStr = 'Selesai (Fisik)';
            } else {
                $statusStr = $r->is_used ? 'Sudah Digunakan' : 'Belum Digunakan';
            }

            $usedAtStr = $r->used_at ? $r->used_at->format('Y-m-d H:i') : '-';
            $invoiceNumberStr = $r->sale ? $r->sale->invoice_number : '-';

            $this->totalPointsSpent += $r->points_spent;

            $rows[] = [
                $i + 1,
                $r->created_at->format('Y-m-d H:i'),
                $r->member->name ?? '-',
                $r->member->phone ?? '-',
                $r->rewardItem->name,
                $typeStr,
                $r->points_spent,
                $r->voucher_code ?? '-',
                $statusStr,
                $usedAtStr,
                $invoiceNumberStr,
            ];
        }

        $this->rowCount = count($rows);

        // Add total row
        $rows[] = [
            '',
            '',
            '',
            '',
            '',
            'TOTAL POIN DITUKAR',
            $this->totalPointsSpent,
            '',
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
                $sheet->mergeCells('A1:K1');
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Merge period row
                $sheet->mergeCells('A2:K2');
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Style total row
                $totalRow = 4 + $this->rowCount + 1;
                $sheet->getStyle("A{$totalRow}:K{$totalRow}")->getFont()->setBold(true);
            },
        ];
    }
}
