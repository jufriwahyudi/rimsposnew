<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JadwalDistribusi extends Model
{

    protected $table = 'jadwal_distribusi';
    protected $fillable = [
        'tanggal',
        'kuota_harian',
        'keterangan',
        'id_divisi',
        'is_active',
    ];

    public function sesi()
    {
        return $this->hasMany(JadwalSesi::class, 'jadwal_id');
    }

    public function peserta()
    {
        return $this->hasMany(JadwalSeragamSiswa::class, 'jadwal_id');
    }

    public function divisi()
    {
        return $this->belongsTo(DivisiFinance::class, 'id_divisi');
    }
}
