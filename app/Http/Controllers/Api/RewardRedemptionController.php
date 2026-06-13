<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberRedemption;
use App\Models\RewardItem;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RewardRedemptionController extends Controller
{
    /**
     * GET /api/pos/reward-items?store_id=N
     */
    public function apiRewardItems(Request $request)
    {
        $storeId = $request->input('store_id');
        if (!$storeId) {
            return response()->json(['message' => 'store_id diperlukan'], 422);
        }

        $store = Store::find($storeId);
        if (!$store) {
            return response()->json(['message' => 'Toko tidak ditemukan'], 404);
        }

        $businessId = $store->business_id ?: 1;

        $rewards = RewardItem::where('business_id', $businessId)
            ->where('is_active', true)
            ->get();

        return response()->json(['data' => $rewards]);
    }

    /**
     * GET /api/pos/members/{id}/rewards?store_id=N
     */
    public function apiMemberRewards(Request $request, $id)
    {
        $storeId = $request->input('store_id');
        if (!$storeId) {
            return response()->json(['message' => 'store_id diperlukan'], 422);
        }

        $store = Store::find($storeId);
        if (!$store) {
            return response()->json(['message' => 'Toko tidak ditemukan'], 404);
        }

        $businessId = $store->business_id ?: 1;
        $member = Member::where('business_id', $businessId)->findOrFail($id);

        $rewards = RewardItem::where('business_id', $businessId)
            ->where('is_active', true)
            ->get()
            ->map(function ($reward) use ($member) {
                $canRedeem = $member->total_points >= $reward->points_required;
                if ($reward->reward_type === 'physical' && $reward->stock !== null && $reward->stock <= 0) {
                    $canRedeem = false;
                }
                return [
                    'id' => $reward->id,
                    'name' => $reward->name,
                    'points_required' => $reward->points_required,
                    'reward_type' => $reward->reward_type,
                    'value' => (float)$reward->value,
                    'stock' => $reward->stock,
                    'can_redeem' => $canRedeem,
                ];
            });

        return response()->json([
            'member' => [
                'id' => $member->id,
                'name' => $member->name,
                'points' => $member->total_points,
            ],
            'rewards' => $rewards,
        ]);
    }

    /**
     * POST /api/pos/members/{id}/redeem
     */
    public function apiRedeem(Request $request, $id)
    {
        $request->validate([
            'reward_item_id' => 'required|exists:reward_items,id',
            'store_id' => 'required|exists:stores,id',
        ]);

        $storeId = $request->store_id;
        $store = Store::find($storeId);
        if (!$store) {
            return response()->json(['message' => 'Toko tidak ditemukan'], 404);
        }

        $businessId = $store->business_id ?: 1;
        $member = Member::where('business_id', $businessId)->findOrFail($id);
        $rewardItem = RewardItem::where('business_id', $businessId)->findOrFail($request->reward_item_id);

        if ($member->total_points < $rewardItem->points_required) {
            return response()->json(['message' => 'Saldo poin member tidak mencukupi untuk penukaran ini'], 422);
        }

        if ($rewardItem->reward_type === 'physical' && $rewardItem->stock !== null && $rewardItem->stock <= 0) {
            return response()->json(['message' => 'Stok barang penukaran ini sudah habis'], 422);
        }

        $redemption = DB::transaction(function () use ($member, $rewardItem, $storeId) {
            // Deduct points
            $member->decrement('total_points', $rewardItem->points_required);

            // Record point history
            \App\Models\MemberPointHistory::create([
                'member_id' => $member->id,
                'store_id' => $storeId,
                'mutation_type' => 'redeem',
                'points' => -$rewardItem->points_required,
                'balance_after' => $member->total_points,
                'notes' => 'Penukaran poin untuk hadiah: ' . $rewardItem->name,
            ]);

            // Decrement stock if physical reward
            if ($rewardItem->reward_type === 'physical' && $rewardItem->stock !== null) {
                $rewardItem->decrement('stock', 1);
            }

            // Generate voucher code if voucher type
            $voucherCode = null;
            if ($rewardItem->reward_type === 'voucher_percent' || $rewardItem->reward_type === 'voucher_nominal') {
                $voucherCode = 'VCH-' . strtoupper(Str::random(8));
                while (MemberRedemption::where('voucher_code', $voucherCode)->exists()) {
                    $voucherCode = 'VCH-' . strtoupper(Str::random(8));
                }
            }

            // Create redemption log
            return MemberRedemption::create([
                'member_id' => $member->id,
                'reward_item_id' => $rewardItem->id,
                'store_id' => $storeId,
                'points_spent' => $rewardItem->points_required,
                'voucher_code' => $voucherCode,
                'is_used' => false,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Penukaran poin berhasil diproses.',
            'voucher_code' => $redemption->voucher_code,
            'updated_points' => $member->total_points,
        ]);
    }

    /**
     * GET /api/pos/members/{id}/vouchers?store_id=N
     */
    public function apiMemberVouchers(Request $request, $id)
    {
        $storeId = $request->input('store_id');
        if (!$storeId) {
            return response()->json(['message' => 'store_id diperlukan'], 422);
        }

        $store = Store::find($storeId);
        if (!$store) {
            return response()->json(['message' => 'Toko tidak ditemukan'], 404);
        }

        $businessId = $store->business_id ?: 1;
        $member = Member::where('business_id', $businessId)->findOrFail($id);

        $vouchers = MemberRedemption::with('rewardItem')
            ->where('member_id', $member->id)
            ->where('is_used', false)
            ->whereNotNull('voucher_code')
            ->get()
            ->map(function ($redemption) {
                $reward = $redemption->rewardItem;
                return [
                    'id' => $redemption->id,
                    'voucher_code' => $redemption->voucher_code,
                    'reward_name' => $reward->name,
                    'reward_type' => $reward->reward_type,
                    'value' => (float)$reward->value,
                ];
            });

        return response()->json(['data' => $vouchers]);
    }
}
