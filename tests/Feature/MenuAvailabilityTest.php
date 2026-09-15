<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockBatch;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use App\Services\FirestoreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MenuAvailabilityTest extends TestCase
{
    use DatabaseTransactions;

    protected $store;
    protected $business;
    protected $user;
    protected $tenant;
    protected $tenantUser;
    protected $fnbProduct;
    protected $fnbVariant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(FirestoreService::class, function ($mock) {
            $mock->shouldReceive('syncStore')->andReturn(true);
        });

        $this->business = Business::create([
            'name' => 'FnB Test Biz',
            'code' => 'FNB',
        ]);

        $this->store = Store::create([
            'business_id'          => $this->business->id,
            'name'                 => 'FnB Test Resto',
            'code'                 => 'FNB01',
            'is_active'            => true,
            'business_type'        => 'fnb',
            'enable_cash_register' => false,
        ]);

        $this->user = User::create([
            'name'     => 'Kasir Utama',
            'email'    => 'kasir.fnb@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->user->stores()->attach($this->store->id);

        $this->tenant = Tenant::create([
            'store_id'    => $this->store->id,
            'kode_tenant' => 'TNT-SATE',
            'nama_tenant' => 'Tenant Sate Madura',
            'stts'        => 'Y',
        ]);

        $this->tenantUser = User::create([
            'name'      => 'Staf Tenant Sate',
            'email'     => 'staff.sate@example.com',
            'password'  => bcrypt('password'),
            'tenant_id' => $this->tenant->id,
        ]);
        $this->tenantUser->stores()->attach($this->store->id);

        $this->fnbProduct = Product::create([
            'store_id'    => $this->store->id,
            'tenant_id'   => $this->tenant->id,
            'kode_produk' => 'PRD-SATE',
            'nama_produk' => 'Sate Ayam Madura',
            'base_unit'   => 'Porsi',
        ]);

        $this->fnbVariant = ProductVariant::create([
            'store_id'     => $this->store->id,
            'product_id'   => $this->fnbProduct->id,
            'variant_name' => '10 Tusuk',
            'sku'          => 'SATE-AYAM-10',
            'barcode'      => 'BC-SATE-AYAM-10',
            'harga_jual'   => 25000,
            'track_stock'  => false,
            'is_active'    => 'Y',
            'is_available' => true,
            'daily_quota'  => null,
            'quota_date'   => null,
        ]);
    }

    public function test_get_menu_availability_api()
    {
        $response = $this->actingAs($this->user)
            ->withSession(['store_id' => $this->store->id, 'active_store_id' => $this->store->id])
            ->getJson("/api/pos/menu-availability?store_id={$this->store->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'product_id',
                    'sku',
                    'name',
                    'variant',
                    'stok',
                    'product_type',
                    'units',
                    'product_name',
                    'variant_name',
                    'is_available',
                    'daily_quota',
                    'quota_date',
                    'is_sold_out',
                    'effective_stock',
                ]
            ],
        ]);
    }

    public function test_toggle_menu_availability_and_set_quota()
    {
        // 1. Matikan ketersediaan (Habis / Sold Out 86)
        $resp1 = $this->actingAs($this->user)
            ->withSession(['store_id' => $this->store->id, 'active_store_id' => $this->store->id])
            ->postJson('/api/pos/menu-availability/update', [
                'store_id'     => $this->store->id,
                'variant_id'   => $this->fnbVariant->id,
                'is_available' => false,
            ]);

        $resp1->assertStatus(200);
        $this->fnbVariant->refresh();
        $this->assertFalse($this->fnbVariant->is_available);
        $this->assertTrue($this->fnbVariant->is_sold_out);
        $this->assertEquals(0, $this->fnbVariant->effective_stock);

        // 2. Aktifkan kembali di hari yang sama dengan kuota terbatas (contoh sisa 5 porsi)
        $resp2 = $this->actingAs($this->user)
            ->withSession(['store_id' => $this->store->id, 'active_store_id' => $this->store->id])
            ->postJson('/api/pos/menu-availability/update', [
                'store_id'     => $this->store->id,
                'variant_id'   => $this->fnbVariant->id,
                'is_available' => true,
                'daily_quota'  => 5,
            ]);

        $resp2->assertStatus(200);
        $this->fnbVariant->refresh();
        $this->assertTrue($this->fnbVariant->is_available);
        $this->assertEquals(5, $this->fnbVariant->daily_quota);
        $this->assertEquals(date('Y-m-d'), $this->fnbVariant->quota_date?->format('Y-m-d'));
        $this->assertFalse($this->fnbVariant->is_sold_out);
        $this->assertEquals(5, $this->fnbVariant->effective_stock);
    }

    public function test_tenant_can_only_update_own_menu()
    {
        // Menu milik toko umum tanpa tenant
        $otherProduct = Product::create([
            'store_id'    => $this->store->id,
            'tenant_id'   => null,
            'kode_produk' => 'PRD-TEH',
            'nama_produk' => 'Teh Manis Toko',
        ]);
        $otherVariant = ProductVariant::create([
            'store_id'     => $this->store->id,
            'product_id'   => $otherProduct->id,
            'variant_name' => 'Dingin',
            'sku'          => 'TEH-01',
            'barcode'      => 'BC-TEH-01',
            'harga_jual'   => 5000,
            'track_stock'  => false,
            'is_active'    => 'Y',
            'is_available' => true,
        ]);

        // Tenant user mencoba update menu toko umum -> ditolak 403
        $resp = $this->actingAs($this->tenantUser)
            ->withSession(['store_id' => $this->store->id, 'active_store_id' => $this->store->id])
            ->postJson('/api/pos/menu-availability/update', [
                'store_id'     => $this->store->id,
                'variant_id'   => $otherVariant->id,
                'is_available' => false,
            ]);

        $resp->assertStatus(403);

        // Tenant user update menu miliknya sendiri -> sukses 200
        $respOwn = $this->actingAs($this->tenantUser)
            ->withSession(['store_id' => $this->store->id, 'active_store_id' => $this->store->id])
            ->postJson('/api/pos/menu-availability/update', [
                'store_id'     => $this->store->id,
                'variant_id'   => $this->fnbVariant->id,
                'is_available' => false,
            ]);

        $respOwn->assertStatus(200);
    }

    public function test_physical_stock_stok_store_remains_unaffected_by_menu_availability()
    {
        // 1. Non-track stock: stok_store harus selalu 999999
        $this->assertEquals(999999, $this->fnbVariant->stok_store);

        // Saat menu di-86 (is_available = false)
        $this->fnbVariant->update(['is_available' => false]);
        $this->assertEquals(0, $this->fnbVariant->effective_stock);
        $this->assertEquals(999999, $this->fnbVariant->stok_store); // Tetap 999999, tidak rusak!

        // Saat menu diberi kuota harian 5
        $this->fnbVariant->update(['is_available' => true, 'daily_quota' => 5, 'quota_date' => now()]);
        $this->assertEquals(5, $this->fnbVariant->effective_stock);
        $this->assertEquals(999999, $this->fnbVariant->stok_store); // Tetap 999999, tidak rusak!

        // 2. Track stock variant dengan batch fisik
        $trackedProduct = Product::create([
            'store_id'    => $this->store->id,
            'kode_produk' => 'PRD-COCA',
            'nama_produk' => 'Coca Cola Kaleng',
        ]);
        $trackedVariant = ProductVariant::create([
            'store_id'     => $this->store->id,
            'product_id'   => $trackedProduct->id,
            'variant_name' => '330ml',
            'sku'          => 'COCA-330',
            'barcode'      => 'BC-COCA-330',
            'harga_jual'   => 8000,
            'track_stock'  => true,
            'is_active'    => 'Y',
            'is_available' => true,
        ]);
        StockBatch::create([
            'product_variant_id' => $trackedVariant->id,
            'posisi'             => 'store',
            'batch_number'       => 'BATCH-001',
            'tanggal_masuk'      => now()->toDateString(),
            'qty_masuk'          => 20,
            'qty_sisa'           => 20,
            'cost_price'         => 6000,
        ]);

        $trackedVariant->refresh();
        $this->assertEquals(20, $trackedVariant->stok_store);
        $this->assertEquals(20, $trackedVariant->effective_stock);

        // Jika menu ditutup (86)
        $trackedVariant->update(['is_available' => false]);
        $this->assertEquals(0, $trackedVariant->effective_stock);
        $this->assertEquals(20, $trackedVariant->stok_store); // Stok fisik tetap 20!
    }

    public function test_checkout_blocks_sold_out_item()
    {
        // Tandai habis
        $this->fnbVariant->update(['is_available' => false]);

        $cartPayload = [
            'payment_method'   => 'cash',
            'paid_amount'      => 50000,
            'customer_name'    => 'Pelanggan Meja 1',
            'transaction_date' => now()->toDateString(),
            'subtotal'         => 50000,
            'discount_total'   => 0,
            'total'            => 50000,
            'items'            => [
                [
                    'product_id'      => $this->fnbProduct->id,
                    'variant_id'      => $this->fnbVariant->id,
                    'name'            => '10 Tusuk',
                    'sku'             => 'SATE-AYAM-10',
                    'price'           => 25000,
                    'qty'             => 2,
                    'discount_amount' => 0,
                    'subtotal'        => 50000,
                ],
            ],
        ];

        $resp = $this->actingAs($this->user)
            ->withSession(['store_id' => $this->store->id, 'active_store_id' => $this->store->id])
            ->postJson('/api/pos/checkout', [
                'store_id' => $this->store->id,
                'cart'     => $cartPayload,
            ]);

        $resp->assertStatus(422);
        $resp->assertJsonFragment([
            'success' => false,
        ]);
    }

    public function test_checkout_decrements_daily_quota_and_void_restores()
    {
        // Set kuota 5 porsi
        $this->fnbVariant->update([
            'is_available' => true,
            'daily_quota'  => 5,
            'quota_date'   => date('Y-m-d'),
        ]);

        $cartPayload = [
            'payment_method'   => 'cash',
            'paid_amount'      => 100000,
            'customer_name'    => 'Pelanggan Meja 2',
            'transaction_date' => now()->toDateString(),
            'subtotal'         => 75000,
            'discount_total'   => 0,
            'total'            => 75000,
            'items'            => [
                [
                    'product_id'      => $this->fnbProduct->id,
                    'variant_id'      => $this->fnbVariant->id,
                    'name'            => '10 Tusuk',
                    'sku'             => 'SATE-AYAM-10',
                    'price'           => 25000,
                    'qty'             => 3,
                    'discount_amount' => 0,
                    'subtotal'        => 75000,
                ],
            ],
        ];

        $resp = $this->actingAs($this->user)
            ->withSession(['store_id' => $this->store->id, 'active_store_id' => $this->store->id])
            ->postJson('/api/pos/checkout', [
                'store_id' => $this->store->id,
                'cart'     => $cartPayload,
            ]);

        $resp->assertStatus(200);
        $saleId = $resp->json('sale_id');

        // Kuota harus berkurang dari 5 menjadi 2
        $this->fnbVariant->refresh();
        $this->assertEquals(2, $this->fnbVariant->daily_quota);

        // Lakukan void transaksi
        $voidResp = $this->actingAs($this->user)
            ->withSession(['store_id' => $this->store->id, 'active_store_id' => $this->store->id])
            ->postJson("/api/pos/sales/{$saleId}/void", [
                'store_id'    => $this->store->id,
                'void_reason' => 'Salah pesan',
            ]);

        $voidResp->assertStatus(200);

        // Kuota harus kembali menjadi 5
        $this->fnbVariant->refresh();
        $this->assertEquals(5, $this->fnbVariant->daily_quota);
    }

    public function test_void_does_not_reopen_deliberately_closed_menu()
    {
        // Kuota 5, jual 2 porsi
        $this->fnbVariant->update([
            'is_available' => true,
            'daily_quota'  => 5,
            'quota_date'   => date('Y-m-d'),
        ]);

        $cartPayload = [
            'payment_method'   => 'cash',
            'paid_amount'      => 50000,
            'customer_name'    => 'Pelanggan Meja 3',
            'transaction_date' => now()->toDateString(),
            'subtotal'         => 50000,
            'discount_total'   => 0,
            'total'            => 50000,
            'items'            => [
                [
                    'product_id'      => $this->fnbProduct->id,
                    'variant_id'      => $this->fnbVariant->id,
                    'name'            => '10 Tusuk',
                    'sku'             => 'SATE-AYAM-10',
                    'price'           => 25000,
                    'qty'             => 2,
                    'discount_amount' => 0,
                    'subtotal'        => 50000,
                ],
            ],
        ];

        $resp = $this->actingAs($this->user)
            ->withSession(['store_id' => $this->store->id, 'active_store_id' => $this->store->id])
            ->postJson('/api/pos/checkout', [
                'store_id' => $this->store->id,
                'cart'     => $cartPayload,
            ]);

        $resp->assertStatus(200);
        $saleId = $resp->json('sale_id');

        // Sisa kuota 3
        $this->fnbVariant->refresh();
        $this->assertEquals(3, $this->fnbVariant->daily_quota);

        // Staf sengaja menutup menu (misal kehabisan bumbu/tusuk sate)
        $this->fnbVariant->update(['is_available' => false]);

        // Transaksi di-void
        $voidResp = $this->actingAs($this->user)
            ->withSession(['store_id' => $this->store->id, 'active_store_id' => $this->store->id])
            ->postJson("/api/pos/sales/{$saleId}/void", [
                'store_id'    => $this->store->id,
                'void_reason' => 'Batal',
            ]);

        $voidResp->assertStatus(200);

        // Kuota bertambah kembali menjadi 5, TETAPI is_available TETAP FALSE (tidak otomatis hidup kembali)
        $this->fnbVariant->refresh();
        $this->assertEquals(5, $this->fnbVariant->daily_quota);
        $this->assertFalse($this->fnbVariant->is_available);
        $this->assertTrue($this->fnbVariant->is_sold_out);
    }

    public function test_yesterday_quota_does_not_block_today()
    {
        // Kuota tanggal kemarin habis (0)
        $this->fnbVariant->update([
            'is_available' => true,
            'daily_quota'  => 0,
            'quota_date'   => now()->subDays(1)->toDateString(),
        ]);

        $this->fnbVariant->refresh();
        // Karena tanggal kemarin, lazy check mengabaikan kuota kemarin
        $this->assertFalse($this->fnbVariant->is_sold_out);
        $this->assertEquals(999999, $this->fnbVariant->effective_stock);
    }
}
