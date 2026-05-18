<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $connection = 'mysql';
    protected $table = 'attributes';

    protected $fillable = ['kode', 'nama', 'urutan'];

    public function values()
    {
        return $this->hasMany(AttributeValue::class);
    }
}
