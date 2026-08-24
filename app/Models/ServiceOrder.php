<?php

namespace App\Models;

use App\Models\Traits\HasStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceOrder extends Model
{
    use SoftDeletes;
    use HasStore;

    protected $connection = 'mysql';
    protected $table = 'service_orders';

    protected $fillable = [
        'store_id',
        'order_number',
        'customer_id',
        'customer_name',
        'customer_phone',
        'target_name',
        'target_identifier',
        'target_attributes',
        'complaint_notes',
        'diagnosis_notes',
        'assigned_staff_id',
        'estimated_cost',
        'estimated_completed_at',
        'warranty_days',
        'status',
        'payment_status',
        'down_payment',
        'sale_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'target_attributes'      => 'array',
        'estimated_cost'         => 'decimal:2',
        'down_payment'           => 'decimal:2',
        'estimated_completed_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedStaff()
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function items()
    {
        return $this->hasMany(ServiceOrderItem::class, 'service_order_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function commissions()
    {
        return $this->hasMany(StaffCommission::class, 'service_order_id');
    }

    public function getTotalCostAttribute()
    {
        return $this->items->sum('subtotal');
    }

    public function getRemainingPaymentAttribute()
    {
        return max(0, $this->total_cost - (float)$this->down_payment);
    }
}
