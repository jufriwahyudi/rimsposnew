<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeragamDistribusi extends Model
{
    protected $connection = 'nsedb';
    protected $table = 'seragam_distribusi';
    protected $fillable = [
        'id_biodata',
        'id_seragam',
        'id_product_variant',
        'qty',
        'status',
        'scanned_at',
        'scanned_by',
        'sale_item_id',
        'isAdditional'
    ];

    public function seragam()
    {
        return $this->belongsTo(MasterSeragam::class, 'id_seragam');
    }
    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'id_product_variant', 'id');
    }
}
