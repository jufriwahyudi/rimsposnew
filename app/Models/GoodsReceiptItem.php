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
        'qty_received',
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
