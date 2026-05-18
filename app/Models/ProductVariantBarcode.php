<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantBarcode extends Model
{
    protected $connection = 'mysql';
    protected $table = 'product_variant_barcodes';
    protected $fillable = [
        'product_variant_id',
        'barcode',
        'is_active'
    ];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
