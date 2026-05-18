<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $connection = 'mysql';
    protected $table = 'stock_movements';
    protected $fillable = [
        'product_variant_id',
        'stock_batch_id',
        'posisi',
        'tanggal',
        'tipe',
        'direction',
        'qty',
        'ref_type',
        'ref_id',
    ];

    protected $casts = [
        'tipe' => 'string', // IN, OUT, ADJUST
        'posisi' => 'string',      // warehouse, store
        'tanggal' => 'datetime',
    ];

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function batch()
    {
        return $this->belongsTo(StockBatch::class, 'stock_batch_id');
    }
}
