<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    protected $connection = 'mysql';
    protected $table = 'stock_transfer_items';
    protected $fillable = [
        'stock_transfer_id',
        'product_variant_id',
        'qty_requested',
        'qty_approved',
        'qty_received',
    ];

    /* ================== RELATIONSHIP ================== */

    public function stockTransfer()
    {
        return $this->belongsTo(StockTransfer::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
