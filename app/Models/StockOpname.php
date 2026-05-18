<?php

namespace App\Models;

use App\Models\Traits\HasStore;
use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    use HasStore;

    protected $fillable = [
        'store_id',
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

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

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
