<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\CashRegister;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RoleMaster;
use App\Models\StockBatch;
use App\Models\Store;
use App\Models\User;
use App\Services\FirestoreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CashRegisterShiftTest extends TestCase
{
    use DatabaseTransactions;

    protected $store;
    protected $business;
    protected $user;
    protected $role;
    protected $product;
    protected $variant;
    protected $batch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(FirestoreService::class, function ($mock) {
            $mock->shouldReceive('syncStore')->andReturn(true);
        });

        $this->business = Business::create([
            'name' => 'Cashier Shift Biz',
            'code' => 'CSB',
        ]);

        $this->store = Store::create([
            'business_id'          => $this->business->id,
            'name'                 => 'Cashier Shift Store',
            'code'                 => 'CSS',
            'is_active'            => true,
            'business_type'        => 'retail',
            'enable_cash_register' => true,
        ]);

        $this->user = User::create([
            'name'     => 'Kasir Shift 1',
            'email'    => 'kasir.shift@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->user->stores()->attach($this->store->id);

        $this->role = RoleMaster::create([
            'store_id'              => $this->store->id,
            'nama'                  => 'Kasir Role',
            'role_type'             => 'STORE',
            'can_access_all_divisi' => 'Y',
            'stts'                  => 'Y',
        ]);

        $this->actingAs($this->user);
        $this->withSession([
            'store_id'      => $this->store->id,
            'selected_role' => $this->role->id,
        ]);
        \App\Support\Tenant::set($this->store->id);

        $this->product = Product::create([
            'store_id'    => $this->store->id,
            'kode_produk' => 'PRD-SHF-01',
            'nama_produk' => 'Air Mineral 600ml',
        ]);

        $this->variant = ProductVariant::create([
            'store_id'          => $this->store->id,
            'product_id'        => $this->product->id,
            'variant_name'      => 'Air Mineral Botol',
            'sku'               => 'AIR-600',
            'barcode'           => 'BC-AIR-600',
            'harga_jual'        => 5000,
            'cost_price_manual' => 2500,
            'track_stock'       => true,
            'is_active'         => true,
        ]);

        $this->batch = StockBatch::create([
            'store_id'           => $this->store->id,
            'product_variant_id' => $this->variant->id,
            'batch_number'       => 'BATCH-AIR-01',
            'posisi'             => 'store',
            'qty_masuk'          => 100,
            'qty_sisa'           => 100,
            'harga_beli'         => 2500,
            'tanggal_masuk'      => now(),
            'is_active'          => true,
        ]);
    }

    public function test_full_cashier_shift_workflow_open_movement_checkout_and_close()
    {
        // 1. Cek status sebelum buka kasir -> is_open = false
        $statusBefore = $this->getJson('/api/pos/cash-register/status?store_id=' . $this->store->id);
        $statusBefore->assertStatus(200);
        $this->assertFalse($statusBefore->json('is_open'));

        // 2. Buka kasir dengan modal awal Rp 100.000
        $openResponse = $this->postJson('/api/pos/cash-register/open', [
            'store_id'     => $this->store->id,
            'opening_cash' => 100000,
            'notes'        => 'Shift pagi kasir 1',
        ]);
        $openResponse->assertStatus(200);
        $openResponse->assertJson(['success' => true]);

        $this->assertDatabaseHas('cash_registers', [
            'store_id'     => $this->store->id,
            'user_id'      => $this->user->id,
            'opening_cash' => 100000,
            'status'       => 'open',
        ]);

        $register = CashRegister::where('store_id', $this->store->id)
            ->where('user_id', $this->user->id)
            ->where('status', 'open')
            ->first();
        $this->assertNotNull($register);

        // 3. Catat Kas Masuk (Petty Cash In) Rp 50.000
        $cashInRes = $this->postJson('/api/pos/cash-register/movement', [
            'store_id' => $this->store->id,
            'type'     => 'cash_in',
            'amount'   => 50000,
            'notes'    => 'Tambahan uang kembalian pecahan kecil',
        ]);
        $cashInRes->assertStatus(200);
        $cashInRes->assertJson(['success' => true]);

        // 4. Catat Kas Keluar (Petty Cash Out) Rp 20.000
        $cashOutRes = $this->postJson('/api/pos/cash-register/movement', [
            'store_id' => $this->store->id,
            'type'     => 'cash_out',
            'amount'   => 20000,
            'notes'    => 'Beli pembersih lantai',
        ]);
        $cashOutRes->assertStatus(200);
        $cashOutRes->assertJson(['success' => true]);

        // 5. Transaksi POS checkout tunai Rp 10.000 (2 botol air @ 5000)
        $cartPayload = [
            'payment_method'   => 'cash',
            'paid_amount'      => 10000,
            'cash_amount'      => 10000,
            'customer_name'    => 'Pelanggan Toko',
            'transaction_date' => now()->toDateString(),
            'subtotal'         => 10000,
            'discount_total'   => 0,
            'total'            => 10000,
            'items'            => [
                [
                    'product_id'      => $this->product->id,
                    'variant_id'      => $this->variant->id,
                    'name'            => 'Air Mineral Botol',
                    'sku'             => 'AIR-600',
                    'price'           => 5000,
                    'qty'             => 2,
                    'discount_amount' => 0,
                    'subtotal'        => 10000,
                ],
            ],
        ];

        $checkoutRes = $this->postJson('/api/pos/checkout', [
            'store_id' => $this->store->id,
            'cart'     => $cartPayload,
        ]);
        $checkoutRes->assertStatus(200);
        $saleId = $checkoutRes->json('sale_id');

        // Sale terhubung dengan cash register aktif
        $this->assertDatabaseHas('sales', [
            'id'               => $saleId,
            'cash_register_id' => $register->id,
        ]);

        // 6. Cek Ringkasan Kasir (Summary)
        // Expected Cash = 100.000 (modal) + 50.000 (kas masuk) - 20.000 (kas keluar) + 10.000 (penjualan tunai) = 140.000
        $summaryRes = $this->getJson('/api/pos/cash-register/summary?store_id=' . $this->store->id);
        $summaryRes->assertStatus(200);

        $summary = $summaryRes->json('summary');
        $this->assertEquals(100000, $summary['opening_cash']);
        $this->assertEquals(50000, $summary['cash_in']);
        $this->assertEquals(20000, $summary['cash_out']);
        $this->assertEquals(10000, $summary['cash_sales']);
        $this->assertEquals(140000, $summary['expected_cash']);

        // 7. Tutup kasir dengan uang fisik Rp 145.000 (ada lebihan / selisih +5.000)
        $closeRes = $this->postJson('/api/pos/cash-register/close', [
            'store_id'    => $this->store->id,
            'actual_cash' => 145000,
            'notes'       => 'Tutup shift sore, ada lebihan kas 5.000',
        ]);
        $closeRes->assertStatus(200);
        $closeRes->assertJson(['success' => true]);

        // Verifikasi status kasir berubah menjadi closed dengan perhitungan selisih akurat
        $this->assertDatabaseHas('cash_registers', [
            'id'              => $register->id,
            'status'          => 'closed',
            'opening_cash'    => 100000,
            'total_cash_in'   => 50000,
            'total_cash_out'  => 20000,
            'total_cash_sales'=> 10000,
            'expected_cash'   => 140000,
            'actual_cash'     => 145000,
            'cash_difference' => 5000,
        ]);
    }

    public function test_cannot_checkout_when_cash_register_is_required_but_closed()
    {
        // Pastikan tidak ada sesi kasir aktif
        $this->assertDatabaseMissing('cash_registers', [
            'store_id' => $this->store->id,
            'user_id'  => $this->user->id,
            'status'   => 'open',
        ]);

        $cartPayload = [
            'payment_method'   => 'cash',
            'paid_amount'      => 5000,
            'cash_amount'      => 5000,
            'customer_name'    => 'Pelanggan Toko',
            'transaction_date' => now()->toDateString(),
            'subtotal'         => 5000,
            'discount_total'   => 0,
            'total'            => 5000,
            'items'            => [
                [
                    'product_id'      => $this->product->id,
                    'variant_id'      => $this->variant->id,
                    'name'            => 'Air Mineral Botol',
                    'sku'             => 'AIR-600',
                    'price'           => 5000,
                    'qty'             => 1,
                    'discount_amount' => 0,
                    'subtotal'        => 5000,
                ],
            ],
        ];

        $response = $this->postJson('/api/pos/checkout', [
            'store_id' => $this->store->id,
            'cart'     => $cartPayload,
        ]);

        $response->assertStatus(500);
        $response->assertJsonFragment([
            'message' => 'Transaksi gagal: Kasir belum dibuka. Silakan lakukan Buka Kasir terlebih dahulu.',
        ]);
    }
}
