<?php

namespace App\Models;

use App\Models\Traits\HasStore;
use Illuminate\Database\Eloquent\Model;

class CashTransaction extends Model
{
    use HasStore;
    protected $connection = 'mysql';
    protected $table = 'cash_transactions';
    protected $fillable = [
        'store_id',
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

    public function rekening()
    {
        return $this->belongsTo(Rekening::class, 'account_code', 'id');
    }
}
