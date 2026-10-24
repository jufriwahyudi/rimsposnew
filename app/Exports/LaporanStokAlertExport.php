<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanStokAlertExport implements
    FromArray,
    WithHeadings,
    WithStyles,
    ShouldAutoSize
{
    protected $tanggal;
    protected $threshold;
    protected $alertType;
    protected $posisiStok;
    protected $outOfStockRows = [];
    protected $lowStockRows = [];
    protected $lastRow = 0;

    public function __construct($tanggal, $threshold = 5, $alertType = 'all', $posisiStok = 'total')
    {
        $this->tanggal    = $tanggal;
        $this->threshold  = max(0, (int) $threshold);
        $this->alertType  = $alertType;
        $this->posisiStok = $posisiStok;
    }

    public function headings(): array
    {
        $labelAcuan = match ($this->posisiStok) {
            'warehouse' => 'Stok Gudang',
            'store'     => 'Stok Toko',
            default     => 'Total Stok',
        };

        return [
            'SKU',
            'Barcode',
            'Produk / Varian',
            'Warehouse',
            'Store',
            'Total Stok',
            'Stok Terpantau (' . $labelAcuan . ')',
            'Batas Alert',
            'Status Alert',
            'Harga Modal',
            'Harga Jual',
        ];
    }

    public function array(): array
    {
        $rows = [];
        $rowNumber = 2; // Row 1 = Heading
        $tanggal = $this->tanggal;

        $products = Product::query()
            ->with([
                'variants' => function ($q) use ($tanggal) {
                    $q->select('product_variants.*')
                        ->selectSub(function ($sub) use ($tanggal) {
                            $sub->from('stock_movements')
                                ->selectRaw("
                                    COALESCE(SUM(
                                        CASE 
                                            WHEN direction = 'in' THEN qty
                                            WHEN direction = 'out' THEN -qty
                                            ELSE 0
                                        END
                                    ),0)
                                ")
                                ->whereColumn('stock_movements.product_variant_id', 'product_variants.id')
                                ->where('posisi', 'warehouse')
                                ->whereDate('tanggal', '<=', $tanggal);
                        }, 'stock_warehouse')
                        ->selectSub(function ($sub) use ($tanggal) {
                            $sub->from('stock_movements')
                                ->selectRaw("
                                    COALESCE(SUM(
                                        CASE 
                                            WHEN direction = 'in' THEN qty
                                            WHEN direction = 'out' THEN -qty
                                            ELSE 0
                                        END
                                    ),0)
                                ")
                                ->whereColumn('stock_movements.product_variant_id', 'product_variants.id')
                                ->where('posisi', 'store')
                                ->whereDate('tanggal', '<=', $tanggal);
                        }, 'stock_store')
                        ->with(['variantAttributes.value'])
                        ->where('is_active', 'Y');
                }
            ])
            ->orderBy('nama_produk')
            ->get();

        $alertList = collect();

        foreach ($products as $product) {
            $varianCount = $product->variants->count();

            foreach ($product->variants as $variant) {
                if (!$variant->track_stock) {
                    continue;
                }

                $stokWarehouse = (int) data_get($variant, 'stock_warehouse', 0);
                $stokStore     = (int) data_get($variant, 'stock_store', 0);
                $stokTotal     = $stokWarehouse + $stokStore;

                $checkedStock = match ($this->posisiStok) {
                    'warehouse' => $stokWarehouse,
                    'store'     => $stokStore,
                    default     => $stokTotal,
                };

                $status = null;
                if ($checkedStock <= 0) {
                    $status = 'out_of_stock';
                } elseif ($checkedStock <= $this->threshold) {
                    $status = 'low_stock';
                }

                if ($status !== null) {
                    $matchesType = ($this->alertType === 'all')
                        || ($this->alertType === 'out_of_stock' && $status === 'out_of_stock')
                        || ($this->alertType === 'low_stock' && $status === 'low_stock');

                    if ($matchesType) {
                        $label = $variant->variant_label ?: 'Tidak ada varian';
                        if ($varianCount === 1) {
                            $displayName = $product->nama_produk
                                . (($label !== 'Tidak ada varian' && $label !== 'Default') ? ' — ' . $label : '');
                        } else {
                            $displayName = $product->nama_produk . ' — ' . $label;
                        }

                        $alertList->push([
                            'sku'            => $variant->sku,
                            'barcode'        => $variant->barcode ?? '-',
                            'display_name'   => $displayName,
                            'stok_warehouse' => $stokWarehouse,
                            'stok_store'     => $stokStore,
                            'stok_total'     => $stokTotal,
                            'checked_stock'  => $checkedStock,
                            'threshold'      => $this->threshold,
                            'status'         => $status,
                            'status_label'   => $status === 'out_of_stock' ? 'HABIS' : 'MENIPIS',
                            'modal'          => (float) ($variant->modalPerTanggal($tanggal) ?? 0),
                            'harga_jual'     => (float) ($variant->harga_jual ?? 0),
                        ]);
                    }
                }
            }
        }

        // Sort: Habis (<= 0) first, then lowest stock
        $sorted = $alertList->sortBy(function ($item) {
            $prefix = $item['status'] === 'out_of_stock' ? '0_' : '1_';
            return $prefix . str_pad((string) max(0, $item['checked_stock']), 10, '0', STR_PAD_LEFT);
        })->values();

        foreach ($sorted as $item) {
            if ($item['status'] === 'out_of_stock') {
                $this->outOfStockRows[] = $rowNumber;
            } else {
                $this->lowStockRows[] = $rowNumber;
            }

            $rows[] = [
                $item['sku'],
                $item['barcode'],
                $item['display_name'],
                $item['stok_warehouse'],
                $item['stok_store'],
                $item['stok_total'],
                $item['checked_stock'],
                $item['threshold'],
                $item['status_label'],
                $item['modal'],
                $item['harga_jual'],
            ];
            $rowNumber++;
        }

        $this->lastRow = $rowNumber;
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Header style
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->getStyle('A1:K1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FFE2E8F0');

        // Out of stock rows (soft light red)
        foreach ($this->outOfStockRows as $row) {
            $sheet->getStyle("I{$row}")->getFont()->setBold(true)->getColor()->setARGB('FFDC2626');
            $sheet->getStyle("A{$row}:K{$row}")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('FFFEE2E2');
        }

        // Low stock rows (soft light yellow)
        foreach ($this->lowStockRows as $row) {
            $sheet->getStyle("I{$row}")->getFont()->setBold(true)->getColor()->setARGB('FFD97706');
            $sheet->getStyle("A{$row}:K{$row}")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('FFFEF3C7');
        }

        return [];
    }
}
