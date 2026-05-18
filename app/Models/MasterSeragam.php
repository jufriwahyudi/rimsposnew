<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterSeragam extends Model
{
    protected $connection = 'nsedb';
    protected $table = 'master_seragam';
    protected $fillable = [
        'id_divisi',
        'nama',
        'jk',
        'hari',
        'jenis',
        'img',
        'pcs',
        'pilih',
        'wajib',
    ];

    public function ukuranSeragam()
    {
        return $this->hasOne(UkuranSeragam::class, 'id_seragam');
    }
    public function divisi()
    {
        return $this->belongsTo(Divisi::class, 'id_divisi');
    }
    public function getJenisLabelAttribute()
    {
        $jenisMap = [
            'baju' => 'Baju',
            'celana' => 'Celana',
            'lengkap' => 'Lengkap',
            'jilbab' => 'Jilbab',
            'dasi' => 'Dasi',
            'stiker' => 'Stiker',
            'godybag' => 'Godybag',
        ];
        return $jenisMap[$this->jenis] ?? 'Unknown';
    }
    public function getHariLabelAttribute()
    {
        // 1 = Senin, 2 = Selasa, 3 = Rabu, 4 = Kamis, 5 = Jumat, 6 = Sabtu, 7 = Minggu
        $hariMap = [
            0 => 'Umum',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
            8 => 'Jam Pelajaran',
        ];
        return $hariMap[$this->hari] ?? 'Unknown';
    }
    //label attribute untuk jenis kelamin
    public function getJkLabelAttribute()
    {
        if ($this->jk === 'L') {
            return 'Laki-laki';
        } elseif ($this->jk === 'P') {
            return 'Perempuan';
        } else {
            return 'Umum';
        }
    }
}
