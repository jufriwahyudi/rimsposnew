<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SalesPerson;
use App\Models\StockBatch;
use App\Models\Store;
use App\Models\User;
use App\Services\FirestoreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PosCheckoutTest extends TestCase
{
    use DatabaseTransactions;

    protected $store;
    protected $business;
    protected $user;
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
            'name' => 'POS Checkout Biz',
            'code' => 'PCB',
        ]);

        $this->store = Store::create([
            'business_id'          => $this->business->id,
            'name'                 => 'POS Checkout Store',
            'code'                 => 'PCS',
            'is_active'            => true,
            'business_type'        => 'retail',
            'enable_cash_register' => false,
        ]);

        $this->user = User::create([
            'name'     => 'Kasir POS',
            'email'    => 'kasir.pos@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->user->stores()->attach($this->store->id);

        $this->actingAs($this->user);
        $this->withSession([
            'store_id' => $this->store->id,
        ]);
        \App\Support\Tenant::set($this->store->id);

        // Buat produk, varian, dan stok awal di batch
        $this->product = Product::create([
            'store_id'    => $this->store->id,
            'kode_produk' => 'PRD-POS-01',
            'nama_produk' => 'Kopi Arabika Premium',
        ]);

        $this->variant = ProductVariant::create([
            'store_id'          => $this->store->id,
            'product_id'        => $this->product->id,
            'variant_name'      => 'Kopi 250gr',
            'sku'               => 'KOP-250',
            'barcode'           => 'BC-KOP-250',
            'harga_jual'        => 50000,
            'cost_price_manual' => 25000,
            'track_stock'       => true,
            'is_active'         => true,
        ]);

        $this->batch = StockBatch::create([
            'store_id'           => $this->store->id,
            'product_variant_id' => $this->variant->id,
            'batch_number'       => 'BATCH-INIT-01',
            'posisi'             => 'store',
            'qty_masuk'          => 50,
            'qty_sisa'           => 50,
            'harga_beli'         => 25000,
            'tanggal_masuk'      => now(),
            'is_active'          => true,
        ]);
    }

    public function test_successful_cash_checkout_and_fifo_stock_deduction()
    {
        $cartPayload = [
            'payment_method'   => 'cash',
            'paid_amount'      => 100000,
            'customer_name'    => 'Pelanggan Tunai',
            'transaction_date' => now()->toDateString(),
            'subtotal'         => 100000,
            'discount_total'   => 0,
            'total'            => 100000,
            'items'            => [
                [
                    'product_id'      => $this->product->id,
                    'variant_id'      => $this->variant->id,
                    'name'            => 'Kopi 250gr',
                    'sku'             => 'KOP-250',
                    'price'           => 50000,
                    'qty'             => 2,
                    'discount_amount' => 0,
                    'subtotal'        => 100000,
                ],
            ],
        ];

        $response = $this->postJson('/api/pos/checkout', [
            'store_id' => $this->store->id,
            'cart'     => $cartPayload,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['invoice', 'sale_id']);

        $saleId = $response->json('sale_id');
        $this->assertNotNull($saleId);

        // 1. Verifikasi sale tercatat di database
        $this->assertDatabaseHas('sales', [
            'id'             => $saleId,
            'store_id'       => $this->store->id,
            'customer_name'  => 'Pelanggan Tunai',
            'subtotal'       => 100000,
            'grand_total'    => 100000,
            'paid_amount'    => 100000,
            'status'         => 'paid',
            'payment_status' => 'lunas',
        ]);

        // 2. Verifikasi item penjualan
        $this->assertDatabaseHas('sale_items', [
            'sale_id'            => $saleId,
            'product_variant_id' => $this->variant->id,
            'qty'                => 2,
            'price'              => 50000,
        ]);

        // 3. Verifikasi pemotongan stok otomatis secara FIFO (50 - 2 = 48)
        $this->batch->refresh();
        $this->assertEquals(48, $this->batch->qty_sisa);
    }

    public function test_checkout_with_discount_and_sales_person()
    {
        $discount = Discount::create([
            'store_id'       => $this->store->id,
            'name'           => 'Diskon Member 10%',
            'discount_type'  => 'percentage',
            'discount_value' => 10,
            'target_type'    => 'all',
            'scope_type'     => 'all',
            'is_active'      => true,
        ]);

        $spg = SalesPerson::create([
            'store_id'        => $this->store->id,
            'name'            => 'Sarah Pramuniaga',
            'code'            => 'SPG-02',
            'commission_rate' => 5.0,
            'is_active'       => true,
        ]);

        $cartPayload = [
            'payment_method'   => 'cash',
            'paid_amount'      => 50000,
            'customer_name'    => 'Ibu Anita',
            'transaction_date' => now()->toDateString(),
            'subtotal'         => 50000,
            'discount_total'   => 5000,
            'discount_id'      => $discount->id,
            'discount_name'    => $discount->name,
            'sales_person_id'  => $spg->id,
            'total'            => 45000,
            'items'            => [
                [
                    'product_id'      => $this->product->id,
                    'variant_id'      => $this->variant->id,
                    'name'            => 'Kopi 250gr',
                    'sku'             => 'KOP-250',
                    'price'           => 50000,
                    'qty'             => 1,
                    'discount_amount' => 0,
                    'subtotal'        => 50000,
                ],
            ],
        ];

        $response = $this->postJson('/api/pos/checkout', [
            'store_id' => $this->store->id,
            'cart'     => $cartPayload,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['invoice', 'sale_id']);

        $saleId = $response->json('sale_id');

        $this->assertDatabaseHas('sales', [
            'id'              => $saleId,
            'discount_id'     => $discount->id,
            'discount_name'   => $discount->name,
            'sales_person_id' => $spg->id,
            'discount_total'  => 5000,
            'grand_total'     => 45000,
        ]);
    }

    public function test_hold_bill_and_subsequent_completion()
    {
        // 1. Simpan order sementara (hold bill / meja)
        $holdPayload = [
            'payment_method'   => 'hold',
            'paid_amount'      => 0,
            'table_number'     => 'Meja 12',
            'customer_name'    => 'Meja 12',
            'transaction_date' => now()->toDateString(),
            'subtotal'         => 50000,
            'discount_total'   => 0,
            'total'            => 50000,
            'items'            => [
                [
                    'product_id'      => $this->product->id,
                    'variant_id'      => $this->variant->id,
                    'name'            => 'Kopi 250gr',
                    'sku'             => 'KOP-250',
                    'price'           => 50000,
                    'qty'             => 1,
                    'discount_amount' => 0,
                    'subtotal'        => 50000,
                ],
            ],
        ];

        $holdRes = $this->postJson('/api/pos/checkout', [
            'store_id' => $this->store->id,
            'cart'     => $holdPayload,
        ]);

        $holdRes->assertStatus(200);
        $holdSaleId = $holdRes->json('sale_id');

        $this->assertDatabaseHas('sales', [
            'id'             => $holdSaleId,
            'table_number'   => 'Meja 12',
            'status'         => 'hold',
            'payment_status' => 'unpaid',
        ]);

        // 2. Bayar tagihan meja yang di-hold tadi
        $payPayload = array_merge($holdPayload, [
            'existing_sale_id' => $holdSaleId,
            'payment_method'   => 'cash',
            'paid_amount'      => 50000,
        ]);

        $payRes = $this->postJson('/api/pos/checkout', [
            'store_id' => $this->store->id,
            'cart'     => $payPayload,
        ]);

        $payRes->assertStatus(200);

        $this->assertDatabaseHas('sales', [
            'id'             => $holdSaleId,
            'status'         => 'paid',
            'payment_status' => 'lunas',
        ]);
    }

    public function test_api_void_cancels_sale_and_restores_stock()
    {
        // 1. Checkout transaksi beli 3 item
        $cartPayload = [
            'payment_method'   => 'cash',
            'paid_amount'      => 150000,
            'customer_name'    => 'Void Test Customer',
            'transaction_date' => now()->toDateString(),
            'subtotal'         => 150000,
            'discount_total'   => 0,
            'total'            => 150000,
            'items'            => [
                [
                    'product_id'      => $this->product->id,
                    'variant_id'      => $this->variant->id,
                    'name'            => 'Kopi 250gr',
                    'sku'             => 'KOP-250',
                    'price'           => 50000,
                    'qty'             => 3,
                    'discount_amount' => 0,
                    'subtotal'        => 150000,
                ],
            ],
        ];

        $checkoutRes = $this->postJson('/api/pos/checkout', [
            'store_id' => $this->store->id,
            'cart'     => $cartPayload,
        ]);

        $checkoutRes->assertStatus(200);
        $saleId = $checkoutRes->json('sale_id');

        // Stok awal 50 terpotong 3 -> sisa 47
        $this->batch->refresh();
        $this->assertEquals(47, $this->batch->qty_sisa);

        // 2. Void transaksi
        $voidRes = $this->postJson("/api/pos/sales/{$saleId}/void", [
            'store_id' => $this->store->id,
            'reason'   => 'Pelanggan membatalkan pesanan',
        ]);

        $voidRes->assertStatus(200);

        // Status sale berubah menjadi void
        $this->assertDatabaseHas('sales', [
            'id'     => $saleId,
            'status' => 'void',
        ]);

        // Stok dikembalikan kembali ke batch (47 + 3 = 50)
        $this->batch->refresh();
        $this->assertEquals(50, $this->batch->qty_sisa);
    }
}
