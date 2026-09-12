<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RoleMaster;
use App\Models\StockAdjustment;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\StockOpnamePeriod;
use App\Models\Store;
use App\Models\User;
use App\Services\FirestoreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StockOpnameTest extends TestCase
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
            'name' => 'Opname Business',
            'code' => 'OPBIZ',
        ]);

        $this->store = Store::create([
            'business_id'          => $this->business->id,
            'name'                 => 'Opname Test Store',
            'code'                 => 'OPTS',
            'is_active'            => true,
            'business_type'        => 'retail',
            'enable_cash_register' => false,
        ]);

        $this->user = User::create([
            'name'     => 'Auditor Toko',
            'email'    => 'auditor@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->user->stores()->attach($this->store->id);

        $this->role = RoleMaster::create([
            'store_id'              => $this->store->id,
            'nama'                  => 'Auditor Role',
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
            'kode_produk' => 'PRD-OP-01',
            'nama_produk' => 'Minyak Goreng 1L',
        ]);

        $this->variant = ProductVariant::create([
            'store_id'          => $this->store->id,
            'product_id'        => $this->product->id,
            'variant_name'      => 'Minyak Goreng Pouch',
            'sku'               => 'MYK-1L',
            'barcode'           => 'BC-MYK-1L',
            'harga_jual'        => 20000,
            'cost_price_manual' => 15000,
            'track_stock'       => true,
            'is_active'         => 'Y',
        ]);

        $this->batch = StockBatch::create([
            'store_id'           => $this->store->id,
            'product_variant_id' => $this->variant->id,
            'batch_number'       => 'BATCH-MYK-01',
            'posisi'             => 'store',
            'qty_masuk'          => 100,
            'qty_sisa'           => 100,
            'harga_beli'         => 15000,
            'tanggal_masuk'      => now(),
            'is_active'          => true,
        ]);

        // Catat Stock Movement awal agar terdeteksi di penghitungan stok sistem
        StockMovement::create([
            'product_variant_id' => $this->variant->id,
            'stock_batch_id'     => $this->batch->id,
            'posisi'             => 'store',
            'tanggal'            => now(),
            'tipe'               => 'in',
            'direction'          => 'in',
            'qty'                => 100,
            'ref_type'           => 'InitialStock',
            'ref_id'             => 1,
        ]);
    }

    public function test_full_stock_opname_lifecycle_period_count_and_approval()
    {
        // 1. Buat Periode Stock Opname
        $periodResponse = $this->post(route('stock-opname-periods.store'), [
            'code'        => 'PERIODE-TEST-' . time(),
            'period_date' => now()->toDateString(),
            'description' => 'Opname Bulanan Akhir Kuartal',
        ]);
        $periodResponse->assertRedirect(route('stock-opname-periods.index'));

        $period = StockOpnamePeriod::where('store_id', $this->store->id)->latest()->first();
        $this->assertNotNull($period);
        $this->assertEquals('OPEN', $period->status);

        // 2. Generate lembar kerja Stock Opname untuk posisi "store"
        $opnameResponse = $this->post(route('stock-opnames.store', $period->id), [
            'posisi' => 'store',
        ]);
        $opnameResponse->assertRedirect();

        $opname = StockOpname::where('stock_opname_period_id', $period->id)->first();
        $this->assertNotNull($opname);
        $this->assertEquals('store', $opname->posisi);

        // 3. Verifikasi item opname ter-generate dengan stok sistem = 100
        $opnameItem = StockOpnameItem::where('stock_opname_id', $opname->id)
            ->where('product_variant_id', $this->variant->id)
            ->first();
        $this->assertNotNull($opnameItem);
        $this->assertEquals(100, $opnameItem->system_qty);

        // 4. Input perhitungan fisik: ditemukan hanya 90 (selisih -10 / SHORTAGE)
        $updateResponse = $this->putJson(route('stock-opnames.update', $opnameItem->id), [
            'physical_qty' => 90,
            'harga_beli'   => 15000,
        ]);
        $updateResponse->assertStatus(200);
        $updateResponse->assertJson(['success' => true]);

        $opnameItem->refresh();
        $this->assertEquals(90, $opnameItem->physical_qty);
        $this->assertEquals(-10, $opnameItem->difference_qty);
        $this->assertEquals('SHORTAGE', $opnameItem->status);

        $opname->refresh();
        $this->assertEquals('COUNTED', $opname->status);

        // 5. Approve Stock Opname -> Menghasilkan Stock Adjustment (Draft)
        $approveResponse = $this->post(route('stock-opnames.approve', $opname->id));
        $approveResponse->assertRedirect();
        $approveResponse->assertSessionHas('success');

        $opname->refresh();
        $this->assertEquals('APPROVED', $opname->status);

        // 6. Verifikasi terbentuk Stock Adjustment
        $this->assertDatabaseHas('stock_adjustments', [
            'stock_opname_id' => $opname->id,
            'reason_type'     => 'OPNAME',
            'status'          => 'DRAFT',
            'posisi'          => 'store',
        ]);

        $adjustment = StockAdjustment::where('stock_opname_id', $opname->id)->first();
        $this->assertNotNull($adjustment);

        // Verifikasi item adjustment mencatat selisih -10
        $this->assertDatabaseHas('stock_adjustment_items', [
            'stock_adjustment_id' => $adjustment->id,
            'product_variant_id'  => $this->variant->id,
            'qty'                 => -10,
        ]);
    }

    public function test_cancel_stock_opname_and_close_period()
    {
        $period = StockOpnamePeriod::create([
            'store_id'    => $this->store->id,
            'code'        => 'PERIODE-CANCEL-' . time(),
            'period_date' => now()->toDateString(),
            'description' => 'Periode untuk Dibatalkan',
            'status'      => 'OPEN',
            'created_by'  => $this->user->id,
        ]);

        $opname = StockOpname::create([
            'store_id'               => $this->store->id,
            'code'                   => 'SO-CANCEL-' . time(),
            'stock_opname_period_id' => $period->id,
            'posisi'                 => 'warehouse',
            'input_date'             => now()->toDateString(),
            'status'                 => 'DRAFT',
            'created_by'             => $this->user->id,
        ]);

        // 1. Batalkan stock opname
        $cancelRes = $this->post(route('stock-opnames.cancel', $opname->id));
        $cancelRes->assertRedirect();
        $cancelRes->assertSessionHas('success');

        $opname->refresh();
        $this->assertEquals('CANCELLED', $opname->status);

        // 2. Tutup periode stock opname
        $closePeriodRes = $this->post(route('stock-opname-periods.close', $period->id));
        $closePeriodRes->assertRedirect();

        $period->refresh();
        $this->assertEquals('CLOSED', $period->status);
    }
}
