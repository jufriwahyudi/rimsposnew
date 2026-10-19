<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyConsignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'consignor_id',
        'tanggal',
        'status',
        'total_titip',
        'total_sisa',
        'total_laku',
        'total_payout',
        'total_kantin_margin',
        'settled_at',
        'settled_by_user_id',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'settled_at' => 'datetime',
        'total_titip' => 'integer',
        'total_sisa' => 'integer',
        'total_laku' => 'integer',
        'total_payout' => 'float',
        'total_kantin_margin' => 'float',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function consignor()
    {
        return $this->belongsTo(Consignor::class);
    }

    public function items()
    {
        return $this->hasMany(DailyConsignmentItem::class);
    }

    public function settlement()
    {
        return $this->hasOne(ConsignmentSettlement::class);
    }

    public function settledBy()
    {
        return $this->belongsTo(User::class, 'settled_by_user_id');
    }
}
