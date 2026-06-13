<?php

namespace App\Http\Controllers;

use App\Models\PointSetting;
use App\Models\Store;
use App\Services\LoyaltyPointService;
use Illuminate\Http\Request;

class PointSettingController extends Controller
{
    protected $loyaltyService;

    public function __construct(LoyaltyPointService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
    }

    /**
     * Show the loyalty points configuration page.
     */
    public function index()
    {
        $storeId = session('store_id');
        $store = Store::findOrFail($storeId);
        $businessId = $store->business_id ?: 1;

        // Check if override setting exists for this store
        $storeSetting = PointSetting::where('business_id', $businessId)
            ->where('store_id', $storeId)
            ->first();

        $isOverride = ($storeSetting !== null);

        // Get active settings (either override or global)
        $settings = $this->loyaltyService->getSettings($storeId);

        return view('pengaturan.points.index', compact('settings', 'isOverride', 'store'));
    }

    /**
     * Update/save the loyalty points settings.
     */
    public function update(Request $request)
    {
        $storeId = session('store_id');
        $store = Store::findOrFail($storeId);
        $businessId = $store->business_id ?: 1;

        $request->validate([
            'is_active' => 'boolean',
            'is_override' => 'boolean',
            'earning_method' => 'required|in:transaction,product,hybrid',
            'earning_threshold' => 'nullable|required_if:earning_method,transaction,hybrid|numeric|min:0',
            'earning_points' => 'nullable|required_if:earning_method,transaction,hybrid|integer|min:0',
            'exclude_tax' => 'boolean',
            'exclude_service_charge' => 'boolean',
            'exclude_delivery_fee' => 'boolean',
            'exclude_promo_items' => 'boolean',
            'point_value' => 'nullable|required_if:redemption_method,point_value|numeric|min:0',
            'min_points_to_redeem' => 'nullable|required_if:redemption_method,point_value|integer|min:0',
            'max_redeem_percentage' => 'nullable|required_if:redemption_method,point_value|numeric|min:0|max:100',
            'max_redeem_amount' => 'nullable|required_if:redemption_method,point_value|numeric|min:0',
            'expiration_type' => 'required|in:never,duration,fixed_date',
            'expiration_duration_months' => 'nullable|integer|min:1',
            'expiration_fixed_date' => 'nullable|string|max:5',
            'welcome_points' => 'required|integer|min:0',
            'birthday_multiplier' => 'required|numeric|min:1',
            'redemption_method' => 'nullable|in:point_value,item_redemption',
        ]);

        $isOverride = $request->boolean('is_override');

        if ($isOverride) {
            // Save specific store settings override
            $settings = PointSetting::firstOrNew([
                'business_id' => $businessId,
                'store_id' => $storeId,
            ]);
        } else {
            // Delete specific store override if it exists so it falls back to global
            PointSetting::where('business_id', $businessId)
                ->where('store_id', $storeId)
                ->delete();

            // Save/update global settings
            $settings = PointSetting::firstOrNew([
                'business_id' => $businessId,
                'store_id' => null,
            ]);
        }

        $settings->fill([
            'is_active' => $request->boolean('is_active'),
            'earning_method' => $request->input('earning_method'),
            'earning_threshold' => $request->input('earning_threshold') ?? $settings->earning_threshold ?? 10000.00,
            'earning_points' => $request->input('earning_points') ?? $settings->earning_points ?? 1,
            'exclude_tax' => $request->boolean('exclude_tax'),
            'exclude_service_charge' => $request->boolean('exclude_service_charge'),
            'exclude_delivery_fee' => $request->boolean('exclude_delivery_fee'),
            'exclude_promo_items' => $request->boolean('exclude_promo_items'),
            'point_value' => $request->input('point_value') ?? $settings->point_value ?? 0.00,
            'min_points_to_redeem' => $request->input('min_points_to_redeem') ?? $settings->min_points_to_redeem ?? 0,
            'max_redeem_percentage' => $request->input('max_redeem_percentage') ?? $settings->max_redeem_percentage ?? 100.00,
            'max_redeem_amount' => $request->input('max_redeem_amount') ?? $settings->max_redeem_amount ?? 0.00,
            'expiration_type' => $request->input('expiration_type'),
            'expiration_duration_months' => $request->filled('expiration_duration_months') ? $request->input('expiration_duration_months') : 12,
            'expiration_fixed_date' => $request->filled('expiration_fixed_date') ? $request->input('expiration_fixed_date') : '12-31',
            'welcome_points' => $request->input('welcome_points'),
            'birthday_multiplier' => $request->input('birthday_multiplier'),
            'redemption_method' => $request->input('redemption_method') ?? $settings->redemption_method ?? 'point_value',
        ]);

        $settings->save();

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan Loyalty Points berhasil disimpan.',
        ]);
    }
}
