<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductUnit extends Model
{
    protected $connection = 'mysql';
    protected $table = 'product_units';

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'name',
        'multiplier',
        'price',
        'barcode',
        'is_default_purchase',
        'is_active',
    ];

    protected $casts = [
        'multiplier'          => 'integer',
        'price'               => 'float',
        'is_default_purchase' => 'boolean',
        'is_active'           => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
