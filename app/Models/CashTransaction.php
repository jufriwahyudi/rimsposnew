<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashTransaction extends Model
{
    protected $connection = 'mysql';
    protected $table = 'cash_transactions';
    protected $fillable = [
        'ref_type',
        'ref_id',
        'transaction_type',
        'payment_method',
        'account_code',
        'amount',
        'direction',
        'transaction_date',
        'user_id',
        'notes',
        'nojurnal'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
