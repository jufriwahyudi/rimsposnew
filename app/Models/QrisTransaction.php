<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QrisTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'sale_id',
        'order_id',
        'gross_amount',
        'qr_string',
        'qr_url',
        'transaction_id',
        'transaction_status',
        'settlement_time',
        'raw_response',
        'raw_notification',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'settlement_time' => 'datetime',
        'raw_response' => 'array',
        'raw_notification' => 'array',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function isPaid(): bool
    {
        return in_array($this->transaction_status, ['settlement', 'capture']);
    }
}
