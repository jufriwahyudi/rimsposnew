<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleConcoctionItem extends Model
{
    protected $connection = 'mysql';
    protected $table = 'sale_concoction_items';

    protected $fillable = [
        'sale_item_id',
        'product_variant_id',
        'product_name',
        'dosage_per_package',
        'quantity',
        'unit_name',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'quantity'   => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal'   => 'decimal:2',
    ];

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class, 'sale_item_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
