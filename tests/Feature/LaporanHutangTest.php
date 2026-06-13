<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use App\Models\RoleMaster;
use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LaporanHutangTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $role;
    protected $store;
    protected $customer;

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

        // 5. Create Customer
        $this->customer = Customer::create([
            'store_id' => $this->store->id,
            'name' => 'Mitra Test Hutang',
            'phone' => '08987654321',
            'alamat' => 'Jalan Laporan No 1',
        ]);

        // 6. Create Sale with debt (payment_status = 'hutang')
        Sale::create([
            'store_id' => $this->store->id,
            'invoice_number' => 'INV-TEST-DEBT',
            'sale_date' => now(),
            'sale_type' => 'retail',
            'customer_id' => $this->customer->id,
            'subtotal' => 100000,
            'grand_total' => 100000,
            'paid_amount' => 30000,
            'change_amount' => 0,
            'status' => 'paid',
            'payment_status' => 'hutang',
        ]);
    }

    public function test_laporan_hutang_page_renders_successfully()
    {
        $response = $this->get(route('laporan.hutang'));

        $response->assertStatus(200);
        $response->assertSee('Laporan Hutang per Mitra/Pelanggan');
    }

    public function test_get_laporan_hutang_data_returns_table()
    {
        $response = $this->post(route('laporan.hutang.data'), [
            'mulai' => now()->startOfMonth()->toDateString(),
            'akhir' => now()->toDateString(),
        ], [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);

        $response->assertStatus(200);
        $response->assertSee('Mitra Test Hutang');
        $response->assertSee('Rp 100.000');
        $response->assertSee('Rp 30.000');
        $response->assertSee('Rp 70.000'); // Sisa hutang
    }

    public function test_export_laporan_hutang_excel()
    {
        $response = $this->get(route('laporan.hutang.export', [
            'mulai' => now()->startOfMonth()->toDateString(),
            'akhir' => now()->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }
}
