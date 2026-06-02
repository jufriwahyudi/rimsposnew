<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyAudit extends Model
{
    protected $fillable = [
        'audit_date',
        'store_id',
        'opening_stock_value',
        'closing_stock_value',
        'total_sales',
        'total_purchase',
        'total_cash_in',
        'total_cash_out',
        'stock_difference_value',
        'cash_difference',
        'status',
        'created_by'
    ];

    protected $casts = [
        'audit_date' => 'date'
    ];

    public function details()
    {
        return $this->hasMany(DailyAuditDetail::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
