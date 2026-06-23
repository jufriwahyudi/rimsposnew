<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use App\Models\RoleMaster;
use App\Models\Customer;
use App\Models\CustomerCustomField;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LaporanCustomerTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $role;
    protected $store;
    protected $customer;
    protected $customField;

    protected function setUp(): void
    {
        parent::setUp();

        // 0. Create a Business
        $business = \App\Models\Business::create([
            'name' => 'Business Test',
            'code' => 'BIZTEST',
        ]);

        // 1. Create a Store
        $this->store = Store::create([
            'business_id' => $business->id,
            'name' => 'Toko Laporan Test',
            'code' => 'TLT01',
            'is_active' => 1,
        ]);
        \App\Support\Tenant::set($this->store->id);

        // 2. Create User
        $this->user = User::create([
            'name' => 'Admin Test Laporan',
            'email' => 'admin.laporan@example.com',
            'password' => bcrypt('password'),
            'store_id' => $this->store->id,
        ]);

        // 3. Create RoleMaster
        $this->role = RoleMaster::create([
            'nama' => 'Admin Laporan',
            'role_type' => 'ADMIN',
            'stts' => 'Y',
        ]);

        // 4. Authenticate user
        $this->actingAs($this->user);
        $this->withSession([
            'selected_role' => $this->role->id,
            'store_id' => $this->store->id,
            'store_name' => $this->store->name,
        ]);

        // 5. Create Customer Custom Field
        $this->customField = CustomerCustomField::create([
            'store_id' => $this->store->id,
            'name' => 'kecamatan',
            'label' => 'Kecamatan',
            'type' => 'text',
            'is_required' => false,
        ]);

        // 6. Create Customer with Custom Field Values
        $this->customer = Customer::create([
            'store_id' => $this->store->id,
            'name' => 'Mitra Test Customer Report',
            'phone' => '08987654321',
            'alamat' => 'Jalan Laporan No 1',
            'custom_values' => [
                'kecamatan' => 'Wonokromo'
            ]
        ]);
    }

    public function test_laporan_customer_page_renders_successfully()
    {
        $response = $this->get(route('laporan.customer'));

        $response->assertStatus(200);
        $response->assertSee('Laporan Customer / Mitra');
    }

    public function test_get_laporan_customer_data_returns_table()
    {
        $response = $this->post(route('laporan.customer.data'), [
            'search' => 'Mitra Test',
        ], [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);

        $response->assertStatus(200);
        $response->assertSee('Mitra Test Customer Report');
        $response->assertSee('08987654321');
        $response->assertSee('Jalan Laporan No 1');
        $response->assertSee('Kecamatan');
        $response->assertSee('Wonokromo');
    }

    public function test_export_laporan_customer_excel()
    {
        $response = $this->get(route('laporan.customer.export', [
            'search' => 'Mitra Test',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }
}
