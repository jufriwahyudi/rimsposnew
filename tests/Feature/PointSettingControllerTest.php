<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\Business;
use App\Models\PointSetting;
use App\Models\User;
use App\Models\RoleMaster;
use App\Services\FirestoreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PointSettingControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $store;
    protected $business;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock FirestoreService
        $this->mock(FirestoreService::class, function ($mock) {
            $mock->shouldReceive('syncStore')->andReturn(true);
        });

        // 1. Create Business
        $this->business = Business::create([
            'name' => 'Point Settings Test Biz',
            'code' => 'PSTB',
        ]);

        // 2. Create Store
        $this->store = Store::create([
            'business_id' => $this->business->id,
            'name' => 'Point Settings Test Store',
            'code' => 'PSTS',
            'is_active' => true,
            'business_type' => 'retail',
        ]);

        // 3. Create User
        $this->user = User::create([
            'name' => 'Store Admin',
            'email' => 'storeadmin@example.com',
            'password' => bcrypt('password'),
        ]);

        // 4. Create RoleMaster
        $role = RoleMaster::create([
            'nama' => 'Admin',
            'role_type' => 'ADMIN',
            'stts' => 'Y',
        ]);

        // 5. Authenticate and set sessions
        $this->actingAs($this->user);
        $this->withSession([
            'selected_role' => $role->id,
            'store_id' => $this->store->id,
        ]);
    }

    public function test_save_settings_with_null_expiration_fields()
    {
        $payload = [
            'is_active' => 1,
            'is_override' => 0,
            'earning_method' => 'transaction',
            'earning_threshold' => 10000,
            'earning_points' => 1,
            'exclude_tax' => 1,
            'exclude_service_charge' => 1,
            'exclude_delivery_fee' => 1,
            'exclude_promo_items' => 0,
            'point_value' => 100,
            'min_points_to_redeem' => 10,
            'max_redeem_percentage' => 50,
            'max_redeem_amount' => 50000,
            'expiration_type' => 'never',
            'expiration_duration_months' => null, // Explicitly null
            'expiration_fixed_date' => null,      // Explicitly null
            'welcome_points' => 0,
            'birthday_multiplier' => 1.00,
        ];

        $response = $this->postJson(route('settings.points.update'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Check that the point setting was saved with default values instead of nulls
        $this->assertDatabaseHas('point_settings', [
            'business_id' => $this->business->id,
            'store_id' => null, // since is_override is 0
            'expiration_type' => 'never',
            'expiration_duration_months' => 12, // fallback applied
            'expiration_fixed_date' => '12-31', // fallback applied
        ]);
    }

    public function test_save_settings_with_product_method_omitting_earning_ratio_fields()
    {
        $payload = [
            'is_active' => 1,
            'is_override' => 0,
            'earning_method' => 'product',
            // earning_threshold and earning_points are omitted
            'exclude_tax' => 1,
            'exclude_service_charge' => 1,
            'exclude_delivery_fee' => 1,
            'exclude_promo_items' => 0,
            'point_value' => 100,
            'min_points_to_redeem' => 10,
            'max_redeem_percentage' => 50,
            'max_redeem_amount' => 50000,
            'expiration_type' => 'never',
            'expiration_duration_months' => null,
            'expiration_fixed_date' => null,
            'welcome_points' => 0,
            'birthday_multiplier' => 1.00,
        ];

        $response = $this->postJson(route('settings.points.update'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Check that the point setting was saved and earning_threshold and earning_points fell back to defaults
        $this->assertDatabaseHas('point_settings', [
            'business_id' => $this->business->id,
            'store_id' => null,
            'earning_method' => 'product',
            'earning_threshold' => 10000.00,
            'earning_points' => 1,
        ]);
    }
}

