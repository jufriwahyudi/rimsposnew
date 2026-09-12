<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $connection = 'mysql';
    protected $table = 'purchase_order_items';
    protected $fillable = [
        'purchase_order_id',
        'product_variant_id',
        'unit_id',
        'unit_name',
        'unit_multiplier',
        'qty_order',
        'qty_received',
        'price',
        'subtotal',
    ];

    protected $casts = [
        'unit_multiplier' => 'integer',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function productUnit()
    {
        return $this->belongsTo(ProductUnit::class, 'unit_id');
    }

    /* ================== HELPER ================== */

    public function isFullyReceived()
    {
        return $this->qty_received >= $this->qty_order;
    }
}
