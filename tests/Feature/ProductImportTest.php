<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Store;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $store;
    protected $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create([
            'name' => 'Import Test Biz',
            'code' => 'IMPBZ',
        ]);

        $this->store = Store::create([
            'business_id' => $this->business->id,
            'name' => 'Import Test Store',
            'code' => 'IMPST',
            'is_active' => true,
            'business_type' => 'retail',
        ]);

        $this->user = User::create([
            'name' => 'Admin User',
            'email' => 'admin_import@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->user->stores()->attach($this->store->id);
    }

    public function test_download_template_route()
    {
        $this->actingAs($this->user);

        $response = $this->withSession(['store_id' => $this->store->id])
            ->get(route('produk.import.template'));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename="template_import_produk_retail.xlsx"');
    }

    public function test_dry_run_validation_success()
    {
        $this->actingAs($this->user);

        $fakeFile = UploadedFile::fake()->create('template.xlsx');

        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [
                    ['Kode Produk', 'Nama Produk', 'Deskripsi', 'Nama Varian', 'Barcode', 'Harga Jual', 'Poin Reward'],
                    ['TST001', 'Produk Impor A', 'Deskripsi A', 'Varian 1', 'BARCODE_IMP_1', '25000', '10']
                ]
            ]);

        $response = $this->withSession(['store_id' => $this->store->id])
            ->postJson(route('produk.import.proses'), [
                'file' => $fakeFile,
                'dry_run' => '1',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success_status' => true,
            'success' => true,
            'is_dry_run' => true,
            'total_rows' => 1,
            'valid_rows' => 1,
            'invalid_rows' => 0,
        ]);

        // Since it's a dry run, product should not be created in DB
        $this->assertDatabaseMissing('products', [
            'kode_produk' => 'TST001',
        ]);
    }

    public function test_actual_import_success()
    {
        $this->actingAs($this->user);

        $fakeFile = UploadedFile::fake()->create('template.xlsx');

        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [
                    ['Kode Produk', 'Nama Produk', 'Deskripsi', 'Nama Varian', 'Barcode', 'Harga Jual', 'Poin Reward'],
                    ['TST002', 'Produk Impor B', 'Deskripsi B', 'Varian 2', 'BARCODE_IMP_2', '35000', '15']
                ]
            ]);

        $response = $this->withSession(['store_id' => $this->store->id])
            ->postJson(route('produk.import.proses'), [
                'file' => $fakeFile,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success_status' => true,
            'success' => true,
            'is_dry_run' => false,
            'total_rows' => 1,
            'valid_rows' => 1,
            'invalid_rows' => 0,
            'imported_count' => 1,
        ]);

        // Since it's actual import, product should exist in DB
        $this->assertDatabaseHas('products', [
            'store_id' => $this->store->id,
            'kode_produk' => 'TST002',
            'nama_produk' => 'Produk Impor B',
        ]);

        $this->assertDatabaseHas('product_variants', [
            'store_id' => $this->store->id,
            'variant_name' => 'Varian 2',
            'harga_jual' => 35000,
            'reward_points' => 15,
        ]);
    }

    public function test_import_validation_fails_with_errors()
    {
        $this->actingAs($this->user);

        $fakeFile = UploadedFile::fake()->create('template.xlsx');

        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [
                    ['Kode Produk', 'Nama Produk', 'Deskripsi', 'Nama Varian', 'Barcode', 'Harga Jual', 'Poin Reward'],
                    ['', 'Produk Impor Tanpa Kode', 'Deskripsi C', 'Varian 3', 'BARCODE_IMP_3', '-5000', '-5']
                ]
            ]);

        $response = $this->withSession(['store_id' => $this->store->id])
            ->postJson(route('produk.import.proses'), [
                'file' => $fakeFile,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success_status' => true,
            'success' => false,
            'total_rows' => 1,
            'valid_rows' => 0,
            'invalid_rows' => 1,
        ]);

        $resultData = $response->json();
        $this->assertContains("Kode Produk tidak boleh kosong.", $resultData['results'][0]['errors']);
        $this->assertContains("Harga Jual tidak boleh negatif.", $resultData['results'][0]['errors']);
        $this->assertContains("Poin Reward tidak boleh negatif.", $resultData['results'][0]['errors']);
    }

    public function test_download_stock_template_route()
    {
        $this->actingAs($this->user);

        $response = $this->withSession(['store_id' => $this->store->id])
            ->get(route('produk.import.template-stok'));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename="template_import_stok_awal.xlsx"');
    }

    public function test_stock_dry_run_validation_success()
    {
        $this->actingAs($this->user);

        $product = Product::create([
            'store_id' => $this->store->id,
            'kode_produk' => 'TSTSTK',
            'nama_produk' => 'Product Stock A',
        ]);
        $variant = ProductVariant::create([
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'sku' => 'SKU-STK-01',
            'variant_name' => 'V1',
            'harga_jual' => 15000,
            'track_stock' => true,
            'is_active' => 'Y',
        ]);

        $fakeFile = UploadedFile::fake()->create('template.xlsx');

        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [
                    ['SKU', 'Nama Produk', 'Nama Varian', 'Posisi (store/warehouse)', 'Jumlah Stok', 'Harga Beli/Modal'],
                    ['SKU-STK-01', 'Product Stock A', 'V1', 'store', '50', '10000']
                ]
            ]);

        $response = $this->withSession(['store_id' => $this->store->id])
            ->postJson(route('produk.import.proses-stok'), [
                'file' => $fakeFile,
                'transaction_type' => 'stock_adjustment',
                'dry_run' => '1',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success_status' => true,
            'success' => true,
            'is_dry_run' => true,
            'total_rows' => 1,
            'valid_rows' => 1,
            'invalid_rows' => 0,
        ]);

        $this->assertDatabaseMissing('stock_batches', [
            'product_variant_id' => $variant->id,
        ]);
    }

    public function test_stock_import_via_stock_adjustment()
    {
        $this->actingAs($this->user);

        $product = Product::create([
            'store_id' => $this->store->id,
            'kode_produk' => 'TSTSTK2',
            'nama_produk' => 'Product Stock B',
        ]);
        $variant = ProductVariant::create([
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'sku' => 'SKU-STK-02',
            'variant_name' => 'V2',
            'harga_jual' => 20000,
            'track_stock' => true,
            'is_active' => 'Y',
        ]);

        $fakeFile = UploadedFile::fake()->create('template.xlsx');

        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [
                    ['SKU', 'Nama Produk', 'Nama Varian', 'Posisi (store/warehouse)', 'Jumlah Stok', 'Harga Beli/Modal'],
                    ['SKU-STK-02', 'Product Stock B', 'V2', 'store', '30', '12000']
                ]
            ]);

        $response = $this->withSession(['store_id' => $this->store->id])
            ->postJson(route('produk.import.proses-stok'), [
                'file' => $fakeFile,
                'transaction_type' => 'stock_adjustment',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success_status' => true,
            'success' => true,
            'is_dry_run' => false,
            'total_rows' => 1,
            'valid_rows' => 1,
            'invalid_rows' => 0,
            'imported_count' => 1,
        ]);

        $this->assertDatabaseHas('stock_adjustments', [
            'store_id' => $this->store->id,
            'posisi' => 'store',
            'status' => 'POSTED',
        ]);

        $this->assertDatabaseHas('stock_batches', [
            'product_variant_id' => $variant->id,
            'posisi' => 'store',
            'qty_sisa' => 30,
            'harga_beli' => 12000,
        ]);
    }

    public function test_stock_import_via_purchase_order()
    {
        $this->actingAs($this->user);

        $product = Product::create([
            'store_id' => $this->store->id,
            'kode_produk' => 'TSTSTK3',
            'nama_produk' => 'Product Stock C',
        ]);
        $variant = ProductVariant::create([
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'sku' => 'SKU-STK-03',
            'variant_name' => 'V3',
            'harga_jual' => 30000,
            'track_stock' => true,
            'is_active' => 'Y',
        ]);

        $fakeFile = UploadedFile::fake()->create('template.xlsx');

        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [
                    ['SKU', 'Nama Produk', 'Nama Varian', 'Posisi (store/warehouse)', 'Jumlah Stok', 'Harga Beli/Modal'],
                    ['SKU-STK-03', 'Product Stock C', 'V3', 'warehouse', '100', '18000']
                ]
            ]);

        $response = $this->withSession(['store_id' => $this->store->id])
            ->postJson(route('produk.import.proses-stok'), [
                'file' => $fakeFile,
                'transaction_type' => 'purchase_order',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success_status' => true,
            'success' => true,
            'is_dry_run' => false,
            'total_rows' => 1,
            'valid_rows' => 1,
            'invalid_rows' => 0,
            'imported_count' => 1,
        ]);

        $this->assertDatabaseHas('purchase_orders', [
            'store_id' => $this->store->id,
            'status' => 'RECEIVED',
            'grand_total' => 1800000,
        ]);

        $this->assertDatabaseHas('goods_receipts', [
            'store_id' => $this->store->id,
        ]);

        $this->assertDatabaseHas('stock_batches', [
            'product_variant_id' => $variant->id,
            'posisi' => 'warehouse',
            'qty_sisa' => 100,
            'harga_beli' => 18000,
            'sumber' => 'purchase',
        ]);
    }
}
