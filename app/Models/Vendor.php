<?php

namespace App\Models;

use App\Models\Traits\HasStore;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasStore;
    protected $table = 'vendors';

    protected $fillable = [
        'store_id',
        'kode_vendor',
        'nama_vendor',
        'telepon',
        'alamat',
    ];
}
