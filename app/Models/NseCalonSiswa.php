<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NseCalonSiswa extends Model
{
    protected $connection = 'nsedb';
    protected $table = 'biodatadiri';
    protected $primaryKey = 'id_biodatadiri';

    // daftar field yang tidak boleh diisi secara massal
    protected $guarded = ['id_biodatadiri'];

    public function divisi()
    {
        return $this->belongsTo(Divisi::class, 'id_divisi');
    }

    public function observasi()
    {
        return $this->hasOne(NseObservasi::class, 'id_biodata', 'id_biodatadiri');
    }

    public function daftarUlang()
    {
        return $this->hasOne(NseDaftarUlang::class, 'id_biodata', 'id_biodatadiri');
    }
    public function jadwalDistribusi()
    {
        return $this->hasOne(JadwalSeragamSiswa::class, 'id_biodata', 'id_biodatadiri')->where('status', '!=', 'batal');
    }
}
