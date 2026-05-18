<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpnamePeriod extends Model
{
    protected $fillable = [
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
}
