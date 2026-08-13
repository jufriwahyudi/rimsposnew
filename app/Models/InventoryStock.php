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
        'qty_original',
        'quantity',
        'cost_per_unit',
        'tanggal',
        'reference_id',
        'notes',
        'parent_id',
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

    public function parent()
    {
        return $this->belongsTo(InventoryStock::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(InventoryStock::class, 'parent_id');
    }
}
