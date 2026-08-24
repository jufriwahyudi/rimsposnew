<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceOrderItem extends Model
{
    protected $connection = 'mysql';
    protected $table = 'service_order_items';

    protected $fillable = [
        'service_order_id',
        'item_type',
        'product_id',
        'product_variant_id',
        'name',
        'price',
        'qty',
        'discount_amount',
        'subtotal',
        'staff_user_id',
        'commission_type',
        'commission_rate',
        'commission_amount',
        'notes',
    ];

    protected $casts = [
        'price'             => 'decimal:2',
        'discount_amount'   => 'decimal:2',
        'subtotal'          => 'decimal:2',
        'commission_rate'   => 'decimal:2',
        'commission_amount' => 'decimal:2',
    ];

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class, 'service_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_user_id');
    }
}
