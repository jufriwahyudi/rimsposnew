<?php

namespace App\Models;

use App\Models\Traits\HasStore;
use Illuminate\Database\Eloquent\Model;

class StockOpnamePeriod extends Model
{
    use HasStore;
    protected $fillable = [
        'store_id',
        'code',
        'period_date',
        'description',
        'status',
        'created_by',
        'closed_by',
        'closed_at'
    ];

    public function opnames()
    {
        return $this->hasMany(StockOpname::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
