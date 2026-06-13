<?php

namespace App\Services;

use App\Models\Member;
use App\Models\PointSetting;
use App\Models\MemberPointHistory;
use App\Models\Store;
use App\Models\Sale;

class LoyaltyPointService
{
    /**
     * Get active point settings for a store branch.
     * Fallbacks to global settings if branch override doesn't exist.
     * Auto-creates a default global config if none exists.
     */
    public function getSettings($storeId)
    {
        $store = Store::find($storeId);
        if (!$store) {
            return null;
        }

        $businessId = $store->business_id ?: 1;

        // 1. Try to find store-specific setting override
        $settings = PointSetting::where('business_id', $businessId)
            ->where('store_id', $storeId)
            ->first();

        // 2. Fallback to global settings (store_id IS NULL)
        if (!$settings) {
            $settings = PointSetting::where('business_id', $businessId)
                ->whereNull('store_id')
                ->first();
        }

        // 3. Auto-create default global settings if none exists
        if (!$settings) {
            $settings = PointSetting::create([
                'business_id' => $businessId,
                'store_id' => null,
                'is_active' => false,
                'earning_method' => 'transaction',
                'earning_threshold' => 10000.00,
                'earning_points' => 1,
                'exclude_tax' => true,
                'exclude_service_charge' => true,
                'exclude_delivery_fee' => true,
                'exclude_promo_items' => false,
                'point_value' => 100.00,
                'min_points_to_redeem' => 0,
                'max_redeem_percentage' => 100.00,
                'max_redeem_amount' => 0.00,
                'expiration_type' => 'never',
            ]);
        }

        return $settings;
    }

    /**
     * Calculate nominal spend that is eligible to earn points.
     * Excludes taxes, service charges, delivery, and promo/discounted items if configured.
     */
    public function calculateEligibleSpend($sale, $settings)
    {
        if (!$settings || !$settings->is_active) {
            return 0;
        }

        $eligibleSpend = 0;

        foreach ($sale->items as $item) {
            // Exclude items that have a discount if exclude_promo_items is true
            if ($settings->exclude_promo_items && $item->discount_amount > 0) {
                continue;
            }

            // Net amount for this item: (price * qty) - discount_amount
            $itemNet = ($item->price * $item->qty) - $item->discount_amount;
            $eligibleSpend += $itemNet;
        }

        // Subtract transaction-level discount
        $eligibleSpend -= ($sale->trans_discount ?? 0);

        // Exclude tax if configured
        if ($settings->exclude_tax && $sale->tax_total > 0) {
            $eligibleSpend -= $sale->tax_total;
        }

        return max(0, $eligibleSpend);
    }

    /**
     * Calculate points earned for a given transaction.
     * Supports Transaction-based, Product-specific, and Hybrid methods.
     * Applies birthday multiplier if transaction occurs on member's birthday.
     */
    public function calculateEarningPoints($sale)
    {
        if (!$sale->member_id) {
            return 0;
        }

        $settings = $this->getSettings($sale->store_id);
        if (!$settings || !$settings->is_active) {
            return 0;
        }

        $points = 0;

        if ($settings->earning_method === 'transaction') {
            // Method 1: Transaction-based Earning
            $eligibleSpend = $this->calculateEligibleSpend($sale, $settings);
            $points = floor($eligibleSpend / $settings->earning_threshold) * $settings->earning_points;
        } elseif ($settings->earning_method === 'product') {
            // Method 2: Product-specific Earning
            foreach ($sale->items as $item) {
                if ($settings->exclude_promo_items && $item->discount_amount > 0) {
                    continue;
                }

                $variant = $item->variant;
                if ($variant && $variant->reward_points > 0) {
                    $points += ($variant->reward_points * $item->qty);
                }
            }
        } elseif ($settings->earning_method === 'hybrid') {
            // Method 3: Hybrid Earning
            $productPoints = 0;
            $eligibleSpendForNormal = 0;

            foreach ($sale->items as $item) {
                if ($settings->exclude_promo_items && $item->discount_amount > 0) {
                    continue;
                }

                $variant = $item->variant;
                if ($variant && $variant->reward_points > 0) {
                    $productPoints += ($variant->reward_points * $item->qty);
                } else {
                    $itemNet = ($item->price * $item->qty) - $item->discount_amount;
                    $eligibleSpendForNormal += $itemNet;
                }
            }

            // Subtract transaction discount proportionally from normal items spend
            $eligibleSpendForNormal = max(0, $eligibleSpendForNormal - ($sale->trans_discount ?? 0));

            // Exclude tax if configured
            if ($settings->exclude_tax && $sale->tax_total > 0) {
                $eligibleSpendForNormal = max(0, $eligibleSpendForNormal - $sale->tax_total);
            }

            $transactionPoints = floor($eligibleSpendForNormal / $settings->earning_threshold) * $settings->earning_points;
            $points = $productPoints + $transactionPoints;
        }

        // Apply birthday multiplier
        $member = $sale->member;
        if ($member && $member->birth_date) {
            $today = now()->format('m-d');
            $birthday = $member->birth_date->format('m-d');
            if ($today === $birthday && $settings->birthday_multiplier > 1.00) {
                $points = (int) round($points * $settings->birthday_multiplier);
            }
        }

        return (int) $points;
    }

