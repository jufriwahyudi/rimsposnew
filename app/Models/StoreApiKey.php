<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StoreApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'user_id',
        'name',
        'key_prefix',
        'key_hash',
        'abilities',
        'ip_whitelist',
        'rate_limit_per_minute',
        'last_used_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'abilities' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'rate_limit_per_minute' => 'integer',
    ];

    /**
     * Generate a new API key pair [plainKey, modelInstance]
     * Format: rims_live_<random_48_chars>
     */
    public static function generate(array $attributes): array
    {
        $random = Str::random(48);
        $plainKey = 'rims_live_' . $random;
        $prefix = substr($plainKey, 0, 14); // "rims_live_xxxx"
        $hash = hash('sha256', $plainKey);

        $apiKey = self::create(array_merge($attributes, [
            'key_prefix' => $prefix,
            'key_hash'   => $hash,
        ]));

        return [
            'plain_key' => $plainKey,
            'api_key'   => $apiKey,
        ];
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if key is currently active and not expired
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if key has specific ability
     */
    public function hasAbility(string $ability): bool
    {
        if (empty($this->abilities)) {
            return true; // No restrictions specified = allow all read abilities
        }

        return in_array('*', $this->abilities) || in_array($ability, $this->abilities);
    }

    /**
     * Check if the incoming request client IP is allowed
     */
    public function isIpAllowed(?string $clientIp): bool
    {
        if (empty($this->ip_whitelist)) {
            return true; // No IP restriction set
        }

        if (!$clientIp) {
            return false;
        }

        // Support comma separated, newline separated, or JSON array
        $allowed = array_map('trim', preg_split('/[\r\n,]+/', $this->ip_whitelist));
        $allowed = array_filter($allowed);

        if (empty($allowed)) {
            return true;
        }

        return in_array($clientIp, $allowed, true);
    }

    /**
     * Record usage timestamp
     */
    public function recordUsage(): void
    {
        $this->updateQuietly(['last_used_at' => now()]);
    }
}
