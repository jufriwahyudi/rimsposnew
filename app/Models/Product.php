<?php

namespace App\Models;

use App\Models\Traits\HasStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    use HasStore;

    protected $connection = 'mysql';
    protected $table = 'products';

    protected $fillable = [
        'store_id',
        'tenant_id',
        'kode_produk',
        'nama_produk',
        'deskripsi',
        'image',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        // If it's already a full URL, return it
        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }

        $storageUrl = \Storage::url($this->image);

        // If the URL is relative (starts with /), prepend the scheme and host of the current request if available
        if (str_starts_with($storageUrl, '/')) {
            return request() 
                ? request()->schemeAndHttpHost() . $storageUrl 
                : url($storageUrl);
        }

        // If the URL is absolute but points to localhost, and the current request came from another host (like mobile IP)
        if (request() && str_contains($storageUrl, 'localhost')) {
            return str_replace('http://localhost', request()->schemeAndHttpHost(), $storageUrl);
        }

        return $storageUrl;
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function batches()
    {
        return $this->hasManyThrough(
            StockBatch::class,
            ProductVariant::class,
            'product_id',           // FK di variants
            'product_variant_id',   // FK di batches
            'id',
            'id'
        );
    }

    /* ===== TOTAL STOK PRODUK ===== */
    public function scopeWithStock($q)
    {
        return $q->withSum('batches as total_stock', 'qty_sisa');
    }

    public function scopeWithStockWarehouse($q)
    {
        return $q->withSum([
            'batches as stock_warehouse' => fn($b) =>
            $b->where('posisi', 'warehouse')
                ->where('qty_sisa', '>', '0')
        ], 'qty_sisa');
    }

    public function scopeWithStockStore($q)
    {
        return $q->withSum([
            'batches as stock_store' => fn($b) =>
            $b->where('posisi', 'store')
                ->where('qty_sisa', '>', '0')
        ], 'qty_sisa');
    }
}
