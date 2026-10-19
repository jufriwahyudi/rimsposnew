<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsignmentSettlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'daily_consignment_id',
        'consignor_id',
        'cash_register_id',
        'user_id',
        'settlement_number',
        'settlement_date',
        'total_amount',
        'payment_method',
        'total_pos_qty',
        'total_pos_amount',
        'variance_qty',
        'variance_amount',
        'audit_status',
        'catatan',
    ];

    protected $casts = [
        'settlement_date'  => 'date',
        'total_amount'     => 'float',
        'total_pos_qty'    => 'integer',
        'total_pos_amount' => 'float',
        'variance_qty'     => 'integer',
        'variance_amount'  => 'float',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function dailyConsignment()
    {
        return $this->belongsTo(DailyConsignment::class);
    }

    public function consignor()
    {
        return $this->belongsTo(Consignor::class);
    }

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
