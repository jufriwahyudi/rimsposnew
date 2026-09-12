<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Sale;
use App\Models\SalesPerson;
use App\Models\Store;
use App\Models\User;
use App\Services\FirestoreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SalesPersonTest extends TestCase
{
    use DatabaseTransactions;

    protected $store;
    protected $business;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(FirestoreService::class, function ($mock) {
            $mock->shouldReceive('syncStore')->andReturn(true);
        });

        $this->business = Business::create([
            'name' => 'SalesPerson Test Biz',
            'code' => 'SPTB',
        ]);

        $this->store = Store::create([
            'business_id'   => $this->business->id,
            'name'          => 'SalesPerson Test Store',
            'code'          => 'SPTS',
            'is_active'     => true,
            'business_type' => 'fashion',
        ]);

        $this->user = User::create([
            'name'     => 'Store Admin SPG',
            'email'    => 'admin.spg@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($this->user);
        $this->withSession([
            'store_id' => $this->store->id,
        ]);
        \App\Support\Tenant::set($this->store->id);
    }

    public function test_can_create_and_update_sales_person()
    {
        // 1. Create SalesPerson
        $payload = [
            'name'            => 'Dewi Lestari',
            'code'            => 'SPG001',
            'phone'           => '081299887766',
            'commission_rate' => 5.0,
            'is_active'       => 1,
        ];

        $response = $this->postJson(route('sales-persons.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('sales_persons', [
            'store_id'        => $this->store->id,
            'name'            => 'Dewi Lestari',
            'code'            => 'SPG001',
            'phone'           => '081299887766',
            'commission_rate' => 5.0,
        ]);

        $spg = SalesPerson::where('code', 'SPG001')->first();
        $this->assertNotNull($spg);

        // 2. Edit route returns json
        $editRes = $this->getJson(route('sales-persons.edit', $spg->id));
        $editRes->assertStatus(200);
        $editRes->assertJsonFragment(['name' => 'Dewi Lestari']);

        // 3. Update SalesPerson
        $updatePayload = [
            'name'            => 'Dewi Lestari SPG Senior',
            'code'            => 'SPG001',
            'phone'           => '081299887799',
            'commission_rate' => 7.5,
            'is_active'       => 1,
        ];

        $updateRes = $this->putJson(route('sales-persons.update', $spg->id), $updatePayload);
        $updateRes->assertStatus(200);

        $this->assertDatabaseHas('sales_persons', [
            'id'              => $spg->id,
            'name'            => 'Dewi Lestari SPG Senior',
            'commission_rate' => 7.5,
        ]);
    }

    public function test_api_pos_sales_persons_returns_active_only()
    {
        SalesPerson::create([
            'store_id'        => $this->store->id,
            'name'            => 'SPG Aktif',
            'code'            => 'SPG-ACT',
            'commission_rate' => 3.0,
            'is_active'       => true,
        ]);

        SalesPerson::create([
            'store_id'        => $this->store->id,
            'name'            => 'SPG Nonaktif',
            'code'            => 'SPG-INACT',
            'commission_rate' => 3.0,
            'is_active'       => false,
        ]);

        $response = $this->getJson('/api/pos/sales-persons?store_id=' . $this->store->id);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertTrue(collect($data)->contains('code', 'SPG-ACT'));
        $this->assertFalse(collect($data)->contains('code', 'SPG-INACT'));
    }

    public function test_deactivates_sales_person_instead_of_delete_when_sales_exist()
    {
        $spg = SalesPerson::create([
            'store_id'        => $this->store->id,
            'name'            => 'Rina Pramuniaga',
            'code'            => 'SPG-RINA',
            'commission_rate' => 5.0,
            'is_active'       => true,
        ]);

        // Buat sale yang terikat dengan SPG ini
        Sale::create([
            'store_id'         => $this->store->id,
            'user_id'          => $this->user->id,
            'sales_person_id'  => $spg->id,
            'customer_name'    => 'Pelanggan Fashion',
            'invoice_number'   => 'INV-SPG-01',
            'sale_date'        => now()->toDateString(),
            'subtotal'         => 250000,
            'grand_total'      => 250000,
            'paid_amount'      => 250000,
            'change_amount'    => 0,
            'status'           => 'paid',
            'payment_status'   => 'lunas',
        ]);

        $response = $this->deleteJson(route('sales-persons.destroy', $spg->id));

        $response->assertStatus(200);
        $spg->refresh();
        $this->assertFalse((bool) $spg->is_active);
        $this->assertDatabaseHas('sales_persons', [
            'id'        => $spg->id,
            'is_active' => 0,
        ]);
    }
}
