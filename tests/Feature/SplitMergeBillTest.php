<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SplitMergeBillTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Store $store;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $business = Business::create([
            'name' => 'Test Biz SPM',
            'code' => 'SPM',
        ]);

        $this->store = Store::create([
            'business_id'          => $business->id,
            'name'                 => 'Test Store SPM',
            'code'                 => 'SPM01',
            'is_active'            => true,
            'enable_cash_register' => false,
        ]);

        $this->user = User::create([
            'name'     => 'Kasir SPM',
            'email'    => 'kasir.spm@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->user->stores()->attach($this->store->id);

        $product = Product::create([
            'store_id'    => $this->store->id,
            'kode_produk' => 'PRD-SPM',
            'nama_produk' => 'Nasi Goreng',
            'base_unit'   => 'Porsi',
        ]);

        $this->variant = ProductVariant::create([
            'store_id'     => $this->store->id,
            'product_id'   => $product->id,
            'variant_name' => 'Biasa',
            'sku'          => 'NSG-01',
            'barcode'      => 'BC-NSG-01',
            'harga_jual'   => 20000,
            'track_stock'  => false,
            'is_active'    => 'Y',
            'is_available' => true,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Helper: buat bill hold dengan item & diskon transaksi
    // ──────────────────────────────────────────────────────────────────────────
    private function makeHoldBill(array $opts = []): Sale
    {
        $subtotal     = $opts['subtotal']      ?? 100000;
        $discTotal    = $opts['discount_total'] ?? 0;
        $transDisc    = $opts['trans_discount'] ?? $discTotal;

        $sale = Sale::create([
            'store_id'        => $this->store->id,
            'invoice_number'  => 'INV-TEST-' . uniqid(),
            'table_number'    => $opts['table'] ?? 'T1',
            'sale_date'       => now(),
            'sale_type'       => 'retail',
            'customer_name'   => 'Test Customer',
            'user_id'         => $this->user->id,
            'subtotal'        => $subtotal,
            'discount_total'  => $discTotal,
            'trans_discount'  => $transDisc,
            'tax_total'       => 0,
            'grand_total'     => $subtotal - $discTotal,
            'paid_amount'     => 0,
            'change_amount'   => 0,
            'status'          => 'hold',
            'payment_status'  => 'unpaid',
        ]);

        $qty     = $opts['qty']      ?? 5;
        $price   = $opts['price']    ?? ($subtotal / $qty);

        SaleItem::create([
            'sale_id'           => $sale->id,
            'product_id'        => $this->variant->product_id,
            'product_variant_id'=> $this->variant->id,
            'sku'               => $this->variant->sku,
            'product_name'      => 'Nasi Goreng Biasa',
            'price'             => $price,
            'qty'               => $qty,
            'unit_id'           => null,
            'unit_name'         => 'Porsi',
            'unit_multiplier'   => 1,
            'discount_amount'   => 0,
            'subtotal'          => $subtotal,
            'status'            => 'sold',
        ]);

        return $sale->fresh(['items']);
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  SPLIT BILL TESTS
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Split nominal: 5 item @20rb (total 100rb), no diskon.
     * Pindah 2 item → bill baru 40rb, sisa 60rb.
     */
    public function test_split_bill_no_discount_splits_items_correctly()
    {
        $sale = $this->makeHoldBill(['subtotal' => 100000, 'qty' => 5, 'price' => 20000]);

        $resp = $this->actingAs($this->user)
            ->withSession(['store_id' => $this->store->id])
            ->postJson("/api/pos/sales/{$sale->id}/split", [
                'store_id' => $this->store->id,
                'items'    => [['variant_id' => $this->variant->id, 'qty' => 2]],
            ]);

        $resp->assertStatus(200);
        $newSaleId = $resp->json('data.new_sale_id');

        $newSale = Sale::find($newSaleId);
        $this->assertEquals(40000, $newSale->subtotal);
        $this->assertEquals(0,     $newSale->discount_total);
        $this->assertEquals(40000, $newSale->grand_total);

        $srcSale = Sale::find($sale->id);
        $this->assertEquals(60000, $srcSale->subtotal);
        $this->assertEquals(0,     $srcSale->discount_total);
        $this->assertEquals(60000, $srcSale->grand_total);
    }

    /**
     * Split dengan diskon transaksi nominal 10rb.
     * Subtotal sumber 100rb, pindah 2/5 item (40%).
     * Diskon baru = 10rb * (40rb / 100rb) = 4000.
     * Diskon sisa = 10rb - 4rb = 6000.
     */
    public function test_split_bill_proportional_trans_discount()
    {
        $sale = $this->makeHoldBill([
            'subtotal'       => 100000,
            'discount_total' => 10000,
            'trans_discount' => 10000,
            'qty'            => 5,
            'price'          => 20000,
        ]);

        $resp = $this->actingAs($this->user)
            ->withSession(['store_id' => $this->store->id])
            ->postJson("/api/pos/sales/{$sale->id}/split", [
                'store_id' => $this->store->id,
                'items'    => [['variant_id' => $this->variant->id, 'qty' => 2]],
            ]);

        $resp->assertStatus(200);
        $newSaleId = $resp->json('data.new_sale_id');

        $newSale = Sale::find($newSaleId);
        $this->assertEquals(40000, $newSale->subtotal);
        $this->assertEquals(4000,  $newSale->discount_total);
        $this->assertEquals(36000, $newSale->grand_total);

        $srcSale = Sale::find($sale->id);
        $this->assertEquals(60000, $srcSale->subtotal);
        $this->assertEquals(6000,  $srcSale->discount_total);
        $this->assertEquals(54000, $srcSale->grand_total);
    }

    /**
     * Split seluruh item → bill sumber dihapus.
     */
    public function test_split_all_items_deletes_source_bill()
    {
        $sale = $this->makeHoldBill(['subtotal' => 100000, 'qty' => 5]);
        $sourceId = $sale->id;

        $resp = $this->actingAs($this->user)
            ->withSession(['store_id' => $this->store->id])
            ->postJson("/api/pos/sales/{$sourceId}/split", [
                'store_id' => $this->store->id,
                'items'    => [['variant_id' => $this->variant->id, 'qty' => 5]],
            ]);

        $resp->assertStatus(200);
        $this->assertNull(Sale::find($sourceId), 'Bill sumber seharusnya dihapus karena seluruh item pindah');
    }

    /**
     * Split qty melebihi yang tersedia → harus gagal 422/500 dengan pesan error.
     */
    public function test_split_exceeding_qty_returns_error()
    {
        $sale = $this->makeHoldBill(['subtotal' => 100000, 'qty' => 3]);

        $resp = $this->actingAs($this->user)
            ->withSession(['store_id' => $this->store->id])
            ->postJson("/api/pos/sales/{$sale->id}/split", [
                'store_id' => $this->store->id,
                'items'    => [['variant_id' => $this->variant->id, 'qty' => 10]],
            ]);

        $resp->assertStatus(500);
        $this->assertStringContainsString('melebihi', $resp->json('message'));
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  MERGE BILL TESTS
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Merge dua bill tanpa diskon → item digabung, grand_total dijumlah.
     */
    public function test_merge_bills_no_discount()
    {
        $source = $this->makeHoldBill(['subtotal' => 40000, 'qty' => 2, 'price' => 20000, 'table' => 'T2']);
        $target = $this->makeHoldBill(['subtotal' => 60000, 'qty' => 3, 'price' => 20000, 'table' => 'T2']);

        $resp = $this->actingAs($this->user)
            ->withSession(['store_id' => $this->store->id])
            ->postJson('/api/pos/sales/merge-bills', [
                'store_id'       => $this->store->id,
                'source_sale_id' => $source->id,
                'target_sale_id' => $target->id,
            ]);

        $resp->assertStatus(200);
        $this->assertNull(Sale::find($source->id), 'Bill sumber harus dihapus setelah merge');

        $merged = Sale::find($target->id);
        $this->assertEquals(100000, $merged->subtotal);
        $this->assertEquals(0,      $merged->discount_total);
        $this->assertEquals(100000, $merged->grand_total);
    }

    /**
     * Merge bill sumber berDiskon 5rb ke target berDiskon 10rb.
     * Diskon gabungan = 5rb + 10rb = 15rb.
     * Grand total = 100rb - 15rb = 85rb.
     */
    public function test_merge_preserves_source_discount_total()
    {
        $source = $this->makeHoldBill([
            'subtotal'       => 40000,
            'discount_total' => 5000,
            'qty'            => 2,
            'price'          => 20000,
            'table'          => 'T3',
        ]);
        $target = $this->makeHoldBill([
            'subtotal'       => 60000,
            'discount_total' => 10000,
            'qty'            => 3,
            'price'          => 20000,
            'table'          => 'T3',
        ]);

        $resp = $this->actingAs($this->user)
            ->withSession(['store_id' => $this->store->id])
            ->postJson('/api/pos/sales/merge-bills', [
                'store_id'       => $this->store->id,
                'source_sale_id' => $source->id,
                'target_sale_id' => $target->id,
            ]);

        $resp->assertStatus(200);

        $merged = Sale::find($target->id);
        $this->assertEquals(100000, $merged->subtotal,        'subtotal harus 100rb');
        $this->assertEquals(15000,  $merged->discount_total,  'discount_total harus 15rb (10rb + 5rb)');
        $this->assertEquals(15000,  $merged->trans_discount,  'trans_discount harus sinkron dengan discount_total');
        $this->assertEquals(85000,  $merged->grand_total,     'grand_total harus 85rb (100rb - 15rb)');
    }

    /**
     * Merge: bill sumber dengan diskon, target tanpa diskon.
     */
    public function test_merge_source_discount_into_zero_discount_target()
    {
        $source = $this->makeHoldBill([
            'subtotal'       => 40000,
            'discount_total' => 8000,
            'qty'            => 2,
            'price'          => 20000,
            'table'          => 'T4',
        ]);
        $target = $this->makeHoldBill([
            'subtotal'       => 60000,
            'discount_total' => 0,
            'qty'            => 3,
            'price'          => 20000,
            'table'          => 'T4',
        ]);

        $resp = $this->actingAs($this->user)
            ->withSession(['store_id' => $this->store->id])
            ->postJson('/api/pos/sales/merge-bills', [
                'store_id'       => $this->store->id,
                'source_sale_id' => $source->id,
                'target_sale_id' => $target->id,
            ]);

        $resp->assertStatus(200);

        $merged = Sale::find($target->id);
        $this->assertEquals(100000, $merged->subtotal);
        $this->assertEquals(8000,   $merged->discount_total, 'Diskon sumber 8rb harus ikut ke target');
        $this->assertEquals(92000,  $merged->grand_total,    'Grand total 100rb - 8rb = 92rb');
    }

    /**
     * Merge non-hold bill harus gagal.
     */
    public function test_merge_non_hold_bill_returns_error()
    {
        $source = $this->makeHoldBill(['subtotal' => 40000, 'qty' => 2]);
        $target = $this->makeHoldBill(['subtotal' => 60000, 'qty' => 3]);

        // Ubah target menjadi paid
        $target->update(['status' => 'paid']);

        $resp = $this->actingAs($this->user)
            ->withSession(['store_id' => $this->store->id])
            ->postJson('/api/pos/sales/merge-bills', [
                'store_id'       => $this->store->id,
                'source_sale_id' => $source->id,
                'target_sale_id' => $target->id,
            ]);

        $resp->assertStatus(500);
        $this->assertStringContainsString('hold', strtolower($resp->json('message')));
    }
}
