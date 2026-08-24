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
        'category_id',
        'kode_produk',
        'nama_produk',
        'deskripsi',
        'image',
        'product_type',
        'default_commission_type',
        'default_commission_rate',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        // If it's an external full URL
        if (filter_var($this->image, FILTER_VALIDATE_URL) && !str_contains($this->image, '/storage/')) {
            return $this->image;
        }

        $path = ltrim($this->image, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, 8);
        }

        // Use current incoming HTTP request host so mobile IPs / domain always match
        if (request() && request()->schemeAndHttpHost()) {
            return request()->schemeAndHttpHost() . '/storage/' . $path;
        }

        return url('storage/' . $path);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
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

    public function recipes()
    {
        return $this->hasMany(ProductRecipe::class, 'product_id')->whereNull('product_variant_id');
    }

    public function allRecipes()
    {
        return $this->hasMany(ProductRecipe::class, 'product_id');
    }
}
