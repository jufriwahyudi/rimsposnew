<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
        'code',
        'address',
        'city',
        'phone',
        'is_active',
        'printer_type',
        'logo',
        'business_type',
        'addon_self_service',
        'addon_kds',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'addon_self_service' => 'boolean',
        'addon_kds' => 'boolean',
    ];

    protected static function booted()
    {
        static::saved(function ($store) {
            try {
                app(\App\Services\FirestoreService::class)->syncStore($store);
            } catch (\Throwable $e) {
                \Log::error("Failed to sync store #{$store->id} to Firestore: " . $e->getMessage());
            }
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function pointSetting()
    {
        return $this->hasOne(PointSetting::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'store_user')
            ->withTimestamps();
    }

    public function subscription()
    {
        return $this->hasOne(StoreSubscription::class);
    }

    public function invoices()
    {
        return $this->hasMany(SubscribedInvoice::class);
    }
}
