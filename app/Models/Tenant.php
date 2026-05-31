<?php

namespace App\Models;

use App\Models\Traits\HasStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;
    use HasStore;

    protected $connection = 'mysql';
    protected $table = 'tenants';

    protected $fillable = [
        'store_id',
        'kode_tenant',
        'nama_tenant',
        'telepon',
        'alamat',
        'commission_rate',
        'stts',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'tenant_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'tenant_id');
    }
}
