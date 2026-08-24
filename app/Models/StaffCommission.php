<?php

namespace App\Models;

use App\Models\Traits\HasStore;
use Illuminate\Database\Eloquent\Model;

class StaffCommission extends Model
{
    use HasStore;

    protected $connection = 'mysql';
    protected $table = 'staff_commissions';

    protected $fillable = [
        'store_id',
        'staff_user_id',
        'source_type',
        'service_order_id',
        'sale_id',
        'sale_item_id',
        'item_name',
        'item_price',
        'commission_type',
        'commission_rate',
        'commission_amount',
        'status',
        'expense_id',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'item_price'        => 'decimal:2',
        'commission_rate'   => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'paid_at'           => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_user_id');
    }

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class, 'service_order_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class, 'sale_item_id');
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class, 'expense_id');
    }
}
