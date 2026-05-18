<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JadwalSeragamSiswa extends Model
{
    protected $connection = 'mysql';
    protected $table = 'jadwal_seragam_siswa';
    protected $fillable = [
        'jadwal_id',
        'sesi_id',
        'id_biodata',
        'status',
    ];

    public function jadwal()
    {
        return $this->belongsTo(JadwalDistribusi::class, 'jadwal_id');
    }

    public function sesi()
    {
        return $this->belongsTo(JadwalSesi::class, 'sesi_id');
    }

    public function biodata()
    {
        return $this->belongsTo(NseCalonSiswa::class, 'id_biodata', 'id_biodatadiri');
    }
}
