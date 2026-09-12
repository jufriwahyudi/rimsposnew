<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\ProductVariant;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PharmacyProductSeeder extends Seeder
{
    /**
     * Run the database seeds for Pharmacy (Store ID: 879).
     */
    public function run(): void
    {
        $storeId = 879;
        $store = Store::find($storeId);

        if (!$store) {
            $this->command->error("Store ID {$storeId} tidak ditemukan!");
            return;
        }

        $this->command->info("Menyiapkan master data Apotek untuk Store: {$store->name} (ID: {$storeId})...");

        // Pastikan fitur multi satuan dan FEFO di store ini aktif
        $store->update([
            'addon_multi_unit' => true,
            'addon_fefo'       => true,
            'business_type'    => 'pharmacy',
        ]);

        DB::transaction(function () use ($storeId) {
            // 1. Kategori Apotek
            $categoriesData = [
                ['name' => 'Obat Bebas & Terbatas', 'icon' => 'pill'],
                ['name' => 'Obat Keras & Antibiotik', 'icon' => 'shield-virus'],
                ['name' => 'Vitamin & Suplemen', 'icon' => 'heart-pulse'],
                ['name' => 'Herbal & Tradisional', 'icon' => 'leaf'],
                ['name' => 'Alat Kesehatan & P3K', 'icon' => 'kit-medical'],
            ];

            $categoryMap = [];
            foreach ($categoriesData as $cat) {
                $category = ProductCategory::firstOrCreate(
                    [
                        'store_id' => $storeId,
                        'name'     => $cat['name'],
                    ],
                    [
                        'slug'       => Str::slug($cat['name']),
                        'icon'       => $cat['icon'],
                        'is_active'  => true,
                        'sort_order' => 1,
                    ]
                );
                $categoryMap[$cat['name']] = $category->id;
            }

            // 2. Daftar 10 Produk Apotek Lengkap
            $productsData = [
                [
                    'kode'        => 'APT-001',
                    'nama'        => 'Paracetamol 500mg Tablet',
                    'kategori'    => 'Obat Bebas & Terbatas',
                    'base_unit'   => 'Tablet',
                    'deskripsi'   => 'Obat analgetik dan antipiretik untuk meredakan sakit kepala, sakit gigi, dan demam.',
                    'sku'         => 'APT-PCT-500',
                    'barcode'     => '8999901001011',
                    'harga_jual'  => 1000,
                    'harga_beli'  => 500,
                    'batches'     => [
                        [
                            'batch_number' => 'PCT-26A01',
                            'expired_date' => Carbon::now()->addDays(25)->toDateString(), // Kritis <= 30 hr (test FEFO priority)
                            'qty'          => 100,
                        ],
                        [
                            'batch_number' => 'PCT-27B02',
                            'expired_date' => Carbon::now()->addMonths(18)->toDateString(), // Aman
                            'qty'          => 400,
                        ],
                    ],
                    'units'       => [
                        ['name' => 'Strip', 'multiplier' => 10,  'price' => 9000,   'barcode' => '8999901001028'],
                        ['name' => 'Box',   'multiplier' => 100, 'price' => 80000,  'barcode' => '8999901001035'],
                    ],
                ],
                [
                    'kode'        => 'APT-002',
                    'nama'        => 'Amoxicillin 500mg Kaplet',
                    'kategori'    => 'Obat Keras & Antibiotik',
                    'base_unit'   => 'Kaplet',
                    'deskripsi'   => 'Antibiotik spektrum luas golongan penisilin untuk mengatasi infeksi bakteri.',
                    'sku'         => 'APT-AMX-500',
                    'barcode'     => '8999901002018',
                    'harga_jual'  => 1500,
                    'harga_beli'  => 800,
                    'batches'     => [
                        [
                            'batch_number' => 'AMX-26K03',
                            'expired_date' => Carbon::now()->addMonths(6)->toDateString(),
                            'qty'          => 150,
                        ],
                        [
                            'batch_number' => 'AMX-27L04',
                            'expired_date' => Carbon::now()->addMonths(20)->toDateString(),
                            'qty'          => 250,
                        ],
                    ],
                    'units'       => [
                        ['name' => 'Strip', 'multiplier' => 10,  'price' => 13500,  'barcode' => '8999901002025'],
                        ['name' => 'Box',   'multiplier' => 100, 'price' => 120000, 'barcode' => '8999901002032'],
                    ],
                ],
                [
                    'kode'        => 'APT-003',
                    'nama'        => 'Vitamin C 500mg IPI',
                    'kategori'    => 'Vitamin & Suplemen',
                    'base_unit'   => 'Tablet',
                    'deskripsi'   => 'Suplemen vitamin C untuk menjaga daya tahan tubuh dan mempercepat penyembuhan.',
                    'sku'         => 'APT-VTC-IPI',
                    'barcode'     => '8999901003015',
                    'harga_jual'  => 300,
                    'harga_beli'  => 150,
                    'batches'     => [
                        [
                            'batch_number' => 'VTC-27M01',
                            'expired_date' => Carbon::now()->addMonths(24)->toDateString(),
                            'qty'          => 1000,
                        ],
                    ],
                    'units'       => [
                        ['name' => 'Pot/Botol', 'multiplier' => 45,  'price' => 12000,  'barcode' => '8999901003022'],
                        ['name' => 'Dus',       'multiplier' => 540, 'price' => 135000, 'barcode' => '8999901003039'],
                    ],
                ],
                [
                    'kode'        => 'APT-004',
                    'nama'        => 'Tolak Angin Cair Herbal',
                    'kategori'    => 'Herbal & Tradisional',
                    'base_unit'   => 'Sachet',
                    'deskripsi'   => 'Obat herbal terstandar untuk meredakan masuk angin, mual, pegal-pegal dan flu.',
                    'sku'         => 'APT-TLK-015',
                    'barcode'     => '8999901004012',
                    'harga_jual'  => 4500,
                    'harga_beli'  => 3200,
                    'batches'     => [
                        [
                            'batch_number' => 'TLK-26H01',
                            'expired_date' => Carbon::now()->addMonths(14)->toDateString(),
                            'qty'          => 240,
                        ],
                    ],
                    'units'       => [
                        ['name' => 'Box',    'multiplier' => 12,  'price' => 50000,  'barcode' => '8999901004029'],
                        ['name' => 'Karton', 'multiplier' => 120, 'price' => 480000, 'barcode' => '8999901004036'],
                    ],
                ],
                [
                    'kode'        => 'APT-005',
                    'nama'        => 'Betadine Antiseptic Solution 30ml',
                    'kategori'    => 'Alat Kesehatan & P3K',
                    'base_unit'   => 'Botol',
                    'deskripsi'   => 'Antiseptik povidone iodine 10% untuk pembersih dan pencegah infeksi luka.',
                    'sku'         => 'APT-BTD-030',
                    'barcode'     => '8999901005019',
                    'harga_jual'  => 25000,
                    'harga_beli'  => 19500,
                    'batches'     => [
                        [
                            'batch_number' => 'BTD-28A01',
                            'expired_date' => Carbon::now()->addMonths(30)->toDateString(),
                            'qty'          => 60,
                        ],
                    ],
                    'units'       => [
                        ['name' => 'Lusin', 'multiplier' => 12, 'price' => 285000, 'barcode' => '8999901005026'],
                    ],
                ],
                [
                    'kode'        => 'APT-006',
                    'nama'        => 'Masker Medis Surgical 3-Ply',
                    'kategori'    => 'Alat Kesehatan & P3K',
                    'base_unit'   => 'Pcs',
                    'deskripsi'   => 'Masker bedah 3 lapis proteksi droplet cairan dan mikroorganisme.',
                    'sku'         => 'APT-MSK-3PL',
                    'barcode'     => '8999901006016',
                    'harga_jual'  => 1000,
                    'harga_beli'  => 500,
                    'batches'     => [
                        [
                            'batch_number' => 'MSK-29Z01',
                            'expired_date' => Carbon::now()->addMonths(36)->toDateString(),
                            'qty'          => 1000,
                        ],
                    ],
                    'units'       => [
                        ['name' => 'Pack', 'multiplier' => 10, 'price' => 9000,  'barcode' => '8999901006023'],
                        ['name' => 'Box',  'multiplier' => 50, 'price' => 40000, 'barcode' => '8999901006030'],
                    ],
                ],
                [
                    'kode'        => 'APT-007',
                    'nama'        => 'Minyak Kayu Putih Cap Lang 60ml',
                    'kategori'    => 'Herbal & Tradisional',
                    'base_unit'   => 'Botol',
                    'deskripsi'   => 'Minyak kayu putih alami untuk menghangatkan tubuh dan meredakan gigitan serangga.',
                    'sku'         => 'APT-MKP-060',
                    'barcode'     => '8999901007013',
                    'harga_jual'  => 26000,
                    'harga_beli'  => 20000,
                    'batches'     => [
                        [
                            'batch_number' => 'MKP-28C01',
                            'expired_date' => Carbon::now()->addMonths(28)->toDateString(),
                            'qty'          => 48,
                        ],
                    ],
                    'units'       => [
                        ['name' => 'Lusin', 'multiplier' => 12, 'price' => 295000, 'barcode' => '8999901007020'],
                    ],
                ],
                [
                    'kode'        => 'APT-008',
                    'nama'        => 'Sanmol Sirup Paracetamol 60ml',
                    'kategori'    => 'Obat Bebas & Terbatas',
                    'base_unit'   => 'Botol',
                    'deskripsi'   => 'Sirup penurun panas dan pereda sakit khusus anak-anak rasa jeruk manis.',
                    'sku'         => 'APT-SNM-060',
                    'barcode'     => '8999901008010',
                    'harga_jual'  => 22000,
                    'harga_beli'  => 16500,
                    'batches'     => [
                        [
                            'batch_number' => 'SNM-27E01',
                            'expired_date' => Carbon::now()->addMonths(16)->toDateString(),
                            'qty'          => 36,
                        ],
                    ],
                    'units'       => [
                        ['name' => 'Lusin', 'multiplier' => 12, 'price' => 250000, 'barcode' => '8999901008027'],
                    ],
                ],
                [
                    'kode'        => 'APT-009',
                    'nama'        => 'Kasa Steril Husada 16x16 cm',
                    'kategori'    => 'Alat Kesehatan & P3K',
                    'base_unit'   => 'Lembar',
                    'deskripsi'   => 'Kasa pembalut steril berdaya serap tinggi untuk membalut dan melindungi luka.',
                    'sku'         => 'APT-KSA-016',
                    'barcode'     => '8999901009017',
                    'harga_jual'  => 1500,
                    'harga_beli'  => 800,
                    'batches'     => [
                        [
                            'batch_number' => 'KSA-29X01',
                            'expired_date' => Carbon::now()->addMonths(40)->toDateString(),
                            'qty'          => 200,
                        ],
                    ],
                    'units'       => [
                        ['name' => 'Box', 'multiplier' => 10, 'price' => 13500, 'barcode' => '8999901009024'],
                    ],
                ],
                [
                    'kode'        => 'APT-010',
                    'nama'        => 'Bodrex Sakit Kepala Tablet',
                    'kategori'    => 'Obat Bebas & Terbatas',
                    'base_unit'   => 'Tablet',
                    'deskripsi'   => 'Meredakan sakit kepala, sakit gigi, dan demam dengan formula aksi cepat.',
                    'sku'         => 'APT-BDX-001',
                    'barcode'     => '8999901010013',
                    'harga_jual'  => 1200,
                    'harga_beli'  => 600,
                    'batches'     => [
                        [
                            'batch_number' => 'BDX-26D01',
                            'expired_date' => Carbon::now()->addMonths(12)->toDateString(),
                            'qty'          => 400,
                        ],
                    ],
                    'units'       => [
                        ['name' => 'Blister', 'multiplier' => 4,   'price' => 4500,   'barcode' => '8999901010020'],
                        ['name' => 'Box',     'multiplier' => 100, 'price' => 105000, 'barcode' => '8999901010037'],
                    ],
                ],
            ];

            foreach ($productsData as $pData) {
                // 2a. Buat Produk
                $product = Product::updateOrCreate(
                    [
                        'store_id'    => $storeId,
                        'kode_produk' => $pData['kode'],
                    ],
                    [
                        'nama_produk'             => $pData['nama'],
                        'category_id'             => $categoryMap[$pData['kategori']] ?? null,
                        'base_unit'               => $pData['base_unit'],
                        'deskripsi'               => $pData['deskripsi'],
                        'product_type'            => 'SINGLE',
                        'default_commission_type' => 'none',
                        'default_commission_rate' => 0,
                    ]
                );

                // 2b. Buat Varian Reguler
                $variant = ProductVariant::updateOrCreate(
                    [
                        'store_id'   => $storeId,
                        'product_id' => $product->id,
                        'sku'        => $pData['sku'],
                    ],
                    [
                        'variant_name'      => 'Reguler',
                        'barcode'           => $pData['barcode'],
                        'harga_jual'        => $pData['harga_jual'],
                        'cost_price_manual' => $pData['harga_beli'],
                        'reward_points'     => 0,
                        'is_active'         => 'Y',
                        'track_stock'       => true,
                        'commission_type'   => 'global',
                        'commission_rate'   => 0,
                    ]
                );

                // 2c. Buat Multi Satuan Bertingkat
                foreach ($pData['units'] as $u) {
                    ProductUnit::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'name'       => $u['name'],
                        ],
                        [
                            'product_variant_id'  => $variant->id,
                            'multiplier'          => $u['multiplier'],
                            'price'               => $u['price'],
                            'barcode'             => $u['barcode'],
                            'is_default_purchase' => false,
                            'is_active'           => true,
                        ]
                    );
                }

                // 2d. Inisialisasi Stok Batch (FEFO dengan No Batch & Exp Date)
                // Hapus movement & batch lama store ini jika ada untuk sinkronisasi ulang yang bersih
                DB::table('stock_movements')->where('product_variant_id', $variant->id)->delete();
                DB::table('stock_batches')->where('product_variant_id', $variant->id)->delete();

                foreach ($pData['batches'] as $b) {
                    $batchId = DB::table('stock_batches')->insertGetId([
                        'product_variant_id' => $variant->id,
                        'posisi'             => 'store',
                        'batch_number'       => $b['batch_number'],
                        'expired_date'       => $b['expired_date'],
                        'tanggal_masuk'      => Carbon::now()->toDateString(),
                        'qty_awal'           => $b['qty'],
                        'qty_sisa'           => $b['qty'],
                        'harga_beli'         => $pData['harga_beli'],
                        'sumber'             => 'opname',
                        'created_at'         => Carbon::now(),
                        'updated_at'         => Carbon::now(),
                    ]);

                    DB::table('stock_movements')->insert([
                        'product_variant_id' => $variant->id,
                        'stock_batch_id'     => $batchId,
                        'posisi'             => 'store',
                        'tanggal'            => Carbon::now(),
                        'tipe'               => 'in',
                        'direction'          => 'in',
                        'qty'                => $b['qty'],
                        'ref_type'           => 'opname',
                        'ref_id'             => null,
                        'created_at'         => Carbon::now(),
                        'updated_at'         => Carbon::now(),
                    ]);
                }
            }
        });

        $this->command->info("Berhasil membuat 10 produk apotek lengkap dengan multi-satuan dan stok awal untuk Store ID: {$storeId}!");
    }
}
