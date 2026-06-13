<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RewardItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
        'points_required',
        'reward_type',
        'value',
        'max_discount',
        'stock',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'value' => 'decimal:2',
        'max_discount' => 'decimal:2',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
