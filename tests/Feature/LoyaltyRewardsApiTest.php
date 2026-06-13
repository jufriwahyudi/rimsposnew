<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Member;
use App\Models\MemberRedemption;
use App\Models\PointSetting;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RewardItem;
use App\Models\Store;
use App\Models\User;
use App\Services\FirestoreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LoyaltyRewardsApiTest extends TestCase
{
    use DatabaseTransactions;

    protected $business;
    protected $store;
    protected $user;
    protected $member;
    protected $physicalReward;
    protected $voucherReward;
    protected $variant;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock FirestoreService globally
        $this->mock(FirestoreService::class, function ($mock) {
            $mock->shouldReceive('syncStore')->andReturn(true);
            $mock->shouldReceive('syncOrder')->andReturn(true);
        });

        // 1. Create Business
        $this->business = Business::create([
            'name' => 'Loyalty Reward Biz',
            'code' => 'LRB01',
        ]);

        // 2. Create Store
        $this->store = Store::create([
            'business_id' => $this->business->id,
            'name' => 'Loyalty Store',
            'code' => 'LST01',
            'is_active' => true,
            'business_type' => 'retail',
        ]);
        \App\Support\Tenant::set($this->store->id);

        // 3. Create User
        $this->user = User::create([
            'name' => 'POS Cashier',
            'email' => 'cashier.loyalty@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->user->stores()->syncWithoutDetaching([$this->store->id]);

        // 4. Create Member
        $this->member = Member::create([
            'business_id' => $this->business->id,
            'name' => 'Andi Member',
            'phone' => '08999999999',
            'email' => 'andi@member.com',
            'total_points' => 100,
            'is_active' => true,
        ]);

        // 5. Create Point Settings
        PointSetting::create([
            'business_id' => $this->business->id,
            'is_active' => true,
            'redemption_method' => 'item_redemption',
            'point_value' => 100,
            'min_points_to_redeem' => 10,
            'max_redeem_percentage' => 100,
            'max_redeem_amount' => 100000,
            'earning_method' => 'transaction',
            'earning_threshold' => 10000,
            'earning_points' => 1,
            'expiration_type' => 'never',
        ]);

        // 6. Create Reward Items
        $this->physicalReward = RewardItem::create([
            'business_id' => $this->business->id,
            'name' => 'Tumbler Rims',
            'points_required' => 40,
            'reward_type' => 'physical',
            'value' => 0,
            'stock' => 5,
            'is_active' => true,
        ]);

        $this->voucherReward = RewardItem::create([
            'business_id' => $this->business->id,
            'name' => 'Voucher Diskon 10rb',
            'points_required' => 50,
            'reward_type' => 'voucher_nominal',
            'value' => 10000,
            'stock' => null,
            'is_active' => true,
        ]);

        // 7. Create Product for checkout
        $product = Product::create([
            'store_id' => $this->store->id,
            'kode_produk' => 'PRD-L1',
            'nama_produk' => 'Loyalty Product',
        ]);

        $this->variant = ProductVariant::create([
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'variant_name' => 'Regular',
            'sku' => 'SKU-L1',
            'barcode' => 'BC-L1',
            'harga_jual' => 20000,
            'track_stock' => false,
            'is_active' => 'Y',
        ]);
    }

    public function test_api_get_reward_items()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/pos/reward-items?store_id=' . $this->store->id);

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_api_get_member_rewards()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/pos/members/' . $this->member->id . '/rewards?store_id=' . $this->store->id);

        $response->assertStatus(200);
        $response->assertJsonPath('member.points', 100);
        $response->assertJsonCount(2, 'rewards');
    }

    public function test_api_redeem_physical_reward()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/pos/members/' . $this->member->id . '/redeem', [
                'reward_item_id' => $this->physicalReward->id,
                'store_id' => $this->store->id,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('updated_points', 60);

        // Check stock reduced
        $this->physicalReward->refresh();
        $this->assertEquals(4, $this->physicalReward->stock);

        // Check points in DB Mutated
        $this->member->refresh();
        $this->assertEquals(60, $this->member->total_points);

        // Check point history created
        $this->assertDatabaseHas('member_point_histories', [
            'member_id' => $this->member->id,
            'points' => -40,
            'mutation_type' => 'redeem',
        ]);
    }

    public function test_api_redeem_voucher_reward_generates_code()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/pos/members/' . $this->member->id . '/redeem', [
                'reward_item_id' => $this->voucherReward->id,
                'store_id' => $this->store->id,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        
        $voucherCode = $response->json('voucher_code');
        $this->assertNotNull($voucherCode);
        $this->assertStringStartsWith('VCH-', $voucherCode);

        // Check points reduced
        $this->member->refresh();
        $this->assertEquals(50, $this->member->total_points);

        // Check vouchers listing
        $vouchersResponse = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/pos/members/' . $this->member->id . '/vouchers?store_id=' . $this->store->id);

        $vouchersResponse->assertStatus(200);
        $vouchersResponse->assertJsonCount(1, 'data');
        $vouchersResponse->assertJsonPath('data.0.voucher_code', $voucherCode);
        $vouchersResponse->assertJsonPath('data.0.value', 10000);
    }

    public function test_api_checkout_with_applied_voucher()
    {
        // 1. Redeem first to get a voucher
        $redeemResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/pos/members/' . $this->member->id . '/redeem', [
                'reward_item_id' => $this->voucherReward->id,
                'store_id' => $this->store->id,
            ]);
        $voucherCode = $redeemResponse->json('voucher_code');

        // 2. Perform checkout with the voucher code
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/pos/checkout', [
                'store_id' => $this->store->id,
                'cart' => [
                    'payment_method' => 'cash',
                    'paid_amount' => 10000, // Regular total is 20000. Voucher nominal discount is 10000. So net total is 10000.
                    'cash_amount' => 10000,
                    'member_id' => $this->member->id,
                    'voucher_code' => $voucherCode,
                    'subtotal' => 20000,
                    'discount_total' => 0,
                    'transaction_discount' => 0,
                    'total' => 10000,
                    'items' => [
                        [
                            'product_id' => $this->variant->product_id,
                            'variant_id' => $this->variant->id,
                            'sku' => $this->variant->sku,
                            'variant' => 'Regular',
                            'price' => 20000,
                            'qty' => 1,
                            'discount_amount' => 0,
                            'subtotal' => 20000,
                        ]
                    ]
                ]
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['invoice', 'sale_id', 'change']);

        // 3. Verify voucher is marked as used
        $redemption = MemberRedemption::where('voucher_code', $voucherCode)->first();
        $this->assertTrue((bool)$redemption->is_used);
        $this->assertNotNull($redemption->used_at);
        $this->assertNotNull($redemption->sale_id);

        // 4. Verify sales database has voucher details recorded
        $this->assertDatabaseHas('sales', [
            'id' => $redemption->sale_id,
            'voucher_code' => $voucherCode,
            'voucher_discount_amount' => 10000,
            'grand_total' => 10000,
        ]);
    }

    public function test_api_checkout_with_applied_percent_voucher_with_max_discount_cap()
    {
        // 1. Create a percent voucher reward with a max discount cap
        $percentVoucherReward = RewardItem::create([
            'business_id' => $this->business->id,
            'name' => 'Voucher 50% Max 5rb',
            'points_required' => 50,
            'reward_type' => 'voucher_percent',
            'value' => 50,
            'max_discount' => 5000,
            'stock' => null,
            'is_active' => true,
        ]);

        // 2. Redeem points to get the voucher code
        $redeemResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/pos/members/' . $this->member->id . '/redeem', [
                'reward_item_id' => $percentVoucherReward->id,
                'store_id' => $this->store->id,
            ]);
        $voucherCode = $redeemResponse->json('voucher_code');

        // 3. Perform checkout with the voucher code. Subtotal is 20000. 50% of 20000 = 10000, but capped at 5000.
        // So grand total is 15000.
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/pos/checkout', [
                'store_id' => $this->store->id,
                'cart' => [
                    'payment_method' => 'cash',
                    'paid_amount' => 15000,
                    'cash_amount' => 15000,
                    'member_id' => $this->member->id,
                    'voucher_code' => $voucherCode,
                    'subtotal' => 20000,
                    'discount_total' => 0,
                    'transaction_discount' => 0,
                    'total' => 15000,
                    'items' => [
                        [
                            'product_id' => $this->variant->product_id,
                            'variant_id' => $this->variant->id,
                            'sku' => $this->variant->sku,
                            'variant' => 'Regular',
                            'price' => 20000,
                            'qty' => 1,
                            'discount_amount' => 0,
                            'subtotal' => 20000,
                        ]
                    ]
                ]
            ]);

        $response->assertStatus(200);

        // 4. Verify sales database records capped discount
        $this->assertDatabaseHas('sales', [
            'voucher_code' => $voucherCode,
            'voucher_discount_amount' => 5000,
            'grand_total' => 15000,
        ]);
    }

    public function test_api_void_reverts_voucher_usage()
    {
        // 1. Redeem points to get a voucher
        $redeemResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/pos/members/' . $this->member->id . '/redeem', [
                'reward_item_id' => $this->voucherReward->id,
                'store_id' => $this->store->id,
            ]);
        $voucherCode = $redeemResponse->json('voucher_code');

        // 2. Perform checkout using the voucher code
        $checkoutResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/pos/checkout', [
                'store_id' => $this->store->id,
                'cart' => [
                    'payment_method' => 'cash',
                    'paid_amount' => 10000,
                    'cash_amount' => 10000,
                    'member_id' => $this->member->id,
                    'voucher_code' => $voucherCode,
                    'subtotal' => 20000,
                    'discount_total' => 0,
                    'transaction_discount' => 0,
                    'total' => 10000,
                    'items' => [
                        [
                            'product_id' => $this->variant->product_id,
                            'variant_id' => $this->variant->id,
                            'sku' => $this->variant->sku,
                            'variant' => 'Regular',
                            'price' => 20000,
                            'qty' => 1,
                            'discount_amount' => 0,
                            'subtotal' => 20000,
                        ]
                    ]
                ]
            ]);

        $checkoutResponse->assertStatus(200);
        $saleId = $checkoutResponse->json('sale_id');

        // Verify voucher is marked as used
        $this->assertDatabaseHas('member_redemptions', [
            'voucher_code' => $voucherCode,
            'is_used' => true,
            'sale_id' => $saleId,
        ]);

        // 3. Void the sale transaction
        $voidResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/pos/sales/{$saleId}/void", [
                'store_id' => $this->store->id,
            ]);

        $voidResponse->assertStatus(200);

        // 4. Verify voucher is reverted back to active / unused state
        $this->assertDatabaseHas('member_redemptions', [
            'voucher_code' => $voucherCode,
            'is_used' => false,
            'used_at' => null,
            'sale_id' => null,
        ]);
    }
}
