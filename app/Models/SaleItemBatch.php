<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItemBatch extends Model
{
    protected $connection = 'mysql';
    protected $table = 'sale_item_batches';
    protected $fillable = [
        'sale_item_id',
        'stock_batch_id',
        'batch_number',
        'expired_date',
        'qty',
        'cost_price',
        'sell_price',
    ];

    protected $casts = [
        'expired_date' => 'date',
    ];

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class);
    }
    public function stockBatch()
    {
        return $this->belongsTo(StockBatch::class, 'stock_batch_id');
    }
}
