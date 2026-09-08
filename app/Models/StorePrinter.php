<?php

namespace App\Models;

use App\Models\Traits\HasStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StorePrinter extends Model
{
    use HasFactory, HasStore, SoftDeletes;

    protected $table = 'store_printers';

    protected $fillable = [
        'store_id',
        'name',
        'code',
        'connection_type',
        'ip_address',
        'port',
        'mac_address',
        'paper_size',
        'is_active',
    ];

    protected $casts = [
        'port'      => 'integer',
        'is_active' => 'boolean',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function categories()
    {
        return $this->hasMany(ProductCategory::class, 'printer_id');
    }
}
