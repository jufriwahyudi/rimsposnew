<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\RoleMaster;
use App\Models\StockBatch;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use App\Services\FirestoreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GoodsReceiptTest extends TestCase
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
    protected $po;
    protected $poItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(FirestoreService::class, function ($mock) {
            $mock->shouldReceive('syncStore')->andReturn(true);
        });

        $this->business = Business::create([
            'name' => 'GR Business',
            'code' => 'GRBIZ',
        ]);

        $this->store = Store::create([
            'business_id'          => $this->business->id,
            'name'                 => 'GR Test Store',
            'code'                 => 'GRTS',
            'is_active'            => true,
            'business_type'        => 'retail',
            'enable_cash_register' => false,
        ]);

        $this->user = User::create([
            'name'     => 'Staff Penerimaan Gudang',
            'email'    => 'staff.gudang@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->user->stores()->attach($this->store->id);

        $this->role = RoleMaster::create([
            'store_id'              => $this->store->id,
            'nama'                  => 'Warehouse Role',
            'role_type'             => 'WAREHOUSE',
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
            'kode_vendor' => 'VND-GR-01',
            'nama_vendor' => 'PT Distribusi Farmasi',
        ]);

        $this->product = Product::create([
            'store_id'    => $this->store->id,
            'kode_produk' => 'PRD-GR-01',
            'nama_produk' => 'Amoxicillin 500mg',
        ]);

        $this->variant = ProductVariant::create([
            'store_id'          => $this->store->id,
            'product_id'        => $this->product->id,
            'variant_name'      => 'Amoxicillin Kaplet',
            'sku'               => 'AMX-500',
            'barcode'           => 'BC-AMX-500',
            'harga_jual'        => 3000,
            'cost_price_manual' => 1500,
            'track_stock'       => true,
            'is_active'         => true,
        ]);

        $this->unit = ProductUnit::create([
            'product_id'         => $this->product->id,
            'product_variant_id' => $this->variant->id,
            'name'               => 'Box (10 Strip)',
            'multiplier'         => 10,
            'price'              => 25000,
            'barcode'            => 'BC-BOX-AMX',
            'is_active'          => true,
        ]);

        $this->po = PurchaseOrder::create([
            'store_id'      => $this->store->id,
            'po_number'     => 'PO-TEST-GR-001',
            'vendor_id'     => $this->vendor->id,
            'requested_by'  => $this->user->id,
            'request_date'  => now(),
            'expected_date' => now()->addDays(5),
            'status'        => 'APPROVED',
            'subtotal'      => 250000,
            'grand_total'   => 250000,
        ]);

        $this->poItem = PurchaseOrderItem::create([
            'purchase_order_id'  => $this->po->id,
            'product_variant_id' => $this->variant->id,
            'unit_id'            => $this->unit->id,
            'unit_name'          => 'Box (10 Strip)',
            'unit_multiplier'    => 10,
            'qty_order'          => 10, // 10 Box = 100 base units
            'qty_received'       => 0,
            'price'              => 25000,
            'subtotal'           => 250000,
        ]);
    }

    public function test_partial_goods_receipt_creates_stock_batch_with_batch_and_expiry()
    {
        $payload = [
            'purchase_order_id' => $this->po->id,
            'receipt_date'      => now()->toDateString(),
            'items'             => [
                [
                    'purchase_item_id' => $this->poItem->id,
                    'qty_received'     => 4, // 4 Box = 40 base units
                    'batch_number'     => 'BATCH-AMX-2026',
                    'expired_date'     => now()->addYears(2)->toDateString(),
                ],
            ],
        ];

        $response = $this->post(route('gr.store'), $payload);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // 1. Verifikasi Header Goods Receipt
        $this->assertDatabaseHas('goods_receipts', [
            'store_id'          => $this->store->id,
            'purchase_order_id' => $this->po->id,
            'received_by'       => $this->user->id,
        ]);

        // 2. Verifikasi Item Goods Receipt
        $this->assertDatabaseHas('goods_receipt_items', [
            'purchase_order_item_id' => $this->poItem->id,
            'qty_received'           => 4,
            'unit_multiplier'        => 10,
            'base_qty_received'      => 40,
            'batch_number'           => 'BATCH-AMX-2026',
        ]);

        // 3. Verifikasi Stock Batch bertambah sesuai konversi satuan (4 * 10 = 40)
        $this->assertDatabaseHas('stock_batches', [
            'product_variant_id' => $this->variant->id,
            'purchase_item_id'   => $this->poItem->id,
            'batch_number'       => 'BATCH-AMX-2026',
            'qty_awal'           => 40,
            'qty_sisa'           => 40,
            'sumber'             => 'purchase',
        ]);

        // 4. Verifikasi status PO berubah menjadi PARTIAL_RECEIVED
        $this->po->refresh();
        $this->assertEquals('PARTIAL_RECEIVED', $this->po->status);

        $this->poItem->refresh();
        $this->assertEquals(4, $this->poItem->qty_received);
    }

    public function test_full_goods_receipt_marks_po_as_received()
    {
        $payload = [
            'purchase_order_id' => $this->po->id,
            'receipt_date'      => now()->toDateString(),
            'items'             => [
                [
                    'purchase_item_id' => $this->poItem->id,
                    'qty_received'     => 10, // Full 10 Box
                    'batch_number'     => 'BATCH-FULL-01',
                    'expired_date'     => now()->addYears(1)->toDateString(),
                ],
            ],
        ];

        $response = $this->post(route('gr.store'), $payload);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->po->refresh();
        $this->assertEquals('RECEIVED', $this->po->status);

        $this->poItem->refresh();
        $this->assertEquals(10, $this->poItem->qty_received);
    }

    public function test_cannot_receive_more_than_po_remaining_quantity()
    {
        $payload = [
            'purchase_order_id' => $this->po->id,
            'receipt_date'      => now()->toDateString(),
            'items'             => [
                [
                    'purchase_item_id' => $this->poItem->id,
                    'qty_received'     => 15, // Melebihi order (order hanya 10)
                    'batch_number'     => 'BATCH-OVER',
                    'expired_date'     => now()->addYears(1)->toDateString(),
                ],
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Qty diterima melebihi sisa PO');

        $this->withoutExceptionHandling()->post(route('gr.store'), $payload);
    }

    public function test_destroy_goods_receipt_reverts_po_qty_and_deletes_batch()
    {
        // 1. Terima 5 Box
        $payload = [
            'purchase_order_id' => $this->po->id,
            'receipt_date'      => now()->toDateString(),
            'items'             => [
                [
                    'purchase_item_id' => $this->poItem->id,
                    'qty_received'     => 5,
                    'batch_number'     => 'BATCH-ROLLBACK',
                    'expired_date'     => now()->addYears(1)->toDateString(),
                ],
            ],
        ];

        $this->post(route('gr.store'), $payload);

        $gr = GoodsReceipt::where('purchase_order_id', $this->po->id)->first();
        $this->assertNotNull($gr);

        $this->poItem->refresh();
        $this->assertEquals(5, $this->poItem->qty_received);

        // 2. Rollback / delete Goods Receipt
        $response = $this->delete(route('gr.destroy', $gr));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Qty received di PO kembali 0
        $this->poItem->refresh();
        $this->assertEquals(0, $this->poItem->qty_received);

        // Batch terhapus
        $this->assertDatabaseMissing('stock_batches', [
            'batch_number' => 'BATCH-ROLLBACK',
        ]);
    }
}
