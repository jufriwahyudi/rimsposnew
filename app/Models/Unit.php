<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $connection = 'mysql';
    protected $table = 'units';

    protected $fillable = [
        'name',
        'symbol',
    ];

    public $timestamps = false;
}
