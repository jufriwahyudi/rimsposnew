<?php

namespace App\Models;

use App\Models\Traits\HasStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory, HasStore;

    protected $table = 'discounts';

    protected $fillable = [
        'store_id',
        'code',
        'name',
        'discount_type',
        'discount_value',
        'min_purchase_amount',
        'max_discount_amount',
        'target_type',
        'scope_type',
        'start_date',
        'end_date',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active'           => 'boolean',
        'discount_value'      => 'float',
        'min_purchase_amount' => 'float',
        'max_discount_amount' => 'float',
        'start_date'          => 'date',
        'end_date'            => 'date',
    ];

    /**
     * Scope untuk promo yang sedang aktif dan berlaku hari ini
     */
    public function scopeActive($query)
    {
        $today = now()->toDateString();

        return $query->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            });
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'discount_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function items()
    {
        return $this->hasMany(DiscountItem::class, 'discount_id');
    }

    public function variants()
    {
        return $this->belongsToMany(ProductVariant::class, 'discount_items', 'discount_id', 'product_variant_id');
    }

    /**
     * Hitung besaran diskon berdasarkan subtotal transaksi
     */
    public function calculateDiscount(float $subtotal, ?float $eligibleSubtotal = null): float
    {
        if ($this->min_purchase_amount > 0 && $subtotal < $this->min_purchase_amount) {
            return 0.0;
        }

        $baseAmount = ($this->scope_type === 'product' && $eligibleSubtotal !== null)
            ? $eligibleSubtotal
            : $subtotal;

        if ($baseAmount <= 0) {
            return 0.0;
        }

        if ($this->discount_type === 'percentage') {
            $discount = ($baseAmount * $this->discount_value) / 100.0;
            if ($this->max_discount_amount && $this->max_discount_amount > 0) {
                $discount = min($discount, (float) $this->max_discount_amount);
            }
            return (float) min($discount, $baseAmount);
        }

        // Nominal
        return (float) min($this->discount_value, $baseAmount);
    }
}
