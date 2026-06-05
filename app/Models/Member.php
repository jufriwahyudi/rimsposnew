<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $fillable = [
        'business_id',
        'name',
        'phone',
        'email',
        'total_points',
        'birth_date',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'birth_date' => 'date',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function pointHistories()
    {
        return $this->hasMany(MemberPointHistory::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
