<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Store;

class EnsureAddonEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $addonName
     */
    public function handle(Request $request, Closure $next, string $addonName): Response
    {
        $storeId = session('store_id') 
            ?? $request->header('X-Store-ID') 
            ?? $request->input('store_id') 
            ?? $request->route('store_id');

        if (!$storeId) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'store_id diperlukan'], 422);
            }
            abort(403, 'Akses ditolak: ID Toko tidak terdeteksi.');
        }

        $store = Store::find($storeId);

        if (!$store || $store->business_type !== 'fnb') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akses ditolak: Fitur ini hanya untuk tipe bisnis FnB.'], 403);
            }
            abort(403, 'Akses ditolak: Fitur ini hanya untuk tipe bisnis FnB.');
        }

        if ($addonName === 'self_service' && !$store->addon_self_service) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Fitur Customer Self-Service (QR Order) dinonaktifkan untuk toko ini.'], 403);
            }
            return response()->view('self-service.addon_disabled', ['store' => $store], 403);
        }

        if ($addonName === 'kds' && !$store->addon_kds) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Fitur Kitchen Display System (KDS) dinonaktifkan untuk toko ini.'], 403);
            }
            abort(403, 'Fitur Kitchen Display System (KDS) dinonaktifkan untuk toko ini.');
        }

        return $next($request);
    }
}
