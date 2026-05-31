<?php

namespace App\Exports;

use App\Models\SaleItem;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LaporanTenantExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithEvents
{
    protected $totalPenjualan = 0;
    protected $totalKomisi = 0;
    protected $totalHakTenant = 0;
    protected $rowCount = 0;

    public function __construct(
        protected string $mulai,
        protected string $akhir
    ) {}

    public function headings(): array
    {
        return [
            ['Laporan Piutang Tenant (Settlement)'],
            ['Periode: ' . date('d/m/Y', strtotime($this->mulai)) . ' s/d ' . date('d/m/Y', strtotime($this->akhir))],
            [],
            [
                'No',
                'Kode Tenant',
                'Nama Tenant',
                'Total Qty Terjual',
                'Total Penjualan (Gross)',
                'Komisi Toko',
                'Hak Tenant (Net Payout)',
            ],
        ];
    }

    public function array(): array
    {
        $items = SaleItem::with(['variant.product.tenant', 'fnbDetail', 'sale'])
            ->whereHas('sale', function ($q) {
                $q->where('status', 'paid')
                    ->whereNull('ref_sale_id')
                    ->whereDoesntHave('refunds')
                    ->whereBetween('sale_date', [$this->mulai . " 00:00:00", $this->akhir . " 23:59:59"]);
            })
            ->whereIn('status', ['sold', 'exchanged_in'])
            ->whereHas('variant.product', function ($q) {
                $q->whereNotNull('tenant_id');
            })
            ->get();

        $tenantsData = $items->groupBy(function ($item) {
            return $item->variant->product->tenant_id;
        })->map(function ($tenantItems) {
            $first = $tenantItems->first();
            $tenant = $first->variant->product->tenant;

            $totalQty = $tenantItems->sum('qty');
            $grossSales = 0;
            $totalCommission = 0;

            foreach ($tenantItems as $item) {
                $subtotal = $item->price * $item->qty;
                $commissionAmount = $item->commission_amount ?? ($item->variant ? $item->variant->calculateCommission($item->price) : 0);
                $commission = $commissionAmount * $item->qty;
                $grossSales += $subtotal;
                $totalCommission += $commission;
            }

            $tenantShare = $grossSales - $totalCommission;

            return (object) [
                'kode_tenant' => $tenant->kode_tenant ?? '-',
                'nama_tenant' => $tenant->nama_tenant ?? 'Unknown Tenant',
                'total_qty'   => $totalQty,
                'gross_sales' => $grossSales,
                'commission'  => $totalCommission,
                'net_payout'  => $tenantShare,
            ];
        })->values();

        $rows = [];
        foreach ($tenantsData as $i => $tenant) {
            $this->totalPenjualan += $tenant->gross_sales;
            $this->totalKomisi += $tenant->commission;
            $this->totalHakTenant += $tenant->net_payout;

            $rows[] = [
                $i + 1,
                $tenant->kode_tenant,
                $tenant->nama_tenant,
                $tenant->total_qty,
                $tenant->gross_sales,
                $tenant->commission,
                $tenant->net_payout,
            ];
        }

        $this->rowCount = count($rows);

        // Append Total Row
        $rows[] = [
            '',
            '',
            'TOTAL',
            '',
            $this->totalPenjualan,
            $this->totalKomisi,
            $this->totalHakTenant,
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
                $sheet->mergeCells('A1:G1');
                $sheet->mergeCells('A2:G2');
                $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Format number columns (E, F, G)
                $totalRow = 4 + $this->rowCount + 1;
                $sheet->getStyle("E5:G{$totalRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');

                // Bold total row
                $sheet->getStyle("A{$totalRow}:G{$totalRow}")->getFont()->setBold(true);
            },
        ];
    }
}
