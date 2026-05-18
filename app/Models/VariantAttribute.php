<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariantAttribute extends Model
{
    protected $connection = 'mysql';
    protected $table = 'variant_attributes';
    public $timestamps = false;

    protected $fillable = [
        'product_variant_id',
        'attribute_id',
        'attribute_value_id',
    ];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
    public function attribute()
    {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }
    public function value()
    {
        return $this->belongsTo(AttributeValue::class, 'attribute_value_id');
    }
}
