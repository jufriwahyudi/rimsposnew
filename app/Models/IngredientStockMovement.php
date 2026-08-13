<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IngredientStockMovement extends Model
{
    protected $connection = 'mysql';
    protected $table = 'ingredient_stock_movements';

    protected $fillable = [
        'ingredient_id',
        'location_type',
        'location_id',
        'type',
        'quantity_change',
        'reference_id',
        'notes',
        'inventory_stock_id',
        'tanggal',
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

    public function stock()
    {
        return $this->belongsTo(InventoryStock::class, 'inventory_stock_id');
    }
}
