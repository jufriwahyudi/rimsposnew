<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Business;
use App\Models\Store;
use App\Models\Member;
use App\Models\PointSetting;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\LoyaltyPointService;

class LoyaltyPointServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected $service;
    protected $business;
    protected $store;
    protected $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LoyaltyPointService();

        // Seed default parent business
        $this->business = Business::create([
            'name' => 'Test Business',
            'code' => 'TESTBIZ',
        ]);

        // Seed default store branch
        $this->store = Store::create([
            'business_id' => $this->business->id,
            'name' => 'Test Branch Store',
            'code' => 'TBS01',
            'address' => 'Test Address',
            'city' => 'Test City',
            'phone' => '0812345678',
            'is_active' => true,
            'business_type' => 'retail',
        ]);

        // Seed default member
        $this->member = Member::create([
            'business_id' => $this->business->id,
            'name' => 'John Doe',
            'phone' => '08123456789',
            'birth_date' => now()->format('Y-m-d'),
            'total_points' => 100,
            'is_active' => true,
        ]);
    }

    /**
     * Test point settings retrieval (global fallback vs store override).
     */
    public function test_get_settings_retrieval()
    {
        // Initially should create and return a default inactive settings
        $settings = $this->service->getSettings($this->store->id);
        $this->assertNotNull($settings);
        $this->assertFalse($settings->is_active);
        $this->assertNull($settings->store_id); // Default global config

        // Update global settings
        $settings->update([
            'is_active' => true,
            'point_value' => 200,
        ]);

        // Retrieve settings again, should be active
        $settings = $this->service->getSettings($this->store->id);
        $this->assertTrue($settings->is_active);
        $this->assertEquals(200, $settings->point_value);

        // Create a store override
        $overrideSettings = PointSetting::create([
            'business_id' => $this->business->id,
            'store_id' => $this->store->id,
            'is_active' => true,
            'point_value' => 500,
            'earning_method' => 'transaction',
            'earning_threshold' => 10000,
            'earning_points' => 1,
            'expiration_type' => 'never',
        ]);

        // Retrieve settings again, should return the store override instead of global
        $settings = $this->service->getSettings($this->store->id);
        $this->assertEquals($overrideSettings->id, $settings->id);
        $this->assertEquals(500, $settings->point_value);
    }

    /**
     * Test eligible spend calculation with various exclusions (tax, Service charge, promo items).
     */
    public function test_calculate_eligible_spend()
    {
        $settings = $this->service->getSettings($this->store->id);
        $settings->update([
            'is_active' => true,
            'exclude_tax' => true,
            'exclude_promo_items' => true,
        ]);

        // Create Sale
        $sale = Sale::create([
            'store_id' => $this->store->id,
            'invoice_number' => 'INV-001',
            'sale_date' => now(),
            'sale_type' => 'retail',
            'member_id' => $this->member->id,
            'trans_discount' => 5000,
            'tax_total' => 10000,
            'subtotal' => 70000,
            'grand_total' => 55000,
            'status' => 'paid',
            'payment_status' => 'lunas',
        ]);

        // Add items: item 1 (normal), item 2 (discounted/promo)
        $item1 = new \App\Models\SaleItem([
            'price' => 50000,
            'qty' => 1,
            'discount_amount' => 0,
        ]);

        $item2 = new \App\Models\SaleItem([
            'price' => 20000,
            'qty' => 1,
            'discount_amount' => 2000, // Promo item
        ]);

        $sale->setRelation('items', collect([$item1, $item2]));

        // Eligible spend calculation:
        // Item 1: (50000 * 1) - 0 = 50000
        // Item 2: excluded because of promo exclusion (exclude_promo_items = true)
        // Subtotal of eligible = 50000
        // Subtract trans_discount: 50000 - 5000 = 45000
        // Subtract tax_total: 45000 - 10000 = 35000
        $spend = $this->service->calculateEligibleSpend($sale, $settings);
        $this->assertEquals(35000, $spend);
    }

    /**
     * Test points earning calculation using transaction, product, and hybrid methods.
     */
    public function test_calculate_earning_points()
    {
        $settings = $this->service->getSettings($this->store->id);
        $settings->update([
            'is_active' => true,
            'earning_method' => 'transaction',
            'earning_threshold' => 10000,
            'earning_points' => 1,
            'exclude_tax' => false,
            'exclude_promo_items' => false,
            'birthday_multiplier' => 2.00, // 2x birthday multiplier
        ]);

        $sale = Sale::create([
            'store_id' => $this->store->id,
            'invoice_number' => 'INV-002',
            'sale_date' => now(),
            'sale_type' => 'retail',
            'member_id' => $this->member->id,
            'tax_total' => 0,
            'trans_discount' => 0,
            'subtotal' => 50000,
            'grand_total' => 50000,
            'status' => 'paid',
            'payment_status' => 'lunas',
        ]);
        $sale->setRelation('member', $this->member);

        $item1 = new \App\Models\SaleItem([
            'price' => 50000,
            'qty' => 1,
            'discount_amount' => 0,
        ]);
        $sale->setRelation('items', collect([$item1]));

        // Today is member's birthday (setUp sets birth_date to now), so we expect double points!
        // Nominal points: (50,000 / 10,000) * 1 = 5
        // Birthday double points = 10 Pts
        $points = $this->service->calculateEarningPoints($sale);
        $this->assertEquals(10, $points);
    }

    /**
     * Test points redemption and history log creation.
     */
    public function test_debit_points_for_redemption()
    {
        $sale = Sale::create([
            'store_id' => $this->store->id,
            'invoice_number' => 'INV-003',
            'sale_date' => now(),
            'sale_type' => 'retail',
            'subtotal' => 10000,
            'grand_total' => 10000,
            'status' => 'paid',
            'payment_status' => 'lunas',
        ]);

        $this->service->debitPointsForRedemption($this->member, 40, $sale);

        // Member points should be decremented: 100 - 40 = 60
        $this->assertEquals(60, $this->member->total_points);

        // Point history should be logged
        $this->assertDatabaseHas('member_point_histories', [
            'member_id' => $this->member->id,
            'sale_id' => $sale->id,
            'mutation_type' => 'redeem',
            'points' => -40,
            'balance_after' => 60,
        ]);
    }

    /**
     * Test transaction void and reversal of points.
     */
    public function test_revert_points_for_void()
    {
        $sale = Sale::create([
            'store_id' => $this->store->id,
            'invoice_number' => 'INV-004',
            'sale_date' => now(),
            'sale_type' => 'retail',
            'member_id' => $this->member->id,
            'points_earned' => 10,
            'points_redeemed' => 20,
            'subtotal' => 10000,
            'grand_total' => 10000,
            'status' => 'void',
            'payment_status' => 'lunas',
        ]);

        // Initially total_points is 100
        $this->service->revertPointsForVoid($sale);

        // Net points modification:
        // - Deduct earned points (-10)
        // - Refund redeemed points (+20)
        // Final total_points = 100 - 10 + 20 = 110
        $this->member->refresh();
        $this->assertEquals(110, $this->member->total_points);

        // Assert histories created
        $this->assertDatabaseHas('member_point_histories', [
            'member_id' => $this->member->id,
            'sale_id' => $sale->id,
            'mutation_type' => 'adjust',
            'points' => -10,
        ]);

        $this->assertDatabaseHas('member_point_histories', [
            'member_id' => $this->member->id,
            'sale_id' => $sale->id,
            'mutation_type' => 'adjust',
            'points' => 20,
        ]);
    }
}
