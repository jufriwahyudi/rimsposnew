<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyConsignmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_consignment_id',
        'consignor_item_id',
        'product_variant_id',
        'qty_titip',
        'qty_sisa',
        'qty_laku',
        'harga_setor',
        'harga_jual',
        'subtotal_payout',
        'subtotal_kantin_margin',
    ];

    protected $casts = [
        'qty_titip' => 'integer',
        'qty_sisa' => 'integer',
        'qty_laku' => 'integer',
        'harga_setor' => 'float',
        'harga_jual' => 'float',
        'subtotal_payout' => 'float',
        'subtotal_kantin_margin' => 'float',
    ];

    public function dailyConsignment()
    {
        return $this->belongsTo(DailyConsignment::class);
    }

    public function consignorItem()
    {
        return $this->belongsTo(ConsignorItem::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
