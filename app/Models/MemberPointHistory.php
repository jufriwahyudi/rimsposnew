<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberPointHistory extends Model
{
    protected $fillable = [
        'member_id',
        'store_id',
        'sale_id',
        'mutation_type',
        'points',
        'balance_after',
        'notes',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
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
