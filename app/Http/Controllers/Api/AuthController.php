<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * POST /api/auth/login
     *
     * Body: { "email": "...", "password": "..." }
     * Response: { "token": "...", "user": { "id", "name", "email", "stores": [...] } }
     *
     * - Toko 'expired' (melewati grace period) disembunyikan dari daftar stores.
     * - Toko 'grace_period' tetap dikembalikan dengan flag peringatan.
     * - Jika semua toko expired → login ditolak.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        //dd(json_encode($request->all(), JSON_PRETTY_PRINT));
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // Ambil toko aktif beserta data langganan
        $stores = $user->stores()
            ->where('is_active', true)
            ->orderBy('name')
            ->with('subscription')
            ->get();

        // Filter: hanya kembalikan toko yang belum expired
        $accessibleStores = $stores->filter(function ($store) {
            if (!$store->subscription) {
                return true; // Toko tanpa subscription data → anggap aktif
            }
            return !$store->subscription->isExpired();
        });

        // Jika semua toko expired, tolak login
        if ($accessibleStores->isEmpty()) {
            throw ValidationException::withMessages([
                'email' => ['Masa aktif toko Anda telah habis. Silakan hubungi administrator.'],
            ]);
        }

        // Hapus token lama agar tidak menumpuk
        $user->tokens()->where('name', 'mobile')->delete();

        $token = $user->createToken('mobile')->plainTextToken;

        $storesData = $accessibleStores->map(function ($store) {
            $subscriptionInfo = null;

            if ($store->subscription) {
                $sub = $store->subscription;
                $status = $sub->subscription_status;

                $subscriptionInfo = [
                    'package_type' => $sub->package_type,
                    'status'       => $status,
                    'start_date'   => $sub->start_date?->format('Y-m-d'),
                    'end_date'     => $sub->end_date?->format('Y-m-d'),
                ];

                if ($status === 'grace_period') {
                    $subscriptionInfo['show_subscription_alert'] = true;
                    $subscriptionInfo['grace_days_left'] = $sub->grace_days_left;
                    $subscriptionInfo['grace_expires_at'] = $sub->end_date->copy()->addDays(7)->format('Y-m-d');
                    $subscriptionInfo['alert_message'] = 'Masa aktif toko ini telah berakhir pada '
                        . $sub->end_date->format('d M Y')
                        . '. Sisa masa tenggang Anda adalah ' . $sub->grace_days_left . ' hari. '
                        . 'Harap hubungi administrator sebelum operasional dihentikan.';
                } else {
                    $subscriptionInfo['show_subscription_alert'] = false;
                }
            }

            return [
                'id'                => $store->id,
                'name'              => $store->name,
                'printer_type'      => $store->printer_type,
                'business_type'     => $store->business_type,
                'subscription_info' => $subscriptionInfo,
            ];
        })->values();

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'     => $user->id,
                'name'   => $user->name,
                'email'  => $user->email,
                'stores' => $storesData,
            ],
        ]);
    }

    /**
     * POST /api/auth/logout  (requires Bearer token)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * GET /api/auth/me  (requires Bearer token)
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
        ]);
    }

    /**
     * POST /api/auth/fcm-token  (requires Bearer token)
     *
     * Body: { "fcm_token": "..." }
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);
        \Log::info('FCM Token: ' . $request->fcm_token);
        \Log::info('User: ' . $request->user()->id);

        $request->user()->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return response()->json(['message' => 'Token FCM berhasil disimpan.']);
    }
}
