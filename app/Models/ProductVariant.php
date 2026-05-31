<?php

namespace App\Models;

use App\Models\Traits\HasStore;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasStore;
    protected $connection = 'mysql';
    protected $table = 'product_variants';
    protected $fillable = [
        'store_id',
        'product_id',
        'variant_name',
        'sku',
        'barcode',
        'harga_jual',
        'is_active',
        'track_stock',
        'cost_price_manual',
        'commission_type',
        'commission_rate',
    ];

    protected $appends = [
        'stok_warehouse',
        'stok_store',
        'stok_total',
        'variant_label'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variantAttributes()
    {
        return $this->hasMany(VariantAttribute::class)
            ->join('attributes', 'variant_attributes.attribute_id', '=', 'attributes.id')
            ->orderBy('attributes.urutan')
            ->select('variant_attributes.*');
    }
    public function variantAttr()
    {
        return $this->hasMany(VariantAttribute::class, 'product_variant_id')
            ->join('attributes', 'variant_attributes.attribute_id', '=', 'attributes.id')
            ->join('attribute_values', 'variant_attributes.attribute_value_id', '=', 'attribute_values.id')
            ->orderBy('attributes.urutan')
            ->select(
                'variant_attributes.*',
                'attributes.nama as attribute_nama',
                'attribute_values.nama as value_nama',
                'attributes.urutan'
            );
    }

    public function batches()
    {
        return $this->hasMany(StockBatch::class);
    }

    public function barcodes()
    {
        return $this->hasMany(ProductVariantBarcode::class);
    }

    public function barcodeActive()
    {
        return $this->hasOne(ProductVariantBarcode::class)->where('is_active', 'Y');
    }

    /* 
    cara cepat untuk mendapatkan stok di gudang atau toko 

    List varian + stok siap jual
    ProductVariant::withStockStore()
        ->where('is_active', 'Y')
        ->get();

    Ambil FIFO batch untuk jual
    $batch = StockBatch::store()
        ->available()
        ->where('product_variant_id', $variantId)
        ->orderBy('tanggal_masuk')
        ->first();
    */
    public function scopeWarehouse($q)
    {
        return $q->where('posisi', 'warehouse');
    }

    public function scopeStore($q)
    {
        return $q->where('posisi', 'store');
    }

    public function scopeAvailable($q)
    {
        return $q->where('qty_sisa', '>', '0');
    }


    /* ===== TOTAL STOK VARIAN ===== */
    public function getStokWarehouseAttribute()
    {
        return $this->batches()
            ->where('posisi', 'warehouse')
            ->sum('qty_sisa');
    }

    public function getStokStoreAttribute()
    {
        if (!$this->track_stock) {
            return 999999;
        }
        return $this->batches()
            ->where('posisi', 'store')
            ->sum('qty_sisa');
    }

    public function calculateCommission($sellPrice)
    {
        if ($this->commission_type === 'percentage') {
            return $sellPrice * ($this->commission_rate / 100);
        } elseif ($this->commission_type === 'nominal') {
            return $this->commission_rate;
        } else {
            // default: global
            $globalRate = $this->product->tenant->commission_rate ?? 0;
            return $sellPrice * ($globalRate / 100);
        }
    }

    public function getStokTotalAttribute()
    {
        return $this->stok_warehouse + $this->stok_store;
    }

    public function getVariantLabelAttribute()
    {
        if (!empty($this->variant_name)) {
            return $this->variant_name;
        }
        return $this->variantAttributes
            ->map(fn($va) => optional($va->value)->nama)
            ->filter()
            ->implode(' · ');
    }
    public function getVariasiLabelAttribute()
    {
        return $this->variantAttr
            ->map(fn($v) => "{$v->attribute_nama}: {$v->value_nama}")
            ->implode(', ');
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class, 'product_variant_id');
    }

    /**
     * Modal historis (Weighted Average)
     */
    public function modalPerTanggal($tanggal)
    {
        $data = $this->batches()
            ->whereDate('tanggal_masuk', '<=', $tanggal)
            ->selectRaw('
            SUM(qty_awal) as total_qty,
            SUM(qty_awal * harga_beli) as total_nilai
        ')
            ->first();

        if (!$data || $data->total_qty == 0) {
            return 0;
        }

        return $data->total_nilai / $data->total_qty;
    }
}
