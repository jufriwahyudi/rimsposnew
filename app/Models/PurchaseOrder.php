<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    protected $connection = 'mysql';
    protected $table = 'purchase_orders';
    protected $fillable = [
        'po_number',
        'vendor_id',
        'divisi_id',
        'notes',
        'request_date',
        'expected_date',
        'status',
        'subtotal',
        'tax_total',
        'discount_total',
        'grand_total',
        'requested_by',
        'approved_by',
        'approved_at',
        'note',
        'nojurnal',
    ];

    protected $casts = [
        'request_date' => 'date',
        'expected_date' => 'date',
        'approved_at' => 'datetime',
    ];
    /* ================== RELATIONSHIPS ================== */

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'Id');
    }
    public function divisi()
    {
        return $this->belongsTo(DivisiFinance::class, 'divisi_id', 'Id');
    }
    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
    public function goodsReceipts()
    {
        return $this->hasMany(GoodsReceipt::class);
    }
    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'nojurnal');
    }

    /* ============= HELPER FUNCTIONS ============= */
    public function isApproved()
    {
        return $this->status === 'APPROVED';
    }
    public function isCompleted()
    {
        return in_array($this->status, ['RECEIVED', 'CLOSED']);
    }
}
