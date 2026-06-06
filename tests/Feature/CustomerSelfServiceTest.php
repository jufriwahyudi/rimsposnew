<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\Tenant;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\FirestoreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CustomerSelfServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected $store;
    protected $tenant;
    protected $product;
    protected $variant;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock FirestoreService globally for tests to prevent real HTTP calls
        $this->mock(FirestoreService::class, function ($mock) {
            $mock->shouldReceive('syncStore')->andReturn(true);
            $mock->shouldReceive('syncOrder')->andReturn(true);
            $mock->shouldReceive('deleteOrder')->andReturn(true);
        });

        // 1. Create a default FnB Store with addons disabled by default
        $this->store = Store::create([
            'name' => 'Self Service Cafe',
            'code' => 'SSC01',
            'address' => 'Self Service Rd',
            'city' => 'Self Service City',
            'phone' => '08123456789',
            'is_active' => true,
            'business_type' => 'fnb',
            'addon_self_service' => false,
            'addon_kds' => false,
        ]);

        // Set active store context
        \App\Support\Tenant::set($this->store->id);

        // 2. Create Tenant
        $this->tenant = Tenant::create([
            'store_id' => $this->store->id,
            'kode_tenant' => 'TNT-SS',
            'nama_tenant' => 'Self Service Tenant',
            'commission_rate' => 10.00,
        ]);

        // 3. Create Product
        $this->product = Product::create([
            'store_id' => $this->store->id,
            'tenant_id' => $this->tenant->id,
            'kode_produk' => 'PRD-SS',
            'nama_produk' => 'Self Service Product',
        ]);

        // 4. Create Product Variant (track_stock = false to simplify tests)
        $this->variant = ProductVariant::create([
            'store_id' => $this->store->id,
            'product_id' => $this->product->id,
            'variant_name' => 'Regular',
            'sku' => 'SKU-REG',
            'barcode' => 'BC-REG',
            'harga_jual' => 15000,
            'cost_price_manual' => 5000,
            'commission_type' => 'global',
            'track_stock' => false,
            'is_active' => 'Y',
        ]);

        // 5. Create Cashier User
        $this->user = User::first() ?: User::create([
            'name' => 'Cashier User',
            'email' => 'cashier.test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->user->stores()->syncWithoutDetaching([$this->store->id]);
        
        \App\Support\Tenant::clear();
    }

    public function test_order_portal_fails_without_parameters()
    {
        $response = $this->get('/order');
        // Because of the addon:self_service middleware check, missing store_id triggers a 403 response
        $response->assertStatus(403);
    }

    public function test_order_portal_fails_with_invalid_hash()
    {
        $response = $this->get('/order?store_id=' . $this->store->id . '&table=Meja%201&hash=wronghash');
        $response->assertStatus(403); // Akses tidak sah
    }

    public function test_order_portal_blocked_when_addon_disabled()
    {
        $table = 'Meja 1';
        $hash = hash_hmac('sha256', "store_id={$this->store->id}&table={$table}", config('app.key'));

        $response = $this->get("/order?store_id={$this->store->id}&table=" . urlencode($table) . "&hash={$hash}");
        $response->assertStatus(403);
        $response->assertViewIs('self-service.addon_disabled');
    }

    public function test_order_portal_loads_when_addon_enabled()
    {
        // Enable self service addon
        $this->store->update(['addon_self_service' => true]);

        $table = 'Meja 1';
        $hash = hash_hmac('sha256', "store_id={$this->store->id}&table={$table}", config('app.key'));

        $response = $this->get("/order?store_id={$this->store->id}&table=" . urlencode($table) . "&hash={$hash}");
        $response->assertStatus(200);
        $response->assertViewIs('self-service.order_portal');
        $response->assertViewHas('store');
        $response->assertViewHas('table', $table);
    }

    public function test_submit_order_fails_with_invalid_hash()
    {
        $this->store->update(['addon_self_service' => true]);

        $response = $this->postJson('/order/submit', [
            'store_id' => $this->store->id,
            'table_number' => 'Meja 1',
            'customer_name' => 'John Doe',
            'hash' => 'wronghash',
            'items' => [
                [
                    'variant_id' => $this->variant->id,
                    'qty' => 2,
                    'notes' => 'No sugar'
                ]
            ]
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment(['message' => 'Akses tidak sah: QR Code tidak valid.']);
    }

    public function test_submit_order_creates_sale_and_triggers_sync()
    {
        $this->store->update(['addon_self_service' => true]);

        $table = 'Meja 1';
        $hash = hash_hmac('sha256', "store_id={$this->store->id}&table={$table}", config('app.key'));

        $response = $this->postJson('/order/submit', [
            'store_id' => $this->store->id,
            'table_number' => $table,
            'customer_name' => 'John Doe',
            'hash' => $hash,
            'items' => [
                [
                    'variant_id' => $this->variant->id,
                    'qty' => 2,
                    'notes' => 'No sugar'
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        
        $invoice = $response->json('invoice');
        $this->assertNotEmpty($invoice);

        // Verify database sale entry
        $sale = Sale::where('invoice_number', $invoice)->first();
        $this->assertNotNull($sale);
        $this->assertEquals('hold', $sale->status);
        $this->assertEquals('unpaid', $sale->payment_status);
        $this->assertEquals('John Doe', $sale->customer_name);
        $this->assertEquals($table, $sale->table_number);
        $this->assertEquals(30000.00, $sale->grand_total);

        // Verify sale item and notes field
        $item = $sale->items()->first();
        $this->assertNotNull($item);
        $this->assertEquals($this->variant->id, $item->product_variant_id);
        $this->assertEquals(2, $item->qty);
        $this->assertEquals('Regular', $item->product_name);
        $this->assertEquals('No sugar', $item->notes);
    }

    public function test_submit_order_merges_with_existing_unpaid_sale()
    {
        $this->store->update(['addon_self_service' => true]);

        $table = 'Meja 1';
        $hash = hash_hmac('sha256', "store_id={$this->store->id}&table={$table}", config('app.key'));

        // First submit: 2 qty of Coffee with 'No sugar' notes
        $response1 = $this->postJson('/order/submit', [
            'store_id' => $this->store->id,
            'table_number' => $table,
            'customer_name' => 'John Doe',
            'hash' => $hash,
            'items' => [
                [
                    'variant_id' => $this->variant->id,
                    'qty' => 2,
                    'notes' => 'No sugar'
                ]
            ]
        ]);

        $response1->assertStatus(200);
        $invoice1 = $response1->json('invoice');

        // Second submit (same table, same variant, same notes): 1 qty
        $response2 = $this->postJson('/order/submit', [
            'store_id' => $this->store->id,
            'table_number' => $table,
            'customer_name' => 'John Doe',
            'hash' => $hash,
            'items' => [
                [
                    'variant_id' => $this->variant->id,
                    'qty' => 1,
                    'notes' => 'No sugar'
                ]
            ]
        ]);

        $response2->assertStatus(200);
        $this->assertEquals($invoice1, $response2->json('invoice'));

        // Third submit (same table, same variant, DIFFERENT notes): 1 qty
        $response3 = $this->postJson('/order/submit', [
            'store_id' => $this->store->id,
            'table_number' => $table,
            'customer_name' => 'John Doe',
            'hash' => $hash,
            'items' => [
                [
                    'variant_id' => $this->variant->id,
                    'qty' => 1,
                    'notes' => 'Extra ice'
                ]
            ]
        ]);

        $response3->assertStatus(200);
        $this->assertEquals($invoice1, $response3->json('invoice'));

        // Verify database: Only 1 Sale record should exist with 2 distinct SaleItems
        $salesCount = Sale::where('table_number', $table)->where('store_id', $this->store->id)->count();
        $this->assertEquals(1, $salesCount);

        $sale = Sale::where('invoice_number', $invoice1)->first();
        $this->assertNotNull($sale);
        $this->assertEquals(60000.00, $sale->grand_total); // (2+1)*15k + (1)*15k = 60k
        
        $items = $sale->items()->get();
        $this->assertEquals(2, $items->count());

        $itemNoSugar = $items->where('notes', 'No sugar')->first();
        $this->assertNotNull($itemNoSugar);
        $this->assertEquals('Regular', $itemNoSugar->product_name);
        $this->assertEquals(3, $itemNoSugar->qty);

        $itemExtraIce = $items->where('notes', 'Extra ice')->first();
        $this->assertNotNull($itemExtraIce);
        $this->assertEquals('Regular', $itemExtraIce->product_name);
        $this->assertEquals(1, $itemExtraIce->qty);
    }

    public function test_order_status_page_loads_successfully()
    {
        $sale = Sale::create([
            'store_id' => $this->store->id,
            'invoice_number' => 'QR-TESTORDER',
            'table_number' => 'Meja 1',
            'sale_date' => now(),
            'sale_type' => 'retail',
            'customer_name' => 'John Doe',
            'subtotal' => 15000,
            'grand_total' => 15000,
            'status' => 'hold',
            'payment_status' => 'unpaid',
        ]);

        $response = $this->get("/order/status/{$sale->invoice_number}");
        $response->assertStatus(200);
        $response->assertViewIs('self-service.order_status');
        $response->assertViewHas('sale');
    }

    public function test_pos_confirm_self_service_order()
    {
        $sale = Sale::create([
            'store_id' => $this->store->id,
            'invoice_number' => 'QR-TESTORDER-CONFIRM',
            'table_number' => 'Meja 1',
            'sale_date' => now(),
            'sale_type' => 'retail',
            'customer_name' => 'John Doe',
            'subtotal' => 15000,
            'grand_total' => 15000,
            'status' => 'hold',
            'payment_status' => 'unpaid',
        ]);

        // Login as user to pass auth:sanctum
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/pos/self-service/{$sale->id}/confirm?store_id=" . $this->store->id);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $sale->refresh();
        $this->assertEquals('hold', $sale->status);
        $this->assertEquals($this->user->id, $sale->user_id);
    }

    public function test_pos_decline_self_service_order()
    {
        $sale = Sale::create([
            'store_id' => $this->store->id,
            'invoice_number' => 'QR-TESTORDER-DECLINE',
            'table_number' => 'Meja 1',
            'sale_date' => now(),
            'sale_type' => 'retail',
            'customer_name' => 'John Doe',
            'subtotal' => 15000,
            'grand_total' => 15000,
            'status' => 'hold',
            'payment_status' => 'unpaid',
        ]);

        // Add an item so voiding/declining can run correctly
        $item = SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'product_variant_id' => $this->variant->id,
            'sku' => $this->variant->sku,
            'product_name' => $this->product->nama_produk,
            'price' => 15000,
            'qty' => 1,
            'subtotal' => 15000,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/pos/self-service/{$sale->id}/decline?store_id=" . $this->store->id, [
                'reason' => 'Out of stock'
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Verify sale status is 'void' in database
        $sale->refresh();
        $this->assertEquals('void', $sale->status);
    }

    public function test_kitchen_blocked_when_kds_addon_disabled()
    {
        $this->store->update(['addon_kds' => false]);

        $response = $this->actingAs($this->user)
            ->withSession(['store_id' => $this->store->id])
            ->get('/kitchen');

        $response->assertStatus(403);
    }

    public function test_kitchen_allowed_when_kds_addon_enabled()
    {
        $this->store->update(['addon_kds' => true]);

        $response = $this->actingAs($this->user)
            ->withSession(['store_id' => $this->store->id])
            ->get('/kitchen');

        $response->assertStatus(200);
    }

    public function test_pos_can_fetch_pending_self_service_orders()
    {
        // Create a pending QR order (with user_id = null)
        $sale = Sale::create([
            'store_id' => $this->store->id,
            'invoice_number' => 'QR-TESTORDER-PENDING',
            'table_number' => 'Meja 1',
            'sale_date' => now(),
            'sale_type' => 'retail',
            'customer_name' => 'John Doe',
            'subtotal' => 15000,
            'grand_total' => 15000,
            'status' => 'hold',
            'payment_status' => 'unpaid',
            'user_id' => null,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/pos/self-service/pending?store_id=" . $this->store->id);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.invoice_number', 'QR-TESTORDER-PENDING');
    }

    public function test_pending_orders_do_not_show_in_kds_until_approved()
    {
        $this->store->update(['addon_kds' => true]);

        // 1. Create a pending QR order (user_id = null)
        $sale = Sale::create([
            'store_id' => $this->store->id,
            'invoice_number' => 'QR-PENDING-KDS',
            'table_number' => 'Meja 5',
            'sale_date' => now(),
            'sale_type' => 'retail',
            'customer_name' => 'Alice',
            'subtotal' => 15000,
            'grand_total' => 15000,
            'status' => 'hold',
            'payment_status' => 'unpaid',
            'user_id' => null,
        ]);

        $item = SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'product_variant_id' => $this->variant->id,
            'sku' => $this->variant->sku,
            'product_name' => $this->product->nama_produk,
            'price' => 15000,
            'qty' => 1,
            'discount_amount' => 0,
            'subtotal' => 15000,
        ]);

        // 2. Fetch KDS orders - should be empty since user_id is null
        $response = $this->actingAs($this->user)
            ->withSession(['store_id' => $this->store->id])
            ->getJson('/kitchen/orders');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');

        // 3. Confirm the order via cashier
        $confirmResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/pos/self-service/{$sale->id}/confirm?store_id=" . $this->store->id);
        $confirmResponse->assertStatus(200);

        // 4. Fetch KDS orders again - should now contain the confirmed order
        $responseAfter = $this->actingAs($this->user)
            ->withSession(['store_id' => $this->store->id])
            ->getJson('/kitchen/orders');

        $responseAfter->assertStatus(200);
        $responseAfter->assertJsonCount(1, 'data');
        $responseAfter->assertJsonPath('data.0.invoice_number', 'QR-PENDING-KDS');
        // Ensure KDS status is 'pending' (Belum Dimasak) rather than automatically set to 'cooking'
        $responseAfter->assertJsonPath('data.0.items.0.kds_status', 'pending');
    }

    public function test_confirmed_and_preparing_status_transitions()
    {
        $this->store->update(['addon_kds' => true]);

        // 1. Create a pending QR order (user_id = null)
        $sale = Sale::create([
            'store_id' => $this->store->id,
            'invoice_number' => 'QR-TRANSITION-TEST',
            'table_number' => 'Meja 5',
            'sale_date' => now(),
            'sale_type' => 'retail',
            'customer_name' => 'Alice',
            'subtotal' => 15000,
            'grand_total' => 15000,
            'status' => 'hold',
            'payment_status' => 'unpaid',
            'user_id' => null,
        ]);

        $item = SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'product_variant_id' => $this->variant->id,
            'sku' => $this->variant->sku,
            'product_name' => $this->product->nama_produk,
            'price' => 15000,
            'qty' => 1,
            'discount_amount' => 0,
            'subtotal' => 15000,
        ]);

        // 2. Initial status: should be pending
        $statusResp1 = $this->getJson("/api/order/status/{$sale->invoice_number}");
        $statusResp1->assertStatus(200);
        $statusResp1->assertJsonPath('status', 'pending');

        // 3. Confirm by cashier
        $confirmResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/pos/self-service/{$sale->id}/confirm?store_id=" . $this->store->id);
        $confirmResponse->assertStatus(200);

        // 4. Status should now be 'confirmed'
        $statusResp2 = $this->getJson("/api/order/status/{$sale->invoice_number}");
        $statusResp2->assertStatus(200);
        $statusResp2->assertJsonPath('status', 'confirmed');

        // 5. Kitchen starts cooking item
        $kitchenResp = $this->actingAs($this->user)
            ->withSession(['store_id' => $this->store->id])
            ->postJson("/kitchen/orders/{$item->id}/ready", ['status' => 'cooking']);
        $kitchenResp->assertStatus(200);

        // 6. Status should now be 'preparing'
        $statusResp3 = $this->getJson("/api/order/status/{$sale->invoice_number}");
        $statusResp3->assertStatus(200);
        $statusResp3->assertJsonPath('status', 'preparing');

        // 7. Kitchen marks item as ready
        $readyResp = $this->actingAs($this->user)
            ->withSession(['store_id' => $this->store->id])
            ->postJson("/kitchen/orders/{$item->id}/ready", ['status' => 'ready']);
        $readyResp->assertStatus(200);

        // 8. Status should now be 'served' (REST statusApi returns 'served' for completed)
        $statusResp4 = $this->getJson("/api/order/status/{$sale->invoice_number}");
        $statusResp4->assertStatus(200);
        $statusResp4->assertJsonPath('status', 'served');
    }

    public function test_cashier_order_does_not_sync_to_firestore()
    {
        $this->store->update(['addon_self_service' => true]);

        // Mock FirestoreService explicitly for this test to assert syncOrder is NOT called
        $firestoreMock = $this->mock(FirestoreService::class);
        $firestoreMock->shouldNotReceive('syncOrder');

        // Create cashier sale (starts with POS-)
        $sale = Sale::create([
            'store_id' => $this->store->id,
            'invoice_number' => 'POS-202606050001',
            'table_number' => 'Meja 5',
            'sale_date' => now(),
            'sale_type' => 'retail',
            'customer_name' => 'Cashier Customer',
            'subtotal' => 15000,
            'grand_total' => 15000,
            'status' => 'hold',
            'payment_status' => 'unpaid',
            'user_id' => $this->user->id,
        ]);

        $this->assertNotNull($sale);
    }

    public function test_admin_can_generate_and_store_qr_code()
    {
        $response = $this->actingAs($this->user)
            ->withSession(['store_id' => $this->store->id])
            ->postJson(route('settings.qr-generator.store'), [
                'table_name' => 'Meja VIP 1'
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('qr_code.table_name', 'Meja VIP 1');

        $this->assertDatabaseHas('store_qr_codes', [
            'store_id' => $this->store->id,
            'table_name' => 'Meja VIP 1'
        ]);
    }

    public function test_admin_cannot_duplicate_table_qr_code()
    {
        $qrCode = \App\Models\StoreQrCode::create([
            'store_id' => $this->store->id,
            'table_name' => 'Meja VIP 1',
            'url' => 'http://localhost/order?store_id=' . $this->store->id . '&table=Meja%20VIP%201&hash=somehash',
            'hash' => 'somehash'
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['store_id' => $this->store->id])
            ->postJson(route('settings.qr-generator.store'), [
                'table_name' => 'Meja VIP 1'
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('already_exists', true);
        $response->assertJsonPath('qr_code.id', $qrCode->id);
    }

    public function test_admin_can_download_qr_code_as_png()
    {
        $qrCode = \App\Models\StoreQrCode::create([
            'store_id' => $this->store->id,
            'table_name' => 'Meja VIP 1',
            'url' => 'http://localhost/order?store_id=' . $this->store->id . '&table=Meja%20VIP%201&hash=somehash',
            'hash' => 'somehash'
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['store_id' => $this->store->id])
            ->get(route('settings.qr-generator.download', $qrCode->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');
        $response->assertHeader('Content-Disposition', 'attachment; filename="qrcode-meja-vip-1.png"');
    }

    public function test_admin_can_delete_qr_code()
    {
        $qrCode = \App\Models\StoreQrCode::create([
            'store_id' => $this->store->id,
            'table_name' => 'Meja VIP 1',
            'url' => 'http://localhost/order?store_id=' . $this->store->id . '&table=Meja%20VIP%201&hash=somehash',
            'hash' => 'somehash'
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['store_id' => $this->store->id])
            ->deleteJson(route('settings.qr-generator.delete', $qrCode->id));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertDatabaseMissing('store_qr_codes', [
            'id' => $qrCode->id
        ]);
    }
}
