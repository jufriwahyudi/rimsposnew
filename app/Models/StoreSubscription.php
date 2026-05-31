<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'package_type',
        'billing_amount',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date'     => 'date',
        'end_date'       => 'date',
        'billing_amount' => 'decimal:2',
    ];

    protected $appends = [
        'subscription_status',
        'grace_days_left',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    // ── Status Helpers ───────────────────────────────────────────────────────

    /**
     * Get the current subscription status.
     *
     * - 'active'       : Lifetime, or end_date is in the future.
     * - 'grace_period'  : end_date has passed but within 7-day grace window.
     * - 'expired'       : Past end_date + 7 days.
     */
    public function getSubscriptionStatusAttribute(): string
    {
        if ($this->package_type === 'lifetime') {
            return 'active';
        }

        if (!$this->end_date) {
            return 'active';
        }

        $today = Carbon::today();

        if ($today->lte($this->end_date)) {
            return 'active';
        }

        $graceEnd = $this->end_date->copy()->addDays(7);

        if ($today->lte($graceEnd)) {
            return 'grace_period';
        }

        return 'expired';
    }

    /**
     * Whether the subscription is fully expired (past grace period).
     */
    public function isExpired(): bool
    {
        return $this->subscription_status === 'expired';
    }

    /**
     * Whether the subscription is currently in the grace period.
     */
    public function isInGracePeriod(): bool
    {
        return $this->subscription_status === 'grace_period';
    }

    /**
     * Days remaining in the grace period (0 if not in grace period).
     */
    public function getGraceDaysLeftAttribute(): int
    {
        if (!$this->isInGracePeriod()) {
            return 0;
        }

        $graceEnd = $this->end_date->copy()->addDays(7);

        return max(0, Carbon::today()->diffInDays($graceEnd, false));
    }
}
