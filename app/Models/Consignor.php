<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Consignor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'kode_penitip',
        'nama_penitip',
        'telepon',
        'alamat',
        'nama_bank',
        'nomor_rekening',
        'tipe',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function items()
    {
        return $this->hasMany(ConsignorItem::class)->where('is_active', true);
    }

    public function allItems()
    {
        return $this->hasMany(ConsignorItem::class);
    }

    public function dailyConsignments()
    {
        return $this->hasMany(DailyConsignment::class);
    }

    public function settlements()
    {
        return $this->hasMany(ConsignmentSettlement::class);
    }
}
