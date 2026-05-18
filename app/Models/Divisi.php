<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Divisi extends Model
{
    protected $connection = 'nsedb';
    protected $table = 'master_divisi';

    public function divisifinance()
    {
        return $this->belongsTo(DivisiFinance::class, 'finance_divisi_id', 'Id');
    }
}
