<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UkuranSeragam extends Model
{
    protected $connection = 'nsedb';
    protected $table = 'ukuran_seragam_nse';
    public $timestamps = false;
    protected $fillable = [
        'id_seragam',
        'id_produk_koperasi',
        'size',
        'aktif'
    ];

    public function seragam()
    {
        return $this->belongsTo(MasterSeragam::class, 'id_seragam');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'id_produk_koperasi', 'id');
    }
}
