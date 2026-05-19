<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;


class LaporanStokExport implements
    FromArray,
    WithHeadings,
    WithStyles,
    ShouldAutoSize
{
    protected $tanggal;
    protected $productHeaderRows = [];
    protected $lastRow = 0;

    public function __construct($tanggal)
    {
        $this->tanggal = $tanggal;
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Produk / Varian',
            'Warehouse',
            'Store',
            'Total',
            'Harga Modal',
            'Harga Jual',
            'Nilai Persediaan',
            'Nilai Jual',
        ];
    }

    public function array(): array
    {
        $rows = [];
        $totalNilaiPersediaan = 0;
        $totalNilaiJual = 0;
        $rowNumber = 2; // karena row 1 = heading

        $products = Product::query()
            ->with([
                'variants' => function ($q) {

                    $tanggal = $this->tanggal;

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

        foreach ($products as $product) {
            $varianCount = $product->variants->count();

            // Header produk hanya jika varian > 1
            if ($varianCount > 1) {
                $this->productHeaderRows[] = $rowNumber;
                $rows[] = [
                    '',
                    $product->nama_produk,
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                ];
                $rowNumber++;
            }

            foreach ($product->variants as $variant) {
                $stokWarehouse = (int) data_get($variant, 'stock_warehouse', 0);
                $stokStore     = (int) data_get($variant, 'stock_store', 0);
                $totalStok     = $stokWarehouse + $stokStore;

                $modal           = (float) ($variant->modalPerTanggal($this->tanggal) ?? 0);
                $nilaiPersediaan = $modal * $totalStok;

                $totalNilaiPersediaan += $nilaiPersediaan;
                $totalNilaiJual       += $variant->harga_jual * $totalStok;

                $label = $variant->variant_label ?: 'Tidak ada varian';

                // Kolom nama: gabung produk+varian jika hanya 1 varian
                if ($varianCount === 1) {
                    $namaKolom = $product->nama_produk
                        . (($label !== 'Tidak ada varian' && $label !== 'Default') ? ' — ' . $label : '');
                } else {
                    $namaKolom = $label;
                }

                $rows[] = [
                    $variant->sku,
                    $namaKolom,
                    $stokWarehouse,
                    $stokStore,
                    $totalStok,
                    $modal,
                    $variant->harga_jual,
                    $nilaiPersediaan,
                    $variant->harga_jual * $totalStok,
                ];
                $rowNumber++;
            }
        }

        // Footer total
        $rows[] = [
            '',
            '',
            '',
            '',
            '',
            '',
            'TOTAL',
            $totalNilaiPersediaan == 0 ? '0' : $totalNilaiPersediaan,
            $totalNilaiJual == 0 ? '0' : $totalNilaiJual,
        ];

        $this->lastRow = $rowNumber;

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Bold heading
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);

        // Produk background grey
        foreach ($this->productHeaderRows as $row) {
            $sheet->getStyle("A{$row}:I{$row}")
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('FFE9ECEF'); // grey soft
        }

        // Bold footer total
        $sheet->getStyle("A{$this->lastRow}:I{$this->lastRow}")
            ->getFont()->setBold(true);

        return [];
    }
}
