<?php

namespace App\Models;

use App\Models\Traits\HasStore;
use Illuminate\Database\Eloquent\Model;

class AttributeValue extends Model
{
    use HasStore;

    protected $connection = 'mysql';
    protected $table = 'attribute_values';

    protected $fillable = [
        'attribute_id',
        'store_id',
        'kode',
        'nama',
        'urutan'
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
