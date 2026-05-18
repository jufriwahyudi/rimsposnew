<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JadwalSesi extends Model
{
    protected $table = 'jadwal_sesi';
    protected $fillable = [
        'jadwal_id',
        'jam_mulai',
        'jam_selesai',
        'kuota_sesi',
    ];

    public function jadwal()
    {
        return $this->belongsTo(JadwalDistribusi::class, 'jadwal_id');
    }

    public function peserta()
    {
        return $this->hasMany(JadwalSeragamSiswa::class, 'sesi_id');
    }
}
