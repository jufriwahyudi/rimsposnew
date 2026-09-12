<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\ProductVariant;
use App\Models\RoleMaster;
use App\Models\StockBatch;
use App\Models\Store;
use App\Models\User;
use App\Services\FirestoreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductMultiUnitTest extends TestCase
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
            'name' => 'MultiUnit Biz',
            'code' => 'MUB',
        ]);

        $this->store = Store::create([
            'business_id'          => $this->business->id,
            'name'                 => 'MultiUnit Store',
            'code'                 => 'MUS',
            'is_active'            => true,
            'business_type'        => 'retail',
            'addon_multi_unit'     => true,
            'enable_cash_register' => false,
        ]);

        $this->user = User::create([
            'name'     => 'Staff Inventory',
            'email'    => 'staff.inv@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->user->stores()->attach($this->store->id);

        $this->role = RoleMaster::create([
            'store_id'              => $this->store->id,
            'nama'                  => 'Inventory Admin',
            'role_type'             => 'ADMIN',
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
            'kode_produk' => 'PRD-MULTI-01',
            'nama_produk' => 'Vitamin C 500mg',
            'base_unit'   => 'Tablet',
        ]);

        $this->variant = ProductVariant::create([
            'store_id'          => $this->store->id,
            'product_id'        => $this->product->id,
            'variant_name'      => 'Vitamin C Regular',
            'sku'               => 'VITC-REG',
            'barcode'           => 'BC-VITC-REG',
            'harga_jual'        => 1000,
            'cost_price_manual' => 500,
            'track_stock'       => true,
            'is_active'         => true,
        ]);

        $this->batch = StockBatch::create([
            'store_id'           => $this->store->id,
            'product_variant_id' => $this->variant->id,
            'batch_number'       => 'BATCH-VITC-01',
            'posisi'             => 'store',
            'qty_masuk'          => 200,
            'qty_sisa'           => 200,
            'harga_beli'         => 500,
            'tanggal_masuk'      => now(),
            'is_active'          => true,
        ]);
    }

    public function test_can_update_product_with_hierarchical_multi_units()
    {
        $payload = [
            'nama'      => 'Vitamin C 500mg Updated',
            'base_unit' => 'Tablet',
            'units'     => [
                [
                    'name'       => 'Strip',
                    'multiplier' => 10,
                    'price'      => 9500,
                    'barcode'    => 'BC-STRIP-VITC',
                ],
                [
                    'name'       => 'Box',
                    'multiplier' => 100,
                    'price'      => 90000,
                    'barcode'    => 'BC-BOX-VITC',
                ],
            ],
        ];

        $response = $this->put(route('produk.update', $this->product->id), $payload);
        $response->assertRedirect(route('produk.edit', $this->product->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('product_units', [
            'product_id' => $this->product->id,
            'name'       => 'Strip',
            'multiplier' => 10,
            'price'      => 9500,
            'barcode'    => 'BC-STRIP-VITC',
        ]);

        $this->assertDatabaseHas('product_units', [
            'product_id' => $this->product->id,
            'name'       => 'Box',
            'multiplier' => 100,
            'price'      => 90000,
            'barcode'    => 'BC-BOX-VITC',
        ]);
    }

    public function test_pos_api_barcode_lookup_recognizes_scanned_unit()
    {
        $unit = ProductUnit::create([
            'product_id'         => $this->product->id,
            'product_variant_id' => $this->variant->id,
            'name'               => 'Strip (10 Tab)',
            'multiplier'         => 10,
            'price'              => 9500,
            'barcode'            => 'BC-SCAN-STRIP',
            'is_active'          => true,
        ]);

        $response = $this->getJson('/api/pos/product?q=BC-SCAN-STRIP&store_id=' . $this->store->id);
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertEquals('single', $json['type']);
        $this->assertArrayHasKey('scanned_unit', $json['data']);
        $this->assertEquals($unit->id, $json['data']['scanned_unit']['id']);
        $this->assertEquals(10, $json['data']['scanned_unit']['multiplier']);
        $this->assertEquals(9500, $json['data']['scanned_unit']['price']);
    }

    public function test_pos_checkout_multi_unit_deducts_converted_stock_fifo()
    {
        // Pembelian 2 Strip (masing-masing multiplier 10 -> total 20 tablet dikurangkan)
        $cartPayload = [
            'payment_method'   => 'cash',
            'paid_amount'      => 20000,
            'customer_name'    => 'Pelanggan Multi-Satuan',
            'transaction_date' => now()->toDateString(),
            'subtotal'         => 19000,
            'discount_total'   => 0,
            'total'            => 19000,
            'items'            => [
                [
                    'product_id'      => $this->product->id,
                    'variant_id'      => $this->variant->id,
                    'name'            => 'Vitamin C (Strip)',
                    'sku'             => 'VITC-REG',
                    'price'           => 9500,
                    'qty'             => 2,
                    'unit_multiplier' => 10,
                    'unit_name'       => 'Strip',
                    'discount_amount' => 0,
                    'subtotal'        => 19000,
                ],
            ],
        ];

        $response = $this->postJson('/api/pos/checkout', [
            'store_id' => $this->store->id,
            'cart'     => $cartPayload,
        ]);

        $response->assertStatus(200);
        $saleId = $response->json('sale_id');
        $this->assertNotNull($saleId);

        // 1. Verifikasi sale item menyimpan multiplier
        $this->assertDatabaseHas('sale_items', [
            'sale_id'            => $saleId,
            'product_variant_id' => $this->variant->id,
            'qty'                => 2,
            'unit_multiplier'    => 10,
        ]);

        // 2. Stok awal 200 terpotong (2 * 10 = 20) -> sisa 180
        $this->batch->refresh();
        $this->assertEquals(180, $this->batch->qty_sisa);
    }
}
