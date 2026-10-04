<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StorePaymentGateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'gateway',
        'is_active',
        'is_production',
        'merchant_id',
        'client_key',
        'server_key',
        'public_key',
        'secret_key',
        'additional_config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_production' => 'boolean',
        'additional_config' => 'array',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
