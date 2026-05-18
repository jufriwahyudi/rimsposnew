<?php

namespace App\Models;

use App\Models\Traits\HasStore;
use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    use HasStore;

    protected $connection = 'mysql';
    protected $table = 'attributes';

    protected $fillable = ['store_id', 'kode', 'nama', 'urutan'];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function values()
    {
        return $this->hasMany(AttributeValue::class);
    }
}
