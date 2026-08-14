<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'user_id',
        'closed_by',
        'opened_at',
        'closed_at',
        'opening_cash',
        'total_cash_sales',
        'total_non_cash_sales',
        'total_refund_cash',
        'total_cash_in',
        'total_cash_out',
        'expected_cash',
        'actual_cash',
        'cash_difference',
        'denominations',
        'notes',
        'status',
    ];

    protected $casts = [
        'opened_at'              => 'datetime',
        'closed_at'              => 'datetime',
        'opening_cash'           => 'float',
        'total_cash_sales'       => 'float',
        'total_non_cash_sales'   => 'float',
        'total_refund_cash'      => 'float',
        'total_cash_in'          => 'float',
        'total_cash_out'         => 'float',
        'expected_cash'          => 'float',
        'actual_cash'            => 'float',
        'cash_difference'        => 'float',
        'denominations'          => 'array',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function cashTransactions()
    {
        return $this->hasMany(CashTransaction::class);
    }
}
