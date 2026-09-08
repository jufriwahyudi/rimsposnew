<?php

namespace App\Models;

use App\Models\Traits\HasStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCategory extends Model
{
    use HasStore, SoftDeletes;

    protected $table = 'product_categories';

    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'icon',
        'printer_id',
        'station',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'printer_id' => 'integer',
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function printer()
    {
        return $this->belongsTo(StorePrinter::class, 'printer_id');
    }
}
