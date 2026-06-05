<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\Business;
use App\Models\Vendor;
use App\Models\User;
use App\Models\RoleMaster;
use App\Services\FirestoreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StoreControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $role;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock FirestoreService globally for tests to prevent real HTTP calls
        $this->mock(FirestoreService::class, function ($mock) {
            $mock->shouldReceive('syncStore')->andReturn(true);
        });

        // 1. Create a User
        $this->user = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin.test@example.com',
            'password' => bcrypt('password'),
        ]);

        // 2. Create RoleMaster
        $this->role = RoleMaster::create([
            'nama' => 'Super Admin',
            'role_type' => 'SUPERADMIN',
            'stts' => 'Y',
        ]);

        // 3. Set actingAs and session
        $this->actingAs($this->user);
        $this->withSession([
            'selected_role' => $this->role->id,
            'store_id' => null,
        ]);
    }

    public function test_create_store_with_new_business()
    {
        $payload = [
            'business_id'  => 'new',
            'name'         => 'Cabang Baru Test',
            'code'         => 'CBTST',
            'address'      => 'Alamat Test',
            'city'         => 'Kota Test',
            'phone'        => '081234567890',
            'printer_type' => '58mm',
            'is_active'    => 1,
            'bussiness_type' => 'retail',
            'addon_self_service' => 0,
            'addon_kds'          => 0,
        ];

        $response = $this->postJson(route('stores.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Assert business was created matching the name and code
        $this->assertDatabaseHas('businesses', [
            'name' => 'Cabang Baru Test',
            'code' => 'CBTST',
        ]);

        $business = Business::where('code', 'CBTST')->first();
        $this->assertNotNull($business);

        // Assert store was created and linked to the new business
        $this->assertDatabaseHas('stores', [
            'business_id' => $business->id,
            'name'        => 'Cabang Baru Test',
            'code'        => 'CBTST',
        ]);

        $store = Store::where('code', 'CBTST')->first();
        $this->assertNotNull($store);

        // Assert default vendor was created
        $this->assertDatabaseHas('vendors', [
            'store_id' => $store->id,
            'kode_vendor' => 'TS-CBTST-001',
            'nama_vendor' => 'Tanpa Supplier',
        ]);
    }

    public function test_create_store_with_existing_business()
    {
        $business = Business::first(); // RimsPos Enterprise has ID 1

        $payload = [
            'business_id'  => $business->id,
            'name'         => 'Cabang Baru Test 2',
            'code'         => 'CBTST2',
            'address'      => 'Alamat Test',
            'city'         => 'Kota Test',
            'phone'        => '081234567890',
            'printer_type' => '80mm',
            'is_active'    => 1,
            'bussiness_type' => 'fnb',
            'addon_self_service' => 1,
            'addon_kds'          => 1,
        ];

        $response = $this->postJson(route('stores.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Assert store was created and linked to existing business
        $this->assertDatabaseHas('stores', [
            'business_id' => $business->id,
            'name'        => 'Cabang Baru Test 2',
            'code'        => 'CBTST2',
        ]);
    }

    public function test_create_store_validation_unique_business_code()
    {
        // Let's create a business with code 'BIZCODE'
        Business::create([
            'name' => 'Existing Business',
            'code' => 'BIZCODE'
        ]);

        // Now try to create a store with business_id = 'new' and code 'BIZCODE'
        $payload = [
            'business_id'  => 'new',
            'name'         => 'Cabang Duplicate Code',
            'code'         => 'bizcode', // case insensitive validation check
            'address'      => 'Alamat Test',
            'city'         => 'Kota Test',
            'phone'        => '081234567890',
            'printer_type' => '58mm',
            'is_active'    => 1,
            'bussiness_type' => 'retail',
        ];

        $response = $this->postJson(route('stores.store'), $payload);

        // Validation should fail due to unique code constraint in businesses table
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['code']);
    }
}
