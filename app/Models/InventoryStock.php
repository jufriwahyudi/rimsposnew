<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryStock extends Model
{
    protected $connection = 'mysql';
    protected $table = 'inventory_stocks';

    protected $fillable = [
        'ingredient_id',
        'location_type',
        'location_id',
        'quantity',
    ];

    public $timestamps = false;

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class, 'ingredient_id');
    }

    public function location()
    {
        return $this->belongsTo(Store::class, 'location_id');
    }
}
