<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberRedemption extends Model
{
    protected $fillable = [
        'member_id',
        'reward_item_id',
        'store_id',
        'points_spent',
        'voucher_code',
        'is_used',
        'used_at',
        'sale_id',
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'used_at' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function rewardItem()
    {
        return $this->belongsTo(RewardItem::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
