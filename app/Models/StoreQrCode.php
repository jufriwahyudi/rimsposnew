<?php

namespace App\Models;

use App\Models\Traits\HasStore;
use Illuminate\Database\Eloquent\Model;

class StoreQrCode extends Model
{
    use HasStore;

    protected $table = 'store_qr_codes';

    protected $fillable = [
        'store_id',
        'table_name',
        'url',
        'hash',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
