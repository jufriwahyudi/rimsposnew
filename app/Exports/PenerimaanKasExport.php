<?php

namespace App\Exports;

use App\Models\CashTransaction;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PenerimaanKasExport implements
    FromArray,
    WithHeadings,
    WithStyles,
    ShouldAutoSize,
    WithEvents
{
    protected $mulai;
    protected $akhir;
    protected $userId;
    protected $paymentMethod;
    protected $totalMasuk = 0;
    protected $totalKeluar = 0;
    protected $rowCount = 0;

    public function __construct($mulai, $akhir = null, $userId = null, $paymentMethod = 'cash')
    {
        $this->mulai = $mulai;
        $this->akhir = $akhir ?? $mulai;
        $this->userId = $userId;
        $this->paymentMethod = $paymentMethod;
    }

    public function headings(): array
    {
        $title = $this->paymentMethod === 'transfer' ? 'Laporan Penerimaan Transfer' : 'Laporan Penerimaan Kas (Cash)';
        $inLabel = $this->paymentMethod === 'transfer' ? 'Transfer Masuk' : 'Kas Masuk';
        $outLabel = $this->paymentMethod === 'transfer' ? 'Transfer Keluar' : 'Kas Keluar';
        $periodeText = $this->mulai === $this->akhir 
            ? 'Tanggal: ' . $this->mulai 
            : 'Periode: ' . $this->mulai . ' s/d ' . $this->akhir;

        return [
            [$title],
            [$periodeText],
            [],
            [
                'No',
                'Waktu',
                'Tipe Transaksi',
                'Referensi',
                'Keterangan',
                $inLabel,
                $outLabel,
                'Petugas',
            ],
        ];
    }

    public function array(): array
    {
        $transactions = CashTransaction::with('user')
            ->whereBetween('transaction_date', [$this->mulai . ' 00:00:00', $this->akhir . ' 23:59:59'])
            ->where('payment_method', $this->paymentMethod)
            ->when($this->userId, fn($q) => $q->where('user_id', $this->userId))
            ->orderBy('transaction_date', 'asc')
            ->get();

        $rows = [];
        foreach ($transactions as $i => $trx) {
            $masuk = $trx->direction === 'in' ? $trx->amount : 0;
            $keluar = $trx->direction === 'out' ? $trx->amount : 0;
            $this->totalMasuk += $masuk;
            $this->totalKeluar += $keluar;

            $typeLabel = match ($trx->transaction_type) {
                'sale' => 'Penjualan',
                'refund' => 'Refund',
                'expense' => 'Pengeluaran',
                'purchase' => 'Pembelian',
                'adjustment' => 'Penyesuaian',
                default => ucfirst($trx->transaction_type),
            };

            $rows[] = [
                $i + 1,
                \Carbon\Carbon::parse($trx->transaction_date)->format($this->mulai === $this->akhir ? 'H:i:s' : 'd/m/Y H:i'),
                $typeLabel,
                $trx->ref_type ? $trx->ref_type . '#' . $trx->ref_id : '-',
                $trx->notes ?: '-',
                $masuk,
                $keluar,
                optional($trx->user)->name ?? '-',
            ];
        }

        $this->rowCount = count($rows);

        // Total row
        $rows[] = [
            '', '', '', '', 'TOTAL',
            $this->totalMasuk,
            $this->totalKeluar,
            '',
        ];

        // Saldo row
        $saldo = $this->totalMasuk - $this->totalKeluar;
        $saldoLabel = $this->paymentMethod === 'transfer' ? 'SALDO TRANSFER' : 'SALDO KAS';
        $rows[] = [
            '', '', '', '', $saldoLabel,
            $saldo,
            '',
            '',
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
                $sheet->mergeCells('A1:H1');
                $sheet->mergeCells('A2:H2');
                $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Header row style
                $sheet->getStyle('A4:H4')->applyFromArray([
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
                    $sheet->getStyle('A5:H' . $lastDataRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            ],
                        ],
                    ]);
                }

                // Total row
                $totalRow = $lastDataRow + 1;
                $sheet->getStyle('A' . $totalRow . ':H' . $totalRow)->applyFromArray([
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

                // Saldo row
                $saldoRow = $totalRow + 1;
                $sheet->getStyle('A' . $saldoRow . ':H' . $saldoRow)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F3E8FF'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // Number format for amount columns (F & G)
                $sheet->getStyle('F5:G' . $saldoRow)->getNumberFormat()
                    ->setFormatCode('#,##0');
            },
        ];
    }
}
