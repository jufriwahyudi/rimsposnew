<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItemFnBDetail extends Model
{
    protected $connection = 'mysql';
    protected $table = 'sale_item_fnb_details';

    protected $fillable = [
        'sale_item_id',
        'cost_price',
        'commission_type',
        'commission_rate',
        'commission_amount',
        'kitchen_printed_qty',
        'kds_status',
    ];

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class, 'sale_item_id');
    }
}
