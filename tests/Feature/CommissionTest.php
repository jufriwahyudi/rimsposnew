<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\Tenant;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CommissionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_fnb_store_captures_commission_on_sale_items()
    {
        // 1. Create FnB Store
        $store = Store::create([
            'name' => 'Test Cafe',
            'code' => 'TC01',
            'address' => 'Test Address',
            'city' => 'Test City',
            'phone' => '123456789',
            'is_active' => true,
            'business_type' => 'fnb',
        ]);

        // 2. Set active store in Tenant helper
        \App\Support\Tenant::set($store->id);

        // 3. Create Tenant
        $tenant = Tenant::create([
            'store_id' => $store->id,
            'kode_tenant' => 'TNT01',
            'nama_tenant' => 'Test Tenant',
            'commission_rate' => 15.00,
        ]);

        // 4. Create Product with Tenant
        $product = Product::create([
            'store_id' => $store->id,
            'tenant_id' => $tenant->id,
            'kode_produk' => 'PRD01',
            'nama_produk' => 'Test Product',
        ]);

        // 5. Create Product Variants
        // Case A: Percentage variant commission (10%)
        $variant1 = ProductVariant::create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'variant_name' => 'Variant A',
            'sku' => 'SKU-01',
            'barcode' => 'BC01',
            'harga_jual' => 10000,
            'cost_price_manual' => 2000,
            'commission_type' => 'percentage',
            'commission_rate' => 10.00,
            'is_active' => 'Y',
        ]);

        // Case B: Nominal variant commission (Rp 1500)
        $variant2 = ProductVariant::create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'variant_name' => 'Variant B',
            'sku' => 'SKU-02',
            'barcode' => 'BC02',
            'harga_jual' => 12000,
            'cost_price_manual' => 3000,
            'commission_type' => 'nominal',
            'commission_rate' => 1500.00,
            'is_active' => 'Y',
        ]);

        // Case C: Global tenant commission (uses tenant's 15% rate)
        $variant3 = ProductVariant::create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'variant_name' => 'Variant C',
            'sku' => 'SKU-03',
            'barcode' => 'BC03',
            'harga_jual' => 20000,
            'cost_price_manual' => 4000,
            'commission_type' => 'global',
            'is_active' => 'Y',
        ]);

        // 6. Create User / Cashier
        $user = User::first() ?: User::create([
            'name' => 'Test Cashier',
            'email' => 'cashier@test.com',
            'password' => bcrypt('password'),
        ]);

        // 7. Create Sale
        $sale = Sale::create([
            'store_id' => $store->id,
            'invoice_number' => 'INV-001',
            'sale_date' => now(),
            'sale_type' => 'retail',
            'user_id' => $user->id,
            'subtotal' => 42000,
            'grand_total' => 42000,
            'status' => 'paid',
            'payment_status' => 'lunas',
        ]);

        // 8. Create Sale Items and verify captured commission
        $item1 = SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant1->id,
            'sku' => $variant1->sku,
            'product_name' => $product->nama_produk,
            'price' => 10000,
            'qty' => 1,
            'subtotal' => 10000,
            'status' => 'sold',
        ]);

        $item2 = SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant2->id,
            'sku' => $variant2->sku,
            'product_name' => $product->nama_produk,
            'price' => 12000,
            'qty' => 1,
            'subtotal' => 12000,
            'status' => 'sold',
        ]);

        $item3 = SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant3->id,
            'sku' => $variant3->sku,
            'product_name' => $product->nama_produk,
            'price' => 20000,
            'qty' => 1,
            'subtotal' => 20000,
            'status' => 'sold',
        ]);

        // Assert Model accessors
        $this->assertEquals('percentage', $item1->commission_type);
        $this->assertEquals(10.00, $item1->commission_rate);
        $this->assertEquals(1000.00, $item1->commission_amount);
        $this->assertEquals(2000.00, $item1->cost_price);

        $this->assertEquals('nominal', $item2->commission_type);
        $this->assertEquals(1500.00, $item2->commission_rate);
        $this->assertEquals(1500.00, $item2->commission_amount);
        $this->assertEquals(3000.00, $item2->cost_price);

        $this->assertEquals('global', $item3->commission_type);
        $this->assertEquals(15.00, $item3->commission_rate);
        $this->assertEquals(3000.00, $item3->commission_amount);
        $this->assertEquals(4000.00, $item3->cost_price);

        // Assert direct database records in helper table
        $this->assertDatabaseHas('sale_item_fnb_details', [
            'sale_item_id' => $item1->id,
            'cost_price' => 2000.00,
            'commission_type' => 'percentage',
            'commission_rate' => 10.00,
            'commission_amount' => 1000.00,
        ]);

        $this->assertDatabaseHas('sale_item_fnb_details', [
            'sale_item_id' => $item2->id,
            'cost_price' => 3000.00,
            'commission_type' => 'nominal',
            'commission_rate' => 1500.00,
            'commission_amount' => 1500.00,
        ]);

        $this->assertDatabaseHas('sale_item_fnb_details', [
            'sale_item_id' => $item3->id,
            'cost_price' => 4000.00,
            'commission_type' => 'global',
            'commission_rate' => 15.00,
            'commission_amount' => 3000.00,
        ]);

        // Cleanup active store
        \App\Support\Tenant::clear();
    }

    public function test_retail_store_does_not_capture_commission_on_sale_items()
    {
        // 1. Create Retail Store
        $store = Store::create([
            'name' => 'Test Retail',
            'code' => 'TR01',
            'address' => 'Test Address',
            'city' => 'Test City',
            'phone' => '123456789',
            'is_active' => true,
            'business_type' => 'retail',
        ]);

        // 2. Set active store in Tenant helper
        \App\Support\Tenant::set($store->id);

        // 3. Create Tenant
        $tenant = Tenant::create([
            'store_id' => $store->id,
            'kode_tenant' => 'TNT02',
            'nama_tenant' => 'Test Tenant 2',
            'commission_rate' => 15.00,
        ]);

        // 4. Create Product with Tenant
        $product = Product::create([
            'store_id' => $store->id,
            'tenant_id' => $tenant->id,
            'kode_produk' => 'PRD02',
            'nama_produk' => 'Test Product Retail',
        ]);

        // 5. Create Product Variant with commission set (e.g. percentage 10%)
        $variant = ProductVariant::create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'variant_name' => 'Variant Retail',
            'sku' => 'SKU-RET',
            'barcode' => 'BCRET',
            'harga_jual' => 10000,
            'cost_price_manual' => 2000,
            'commission_type' => 'percentage',
            'commission_rate' => 10.00,
            'is_active' => 'Y',
        ]);

        // 6. Create User / Cashier
        $user = User::first() ?: User::create([
            'name' => 'Test Cashier',
            'email' => 'cashier2@test.com',
            'password' => bcrypt('password'),
        ]);

        // 7. Create Sale
        $sale = Sale::create([
            'store_id' => $store->id,
            'invoice_number' => 'INV-002',
            'sale_date' => now(),
            'sale_type' => 'retail',
            'user_id' => $user->id,
            'subtotal' => 10000,
            'grand_total' => 10000,
            'status' => 'paid',
            'payment_status' => 'lunas',
        ]);

        // 8. Create Sale Item
        $item = SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'sku' => $variant->sku,
            'product_name' => $product->nama_produk,
            'price' => 10000,
            'qty' => 1,
            'subtotal' => 10000,
            'status' => 'sold',
        ]);

        // Assert that commission is NULL or 0 because it's a Retail store
        $this->assertNull($item->commission_type);
        $this->assertEquals(0.00, $item->commission_amount);
        $this->assertEquals(0.00, $item->commission_rate);
        $this->assertEquals(0.00, $item->cost_price);

        // Assert database does not have a helper row for this item
        $this->assertDatabaseMissing('sale_item_fnb_details', [
            'sale_item_id' => $item->id,
        ]);

        // Cleanup active store
        \App\Support\Tenant::clear();
    }
}
