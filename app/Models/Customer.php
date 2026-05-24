<?php

namespace App\Models;

use App\Models\Traits\HasStore;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasStore;

    protected $fillable = [
        'store_id',
        'name',
        'phone',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class, 'customer_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
