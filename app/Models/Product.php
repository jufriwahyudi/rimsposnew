<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $connection = 'mysql';
    protected $table = 'products';

    protected $fillable = [
        'kode_produk',
        'nama_produk',
        'deskripsi',
    ];

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function batches()
    {
        return $this->hasManyThrough(
            StockBatch::class,
            ProductVariant::class,
            'product_id',           // FK di variants
            'product_variant_id',   // FK di batches
            'id',
            'id'
        );
    }

    /* ===== TOTAL STOK PRODUK ===== */
    public function scopeWithStock($q)
    {
        return $q->withSum('batches as total_stock', 'qty_sisa');
    }

    public function scopeWithStockWarehouse($q)
    {
        return $q->withSum([
            'batches as stock_warehouse' => fn($b) =>
            $b->where('posisi', 'warehouse')
                ->where('qty_sisa', '>', '0')
        ], 'qty_sisa');
    }

    public function scopeWithStockStore($q)
    {
        return $q->withSum([
            'batches as stock_store' => fn($b) =>
            $b->where('posisi', 'store')
                ->where('qty_sisa', '>', '0')
        ], 'qty_sisa');
    }
}
