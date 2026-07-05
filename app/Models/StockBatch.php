<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockBatch extends Model
{
    protected $connection = 'mysql';
    protected $table = 'stock_batches';
    protected $fillable = [
        'product_variant_id',
        'purchase_item_id',
        'stock_transfer_id',
        'posisi',
        'tanggal_masuk',
        'qty_awal',
        'qty_sisa',
        'harga_beli',
        'sumber',
    ];

    /*
    Penjelasan dari tipe
    IN : penambahan stok (pembelian, retur penjualan, Stok awal (opening balance)) stok +
    OUT : pengurangan stok (penjualan, Barang rusak dibuang, Sample / bonus, retur pembelian) stok -
    */

    protected $casts = [
        'posisi' => 'string', // warehouse | store
        'tanggal_masuk' => 'date',
    ];


    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
    public function movements()
    {
        return $this->hasMany(StockMovement::class, 'stock_batch_id');
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_item_id');
    }

    public function saleItemBatches()
    {
        return $this->hasMany(SaleItemBatch::class, 'stock_batch_id');
    }
}
