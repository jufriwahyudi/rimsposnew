<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    protected $connection = 'mysql';
    protected $table = 'ingredients';

    protected $fillable = [
        'store_id',
        'sku',
        'name',
        'base_unit_id',
        'cost_per_unit',
    ];

    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function conversions()
    {
        return $this->hasMany(IngredientUnitConversion::class, 'ingredient_id');
    }

    public function stocks()
    {
        return $this->hasMany(InventoryStock::class, 'ingredient_id');
    }
}
