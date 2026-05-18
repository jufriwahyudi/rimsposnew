<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NseDaftarUlang extends Model
{
    protected $connection = 'nsedb';
    protected $table = 'tagihan_daftar_ulang';
    protected $primaryKey = 'id';

    // daftar field yang tidak boleh diisi secara massal
    protected $guarded = ['id'];
}
