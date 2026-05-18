<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NseObservasi extends Model
{
    protected $connection = 'nsedb';
    protected $table = 'tagihan_observasi';
    protected $primaryKey = 'id';
}
