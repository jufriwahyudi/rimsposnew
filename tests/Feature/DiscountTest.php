<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use App\Services\FirestoreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DiscountTest extends TestCase
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
            'name' => 'Discount Test Biz',
            'code' => 'DTB',
        ]);

        $this->store = Store::create([
            'business_id'   => $this->business->id,
            'name'          => 'Discount Test Store',
            'code'          => 'DTS',
            'is_active'     => true,
            'business_type' => 'retail',
        ]);

        $this->user = User::create([
            'name'     => 'Store Admin',
            'email'    => 'admin.discount@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($this->user);
        $this->withSession([
            'store_id' => $this->store->id,
        ]);
        \App\Support\Tenant::set($this->store->id);
    }

    public function test_can_create_percentage_discount_and_calculate_correctly()
    {
        $payload = [
            'name'                => 'Promo Gajian 10%',
            'code'                => 'GAJIAN10',
            'discount_type'       => 'percentage',
            'discount_value'      => 10,
            'min_purchase_amount' => 50000,
            'max_discount_amount' => 20000,
            'target_type'         => 'all',
            'scope_type'          => 'all',
            'is_active'           => 1,
        ];

        $response = $this->postJson(route('discounts.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('discounts', [
            'store_id'            => $this->store->id,
            'name'                => 'Promo Gajian 10%',
            'code'                => 'GAJIAN10',
            'discount_type'       => 'percentage',
            'discount_value'      => 10,
            'min_purchase_amount' => 50000,
            'max_discount_amount' => 20000,
            'scope_type'          => 'all',
        ]);

        $discount = Discount::where('code', 'GAJIAN10')->first();

        // 1. Belanja dibawah minimum (Rp 40.000) -> Diskon 0
        $this->assertEquals(0.0, $discount->calculateDiscount(40000));

        // 2. Belanja normal Rp 100.000 -> Diskon 10% = Rp 10.000
        $this->assertEquals(10000.0, $discount->calculateDiscount(100000));

        // 3. Belanja besar Rp 300.000 -> 10% = Rp 30.000 tapi capped di max Rp 20.000
        $this->assertEquals(20000.0, $discount->calculateDiscount(300000));
    }

    public function test_can_create_nominal_discount()
    {
        $payload = [
            'name'                => 'Voucher Potongan 15rb',
            'code'                => 'HEMAT15',
            'discount_type'       => 'nominal',
            'discount_value'      => 15000,
            'min_purchase_amount' => 50000,
            'target_type'         => 'all',
            'scope_type'          => 'all',
            'is_active'           => 1,
        ];

        $response = $this->postJson(route('discounts.store'), $payload);
        $response->assertStatus(200);

        $discount = Discount::where('code', 'HEMAT15')->first();

        // Belanja Rp 100.000 -> Potongan flat Rp 15.000
        $this->assertEquals(15000.0, $discount->calculateDiscount(100000));

        // Belanja Rp 10.000 (jika tanpa min purchase) capped di subtotal
        $discount->min_purchase_amount = 0;
        $this->assertEquals(10000.0, $discount->calculateDiscount(10000));
    }

    public function test_can_create_product_scoped_discount_and_sync_pivot()
    {
        // 1. Buat produk dan varian
        $product = Product::create([
            'store_id'    => $this->store->id,
            'kode_produk' => 'PRD-DISC',
            'nama_produk' => 'Baju Kemeja Promo',
        ]);

        $variantA = ProductVariant::create([
            'store_id'     => $this->store->id,
            'product_id'   => $product->id,
            'variant_name' => 'Kemeja Ukuran M',
            'sku'          => 'KMJ-M',
            'barcode'      => 'BAR-KMJ-M',
            'harga_jual'   => 100000,
        ]);

        $variantB = ProductVariant::create([
            'store_id'     => $this->store->id,
            'product_id'   => $product->id,
            'variant_name' => 'Kemeja Ukuran L',
            'sku'          => 'KMJ-L',
            'barcode'      => 'BAR-KMJ-L',
            'harga_jual'   => 120000,
        ]);

        // 2. Buat promo khusus Variant A
        $payload = [
            'name'           => 'Diskon 20% Kemeja M',
            'discount_type'  => 'percentage',
            'discount_value' => 20,
            'target_type'    => 'all',
            'scope_type'     => 'product',
            'variant_ids'    => [$variantA->id],
            'is_active'      => 1,
        ];

        $response = $this->postJson(route('discounts.store'), $payload);
        $response->assertStatus(200);

        $discount = Discount::where('name', 'Diskon 20% Kemeja M')->first();
        $this->assertNotNull($discount);
        $this->assertEquals('product', $discount->scope_type);

        // Verifikasi tabel pivot discount_items
        $this->assertDatabaseHas('discount_items', [
            'discount_id'        => $discount->id,
            'product_variant_id' => $variantA->id,
            'product_id'         => $product->id,
        ]);

        // Hitung diskon: Subtotal belanja total Rp 220.000, tapi hanya Variant A (Rp 100.000) yang eligible
        // Diskon 20% dari Rp 100.000 = Rp 20.000
        $discountAmount = $discount->calculateDiscount(220000, 100000);
        $this->assertEquals(20000.0, $discountAmount);

        // 3. Update promo: Tambahkan Variant B ke promo
        $updatePayload = array_merge($payload, [
            'variant_ids' => [$variantA->id, $variantB->id],
        ]);

        $updateRes = $this->putJson(route('discounts.update', $discount->id), $updatePayload);
        $updateRes->assertStatus(200);

        $this->assertDatabaseHas('discount_items', [
            'discount_id'        => $discount->id,
            'product_variant_id' => $variantB->id,
        ]);
        $this->assertEquals(2, $discount->variants()->count());
    }

    public function test_api_pos_discounts_returns_active_promos_with_eligible_variants()
    {
        $discount = Discount::create([
            'store_id'       => $this->store->id,
            'name'           => 'Promo API Test',
            'code'           => 'APITEST',
            'discount_type'  => 'percentage',
            'discount_value' => 15,
            'target_type'    => 'all',
            'scope_type'     => 'product',
            'is_active'      => true,
        ]);

        $product = Product::create([
            'store_id'    => $this->store->id,
            'kode_produk' => 'PRD-API',
            'nama_produk' => 'Barang API',
        ]);

        $variant = ProductVariant::create([
            'store_id'     => $this->store->id,
            'product_id'   => $product->id,
            'variant_name' => 'Varian 1',
            'sku'          => 'VAR-API-1',
            'barcode'      => 'BC-VAR-API-1',
            'harga_jual'   => 50000,
        ]);

        $discount->variants()->sync([$variant->id => ['product_id' => $product->id]]);

        $response = $this->getJson('/api/pos/discounts?store_id=' . $this->store->id);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonFragment([
            'code'       => 'APITEST',
            'scope_type' => 'product',
        ]);

        // Pastikan eligible_variant_ids disertakan
        $data = $response->json('data');
        $found = collect($data)->firstWhere('code', 'APITEST');
        $this->assertNotNull($found);
        $this->assertEquals([$variant->id], $found['eligible_variant_ids']);
    }

    public function test_deactivates_discount_instead_of_delete_when_sales_exist()
    {
        $discount = Discount::create([
            'store_id'       => $this->store->id,
            'name'           => 'Promo Terpakai',
            'discount_type'  => 'percentage',
            'discount_value' => 5,
            'target_type'    => 'all',
            'scope_type'     => 'all',
            'is_active'      => true,
        ]);

        // Buat sale yang menggunakan promo ini
        Sale::create([
            'store_id'         => $this->store->id,
            'user_id'          => $this->user->id,
            'customer_name'    => 'Pelanggan Promo',
            'invoice_number'   => 'INV-DISC-01',
            'sale_date'        => now()->toDateString(),
            'subtotal'         => 100000,
            'discount_id'      => $discount->id,
            'discount_name'    => $discount->name,
            'discount_total'   => 5000,
            'grand_total'      => 95000,
            'paid_amount'      => 100000,
            'change_amount'    => 5000,
            'status'           => 'paid',
            'payment_status'   => 'lunas',
        ]);

        $response = $this->deleteJson(route('discounts.destroy', $discount->id));

        $response->assertStatus(200);
        $discount->refresh();
        $this->assertFalse((bool) $discount->is_active);
        $this->assertDatabaseHas('discounts', [
            'id'        => $discount->id,
            'is_active' => 0,
        ]);
    }
}
