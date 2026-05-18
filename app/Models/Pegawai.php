<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pegawai extends Model
{
    use HasFactory;
    protected $connection = 'casandradb';
    protected $table = 'pegawai';

    public function jabatan()
    {
        return $this->belongsTo(MasterJabatan::class, 'id_jabatan', 'id');
    }

    public function divisi()
    {
        return $this->belongsTo(DivisiFinance::class, 'id_divisi', 'Id');
    }
}
