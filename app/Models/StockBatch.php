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
        'batch_number',
        'expired_date',
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
        'expired_date' => 'date',
    ];

    protected $appends = [
        'days_until_expired',
        'expired_status',
    ];

    public function getDaysUntilExpiredAttribute(): ?int
    {
        if (!$this->expired_date) {
            return null;
        }
        return (int) now()->startOfDay()->diffInDays($this->expired_date->startOfDay(), false);
    }

    public function getExpiredStatusAttribute(): string
    {
        if (!$this->expired_date) {
            return 'none';
        }
        $days = $this->days_until_expired;
        if ($days < 0) {
            return 'expired'; // Sudah lewat kadaluarsa (merah)
        }
        if ($days <= 30) {
            return 'danger';  // Kritis <= 30 hari
        }
        if ($days <= 90) {
            return 'warning'; // Perhatian <= 90 hari
        }
        return 'safe';        // Aman > 90 hari
    }


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
