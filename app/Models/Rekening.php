<?php

namespace App\Models;

use App\Models\Traits\HasStore;
use Illuminate\Database\Eloquent\Model;

class Rekening extends Model
{
    use HasStore;
    protected $fillable = [
        'store_id',
        'no_rek',
        'nama_rek',
        'bank_rek'
    ];

    public function cashTransactions()
    {
        return $this->hasMany(CashTransaction::class, 'account_code', 'no_rek');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
