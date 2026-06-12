<?php

namespace App\Exports;

use App\Models\ProductVariant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StockTemplateExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $storeId;

    public function __construct(int $storeId)
    {
        $this->storeId = $storeId;
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Nama Produk',
            'Nama Varian',
            'Posisi (store/warehouse)',
            'Jumlah Stok',
            'Harga Beli/Modal'
        ];
    }

    public function collection()
    {
        $variants = ProductVariant::where('store_id', $this->storeId)
            ->where('is_active', 'Y')
            ->where('track_stock', true)
            ->with('product')
            ->get();

        return $variants->map(function ($v) {
            return [
                'sku' => $v->sku,
                'nama_produk' => $v->product->nama_produk ?? '',
                'nama_varian' => $v->variant_name ?: ($v->variant_label ?? ''),
                'posisi' => '',       // Empty for user to input: store / warehouse
                'jumlah_stok' => '',  // Empty for user to input
                'harga_beli' => '',   // Empty for user to input
            ];
        });
    }
}
