<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountItem extends Model
{
    use HasFactory;

    protected $table = 'discount_items';

    protected $fillable = [
        'discount_id',
        'product_id',
        'product_variant_id',
    ];

    public function discount()
    {
        return $this->belongsTo(Discount::class, 'discount_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