    /**
     * Credit earned points to a member after a successful transaction checkout.
     */
    public function creditPointsForSale($sale)
    {
        if (!$sale->member_id) {
            return;
        }

        $points = $this->calculateEarningPoints($sale);
        if ($points <= 0) {
            return;
        }

        $member = Member::find($sale->member_id);
        if (!$member) {
            return;
        }

        $member->increment('total_points', $points);
        $sale->update(['points_earned' => $points]);

        MemberPointHistory::create([
            'member_id' => $member->id,
            'store_id' => $sale->store_id,
            'sale_id' => $sale->id,
            'mutation_type' => 'earn',
            'points' => $points,
            'balance_after' => $member->total_points,
            'notes' => 'Perolehan poin dari transaksi #' . $sale->invoice_number,
        ]);
    }

    /**
     * Debit redeemed points from a member during a checkout transaction.
     */
    public function debitPointsForRedemption($member, $points, $sale)
    {
        if (!$member || $points <= 0) {
            return;
        }

        if ($member->total_points < $points) {
            throw new \Exception('Saldo poin member tidak mencukupi untuk penukaran ini');
        }

        $member->decrement('total_points', $points);

        MemberPointHistory::create([
            'member_id' => $member->id,
            'store_id' => $sale->store_id,
            'sale_id' => $sale->id,
            'mutation_type' => 'redeem',
            'points' => -$points,
            'balance_after' => $member->total_points,
            'notes' => 'Penukaran poin pada transaksi #' . $sale->invoice_number,
        ]);
    }

    /**
     * Revert all loyalty points earned/redeemed if a transaction is voided or refunded.
     */
    public function revertPointsForVoid($sale)
    {
        // 0. Revert voucher usage if applied
        if ($sale->voucher_code) {
            \App\Models\MemberRedemption::where('voucher_code', $sale->voucher_code)
                ->where('sale_id', $sale->id)
                ->update([
                    'is_used' => false,
                    'used_at' => null,
                    'sale_id' => null,
                ]);
        }

        $member = Member::find($sale->member_id);
        if (!$member) {
            return;
        }

        // 1. Revert points earned (deduct from member balance)
        if ($sale->points_earned > 0) {
            $member->decrement('total_points', $sale->points_earned);
            
            MemberPointHistory::create([
                'member_id' => $member->id,
                'store_id' => $sale->store_id,
                'sale_id' => $sale->id,
                'mutation_type' => 'adjust',
                'points' => -$sale->points_earned,
                'balance_after' => $member->total_points,
                'notes' => 'Pembatalan perolehan poin dari transaksi #' . $sale->invoice_number . ' (Void)',
            ]);
        }

        // 2. Revert points redeemed (credit back to member balance)
        if ($sale->points_redeemed > 0) {
            $member->increment('total_points', $sale->points_redeemed);
            
            MemberPointHistory::create([
                'member_id' => $member->id,
                'store_id' => $sale->store_id,
                'sale_id' => $sale->id,
                'mutation_type' => 'adjust',
                'points' => $sale->points_redeemed,
                'balance_after' => $member->total_points,
                'notes' => 'Pengembalian poin dari penukaran transaksi #' . $sale->invoice_number . ' (Void)',
            ]);
        }
    }
}
