<?php

namespace App\Models;

use App\Models\Traits\HasStore;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasStore;
    protected $fillable = [
        'store_id',
        'code',
        'effective_date',
        'posisi',
        'reason_type',
        'notes',
        'status',
        'nojurnal',
        'stock_opname_id',
        'created_by',
        'posted_at'
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function items()
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function opname()
    {
        return $this->belongsTo(StockOpname::class, 'stock_opname_id');
    }
}
