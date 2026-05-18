<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $connection = 'financedb';
    protected $table = 'voucher';
    protected $fillable = [
        'tanggal',
        'no_voucer',
        'voucer',
        'referensi',
        'uraian',
        'jlh_debet',
        'jlh_kredit',
        'jlh_bayar',
        'jns_trx',
        'user_input',
        'supplier',
        'unit',
        'stts',
        'dtinput',
        'jns',
        'ref_tagihan',
        'divisi',
    ];
    public $timestamps = false;

    public function jurnalEntries()
    {
        return $this->hasMany(Jurnal::class, 'ref', 'Id');
    }
}
