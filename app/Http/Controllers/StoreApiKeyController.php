<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreApiKeyController extends Controller
{
    /**
     * Get all API Keys for a store
     */
    public function index(Store $store): JsonResponse
    {
        $apiKeys = $store->apiKeys()
            ->with('user:id,name')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $apiKeys,
        ]);
    }

    /**
     * Generate a new API Key for a store
     */
    public function store(Request $request, Store $store): JsonResponse
    {
        $request->validate([
            'name'                  => 'required|string|max:100',
            'abilities'             => 'nullable|array',
            'ip_whitelist'          => 'nullable|string',
            'rate_limit_per_minute' => 'nullable|integer|min:10|max:1000',
            'expires_days'          => 'nullable|integer|min:1',
        ]);

        $abilities = $request->input('abilities', [
            'products:read',
            'categories:read',
            'stock:read',
            'sales:read',
        ]);

        $expiresAt = null;
        if ($request->filled('expires_days')) {
            $expiresAt = now()->addDays((int) $request->input('expires_days'));
        }

        $result = StoreApiKey::generate([
            'store_id'              => $store->id,
            'user_id'               => auth()->id(),
            'name'                  => $request->input('name'),
            'abilities'             => $abilities,
            'ip_whitelist'          => $request->input('ip_whitelist'),
            'rate_limit_per_minute' => $request->input('rate_limit_per_minute', 60),
            'expires_at'            => $expiresAt,
            'is_active'             => true,
        ]);

        return response()->json([
            'success'   => true,
            'message'   => 'API Key berhasil dibuat!',
            'plain_key' => $result['plain_key'], // Shown ONLY ONCE upon creation
            'api_key'   => $result['api_key']->load('user:id,name'),
        ]);
    }

    /**
     * Toggle active/inactive status of an API Key
     */
    public function toggle($id): JsonResponse
    {
        $apiKey = StoreApiKey::findOrFail($id);
        $apiKey->is_active = !$apiKey->is_active;
        $apiKey->save();

        return response()->json([
            'success'   => true,
            'message'   => 'Status API Key berhasil diubah.',
            'is_active' => $apiKey->is_active,
        ]);
    }

    /**
     * Revoke and delete an API Key
     */
    public function destroy($id): JsonResponse
    {
        $apiKey = StoreApiKey::findOrFail($id);
        $apiKey->delete();

        return response()->json([
            'success' => true,
            'message' => 'API Key berhasil dihapus/direvoke.',
        ]);
    }
}
