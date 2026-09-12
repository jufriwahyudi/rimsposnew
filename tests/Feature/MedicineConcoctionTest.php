<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RoleMaster;
use App\Models\Sale;
use App\Models\StockBatch;
use App\Models\Store;
use App\Models\User;
use App\Services\FirestoreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MedicineConcoctionTest extends TestCase
{
    use DatabaseTransactions;

    protected $store;
    protected $business;
    protected $user;
    protected $role;
    protected $drugA;
    protected $variantA;
    protected $batchA;
    protected $drugB;
    protected $variantB;
    protected $batchB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(FirestoreService::class, function ($mock) {
            $mock->shouldReceive('syncStore')->andReturn(true);
        });

        $this->business = Business::create([
            'name' => 'Apotek Concoction Biz',
            'code' => 'ACB',
        ]);

        $this->store = Store::create([
            'business_id'          => $this->business->id,
            'name'                 => 'Apotek Kimia Sehat',
            'code'                 => 'AKS',
            'is_active'            => true,
            'business_type'        => 'pharmacy',
            'addon_concoction'     => true,
            'enable_cash_register' => false,
        ]);

        $this->user = User::create([
            'name'     => 'Asisten Apoteker',
            'email'    => 'apoteker@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->user->stores()->attach($this->store->id);

        $this->role = RoleMaster::create([
            'store_id'              => $this->store->id,
            'nama'                  => 'Apoteker Role',
            'role_type'             => 'STORE',
            'can_access_all_divisi' => 'Y',
            'stts'                  => 'Y',
        ]);

        $this->actingAs($this->user);
        $this->withSession([
            'store_id'      => $this->store->id,
            'selected_role' => $this->role->id,
        ]);
        \App\Support\Tenant::set($this->store->id);

        // Bahan 1: Paracetamol 500mg
        $this->drugA = Product::create([
            'store_id'    => $this->store->id,
            'kode_produk' => 'OBAT-01',
            'nama_produk' => 'Paracetamol 500mg',
        ]);
        $this->variantA = ProductVariant::create([
            'store_id'          => $this->store->id,
            'product_id'        => $this->drugA->id,
            'variant_name'      => 'Paracetamol Tab',
            'sku'               => 'PCT-TAB-01',
            'barcode'           => 'BC-PCT-01',
            'harga_jual'        => 500,
            'cost_price_manual' => 200,
            'track_stock'       => true,
            'is_active'         => true,
        ]);
        $this->batchA = StockBatch::create([
            'store_id'           => $this->store->id,
            'product_variant_id' => $this->variantA->id,
            'batch_number'       => 'BATCH-PCT-01',
            'posisi'             => 'store',
            'qty_masuk'          => 100,
            'qty_sisa'           => 100,
            'harga_beli'         => 200,
            'tanggal_masuk'      => now(),
            'is_active'          => true,
        ]);

        // Bahan 2: Dexamethasone 0.5mg
        $this->drugB = Product::create([
            'store_id'    => $this->store->id,
            'kode_produk' => 'OBAT-02',
            'nama_produk' => 'Dexamethasone 0.5mg',
        ]);
        $this->variantB = ProductVariant::create([
            'store_id'          => $this->store->id,
            'product_id'        => $this->drugB->id,
            'variant_name'      => 'Dexa Tab',
            'sku'               => 'DEX-TAB-01',
            'barcode'           => 'BC-DEX-01',
            'harga_jual'        => 500,
            'cost_price_manual' => 200,
            'track_stock'       => true,
            'is_active'         => true,
        ]);
        $this->batchB = StockBatch::create([
            'store_id'           => $this->store->id,
            'product_variant_id' => $this->variantB->id,
            'batch_number'       => 'BATCH-DEX-01',
            'posisi'             => 'store',
            'qty_masuk'          => 50,
            'qty_sisa'           => 50,
            'harga_beli'         => 200,
            'tanggal_masuk'      => now(),
            'is_active'          => true,
        ]);
    }

    public function test_concoction_checkout_records_tuslah_embalase_and_deducts_ingredients_stock()
    {
        // Racikan: Puyer Batuk (10 Bungkus)
        // Komposisi per resep:
        // - Paracetamol: 10 tablet @ 500 = 5.000
        // - Dexamethasone: 5 tablet @ 500 = 2.500
        // - Tuslah fee (jasa apoteker): 3.000
        // - Embalase fee (kertas puyer/klip): 2.000
        // Total = 5000 + 2500 + 3000 + 2000 = 12.500

        $concoctionPrice = 12500;
        $cartPayload = [
            'payment_method'   => 'cash',
            'paid_amount'      => 12500,
            'cash_amount'      => 12500,
            'customer_name'    => 'Pasien Rawat Jalan',
            'transaction_date' => now()->toDateString(),
            'subtotal'         => $concoctionPrice,
            'discount_total'   => 0,
            'total'            => $concoctionPrice,
            'items'            => [
                [
                    'name'               => 'Puyer Racik Batuk 10 Bks',
                    'price'              => $concoctionPrice,
                    'qty'                => 1,
                    'subtotal'           => $concoctionPrice,
                    'is_concoction'      => true,
                    'concoction_name'    => 'Puyer Racik Batuk',
                    'concoction_form'    => 'puyer',
                    'dosage_instruction' => '3x sehari 1 bungkus sesudah makan',
                    'usage_type'         => 'oral',
                    'tuslah_fee'         => 3000,
                    'embalase_fee'       => 2000,
                    'concoction_items'   => [
                        [
                            'variant_id'   => $this->variantA->id,
                            'product_name' => 'Paracetamol 500mg',
                            'quantity'     => 10,
                            'unit_price'   => 500,
                            'subtotal'     => 5000,
                            'unit_name'    => 'Tablet',
                        ],
                        [
                            'variant_id'   => $this->variantB->id,
                            'product_name' => 'Dexamethasone 0.5mg',
                            'quantity'     => 5,
                            'unit_price'   => 500,
                            'subtotal'     => 2500,
                            'unit_name'    => 'Tablet',
                        ],
                    ],
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

        // 1. Verifikasi header item penjualan tercatat sebagai racikan dengan biaya tuslah & embalase
        $this->assertDatabaseHas('sale_items', [
            'sale_id'            => $saleId,
            'is_concoction'      => 1,
            'concoction_name'    => 'Puyer Racik Batuk',
            'concoction_form'    => 'puyer',
            'tuslah_fee'         => 3000,
            'embalase_fee'       => 2000,
            'subtotal'           => 12500,
        ]);

        $sale = Sale::with('items.concoctionItems')->find($saleId);
        $saleItem = $sale->items->first();
        $this->assertNotNull($saleItem);
        $this->assertCount(2, $saleItem->concoctionItems);

        // 2. Verifikasi bahan-bahan racikan tercatat di sale_concoction_items
        $this->assertDatabaseHas('sale_concoction_items', [
            'sale_item_id'       => $saleItem->id,
            'product_variant_id' => $this->variantA->id,
            'quantity'           => 10,
            'unit_price'         => 500,
            'subtotal'           => 5000,
        ]);

        $this->assertDatabaseHas('sale_concoction_items', [
            'sale_item_id'       => $saleItem->id,
            'product_variant_id' => $this->variantB->id,
            'quantity'           => 5,
            'unit_price'         => 500,
            'subtotal'           => 2500,
        ]);

        // 3. Verifikasi pemotongan stok otomatis pada masing-masing bahan obat
        // Bahan A: 100 - 10 = 90
        $this->batchA->refresh();
        $this->assertEquals(90, $this->batchA->qty_sisa);

        // Bahan B: 50 - 5 = 45
        $this->batchB->refresh();
        $this->assertEquals(45, $this->batchB->qty_sisa);
    }

    public function test_void_concoction_sale_restores_all_ingredients_stock()
    {
        // 1. Checkout racikan memakai 10 Paracetamol dan 5 Dexa
        $concoctionPrice = 12500;
        $cartPayload = [
            'payment_method'   => 'cash',
            'paid_amount'      => 12500,
            'cash_amount'      => 12500,
            'customer_name'    => 'Pasien Void',
            'transaction_date' => now()->toDateString(),
            'subtotal'         => $concoctionPrice,
            'discount_total'   => 0,
            'total'            => $concoctionPrice,
            'items'            => [
                [
                    'name'               => 'Puyer Racik Batuk',
                    'price'              => $concoctionPrice,
                    'qty'                => 1,
                    'subtotal'           => $concoctionPrice,
                    'is_concoction'      => true,
                    'concoction_name'    => 'Puyer Racik Batuk',
                    'concoction_form'    => 'puyer',
                    'tuslah_fee'         => 3000,
                    'embalase_fee'       => 2000,
                    'concoction_items'   => [
                        [
                            'variant_id'   => $this->variantA->id,
                            'product_name' => 'Paracetamol 500mg',
                            'quantity'     => 10,
                            'unit_price'   => 500,
                            'subtotal'     => 5000,
                        ],
                        [
                            'variant_id'   => $this->variantB->id,
                            'product_name' => 'Dexamethasone 0.5mg',
                            'quantity'     => 5,
                            'unit_price'   => 500,
                            'subtotal'     => 2500,
                        ],
                    ],
                ],
            ],
        ];

        $checkoutRes = $this->postJson('/api/pos/checkout', [
            'store_id' => $this->store->id,
            'cart'     => $cartPayload,
        ]);
        $checkoutRes->assertStatus(200);
        $saleId = $checkoutRes->json('sale_id');

        // Pastikan stok terpotong
        $this->batchA->refresh();
        $this->batchB->refresh();
        $this->assertEquals(90, $this->batchA->qty_sisa);
        $this->assertEquals(45, $this->batchB->qty_sisa);

        // 2. Void transaksi
        $voidRes = $this->postJson("/api/pos/sales/{$saleId}/void", [
            'store_id' => $this->store->id,
            'reason'   => 'Salah resep dokter, resep dibatalkan',
        ]);
        $voidRes->assertStatus(200);

        // 3. Stok kedua bahan obat kembali utuh
        $this->batchA->refresh();
        $this->batchB->refresh();
        $this->assertEquals(100, $this->batchA->qty_sisa);
        $this->assertEquals(50, $this->batchB->qty_sisa);
    }
}
