<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jurnal extends Model
{
    protected $connection = 'financedb';
    protected $table = 'jurnal';
    protected $fillable = [
        'tanggal',
        'no_voucer',
        'voucer',
        'referensi',
        'uraian',
        'kode',
        'amount',
        'post',
        'jns_trx',
        'user_input',
        'tgl_input',
        'supplier',
        'unit',
        'stts',
        'ref',
        'divisi'
    ];

    public $timestamps = false;

    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'ref', 'Id');
    }
}
