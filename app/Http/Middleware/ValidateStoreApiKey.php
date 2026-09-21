<?php

namespace App\Http\Middleware;

use App\Models\StoreApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateStoreApiKey
{
    /**
     * Handle an incoming request authenticated via X-API-KEY.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $ability
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ?string $ability = null): Response
    {
        // 1. Extract API Key from header or query
        $rawKey = $request->header('X-API-KEY')
            ?: $request->header('x-api-key')
            ?: $request->bearerToken()
            ?: $request->query('api_key');

        if (!$rawKey) {
            return response()->json([
                'status'  => 'error',
                'message' => 'API Key diperlukan. Sertakan header X-API-KEY pada permintaan.',
                'code'    => 401,
            ], 401);
        }

        // 2. Hash key with SHA-256 for secure database lookup
        $keyHash = hash('sha256', trim($rawKey));

        $apiKey = StoreApiKey::with('store.subscription')
            ->where('key_hash', $keyHash)
            ->first();

        if (!$apiKey || !$apiKey->isValid()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'API Key tidak valid atau telah kedaluwarsa.',
                'code'    => 401,
            ], 401);
        }

        // 3. Verify IP Whitelist if configured
        $clientIp = $request->ip();
        if (!$apiKey->isIpAllowed($clientIp)) {
            return response()->json([
                'status'  => 'error',
                'message' => "Akses ditolak. Alamat IP [{$clientIp}] tidak terdaftar dalam IP Whitelist.",
                'code'    => 403,
            ], 403);
        }

        // 4. Verify Store Active Status
        $store = $apiKey->store;
        if (!$store || !$store->is_active) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Toko terkait sedang dalam status non-aktif.',
                'code'    => 403,
            ], 403);
        }

        // 5. Verify Store Subscription
        if ($store->subscription && method_exists($store->subscription, 'isExpired') && $store->subscription->isExpired()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Masa aktif langganan toko ini telah habis.',
                'code'    => 403,
            ], 403);
        }

        // 6. Verify granular capability / ability
        if ($ability && !$apiKey->hasAbility($ability)) {
            return response()->json([
                'status'  => 'error',
                'message' => "API Key tidak memiliki izin untuk akses '{$ability}'.",
                'code'    => 403,
            ], 403);
        }

        // 7. Record usage asynchronously/quietly
        $apiKey->recordUsage();

        // 8. Inject store and apiKey to request attributes
        $request->attributes->set('store', $store);
        $request->attributes->set('store_id', $store->id);
        $request->attributes->set('apiKey', $apiKey);

        return $next($request);
    }
}
