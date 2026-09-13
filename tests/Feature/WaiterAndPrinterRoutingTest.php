<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\RoleMaster;
use App\Models\RoleUser;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockBatch;
use App\Models\Store;
use App\Models\StorePrinter;
use App\Models\Tenant;
use App\Models\User;
use App\Services\FirestoreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WaiterAndPrinterRoutingTest extends TestCase
{
    use DatabaseTransactions;

    protected $store;
    protected $business;
    protected $waiterUser;
    protected $waiterRole;
    protected $kitchenPrinter;
    protected $barPrinter;
    protected $tenantPrinter;
    protected $tenant;
    protected $foodCategory;
    protected $drinkCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(FirestoreService::class, function ($mock) {
            $mock->shouldReceive('syncStore')->andReturn(true);
        });

        $this->business = Business::create([
            'name' => 'FnB Business',
            'code' => 'FNB',
        ]);

        $this->store = Store::create([
            'business_id'          => $this->business->id,
            'name'                 => 'Warkop & Food Court',
            'code'                 => 'WFC',
            'is_active'            => true,
            'business_type'        => 'fnb',
            'enable_cash_register' => true,
            'addon_multi_printer'  => true,
        ]);

        // Waiter role
        $this->waiterRole = RoleMaster::create([
            'store_id'              => $this->store->id,
            'nama'                  => 'Pelayan / Waiter',
            'role_type'             => 'WAITER',
            'can_access_all_divisi' => 'Y',
            'stts'                  => 'Y',
        ]);

        $this->waiterUser = User::create([
            'name'     => 'Budi Waiter',
            'email'    => 'waiter.budi@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->waiterUser->stores()->attach($this->store->id);
        RoleUser::create([
            'user_id' => $this->waiterUser->id,
            'role_id' => $this->waiterRole->id,
        ]);

        // Store Printers
        $this->kitchenPrinter = StorePrinter::create([
            'store_id'        => $this->store->id,
            'name'            => 'Dapur Utama',
            'code'            => 'kitchen',
            'connection_type' => 'lan',
            'ip_address'      => '192.168.1.201',
            'port'            => 9100,
        ]);

        $this->barPrinter = StorePrinter::create([
            'store_id'        => $this->store->id,
            'name'            => 'Bar Minuman',
            'code'            => 'bar',
            'connection_type' => 'lan',
            'ip_address'      => '192.168.1.202',
            'port'            => 9100,
        ]);

        $this->tenantPrinter = StorePrinter::create([
            'store_id'        => $this->store->id,
            'name'            => 'Cluster Barat LAN',
            'code'            => 'cluster_barat',
            'connection_type' => 'lan',
            'ip_address'      => '192.168.1.203',
            'port'            => 9100,
        ]);

        // Tenant attached to cluster printer
        $this->tenant = Tenant::create([
            'store_id'        => $this->store->id,
            'printer_id'      => $this->tenantPrinter->id,
            'kode_tenant'     => 'TNT-SATE',
            'nama_tenant'     => 'Sate Cak Har',
            'commission_rate' => 10.00,
            'stts'            => 'Y',
        ]);

        // Categories attached to Central Kitchen and Bar
        $this->foodCategory = ProductCategory::create([
            'store_id'   => $this->store->id,
            'name'       => 'Makanan',
            'printer_id' => $this->kitchenPrinter->id,
            'station'    => 'kitchen',
        ]);

        $this->drinkCategory = ProductCategory::create([
            'store_id'   => $this->store->id,
            'name'       => 'Minuman',
            'printer_id' => $this->barPrinter->id,
            'station'    => 'bar',
        ]);
    }

    public function test_waiter_can_checkout_when_payment_method_is_hold()
    {
        $this->actingAs($this->waiterUser);
        \App\Support\Tenant::set($this->store->id);

        $product = Product::create([
            'store_id'    => $this->store->id,
            'category_id' => $this->foodCategory->id,
            'kode_produk' => 'PRD-NASGOR',
            'nama_produk' => 'Nasi Goreng Spesial',
        ]);

        $variant = ProductVariant::create([
            'store_id'          => $this->store->id,
            'product_id'        => $product->id,
            'variant_name'      => 'Porsi Biasa',
            'sku'               => 'NASGOR-01',
            'barcode'           => 'BC-NASGOR-01',
            'harga_jual'        => 25000,
            'cost_price_manual' => 15000,
            'track_stock'       => false,
            'is_active'         => true,
        ]);

        $payload = [
            'store_id' => $this->store->id,
            'cart'     => [
                'payment_method'       => 'hold',
                'paid_amount'          => 0,
                'table_number'         => 'Meja 08',
                'customer_name'        => 'Tamu Meja 8',
                'subtotal'             => 25000,
                'discount_total'       => 0,
                'transaction_discount' => 0,
                'total'                => 25000,
                'items'                => [
                    [
                        'product_variant_id' => $variant->id,
                        'qty'                => 1,
                        'price'              => 25000,
                        'discount_amount'    => 0,
                        'subtotal'           => 25000,
                        'notes'              => 'Pedas sedang',
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/pos/checkout', $payload);
        $response->assertStatus(200);
        $response->assertJsonStructure(['invoice', 'sale_id']);

        $this->assertDatabaseHas('sales', [
            'store_id'     => $this->store->id,
            'table_number' => 'Meja 08',
            'status'       => 'hold',
        ]);
    }

    public function test_waiter_cannot_checkout_cash_payment()
    {
        $this->actingAs($this->waiterUser);
        \App\Support\Tenant::set($this->store->id);

        $payload = [
            'store_id' => $this->store->id,
            'cart'     => [
                'payment_method' => 'cash',
                'paid_amount'    => 50000,
                'table_number'   => 'Meja 08',
                'customer_name'  => 'Tamu Meja 8',
                'subtotal'       => 25000,
                'discount_total' => 0,
                'total'          => 25000,
                'items'          => [],
            ],
        ];

        $response = $this->postJson('/api/pos/checkout', $payload);
        $response->assertStatus(500);
        $this->assertStringContainsString('Akun Waiter hanya diizinkan', $response->json('message'));
    }

    public function test_smart_printer_routing_hierarchy()
    {
        $this->actingAs($this->waiterUser);
        \App\Support\Tenant::set($this->store->id);

        // 1. Item Food (no tenant) -> routes to Kitchen
        $foodProduct = Product::create([
            'store_id'    => $this->store->id,
            'category_id' => $this->foodCategory->id,
            'kode_produk' => 'PRD-MIE',
            'nama_produk' => 'Mie Goreng Seafood',
        ]);
        $foodVariant = ProductVariant::create([
            'store_id'     => $this->store->id,
            'product_id'   => $foodProduct->id,
            'variant_name' => 'Standar',
            'sku'          => 'MIE-01',
            'barcode'      => 'BC-MIE-01',
            'harga_jual'   => 20000,
            'track_stock'  => false,
            'is_active'    => true,
        ]);

        // 2. Item Drink (no tenant) -> routes to Bar
        $drinkProduct = Product::create([
            'store_id'    => $this->store->id,
            'category_id' => $this->drinkCategory->id,
            'kode_produk' => 'PRD-ESTEH',
            'nama_produk' => 'Es Teh Manis',
        ]);
        $drinkVariant = ProductVariant::create([
            'store_id'     => $this->store->id,
            'product_id'   => $drinkProduct->id,
            'variant_name' => 'Jumbo',
            'sku'          => 'ESTEH-01',
            'barcode'      => 'BC-ESTEH-01',
            'harga_jual'   => 5000,
            'track_stock'  => false,
            'is_active'    => true,
        ]);

        // 3. Item Tenant (with cluster printer) -> routes to Tenant Printer
        $tenantProduct = Product::create([
            'store_id'    => $this->store->id,
            'tenant_id'   => $this->tenant->id,
            'category_id' => $this->foodCategory->id,
            'kode_produk' => 'PRD-SATE',
            'nama_produk' => 'Sate Ayam 10 Tusuk',
        ]);
        $tenantVariant = ProductVariant::create([
            'store_id'     => $this->store->id,
            'product_id'   => $tenantProduct->id,
            'variant_name' => 'Bumbu Kacang',
            'sku'          => 'SATE-01',
            'barcode'      => 'BC-SATE-01',
            'harga_jual'   => 30000,
            'track_stock'  => false,
            'is_active'    => true,
        ]);

        $sale = Sale::create([
            'store_id'       => $this->store->id,
            'invoice_number' => 'INV-TEST-001',
            'sale_date'      => now(),
            'sale_type'      => 'retail',
            'table_number'   => 'Meja 12',
            'status'         => 'hold',
            'payment_status' => 'hutang',
            'subtotal'       => 55000,
            'grand_total'    => 55000,
            'paid_amount'    => 0,
            'change_amount'  => 0,
        ]);

        SaleItem::create([
            'sale_id'            => $sale->id,
            'product_id'         => $foodProduct->id,
            'product_variant_id' => $foodVariant->id,
            'product_name'       => $foodProduct->nama_produk,
            'sku'                => $foodVariant->sku,
            'qty'                => 1,
            'price'              => 20000,
            'subtotal'           => 20000,
            'kitchen_printed_qty'=> 0,
        ]);

        SaleItem::create([
            'sale_id'            => $sale->id,
            'product_id'         => $drinkProduct->id,
            'product_variant_id' => $drinkVariant->id,
            'product_name'       => $drinkProduct->nama_produk,
            'sku'                => $drinkVariant->sku,
            'qty'                => 2,
            'price'              => 5000,
            'subtotal'           => 10000,
            'kitchen_printed_qty'=> 0,
        ]);

        SaleItem::create([
            'sale_id'            => $sale->id,
            'product_id'         => $tenantProduct->id,
            'product_variant_id' => $tenantVariant->id,
            'product_name'       => $tenantProduct->nama_produk,
            'sku'                => $tenantVariant->sku,
            'qty'                => 1,
            'price'              => 30000,
            'subtotal'           => 30000,
            'kitchen_printed_qty'=> 0,
        ]);

        // Test receipt filter for kitchen station
        $resKitchen = $this->getJson("/api/pos/sales/{$sale->id}/receipt?store_id={$this->store->id}&checklist=1&station=kitchen");
        $resKitchen->assertStatus(200);

        // Test receipt filter for bar station
        $resBar = $this->getJson("/api/pos/sales/{$sale->id}/receipt?store_id={$this->store->id}&checklist=1&station=bar");
        $resBar->assertStatus(200);

        // Test receipt filter for cluster tenant printer station
        $resTenant = $this->getJson("/api/pos/sales/{$sale->id}/receipt?store_id={$this->store->id}&checklist=1&station=cluster_barat");
        $resTenant->assertStatus(200);
    }
}
