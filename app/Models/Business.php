<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    protected $fillable = ['name', 'code'];

    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function members()
    {
        return $this->hasMany(Member::class);
    }
}
