<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStoreSubscription
{
    /**
     * Middleware untuk mengecek status langganan toko pada request API mobile.
     *
     * - Jika toko status 'expired' (melewati grace period 7 hari):
     *   Hapus token Sanctum dan kembalikan 401.
     * - Jika toko status 'grace_period':
     *   Ijinkan request masuk (operasional tetap berjalan).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $storeId = $request->input('store_id') ?: $request->query('store_id');

        if (!$storeId) {
            return $next($request);
        }

        $store = Store::with('subscription')->find($storeId);

        if (!$store || !$store->subscription) {
            return $next($request);
        }

        $subscription = $store->subscription;

        // Jika langganan sudah expired (melewati grace period 7 hari)
        if ($subscription->isExpired()) {
            // Hapus token Sanctum aktif (matikan session)
            if ($request->user() && $request->user()->currentAccessToken()) {
                $request->user()->currentAccessToken()->delete();
            }

            return response()->json([
                'message' => 'Masa aktif toko Anda telah habis. Silakan hubungi administrator.',
                'subscription_expired' => true,
            ], 401);
        }

        return $next($request);
    }
}
