<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeValue extends Model
{
    protected $connection = 'mysql';
    protected $table = 'attribute_values';

    protected $fillable = [
        'attribute_id',
        'kode',
        'nama',
        'urutan'
    ];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
