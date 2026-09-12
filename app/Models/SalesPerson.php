<?php

namespace App\Models;

use App\Models\Traits\HasStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesPerson extends Model
{
    use HasFactory, HasStore;

    protected $table = 'sales_persons';

    protected $fillable = [
        'store_id',
        'code',
        'name',
        'phone',
        'commission_rate',
        'user_id',
        'is_active',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'commission_rate' => 'float',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class, 'sales_person_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
