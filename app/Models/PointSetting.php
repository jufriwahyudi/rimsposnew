<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointSetting extends Model
{
    protected $fillable = [
        'business_id',
        'store_id',
        'is_active',
        'earning_method',
        'earning_threshold',
        'earning_points',
        'exclude_tax',
        'exclude_service_charge',
        'exclude_delivery_fee',
        'exclude_promo_items',
        'excluded_categories',
        'point_value',
        'min_points_to_redeem',
        'max_redeem_percentage',
        'max_redeem_amount',
        'expiration_type',
        'expiration_duration_months',
        'expiration_fixed_date',
        'welcome_points',
        'birthday_multiplier',
        'birthday_gift_points',
        'redemption_method',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'exclude_tax' => 'boolean',
        'exclude_service_charge' => 'boolean',
        'exclude_delivery_fee' => 'boolean',
        'exclude_promo_items' => 'boolean',
        'excluded_categories' => 'array',
        'earning_threshold' => 'decimal:2',
        'point_value' => 'decimal:2',
        'max_redeem_percentage' => 'decimal:2',
        'max_redeem_amount' => 'decimal:2',
        'birthday_multiplier' => 'decimal:2',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
