<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesExchange extends Model
{
    protected $connection = 'mysql';
    protected $table = 'sales_exchanges';
    protected $fillable = [
        'sale_id',
        'exchange_date',
        'reason',
        'user_id',
    ];

    public function items()
    {
        return $this->hasMany(SalesExchangeItem::class, 'sales_exchange_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
