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
        'qty_order',
        'qty_received',
        'price',
        'subtotal',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /* ================== HELPER ================== */

    public function isFullyReceived()
    {
        return $this->qty_received >= $this->qty_order;
    }
}
