<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IngredientUnitConversion extends Model
{
    protected $connection = 'mysql';
    protected $table = 'ingredient_unit_conversions';

    protected $fillable = [
        'ingredient_id',
        'purchase_unit_id',
        'code',
        'conversion_factor',
    ];

    public $timestamps = false;

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class, 'ingredient_id');
    }

    public function purchaseUnit()
    {
        return $this->belongsTo(Unit::class, 'purchase_unit_id');
    }
}
