<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsignorItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'consignor_id',
        'product_variant_id',
        'nama_item',
        'kode_display',
        'harga_setor',
        'harga_jual',
        'is_active',
    ];

    protected $casts = [
        'harga_setor' => 'float',
        'harga_jual' => 'float',
        'is_active' => 'boolean',
    ];

    public function consignor()
    {
        return $this->belongsTo(Consignor::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
