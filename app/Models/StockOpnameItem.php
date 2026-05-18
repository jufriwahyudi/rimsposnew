<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpnameItem extends Model
{
    protected $fillable = [
        'stock_opname_id',
        'product_variant_id',
        'system_qty',
        'physical_qty',
        'difference_qty',
        'harga_beli',
        'status',
    ];

    public function opname()
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
