<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsReceiptItem extends Model
{
    protected $connection = 'mysql';
    protected $table = 'goods_receipt_items';
    protected $fillable = [
        'goods_receipt_id',
        'purchase_order_item_id',
        'batch_number',
        'expired_date',
        'qty_received',
        'unit_name',
        'unit_multiplier',
        'base_qty_received',
    ];

    protected $casts = [
        'expired_date' => 'date',
        'unit_multiplier' => 'integer',
        'base_qty_received' => 'decimal:2',
    ];

    public function goodsReceipt()
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }
}
