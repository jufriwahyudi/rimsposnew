<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesExchangeItem extends Model
{
    protected $connection = 'mysql';
    protected $table = 'sales_exchange_items';
    protected $fillable = [
        'sales_exchange_id',
        'old_product_variant_id',
        'old_qty',
        'new_product_variant_id',
        'new_qty',
    ];

    public function salesExchange()
    {
        return $this->belongsTo(SalesExchange::class, 'sales_exchange_id');
    }

    public function oldProductVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'old_product_variant_id');
    }

    public function newProductVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'new_product_variant_id');
    }
}
