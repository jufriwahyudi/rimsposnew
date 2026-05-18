<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $connection = 'financedb';
    protected $table = 'master_supplier';
    protected $primaryKey = 'Id';

    protected $fillable = [
        'nama',
        'kontak',
        'alamat',
        'stts',
    ];
}
