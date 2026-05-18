<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    protected $fillable = [
        'code',
        'stock_opname_period_id',
        'posisi',
        'input_date',
        'status',
        'notes',
        'created_by',
        'approved_by',
        'approved_at'
    ];

    public function period()
    {
        return $this->belongsTo(StockOpnamePeriod::class, 'stock_opname_period_id');
    }

    public function items()
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    public function adjustment()
    {
        return $this->hasOne(StockAdjustment::class);
    }
}
