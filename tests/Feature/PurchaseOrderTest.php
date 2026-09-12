<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\RoleMaster;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use App\Services\FirestoreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
{
    use DatabaseTransactions;

    protected $store;
    protected $business;
    protected $user;
    protected $role;
    protected $vendor;
    protected $product;
    protected $variant;
    protected $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(FirestoreService::class, function ($mock) {
            $mock->shouldReceive('syncStore')->andReturn(true);
        });

        $this->business = Business::create([
            'name' => 'PO Business',
            'code' => 'POBIZ',
        ]);

        $this->store = Store::create([
            'business_id'          => $this->business->id,
            'name'                 => 'PO Test Store',
            'code'                 => 'POTS',
            'is_active'            => true,
            'business_type'        => 'retail',
            'enable_cash_register' => false,
        ]);

        $this->user = User::create([
            'name'     => 'Admin Gudang',
            'email'    => 'admin.gudang@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->user->stores()->attach($this->store->id);

        $this->role = RoleMaster::create([
            'store_id'              => $this->store->id,
            'nama'                  => 'Admin Gudang Role',
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

        $this->vendor = Vendor::create([
            'store_id'    => $this->store->id,
            'kode_vendor' => 'VND-001',
            'nama_vendor' => 'PT Kimia Farma Distribusi',
            'telepon'     => '021-5551234',
            'alamat'      => 'Jl. Industri No. 12',
        ]);

        $this->product = Product::create([
            'store_id'    => $this->store->id,
            'kode_produk' => 'PRD-PO-01',
            'nama_produk' => 'Paracetamol 500mg',
        ]);

        $this->variant = ProductVariant::create([
            'store_id'          => $this->store->id,
            'product_id'        => $this->product->id,
            'variant_name'      => 'Paracetamol Tablet',
            'sku'               => 'PCT-TAB',
            'barcode'           => 'BC-PCT-TAB',
            'harga_jual'        => 2000,
            'cost_price_manual' => 1000,
            'track_stock'       => true,
            'is_active'         => true,
        ]);

        $this->unit = ProductUnit::create([
            'product_id'         => $this->product->id,
            'product_variant_id' => $this->variant->id,
            'name'               => 'Box (10 Strip)',
            'multiplier'         => 10,
            'price'              => 18000,
            'barcode'            => 'BC-BOX-10',
            'is_active'          => true,
        ]);
    }

    public function test_can_create_purchase_order_with_multi_unit_items()
    {
        $payload = [
            'vendor_id'      => $this->vendor->id,
            'notes'          => 'PO pengadaan rutin bulanan',
            'request_date'   => now()->toDateString(),
            'expected_date'  => now()->addDays(7)->toDateString(),
            'tax_total'      => 5000,
            'discount_total' => 2000,
            'items'          => [
                [
                    'variant_id'      => $this->variant->id,
                    'unit_id'         => $this->unit->id,
                    'unit_name'       => $this->unit->name,
                    'unit_multiplier' => 10,
                    'qty'             => 5, // 5 Box = 50 Tablet
                    'price'           => 15000, // Rp 15.000 per Box
                ],
            ],
        ];

        $response = $this->post(route('po.store'), $payload);

        $response->assertRedirect(route('po.index'));
        $response->assertSessionHas('success');

        // Subtotal = 5 * 15000 = 75000
        // Grand Total = (75000 - 2000) + 5000 = 78000
        $this->assertDatabaseHas('purchase_orders', [
            'store_id'       => $this->store->id,
            'vendor_id'      => $this->vendor->id,
            'status'         => 'APPROVED',
            'subtotal'       => 75000,
            'discount_total' => 2000,
            'tax_total'      => 5000,
            'grand_total'    => 78000,
        ]);

        $po = PurchaseOrder::where('vendor_id', $this->vendor->id)->first();
        $this->assertNotNull($po);

        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id'  => $po->id,
            'product_variant_id' => $this->variant->id,
            'unit_id'            => $this->unit->id,
            'unit_name'          => 'Box (10 Strip)',
            'unit_multiplier'    => 10,
            'qty_order'          => 5,
            'price'              => 15000,
            'subtotal'           => 75000,
        ]);
    }

    public function test_purchase_order_index_filtering()
    {
        $po1 = PurchaseOrder::create([
            'store_id'      => $this->store->id,
            'po_number'     => 'PO-2026-TEST-001',
            'vendor_id'     => $this->vendor->id,
            'requested_by'  => $this->user->id,
            'request_date'  => now(),
            'expected_date' => now()->addDays(3),
            'status'        => 'APPROVED',
            'subtotal'      => 100000,
            'grand_total'   => 100000,
        ]);

        $po2 = PurchaseOrder::create([
            'store_id'      => $this->store->id,
            'po_number'     => 'PO-2026-TEST-002',
            'vendor_id'     => $this->vendor->id,
            'requested_by'  => $this->user->id,
            'request_date'  => now(),
            'expected_date' => now()->addDays(3),
            'status'        => 'RECEIVED',
            'subtotal'      => 200000,
            'grand_total'   => 200000,
        ]);

        $response = $this->get(route('po.index', ['status' => 'APPROVED']));
        $response->assertStatus(200);
        $response->assertSee($po1->po_number);
        $response->assertDontSee($po2->po_number);

        $searchResponse = $this->get(route('po.index', ['po_number' => 'TEST-002']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee($po2->po_number);
        $searchResponse->assertDontSee($po1->po_number);
    }

    public function test_destroy_approved_or_draft_po_deletes_items_cascade()
    {
        $po = PurchaseOrder::create([
            'store_id'      => $this->store->id,
            'po_number'     => 'PO-TO-DELETE-01',
            'vendor_id'     => $this->vendor->id,
            'requested_by'  => $this->user->id,
            'request_date'  => now(),
            'expected_date' => now()->addDays(3),
            'status'        => 'APPROVED',
            'subtotal'      => 50000,
            'grand_total'   => 50000,
        ]);

        $item = PurchaseOrderItem::create([
            'purchase_order_id'  => $po->id,
            'product_variant_id' => $this->variant->id,
            'qty_order'          => 2,
            'price'              => 25000,
            'subtotal'           => 50000,
        ]);

        $response = $this->delete(route('po.destroy', $po));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('purchase_orders', ['id' => $po->id]);
        $this->assertDatabaseMissing('purchase_order_items', ['id' => $item->id]);
    }
}
