<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsReceipt extends Model
{
    protected $connection = 'mysql';
    protected $table = 'goods_receipts';
    protected $fillable = [
        'purchase_order_id',
        'receipt_number',
        'receipt_date',
        'received_by',
        'note',
        'nojurnal',
    ];

    protected $casts = [
        'receipt_date' => 'date',
    ];

    /* ================== RELATIONSHIP ================== */

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items()
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'nojurnal');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'ref_id')
            ->where('ref_type', 'GoodsReceipt');
    }
}
