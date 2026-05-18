<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $connection = 'mysql';
    protected $table = 'sale_items';
    protected $fillable = [
        'sale_id',
        'product_id',
        'product_variant_id',
        'sku',
        'product_name',
        'price',
        'qty',
        'discount_amount',
        'subtotal',
        'status',
        'ref_sale_item_id',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function batches()
    {
        return $this->hasMany(SaleItemBatch::class, 'sale_item_id');
    }
}
