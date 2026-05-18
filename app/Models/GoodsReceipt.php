<?php

namespace App\Models;

use App\Models\Traits\HasStore;
use Illuminate\Database\Eloquent\Model;

class GoodsReceipt extends Model
{
    use HasStore;
    protected $connection = 'mysql';
    protected $table = 'goods_receipts';
    protected $fillable = [
        'store_id',
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

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
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
