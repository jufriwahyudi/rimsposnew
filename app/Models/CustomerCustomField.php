<?php

namespace App\Models;

use App\Models\Traits\HasStore;
use Illuminate\Database\Eloquent\Model;

class CustomerCustomField extends Model
{
    use HasStore;

    protected $table = 'customer_custom_fields';

    protected $fillable = [
        'store_id',
        'name',
        'label',
        'type',
        'options',
        'is_required',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];
}
