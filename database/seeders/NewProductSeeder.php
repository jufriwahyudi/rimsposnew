<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemBatch;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\VariantAttribute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NewProductSeeder extends Seeder
{
    public function run(): void
    {
        // truncate seluruh tabel terkait untuk memastikan data benar-benar baru walaupun ada foreign key constraint

        // hapus sales batch → sales item → sales
        DB::table('sale_item_batches')->delete();
        DB::table('sale_items')->delete();
        DB::table('sales')->delete();
        // hapus goods receipt items → goods receipts
        DB::table('goods_receipt_items')->delete();
        DB::table('goods_receipts')->delete();
        // hapus purchase order items → purchase orders
        DB::table('purchase_order_items')->delete();
        DB::table('purchase_orders')->delete();
        // hapus relasi dulu
        DB::table('variant_attributes')->delete();
        DB::table('product_variant_barcodes')->delete();

        // hapus nilai atribut lalu atribut
        DB::table('attribute_values')->delete();
        DB::table('attributes')->where('kode', 'varian')->delete();

        // hapus product variants & products
        DB::table('stock_movements')->delete();
        DB::table('stock_batches')->delete();
        DB::table('product_variants')->delete();
        DB::table('products')->delete();

        // reset AUTO_INCREMENT (opsional)
        DB::statement('ALTER TABLE sale_item_batches AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE sale_items AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE sales AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE goods_receipt_items AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE goods_receipts AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE purchase_order_items AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE purchase_orders AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE variant_attributes AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE attribute_values AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE attributes AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE stock_movements AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE stock_batches AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE product_variants AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE products AUTO_INCREMENT = 1');

        $seq = 1;

        // Attribute generik "Varian" sebagai dimensi label varian
        $attrVarian = Attribute::firstOrCreate(
            ['kode' => 'varian'],
            ['nama' => 'Varian', 'urutan' => 1, 'store_id' => 1]
        );

        $products = [
            [
                'kode_produk' => 'TNR65S',
                'nama_produk' => 'Hanasui Blush On',
                'variants'    => [
                    ['label' => '03', 'harga_jual' => 35000, 'harga_beli' => 34000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => 'SP3NMF',
                'nama_produk' => 'Pixy Primer',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 47000, 'harga_beli' => 60000, 'qty' => 8],
                ],
            ],
            [
                'kode_produk' => 'AWZFDP',
                'nama_produk' => 'O.two.o Primer Zero',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 63000, 'harga_beli' => 58000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => '6C25AZ',
                'nama_produk' => 'Wardah Concealer Lightening',
                'variants'    => [
                    ['label' => '11C', 'harga_jual' => 42000, 'harga_beli' => 35000, 'qty' => 1],
                    ['label' => '32N', 'harga_jual' => 42000, 'harga_beli' => 35000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => '4MB2L7',
                'nama_produk' => 'Salsa Eyeshadow',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 35000, 'harga_beli' => 25000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => 'Q4EG73',
                'nama_produk' => 'G2g Body Serum',
                'variants'    => [
                    ['label' => 'niacinamide bright', 'harga_jual' => 45000, 'harga_beli' => 34000, 'qty' => 9],
                    ['label' => 'retinol bright', 'harga_jual' => 45000, 'harga_beli' => 33000, 'qty' => 17],
                    ['label' => 'tropikal Velvet Oren pum', 'harga_jual' => 65000, 'harga_beli' => 57000, 'qty' => 3],
                    ['label' => 'creamy berry pink pum', 'harga_jual' => 65000, 'harga_beli' => 57000, 'qty' => 4],
                    ['label' => 'creamy berry pink tube', 'harga_jual' => 47000, 'harga_beli' => 34000, 'qty' => 3],
                    ['label' => 'tropikal Velvet Oren tube', 'harga_jual' => 47000, 'harga_beli' => 34000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => '7XUCFJ',
                'nama_produk' => 'G2g Fw Tremela',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 38000, 'harga_beli' => 33000, 'qty' => 10],
                ],
            ],
            [
                'kode_produk' => '5BYQUF',
                'nama_produk' => 'G2g Fw Blueberry',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 38000, 'harga_beli' => 33000, 'qty' => 7],
                ],
            ],
            [
                'kode_produk' => '8SLVH2',
                'nama_produk' => 'G2g Fw Centela',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 38000, 'harga_beli' => 33000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'GXZ4F7',
                'nama_produk' => 'G2g Fw Low',
                'variants'    => [
                    ['label' => 'PH', 'harga_jual' => 38000, 'harga_beli' => 33000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => '6CJDWU',
                'nama_produk' => 'G2g Fw Nicinamide',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 39000, 'harga_beli' => 34000, 'qty' => 7],
                ],
            ],
            [
                'kode_produk' => 'CWHES7',
                'nama_produk' => 'G2g Fw Milk',
                'variants'    => [
                    ['label' => 'amino pum', 'harga_jual' => 42000, 'harga_beli' => 36000, 'qty' => 11],
                ],
            ],
            [
                'kode_produk' => 'MFQE8V',
                'nama_produk' => 'G2g Fw Vita',
                'variants'    => [
                    ['label' => 'c pum', 'harga_jual' => 43000, 'harga_beli' => 38000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'M5934F',
                'nama_produk' => 'G2g Fw Matcha',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 43000, 'harga_beli' => 38000, 'qty' => 6],
                ],
            ],
            [
                'kode_produk' => 'S9NPMF',
                'nama_produk' => 'G2g Toner Glycolic',
                'variants'    => [
                    ['label' => 'acid 7%', 'harga_jual' => 47000, 'harga_beli' => 42000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'KXFVGJ',
                'nama_produk' => 'G2g Toner Propolis',
                'variants'    => [
                    ['label' => 'kuning', 'harga_jual' => 40000, 'harga_beli' => 35000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'DV9A3H',
                'nama_produk' => 'G2g Toner Blacberry',
                'variants'    => [
                    ['label' => 'ungu', 'harga_jual' => 40000, 'harga_beli' => 35000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'QYKC2B',
                'nama_produk' => 'G2g Toner Promegranate',
                'variants'    => [
                    ['label' => 'pink', 'harga_jual' => 44000, 'harga_beli' => 39000, 'qty' => 8],
                ],
            ],
            [
                'kode_produk' => 'RKJNP3',
                'nama_produk' => 'G2g Serum Promegranate',
                'variants'    => [
                    ['label' => 'pink', 'harga_jual' => 38000, 'harga_beli' => 33000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'TFRVGD',
                'nama_produk' => 'G2g Serum Jeju',
                'variants'    => [
                    ['label' => 'tangerine kuning', 'harga_jual' => 48000, 'harga_beli' => 42000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'GTYZ84',
                'nama_produk' => 'G2g Serum Centela',
                'variants'    => [
                    ['label' => 'hijau', 'harga_jual' => 40000, 'harga_beli' => 34000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'GZUKY3',
                'nama_produk' => 'G2g Serum Dark',
                'variants'    => [
                    ['label' => 'spot', 'harga_jual' => 42000, 'harga_beli' => 33000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => 'YBFJT7',
                'nama_produk' => 'G2g Serum Retinol',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 48000, 'harga_beli' => 42000, 'qty' => 7],
                ],
            ],
            [
                'kode_produk' => 'KRXC6N',
                'nama_produk' => 'G2g Serum Intensive',
                'variants'    => [
                    ['label' => 'peeling', 'harga_jual' => 48000, 'harga_beli' => 42000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '7Z5D98',
                'nama_produk' => 'G2g Mois Pink',
                'variants'    => [
                    ['label' => '100gr pum', 'harga_jual' => 82000, 'harga_beli' => 36000, 'qty' => 6],
                    ['label' => '100gr jar', 'harga_jual' => 90000, 'harga_beli' => 85000, 'qty' => 6],
                    ['label' => '30gr jar', 'harga_jual' => 43000, 'harga_beli' => 39000, 'qty' => 7],
                ],
            ],
            [
                'kode_produk' => 'EK293S',
                'nama_produk' => 'G2g Mois Putih',
                'variants'    => [
                    ['label' => '100gr jar', 'harga_jual' => 100000, 'harga_beli' => 93000, 'qty' => 7],
                    ['label' => '30gr jar', 'harga_jual' => 47000, 'harga_beli' => 42000, 'qty' => 13],
                ],
            ],
            [
                'kode_produk' => '3CAZY7',
                'nama_produk' => 'G2g Mois Centela',
                'variants'    => [
                    ['label' => '30gr', 'harga_jual' => 39000, 'harga_beli' => 34000, 'qty' => 5],
                    ['label' => '55gr', 'harga_jual' => 47000, 'harga_beli' => 42000, 'qty' => 1],
                    ['label' => '100gr pum', 'harga_jual' => 65000, 'harga_beli' => 59000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'DFPS4B',
                'nama_produk' => 'G2g Mois Blueberry',
                'variants'    => [
                    ['label' => 'ungu 30gr', 'harga_jual' => 43000, 'harga_beli' => 39000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => 'JBTZY8',
                'nama_produk' => 'G2g Mois Vita',
                'variants'    => [
                    ['label' => 'c kuning 30gr', 'harga_jual' => 50000, 'harga_beli' => 46000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => '3KWQY9',
                'nama_produk' => 'G2g Mois Tremella',
                'variants'    => [
                    ['label' => 'Vita B5 30gr', 'harga_jual' => 43000, 'harga_beli' => 39000, 'qty' => 7],
                ],
            ],
            [
                'kode_produk' => 'NFYE4C',
                'nama_produk' => 'G2g Mois Kiwi',
                'variants'    => [
                    ['label' => '3D acid', 'harga_jual' => 43000, 'harga_beli' => 39000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => '6RXN8M',
                'nama_produk' => 'G2g Mois Retinol',
                'variants'    => [
                    ['label' => '30gr', 'harga_jual' => 47000, 'harga_beli' => 42000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => '6EXHZ3',
                'nama_produk' => 'G2g M.w Cherry',
                'variants'    => [
                    ['label' => 'blossom 300ml', 'harga_jual' => 47000, 'harga_beli' => 42000, 'qty' => 9],
                    ['label' => 'blosom all in one 130ml', 'harga_jual' => 28000, 'harga_beli' => 25000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => '3U6QDM',
                'nama_produk' => 'G2g M.w Britening',
                'variants'    => [
                    ['label' => 'all in one 300ml', 'harga_jual' => 47000, 'harga_beli' => 42000, 'qty' => 8],
                ],
            ],
            [
                'kode_produk' => 'M4DEQW',
                'nama_produk' => 'G2g M.w Pore',
                'variants'    => [
                    ['label' => 'clearing all in one 300ml', 'harga_jual' => 47000, 'harga_beli' => 42000, 'qty' => 5],
                    ['label' => 'clearing all in one 130 ml', 'harga_jual' => 28000, 'harga_beli' => 25000, 'qty' => 7],
                    ['label' => 'britening all in one 130ml', 'harga_jual' => 28000, 'harga_beli' => 25000, 'qty' => 10],
                ],
            ],
            [
                'kode_produk' => 'N3B5TQ',
                'nama_produk' => 'G2g M.w Tremella',
                'variants'    => [
                    ['label' => 'panthenol 300ml', 'harga_jual' => 47000, 'harga_beli' => 42000, 'qty' => 3],
                    ['label' => 'panthenol 130 ml', 'harga_jual' => 28000, 'harga_beli' => 25000, 'qty' => 7],
                ],
            ],
            [
                'kode_produk' => 'A2H7R3',
                'nama_produk' => 'G2g M.w Vita',
                'variants'    => [
                    ['label' => 'C 300ml', 'harga_jual' => 47000, 'harga_beli' => 42000, 'qty' => 6],
                    ['label' => 'C 130 ml', 'harga_jual' => 28000, 'harga_beli' => 25000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => '7XFANE',
                'nama_produk' => 'G2g M.w Mugword',
                'variants'    => [
                    ['label' => 'hijau 300ml', 'harga_jual' => 47000, 'harga_beli' => 42000, 'qty' => 3],
                    ['label' => 'hijau 130 ml', 'harga_jual' => 28000, 'harga_beli' => 25000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'KAE5WJ',
                'nama_produk' => 'G2g Remover Penthanol',
                'variants'    => [
                    ['label' => '150ml', 'harga_jual' => 47000, 'harga_beli' => 42000, 'qty' => 2],
                    ['label' => '60ml', 'harga_jual' => 30000, 'harga_beli' => 25000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'FZC4KV',
                'nama_produk' => 'G2g Cleansing Oil',
                'variants'    => [
                    ['label' => '100 ml', 'harga_jual' => 56000, 'harga_beli' => 51000, 'qty' => 3],
                    ['label' => '200 ml', 'harga_jual' => 90000, 'harga_beli' => 85000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'CGMN25',
                'nama_produk' => 'G2g Acne Patch',
                'variants'    => [
                    ['label' => 'nigh', 'harga_jual' => 27000, 'harga_beli' => 22000, 'qty' => 5],
                    ['label' => 'day', 'harga_jual' => 27000, 'harga_beli' => 22000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => '3NWJXH',
                'nama_produk' => 'G2g Acne Driying',
                'variants'    => [
                    ['label' => 'lotion totol jerawat', 'harga_jual' => 65000, 'harga_beli' => 59000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '59ZEDM',
                'nama_produk' => 'G2g Mugwort Acne',
                'variants'    => [
                    ['label' => 'gel mask', 'harga_jual' => 40000, 'harga_beli' => 33000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => 'CS5NZJ',
                'nama_produk' => 'G2g Volcano Clay',
                'variants'    => [
                    ['label' => 'mask 30gr', 'harga_jual' => 40000, 'harga_beli' => 35000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => 'PTNUFX',
                'nama_produk' => 'G2g Clay Stick',
                'variants'    => [
                    ['label' => 'volcano 25gr', 'harga_jual' => 43000, 'harga_beli' => 38000, 'qty' => 5],
                    ['label' => 'pomegranate 25gr', 'harga_jual' => 43000, 'harga_beli' => 38000, 'qty' => 4],
                    ['label' => 'acne 25gr', 'harga_jual' => 43000, 'harga_beli' => 38000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => '3GD6XW',
                'nama_produk' => 'G2g Bright Up',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 50000, 'harga_beli' => 42000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'YRDA8P',
                'nama_produk' => 'G2g Doble Bright',
                'variants'    => [
                    ['label' => 'day cream', 'harga_jual' => 72000, 'harga_beli' => 67000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'EH6GXV',
                'nama_produk' => 'G2g Lip Serum',
                'variants'    => [
                    ['label' => 'clear', 'harga_jual' => 56000, 'harga_beli' => 51000, 'qty' => 2],
                    ['label' => 'berry', 'harga_jual' => 56000, 'harga_beli' => 51000, 'qty' => 2],
                    ['label' => 'pink', 'harga_jual' => 55000, 'harga_beli' => 46000, 'qty' => 3],
                    ['label' => 'peach', 'harga_jual' => 55000, 'harga_beli' => 51000, 'qty' => 7],
                    ['label' => 'mixberry', 'harga_jual' => 50000, 'harga_beli' => 42000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '4HFZUD',
                'nama_produk' => 'G2g Serum Sprey',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 75000, 'harga_beli' => 68000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => 'REKWTQ',
                'nama_produk' => 'G2g Setting Sprey',
                'variants'    => [
                    ['label' => 'pink', 'harga_jual' => 65000, 'harga_beli' => 53000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'SZWJDG',
                'nama_produk' => 'G2g Cushion 2',
                'variants'    => [
                    ['label' => 'in 1 01', 'harga_jual' => 160000, 'harga_beli' => 158000, 'qty' => 1],
                    ['label' => 'in 1 02', 'harga_jual' => 160000, 'harga_beli' => 144000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'MRKY9N',
                'nama_produk' => 'G2g Powder Fondation',
                'variants'    => [
                    ['label' => '00', 'harga_jual' => 80000, 'harga_beli' => 68000, 'qty' => 1],
                    ['label' => '01', 'harga_jual' => 80000, 'harga_beli' => 68000, 'qty' => 2],
                    ['label' => '02', 'harga_jual' => 80000, 'harga_beli' => 68000, 'qty' => 3],
                    ['label' => '03', 'harga_jual' => 80000, 'harga_beli' => 78000, 'qty' => 2],
                    ['label' => '04', 'harga_jual' => 80000, 'harga_beli' => 68000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'Z67MNU',
                'nama_produk' => 'G2g Cushion Pink',
                'variants'    => [
                    ['label' => '00', 'harga_jual' => 89000, 'harga_beli' => 84000, 'qty' => 2],
                    ['label' => '01', 'harga_jual' => 89000, 'harga_beli' => 84000, 'qty' => 2],
                    ['label' => '03', 'harga_jual' => 89000, 'harga_beli' => 84000, 'qty' => 2],
                    ['label' => '04', 'harga_jual' => 89000, 'harga_beli' => 84000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'WSGPY2',
                'nama_produk' => 'G2g Cushion Refil',
                'variants'    => [
                    ['label' => '00', 'harga_jual' => 72000, 'harga_beli' => 68000, 'qty' => 2],
                    ['label' => '01', 'harga_jual' => 72000, 'harga_beli' => 67000, 'qty' => 1],
                    ['label' => '02', 'harga_jual' => 72000, 'harga_beli' => 68000, 'qty' => 2],
                    ['label' => '03', 'harga_jual' => 72000, 'harga_beli' => 68000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '72V935',
                'nama_produk' => 'G2g Cushion Silver',
                'variants'    => [
                    ['label' => '01', 'harga_jual' => 90000, 'harga_beli' => 85000, 'qty' => 2],
                    ['label' => '02', 'harga_jual' => 90000, 'harga_beli' => 85000, 'qty' => 0],
                    ['label' => '03', 'harga_jual' => 90000, 'harga_beli' => 85000, 'qty' => 1],
                    ['label' => '04', 'harga_jual' => 90000, 'harga_beli' => 85000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'DAX82L',
                'nama_produk' => 'G2g Skintnt 01',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 55000, 'harga_beli' => 45000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'H9FLYK',
                'nama_produk' => 'G2g Skintnt 02',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 55555, 'harga_beli' => 50000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => 'N2SZUF',
                'nama_produk' => 'G2g Skintnt 03',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 55000, 'harga_beli' => 45000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => '6CZ2L4',
                'nama_produk' => 'G2g Skintnt 04',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 55000, 'harga_beli' => 50000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'RK956T',
                'nama_produk' => 'Glamfik Finising Powder',
                'variants'    => [
                    ['label' => 'puff', 'harga_jual' => 12000, 'harga_beli' => 9000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'QAVL3C',
                'nama_produk' => 'Glamfik Loose Powder',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 18000, 'harga_beli' => 13000, 'qty' => 3],
                    ['label' => 'puff kuning', 'harga_jual' => 17000, 'harga_beli' => 13000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'RZ8YMB',
                'nama_produk' => 'Glamfik Wet &',
                'variants'    => [
                    ['label' => 'dry powder puff bulat', 'harga_jual' => 15000, 'harga_beli' => 9000, 'qty' => 3],
                    ['label' => 'dry powder puff segi empat', 'harga_jual' => 15000, 'harga_beli' => 9000, 'qty' => 3],
                    ['label' => 'dry powder powder puff campur isi dua', 'harga_jual' => 15000, 'harga_beli' => 11000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'MRF3SU',
                'nama_produk' => 'Glamfik Exellent Brush',
                'variants'    => [
                    ['label' => 'set pink', 'harga_jual' => 35000, 'harga_beli' => 24000, 'qty' => 2],
                    ['label' => 'set hijau', 'harga_jual' => 35000, 'harga_beli' => 24000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'D9HSEJ',
                'nama_produk' => 'Glamfik Eyebrow Trimmer',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 17000, 'harga_beli' => 14000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => '6F8HGD',
                'nama_produk' => 'Glamfik Eye Blending',
                'variants'    => [
                    ['label' => 'brush', 'harga_jual' => 17000, 'harga_beli' => 14000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'P467UF',
                'nama_produk' => 'Glamfik Flawless Eyeshadow',
                'variants'    => [
                    ['label' => 'blanding brush', 'harga_jual' => 15000, 'harga_beli' => 12000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'QAXEPS',
                'nama_produk' => 'Glamfik Blusher Brush',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 37000, 'harga_beli' => 32000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '58BZDT',
                'nama_produk' => 'Glamfik Precision Eyeshadow',
                'variants'    => [
                    ['label' => 'brush', 'harga_jual' => 17000, 'harga_beli' => 14000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'J2M63F',
                'nama_produk' => 'Glamfik Eyebrow Brush',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 15000, 'harga_beli' => 11000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'BG3RTM',
                'nama_produk' => 'Salsa Blush On',
                'variants'    => [
                    ['label' => '02', 'harga_jual' => 20000, 'harga_beli' => 16000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'EG8FAX',
                'nama_produk' => 'Salsa Highlighter 02',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 20000, 'harga_beli' => 16000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'VEQWB4',
                'nama_produk' => 'Salsa Highlighter 03',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 20000, 'harga_beli' => 16000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'EUY9JM',
                'nama_produk' => 'Salsa Highlighter 01',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 20000, 'harga_beli' => 16000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'XGFPB5',
                'nama_produk' => 'Just Miss Wonder',
                'variants'    => [
                    ['label' => 'pallate 05 ia ule', 'harga_jual' => 25000, 'harga_beli' => 20000, 'qty' => 1],
                    ['label' => 'pallate 06 fly me', 'harga_jual' => 25000, 'harga_beli' => 20000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => '3YQESV',
                'nama_produk' => 'Saniye Nude 02',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 40000, 'harga_beli' => 34000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'L5W7PB',
                'nama_produk' => 'Madamgie To Go',
                'variants'    => [
                    ['label' => 'eyeshadow', 'harga_jual' => 40000, 'harga_beli' => 34000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '7LAZK4',
                'nama_produk' => 'Fanbo Browcara 2',
                'variants'    => [
                    ['label' => 'in 1 natural', 'harga_jual' => 55000, 'harga_beli' => 47000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'CJYB8F',
                'nama_produk' => 'Focalur Pomade 05',
                'variants'    => [
                    ['label' => 'ebony', 'harga_jual' => 35000, 'harga_beli' => 27000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'CBAHMZ',
                'nama_produk' => 'Focalur Pomade 01',
                'variants'    => [
                    ['label' => 'auburn', 'harga_jual' => 35000, 'harga_beli' => 27000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'JN4A8H',
                'nama_produk' => 'Wrdh Colorvit Blush',
                'variants'    => [
                    ['label' => '05', 'harga_jual' => 50000, 'harga_beli' => 42000, 'qty' => 1],
                    ['label' => '06', 'harga_jual' => 50000, 'harga_beli' => 42000, 'qty' => 1],
                    ['label' => '03', 'harga_jual' => 50000, 'harga_beli' => 42000, 'qty' => 1],
                    ['label' => '01', 'harga_jual' => 50000, 'harga_beli' => 42000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '6M47H2',
                'nama_produk' => 'Focalur Glow &',
                'variants'    => [
                    ['label' => 'contour', 'harga_jual' => 35000, 'harga_beli' => 29000, 'qty' => 6],
                ],
            ],
            [
                'kode_produk' => 'HKLMPN',
                'nama_produk' => 'Saniye Eyeshadow',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 35000, 'harga_beli' => 28000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'GK8MUA',
                'nama_produk' => 'Brasov Blush On',
                'variants'    => [
                    ['label' => '01', 'harga_jual' => 18000, 'harga_beli' => 13000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'AN45TS',
                'nama_produk' => 'Saniye Eyes Of',
                'variants'    => [
                    ['label' => 'encantment 03', 'harga_jual' => 40000, 'harga_beli' => 36000, 'qty' => 1],
                    ['label' => 'encantment 02', 'harga_jual' => 40000, 'harga_beli' => 36000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => '3KHYTZ',
                'nama_produk' => 'Saniye 3 Colors',
                'variants'    => [
                    ['label' => 'face powder pallet 01', 'harga_jual' => 30000, 'harga_beli' => 26000, 'qty' => 0],
                    ['label' => 'face powder pallet 02', 'harga_jual' => 30000, 'harga_beli' => 26000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '238NY4',
                'nama_produk' => 'Skintifik Pure Vit',
                'variants'    => [
                    ['label' => 'c 1+1', 'harga_jual' => 130000, 'harga_beli' => 115000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => 'VLWYTA',
                'nama_produk' => 'Skintifik Mois Ceramide',
                'variants'    => [
                    ['label' => 'light texture', 'harga_jual' => 125000, 'harga_beli' => 107000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => '8BFAW2',
                'nama_produk' => 'Skintifik Serum 10%',
                'variants'    => [
                    ['label' => 'niacinamide', 'harga_jual' => 125000, 'harga_beli' => 104000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'GV4TYR',
                'nama_produk' => 'Skintifik Mois Niacinamide',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 125000, 'harga_beli' => 119000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'DCRHAN',
                'nama_produk' => 'Skintifik Mois Retinol',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 130000, 'harga_beli' => 114000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => '7AVLYF',
                'nama_produk' => 'Skintifik Serum Retinol',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 125000, 'harga_beli' => 107000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'LRGFJY',
                'nama_produk' => 'Skintifik Mois 377',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 125000, 'harga_beli' => 100000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'MWGUR7',
                'nama_produk' => 'Skintifik Serum 377',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 130000, 'harga_beli' => 109000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'GLZC7T',
                'nama_produk' => 'Skintifik Setting Sprey',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 105000, 'harga_beli' => 93000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'H28KM9',
                'nama_produk' => 'Skintifik M.w Niacinamide',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 68000, 'harga_beli' => 61000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'GDL9HC',
                'nama_produk' => 'Skintifik M.w Ceramide',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 68000, 'harga_beli' => 61000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'APY3L5',
                'nama_produk' => 'Skintifik Cleansing Oil',
                'variants'    => [
                    ['label' => 'centela', 'harga_jual' => 100000, 'harga_beli' => 89000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'JDFZEL',
                'nama_produk' => 'Skintifik Sun Sprey',
                'variants'    => [
                    ['label' => '120ml', 'harga_jual' => 95000, 'harga_beli' => 85000, 'qty' => 5],
                    ['label' => '70ml', 'harga_jual' => 75000, 'harga_beli' => 68000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'LPS75B',
                'nama_produk' => 'Skintifik Sun 5x',
                'variants'    => [
                    ['label' => 'ceramide', 'harga_jual' => 95000, 'harga_beli' => 80000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'MG93WJ',
                'nama_produk' => 'Skintifik Sun Bright',
                'variants'    => [
                    ['label' => 'fit pink', 'harga_jual' => 120000, 'harga_beli' => 107000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'EUTJ7Q',
                'nama_produk' => 'Skintifik Sun Matte',
                'variants'    => [
                    ['label' => 'fit ungu', 'harga_jual' => 95000, 'harga_beli' => 79000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '2WPSAC',
                'nama_produk' => 'Skintifik Fw Niacinamide',
                'variants'    => [
                    ['label' => '80ml', 'harga_jual' => 95000, 'harga_beli' => 76000, 'qty' => 1],
                    ['label' => '60ml', 'harga_jual' => 50000, 'harga_beli' => 41000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'RE9CYB',
                'nama_produk' => 'Skintifik Fw 5x',
                'variants'    => [
                    ['label' => 'ceramide 80ml', 'harga_jual' => 95000, 'harga_beli' => 76000, 'qty' => 2],
                    ['label' => 'ceramide 60 ml', 'harga_jual' => 50000, 'harga_beli' => 41000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'M92Q5C',
                'nama_produk' => 'Skintifik Fw Amino',
                'variants'    => [
                    ['label' => 'acid 100ml', 'harga_jual' => 95000, 'harga_beli' => 76000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => '2TA96P',
                'nama_produk' => 'Skintifik Fw Panthenol',
                'variants'    => [
                    ['label' => '80ml', 'harga_jual' => 50000, 'harga_beli' => 41000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => '5NQ7K2',
                'nama_produk' => 'Slavina Body Lotion',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 70000, 'harga_beli' => 63000, 'qty' => 7],
                ],
            ],
            [
                'kode_produk' => 'HZ9X8Y',
                'nama_produk' => 'Slavina Body Wash',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 70000, 'harga_beli' => 63000, 'qty' => 19],
                ],
            ],
            [
                'kode_produk' => 'TAZNWJ',
                'nama_produk' => 'Bizare Body Lotion',
                'variants'    => [
                    ['label' => 'young', 'harga_jual' => 30000, 'harga_beli' => 25000, 'qty' => 7],
                    ['label' => 'natural', 'harga_jual' => 30000, 'harga_beli' => 25000, 'qty' => 2],
                    ['label' => 'miracle', 'harga_jual' => 30000, 'harga_beli' => 25000, 'qty' => 6],
                ],
            ],
            [
                'kode_produk' => '7KJEQH',
                'nama_produk' => 'Scarlet Body Lotion',
                'variants'    => [
                    ['label' => 'freshy', 'harga_jual' => 60000, 'harga_beli' => 54000, 'qty' => 3],
                    ['label' => 'romansa', 'harga_jual' => 60000, 'harga_beli' => 54000, 'qty' => 2],
                    ['label' => 'jolly', 'harga_jual' => 60000, 'harga_beli' => 54000, 'qty' => 1],
                    ['label' => 'charming', 'harga_jual' => 60000, 'harga_beli' => 54000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'HMJRGX',
                'nama_produk' => 'Body White Toner',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 30000, 'harga_beli' => 24000, 'qty' => 9],
                ],
            ],
            [
                'kode_produk' => 'A95GEU',
                'nama_produk' => 'Body White Body',
                'variants'    => [
                    ['label' => 'lotion + serum', 'harga_jual' => 40000, 'harga_beli' => 27000, 'qty' => 1],
                    ['label' => 'lotion spf 30 + serum', 'harga_jual' => 45000, 'harga_beli' => 32000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => 'XNWR8U',
                'nama_produk' => 'Body White Serum',
                'variants'    => [
                    ['label' => '100 ml', 'harga_jual' => 35000, 'harga_beli' => 29000, 'qty' => 4],
                    ['label' => '30 ml', 'harga_jual' => 25000, 'harga_beli' => 14000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'ZCRPBH',
                'nama_produk' => 'Body White Exfoliting',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000, 'harga_beli' => 15000, 'qty' => 12],
                ],
            ],
            [
                'kode_produk' => 'CJ39H6',
                'nama_produk' => 'Body White Sabun',
                'variants'    => [
                    ['label' => 'mandi', 'harga_jual' => 37000, 'harga_beli' => 34000, 'qty' => 22],
                ],
            ],
            [
                'kode_produk' => 'J53LPT',
                'nama_produk' => 'Alovera 98%',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 60000, 'harga_beli' => 55000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => 'ZA587M',
                'nama_produk' => 'Vesssica Masker Putih',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 40000, 'harga_beli' => 32000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'MFLP36',
                'nama_produk' => 'Vesssica Masker Hijau',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 40000, 'harga_beli' => 32000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'SDTCV8',
                'nama_produk' => 'The Originot Mois',
                'variants'    => [
                    ['label' => 'hyalucera', 'harga_jual' => 40000, 'harga_beli' => 31000, 'qty' => 7],
                    ['label' => 'cica B5', 'harga_jual' => 50000, 'harga_beli' => 45000, 'qty' => 3],
                    ['label' => 'britening', 'harga_jual' => 50000, 'harga_beli' => 45000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => '4SP27C',
                'nama_produk' => 'Omg Mois 2%',
                'variants'    => [
                    ['label' => 'cica', 'harga_jual' => 35000, 'harga_beli' => 31000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'L37PKW',
                'nama_produk' => 'Omg Mois 5%',
                'variants'    => [
                    ['label' => 'niacinamide', 'harga_jual' => 35000, 'harga_beli' => 31000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'BHVJ3X',
                'nama_produk' => 'Scora Serum Cica',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 27000, 'harga_beli' => 22000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'AQM925',
                'nama_produk' => 'Scora Serum Glow',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 30000, 'harga_beli' => 24000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'TBMVJP',
                'nama_produk' => 'Scora Serum Barier',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 27000, 'harga_beli' => 22000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'JGANR8',
                'nama_produk' => 'Scora Mois Panthenol',
                'variants'    => [
                    ['label' => '40 ml', 'harga_jual' => 40000, 'harga_beli' => 36000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'KPS7UR',
                'nama_produk' => 'Scora Mois Niacinamide',
                'variants'    => [
                    ['label' => '100 ml', 'harga_jual' => 65000, 'harga_beli' => 59000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'T58Q3A',
                'nama_produk' => 'Glowsophy Mois Brigh',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 50000, 'harga_beli' => 42000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'BR9DK7',
                'nama_produk' => 'Glowsophy Mois Cica',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 50000, 'harga_beli' => 44000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'LSJKCB',
                'nama_produk' => 'Glowies Dreamy Moisturizer',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 125000, 'harga_beli' => 96000, 'qty' => 23],
                ],
            ],
            [
                'kode_produk' => '2NFRS5',
                'nama_produk' => 'Tisha Body Serum',
                'variants'    => [
                    ['label' => 'pink', 'harga_jual' => 25000, 'harga_beli' => 21000, 'qty' => 2],
                    ['label' => 'hijau', 'harga_jual' => 25000, 'harga_beli' => 21000, 'qty' => 1],
                    ['label' => 'orange', 'harga_jual' => 25000, 'harga_beli' => 21000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '6Z8734',
                'nama_produk' => 'Natur E Body',
                'variants'    => [
                    ['label' => 'lotion pink 245 ml', 'harga_jual' => 30000, 'harga_beli' => 26000, 'qty' => 1],
                    ['label' => 'lotion pink 100 ml', 'harga_jual' => 17000, 'harga_beli' => 13000, 'qty' => 1],
                    ['label' => 'lotion hijau 100 ml', 'harga_jual' => 15000, 'harga_beli' => 11000, 'qty' => 2],
                    ['label' => 'lotion hijau 245 ml', 'harga_jual' => 27000, 'harga_beli' => 22000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'WETRAJ',
                'nama_produk' => 'Enchanteur Body Lotion',
                'variants'    => [
                    ['label' => 'kuning 100 ml', 'harga_jual' => 18000, 'harga_beli' => 14000, 'qty' => 1],
                    ['label' => 'pink 100 ml', 'harga_jual' => 18000, 'harga_beli' => 14000, 'qty' => 2],
                    ['label' => 'ungu 100 ml', 'harga_jual' => 18000, 'harga_beli' => 14000, 'qty' => 3],
                    ['label' => 'kuning 200 ml', 'harga_jual' => 30000, 'harga_beli' => 26000, 'qty' => 2],
                    ['label' => 'pink 200ml', 'harga_jual' => 30000, 'harga_beli' => 26000, 'qty' => 2],
                    ['label' => 'ungu 200 ml', 'harga_jual' => 30000, 'harga_beli' => 26000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'NAY6LB',
                'nama_produk' => 'Wrdh Fw Renew',
                'variants'    => [
                    ['label' => 'you', 'harga_jual' => 38000, 'harga_beli' => 32000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => '6QZDNS',
                'nama_produk' => 'Wrdh Fw Hydra',
                'variants'    => [
                    ['label' => 'rose pink 100 ml', 'harga_jual' => 38000, 'harga_beli' => 30000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'TEJD89',
                'nama_produk' => 'Wrdh Fw C-defense',
                'variants'    => [
                    ['label' => '100 ml', 'harga_jual' => 35000, 'harga_beli' => 29000, 'qty' => 1],
                    ['label' => '50 ml', 'harga_jual' => 25000, 'harga_beli' => 19000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'BUV538',
                'nama_produk' => 'Wrdh Fw Light',
                'variants'    => [
                    ['label' => 'whip 100 ml', 'harga_jual' => 35000, 'harga_beli' => 27000, 'qty' => 1],
                    ['label' => 'gentle 100 ml', 'harga_jual' => 35000, 'harga_beli' => 25000, 'qty' => 0],
                    ['label' => 'gentle 50 ml', 'harga_jual' => 23000, 'harga_beli' => 18000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => 'HWNPUG',
                'nama_produk' => 'Wrdh Fw Crystal',
                'variants'    => [
                    ['label' => 'secreet', 'harga_jual' => 38000, 'harga_beli' => 30000, 'qty' => 14],
                ],
            ],
            [
                'kode_produk' => 'BW9M4F',
                'nama_produk' => 'Wrdh Fw Nature',
                'variants'    => [
                    ['label' => 'daily', 'harga_jual' => 30000, 'harga_beli' => 24000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => 'LMVD35',
                'nama_produk' => 'Wrdh Milk Clenser',
                'variants'    => [
                    ['label' => 'crystal secreet', 'harga_jual' => 30000, 'harga_beli' => 26000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'VXZQBG',
                'nama_produk' => 'Wrdh Fw Bright',
                'variants'    => [
                    ['label' => 'now glutation 100 ml', 'harga_jual' => 30000, 'harga_beli' => 25000, 'qty' => 3],
                    ['label' => 'vit c 100 ml', 'harga_jual' => 30000, 'harga_beli' => 25000, 'qty' => 3],
                    ['label' => 'vit c 50 ml', 'harga_jual' => 20000, 'harga_beli' => 15000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'Y9XDHA',
                'nama_produk' => 'Wrdh Fw Acne',
                'variants'    => [
                    ['label' => '100 ml', 'harga_jual' => 35000, 'harga_beli' => 27000, 'qty' => 2],
                    ['label' => '50 ml', 'harga_jual' => 23000, 'harga_beli' => 19000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'BUP9QW',
                'nama_produk' => 'Wrdh Fw Aloe',
                'variants'    => [
                    ['label' => 'cica 100 ml', 'harga_jual' => 30000, 'harga_beli' => 25000, 'qty' => 3],
                    ['label' => 'cica 50 ml', 'harga_jual' => 20000, 'harga_beli' => 17000, 'qty' => 1],
                    ['label' => 'cooling bright 100 ml', 'harga_jual' => 28000, 'harga_beli' => 25000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '5B3QY2',
                'nama_produk' => 'Wrdh Mois Aloe',
                'variants'    => [
                    ['label' => 'nature dayly 100 ml', 'harga_jual' => 35000, 'harga_beli' => 29000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => '5QLG6E',
                'nama_produk' => 'Wrdh Mois 14x',
                'variants'    => [
                    ['label' => 'hyaluron + pentavitin 30 gr', 'harga_jual' => 95000, 'harga_beli' => 81000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'KWRP4C',
                'nama_produk' => 'Wrdh Mois Cica',
                'variants'    => [
                    ['label' => 'komplek+panthenol 30 gr', 'harga_jual' => 95000, 'harga_beli' => 81000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => '3DLPGA',
                'nama_produk' => 'Wrdh Mois Vit',
                'variants'    => [
                    ['label' => 'c+adenosine 30 gr', 'harga_jual' => 95000, 'harga_beli' => 81000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'CAH2D7',
                'nama_produk' => 'Wrdh Day Light',
                'variants'    => [
                    ['label' => '30gr', 'harga_jual' => 33000, 'harga_beli' => 26000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'MR2ZDW',
                'nama_produk' => 'Wrdh Nigh Retinol',
                'variants'    => [
                    ['label' => 'microkaps 30gr', 'harga_jual' => 95000, 'harga_beli' => 86000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'K72YJ3',
                'nama_produk' => 'Wrdh Light For',
                'variants'    => [
                    ['label' => 'day 20 gr', 'harga_jual' => 33000, 'harga_beli' => 26000, 'qty' => 3],
                    ['label' => 'day & night 20 gr', 'harga_jual' => 33000, 'harga_beli' => 26000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '3U5YFZ',
                'nama_produk' => 'Wrdh Serum 399',
                'variants'    => [
                    ['label' => '15 ml', 'harga_jual' => 40000, 'harga_beli' => 30000, 'qty' => 1],
                    ['label' => '30 ml', 'harga_jual' => 75000, 'harga_beli' => 60000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'HN82MS',
                'nama_produk' => 'Wrdh Mois Crystal',
                'variants'    => [
                    ['label' => 'day secreet 30 gr', 'harga_jual' => 95000, 'harga_beli' => 82000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'HMXWC7',
                'nama_produk' => 'Wrdh Mois Nigh',
                'variants'    => [
                    ['label' => 'crystal secreet 15 gr', 'harga_jual' => 50000, 'harga_beli' => 44000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => 'ZRL2TE',
                'nama_produk' => 'Wrdh Day Crystal',
                'variants'    => [
                    ['label' => 'secreet 15 gr', 'harga_jual' => 50000, 'harga_beli' => 42000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'W534JU',
                'nama_produk' => 'Wrdh Serum Crystal',
                'variants'    => [
                    ['label' => 'secreet 20 ml', 'harga_jual' => 90000, 'harga_beli' => 79000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '3WNQBM',
                'nama_produk' => 'Wrdh Mois C-defence',
                'variants'    => [
                    ['label' => '30 gr', 'harga_jual' => 50000, 'harga_beli' => 43000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => '6UKNX7',
                'nama_produk' => 'Wrdh Day Lightening',
                'variants'    => [
                    ['label' => '20 gr', 'harga_jual' => 30000, 'harga_beli' => 26000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'DSU98B',
                'nama_produk' => 'Wrdh Mois Bright',
                'variants'    => [
                    ['label' => 'oil control 20 ml', 'harga_jual' => 30000, 'harga_beli' => 21000, 'qty' => 2],
                    ['label' => 'smooth 20 ml', 'harga_jual' => 30000, 'harga_beli' => 21000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'BMXUAY',
                'nama_produk' => 'Wrdh Mois Day',
                'variants'    => [
                    ['label' => 'microcaps 30 gr', 'harga_jual' => 95000, 'harga_beli' => 86000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'HWYNJC',
                'nama_produk' => 'Wrdh Mois &',
                'variants'    => [
                    ['label' => 'day ceramide maxtryl 15 gr', 'harga_jual' => 50000, 'harga_beli' => 43000, 'qty' => 3],
                    ['label' => 'night retinol microcaps maxtryl 15 gr', 'harga_jual' => 50000, 'harga_beli' => 43000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'ZQCXTK',
                'nama_produk' => 'Skintifik Cushion Gold',
                'variants'    => [
                    ['label' => '03 A', 'harga_jual' => 150000, 'harga_beli' => 130000, 'qty' => 1],
                    ['label' => '01', 'harga_jual' => 150000, 'harga_beli' => 130000, 'qty' => 1],
                    ['label' => '02', 'harga_jual' => 150000, 'harga_beli' => 130000, 'qty' => 2],
                    ['label' => '03', 'harga_jual' => 150000, 'harga_beli' => 130000, 'qty' => 2],
                    ['label' => '04', 'harga_jual' => 150000, 'harga_beli' => 130000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'NXJHR9',
                'nama_produk' => 'Skintifik Cushion Pink',
                'variants'    => [
                    ['label' => '01', 'harga_jual' => 150000, 'harga_beli' => 117000, 'qty' => 1],
                    ['label' => '02', 'harga_jual' => 150000, 'harga_beli' => 117000, 'qty' => 1],
                    ['label' => '03', 'harga_jual' => 150000, 'harga_beli' => 117000, 'qty' => 1],
                    ['label' => '04', 'harga_jual' => 150000, 'harga_beli' => 130000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'U83DNB',
                'nama_produk' => 'Skintifik Refil Cushion',
                'variants'    => [
                    ['label' => 'silver 03', 'harga_jual' => 120000, 'harga_beli' => 95000, 'qty' => 3],
                    ['label' => 'silver 02', 'harga_jual' => 120000, 'harga_beli' => 95000, 'qty' => 1],
                    ['label' => 'silver 01', 'harga_jual' => 120000, 'harga_beli' => 95000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'S3GRQX',
                'nama_produk' => 'Skintifik Cushion Biru',
                'variants'    => [
                    ['label' => '00', 'harga_jual' => 150000, 'harga_beli' => 115000, 'qty' => 2],
                    ['label' => '01', 'harga_jual' => 150000, 'harga_beli' => 130000, 'qty' => 3],
                    ['label' => '02', 'harga_jual' => 150000, 'harga_beli' => 130000, 'qty' => 1],
                    ['label' => '03', 'harga_jual' => 150000, 'harga_beli' => 130000, 'qty' => 2],
                    ['label' => '04', 'harga_jual' => 150000, 'harga_beli' => 130000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'P6YRG9',
                'nama_produk' => 'Skintifik Cushion Reflil',
                'variants'    => [
                    ['label' => 'biru 00', 'harga_jual' => 120000, 'harga_beli' => 95000, 'qty' => 3],
                    ['label' => 'biru 01', 'harga_jual' => 120000, 'harga_beli' => 95000, 'qty' => 2],
                    ['label' => 'biru 02', 'harga_jual' => 120000, 'harga_beli' => 95000, 'qty' => 0],
                    ['label' => 'biru 03', 'harga_jual' => 120000, 'harga_beli' => 95000, 'qty' => 0],
                    ['label' => 'biru 04', 'harga_jual' => 120000, 'harga_beli' => 95000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'LJKN5P',
                'nama_produk' => 'Skintifik Bedak Padat',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 135000, 'harga_beli' => 115000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => 'R2PK8C',
                'nama_produk' => 'Skintifik Skin Tint',
                'variants'    => [
                    ['label' => '01', 'harga_jual' => 85000, 'harga_beli' => 76000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '2783LR',
                'nama_produk' => 'Esqa Cushion Granula',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 155000, 'harga_beli' => 138000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'P4RHQ3',
                'nama_produk' => 'Esqa Cushion Pancake',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 155000, 'harga_beli' => 138000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'YATJVX',
                'nama_produk' => 'Esqa Cushion Custard',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 155000, 'harga_beli' => 138000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'A6DPEZ',
                'nama_produk' => 'Esqa Cushion Milkshake',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 155000, 'harga_beli' => 138000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'N2T7XS',
                'nama_produk' => 'Wrdh Cushion Glow',
                'variants'    => [
                    ['label' => '11 C 15gr', 'harga_jual' => 110000, 'harga_beli' => 92000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'YACQ2W',
                'nama_produk' => 'Wrdh Colorit Valvet',
                'variants'    => [
                    ['label' => 'powder fondation refil 21 C', 'harga_jual' => 50000, 'harga_beli' => 46000, 'qty' => 0],
                    ['label' => 'powder fondation refil 33 W', 'harga_jual' => 50000, 'harga_beli' => 46000, 'qty' => 1],
                    ['label' => 'powder fondation refil 42 N', 'harga_jual' => 50000, 'harga_beli' => 46000, 'qty' => 1],
                    ['label' => 'powder fondation refil 31 C', 'harga_jual' => 50000, 'harga_beli' => 46000, 'qty' => 1],
                    ['label' => 'powder fondation 32 N', 'harga_jual' => 75000, 'harga_beli' => 64000, 'qty' => 3],
                    ['label' => 'powder fondation 43W', 'harga_jual' => 75000, 'harga_beli' => 64000, 'qty' => 2],
                    ['label' => 'powder fondation 11 C', 'harga_jual' => 75000, 'harga_beli' => 64000, 'qty' => 1],
                    ['label' => 'powder fondation 31C', 'harga_jual' => 75000, 'harga_beli' => 64000, 'qty' => 1],
                    ['label' => 'powder fondation 33 W', 'harga_jual' => 75000, 'harga_beli' => 64000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'YZEBHM',
                'nama_produk' => 'Wrdh Colorfit Glow',
                'variants'    => [
                    ['label' => 'cushion 31W', 'harga_jual' => 115000, 'harga_beli' => 95000, 'qty' => 1],
                    ['label' => 'cushion 32N', 'harga_jual' => 115000, 'harga_beli' => 95000, 'qty' => 1],
                    ['label' => 'cushion 22N', 'harga_jual' => 115000, 'harga_beli' => 95000, 'qty' => 1],
                    ['label' => 'cushion 33W', 'harga_jual' => 115000, 'harga_beli' => 95000, 'qty' => 1],
                    ['label' => 'cushion 42N', 'harga_jual' => 115000, 'harga_beli' => 95000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'R347ZQ',
                'nama_produk' => 'Wrdh Colorfit 5d',
                'variants'    => [
                    ['label' => 'cushion 23W', 'harga_jual' => 125000, 'harga_beli' => 109000, 'qty' => 1],
                    ['label' => 'cushion 31W', 'harga_jual' => 125000, 'harga_beli' => 109000, 'qty' => 1],
                    ['label' => 'cushion 32N', 'harga_jual' => 125000, 'harga_beli' => 109000, 'qty' => 1],
                    ['label' => 'cushion 33W', 'harga_jual' => 125000, 'harga_beli' => 109000, 'qty' => 1],
                    ['label' => 'cushion 21W', 'harga_jual' => 125000, 'harga_beli' => 109000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'L9MWRF',
                'nama_produk' => 'Make Over Matte',
                'variants'    => [
                    ['label' => 'powder N10', 'harga_jual' => 160000, 'harga_beli' => 144000, 'qty' => 1],
                    ['label' => 'powder W32', 'harga_jual' => 160000, 'harga_beli' => 144000, 'qty' => 1],
                    ['label' => 'powder W30', 'harga_jual' => 160000, 'harga_beli' => 144000, 'qty' => 1],
                    ['label' => 'powder W20', 'harga_jual' => 160000, 'harga_beli' => 144000, 'qty' => 1],
                    ['label' => 'powder W22', 'harga_jual' => 160000, 'harga_beli' => 144000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '9JFN3Y',
                'nama_produk' => 'Make Over Hidrastay',
                'variants'    => [
                    ['label' => 'matte cushion W21', 'harga_jual' => 180000, 'harga_beli' => 164000, 'qty' => 1],
                    ['label' => 'cushion W12', 'harga_jual' => 180000, 'harga_beli' => 164000, 'qty' => 1],
                    ['label' => 'matte cushion N30', 'harga_jual' => 180000, 'harga_beli' => 164000, 'qty' => 1],
                    ['label' => 'matte cushion W32', 'harga_jual' => 180000, 'harga_beli' => 164000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '2LBG7K',
                'nama_produk' => 'Make Over Powerstay',
                'variants'    => [
                    ['label' => 'cushion W21', 'harga_jual' => 180000, 'harga_beli' => 164000, 'qty' => 1],
                    ['label' => 'cushion N20', 'harga_jual' => 180000, 'harga_beli' => 164000, 'qty' => 1],
                    ['label' => 'cushion W32', 'harga_jual' => 180000, 'harga_beli' => 164000, 'qty' => 1],
                    ['label' => 'lip matte', 'harga_jual' => 110000, 'harga_beli' => 94000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => 'KFEYNP',
                'nama_produk' => 'Wrdh Luminous Powder',
                'variants'    => [
                    ['label' => '01', 'harga_jual' => 40000, 'harga_beli' => 34000, 'qty' => 2],
                    ['label' => '02', 'harga_jual' => 40000, 'harga_beli' => 34000, 'qty' => 1],
                    ['label' => '03', 'harga_jual' => 40000, 'harga_beli' => 34000, 'qty' => 0],
                    ['label' => '04', 'harga_jual' => 40000, 'harga_beli' => 34000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'J7NVHE',
                'nama_produk' => 'Wrdh Luminous Twe',
                'variants'    => [
                    ['label' => 'cake 01', 'harga_jual' => 48000, 'harga_beli' => 34000, 'qty' => 1],
                    ['label' => 'cake 04', 'harga_jual' => 48000, 'harga_beli' => 34000, 'qty' => 1],
                    ['label' => 'cake refil 01', 'harga_jual' => 35000, 'harga_beli' => 28000, 'qty' => 1],
                    ['label' => 'cake refil 02', 'harga_jual' => 35000, 'harga_beli' => 27000, 'qty' => 3],
                    ['label' => 'cake refil 03', 'harga_jual' => 35000, 'harga_beli' => 26000, 'qty' => 3],
                    ['label' => 'cake refil 04', 'harga_jual' => 35000, 'harga_beli' => 28000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'UJ2RNB',
                'nama_produk' => 'Wrdh Exlusive Two',
                'variants'    => [
                    ['label' => 'way cake refil', 'harga_jual' => 55000, 'harga_beli' => 49000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => 'RYW8DL',
                'nama_produk' => 'Wrdh Luminos Fondation',
                'variants'    => [
                    ['label' => '01', 'harga_jual' => 40000, 'harga_beli' => 32000, 'qty' => 0],
                    ['label' => '03', 'harga_jual' => 40000, 'harga_beli' => 32000, 'qty' => 2],
                    ['label' => '04', 'harga_jual' => 40000, 'harga_beli' => 32000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'V7RBA2',
                'nama_produk' => 'Wrdh Lightening Bb',
                'variants'    => [
                    ['label' => 'tin 30 ml 01', 'harga_jual' => 58000, 'harga_beli' => 54000, 'qty' => 1],
                    ['label' => 'tin 30 ml 02', 'harga_jual' => 58000, 'harga_beli' => 54000, 'qty' => 1],
                    ['label' => 'tin 30 ml 03', 'harga_jual' => 58000, 'harga_beli' => 54000, 'qty' => 0],
                    ['label' => 'tin 30 ml 04', 'harga_jual' => 58000, 'harga_beli' => 54000, 'qty' => 1],
                    ['label' => 'tin 25 ml 01', 'harga_jual' => 48000, 'harga_beli' => 41000, 'qty' => 1],
                    ['label' => 'tin 25 ml 02', 'harga_jual' => 48000, 'harga_beli' => 41000, 'qty' => 1],
                    ['label' => 'tin 25 ml 03', 'harga_jual' => 48000, 'harga_beli' => 41000, 'qty' => 0],
                    ['label' => 'tin 25 ml 04', 'harga_jual' => 48000, 'harga_beli' => 41000, 'qty' => 1],
                    ['label' => 'tin 25 ml 05', 'harga_jual' => 48000, 'harga_beli' => 41000, 'qty' => 1],
                    ['label' => 'tint 15 ml 01', 'harga_jual' => 33000, 'harga_beli' => 25000, 'qty' => 11],
                    ['label' => 'tint 15 ml 02', 'harga_jual' => 33000, 'harga_beli' => 25000, 'qty' => 7],
                    ['label' => 'tint 15 ml 03', 'harga_jual' => 33000, 'harga_beli' => 25000, 'qty' => 8],
                    ['label' => 'tint 15 ml 04', 'harga_jual' => 33000, 'harga_beli' => 25000, 'qty' => 10],
                ],
            ],
            [
                'kode_produk' => 'NGDSET',
                'nama_produk' => 'Wrdh Lightening Powder',
                'variants'    => [
                    ['label' => '04', 'harga_jual' => 37000, 'harga_beli' => 31000, 'qty' => 1],
                    ['label' => '03', 'harga_jual' => 67000, 'harga_beli' => 31000, 'qty' => 0],
                    ['label' => '01', 'harga_jual' => 37000, 'harga_beli' => 31000, 'qty' => 6],
                ],
            ],
            [
                'kode_produk' => 'GEUPHD',
                'nama_produk' => 'Wrdh Lightening Padat',
                'variants'    => [
                    ['label' => '12gr 01', 'harga_jual' => 55000, 'harga_beli' => 46000, 'qty' => 1],
                    ['label' => '12gr 02', 'harga_jual' => 50000, 'harga_beli' => 42000, 'qty' => 0],
                    ['label' => '12gr 03', 'harga_jual' => 50000, 'harga_beli' => 42000, 'qty' => 2],
                    ['label' => '12gr 04', 'harga_jual' => 55000, 'harga_beli' => 46000, 'qty' => 1],
                    ['label' => 'refil 01', 'harga_jual' => 35000, 'harga_beli' => 27000, 'qty' => 6],
                    ['label' => 'refil 02', 'harga_jual' => 35000, 'harga_beli' => 27000, 'qty' => 0],
                    ['label' => 'refil 03', 'harga_jual' => 35000, 'harga_beli' => 27000, 'qty' => 5],
                    ['label' => 'refil 04', 'harga_jual' => 35000, 'harga_beli' => 27000, 'qty' => 2],
                    ['label' => 'refil 05', 'harga_jual' => 35000, 'harga_beli' => 27000, 'qty' => 4],
                    ['label' => 'refil 06', 'harga_jual' => 35000, 'harga_beli' => 27000, 'qty' => 6],
                    ['label' => 'refil 07', 'harga_jual' => 35000, 'harga_beli' => 27000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '5KACEQ',
                'nama_produk' => 'Wrdh Cushion Exclusif',
                'variants'    => [
                    ['label' => '01', 'harga_jual' => 125000, 'harga_beli' => 109000, 'qty' => 2],
                    ['label' => '03', 'harga_jual' => 125000, 'harga_beli' => 109000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'XNP7FW',
                'nama_produk' => 'Wrdh Exclusif Twe',
                'variants'    => [
                    ['label' => 'cake 01', 'harga_jual' => 90000, 'harga_beli' => 84000, 'qty' => 1],
                    ['label' => 'cake 02', 'harga_jual' => 90000, 'harga_beli' => 84000, 'qty' => 1],
                    ['label' => 'cake 03', 'harga_jual' => 90000, 'harga_beli' => 84000, 'qty' => 1],
                    ['label' => 'cake 04', 'harga_jual' => 90000, 'harga_beli' => 84000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '26VSYM',
                'nama_produk' => 'Wrdh Cushion Lite',
                'variants'    => [
                    ['label' => '01', 'harga_jual' => 95000, 'harga_beli' => 85000, 'qty' => 1],
                    ['label' => '02', 'harga_jual' => 95000, 'harga_beli' => 85000, 'qty' => 1],
                    ['label' => '03', 'harga_jual' => 95000, 'harga_beli' => 85000, 'qty' => 1],
                    ['label' => '04', 'harga_jual' => 95000, 'harga_beli' => 85000, 'qty' => 2],
                    ['label' => '05', 'harga_jual' => 95000, 'harga_beli' => 85000, 'qty' => 1],
                    ['label' => '06', 'harga_jual' => 95000, 'harga_beli' => 85000, 'qty' => 2],
                    ['label' => 'refil 01', 'harga_jual' => 70000, 'harga_beli' => 54000, 'qty' => 1],
                    ['label' => 'refil 02', 'harga_jual' => 70000, 'harga_beli' => 54000, 'qty' => 1],
                    ['label' => 'refil 03', 'harga_jual' => 70000, 'harga_beli' => 54000, 'qty' => 1],
                    ['label' => 'refil 04', 'harga_jual' => 70000, 'harga_beli' => 54000, 'qty' => 1],
                    ['label' => 'refil 05', 'harga_jual' => 70000, 'harga_beli' => 54000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'HWKAL9',
                'nama_produk' => 'Wrdh Colorfit Matte',
                'variants'    => [
                    ['label' => 'fondation 22N', 'harga_jual' => 65000, 'harga_beli' => 53000, 'qty' => 1],
                    ['label' => 'fondation 23W', 'harga_jual' => 65000, 'harga_beli' => 53000, 'qty' => 1],
                    ['label' => 'fondation 32N', 'harga_jual' => 65000, 'harga_beli' => 53000, 'qty' => 1],
                    ['label' => 'fondation 33W', 'harga_jual' => 65000, 'harga_beli' => 53000, 'qty' => 0],
                    ['label' => 'fondation 11C', 'harga_jual' => 65000, 'harga_beli' => 53000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => '7LGJ3H',
                'nama_produk' => 'Wrdh Colorfit Valvet',
                'variants'    => [
                    ['label' => 'powder foundation 42N', 'harga_jual' => 70000, 'harga_beli' => 60000, 'qty' => 1],
                    ['label' => 'powder foundation 43W', 'harga_jual' => 70000, 'harga_beli' => 60000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'GNK8YM',
                'nama_produk' => 'Hanasui Mois Retinol',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 40000, 'harga_beli' => 35000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => '7TBU3R',
                'nama_produk' => 'Hanasui Mois Kuning',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 42000, 'harga_beli' => 37000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'THZ7S3',
                'nama_produk' => 'Hanasui Serum Vit',
                'variants'    => [
                    ['label' => 'C', 'harga_jual' => 25000, 'harga_beli' => 17000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => '5AGCPM',
                'nama_produk' => 'Hanasui Serum Gold',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000, 'harga_beli' => 17000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => '9VJHXF',
                'nama_produk' => 'Hanasui Serum Colagen',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 23000, 'harga_beli' => 18000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'G6UDHE',
                'nama_produk' => 'Hanasui Serum Retinol',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 23000, 'harga_beli' => 18000, 'qty' => 2],
                    ['label' => 'expert', 'harga_jual' => 35000, 'harga_beli' => 31000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'DSZBAR',
                'nama_produk' => 'Hanasui Serum Peeling',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000, 'harga_beli' => 22000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'TD2ZJ8',
                'nama_produk' => 'Hanasui Serum Bright',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000, 'harga_beli' => 22000, 'qty' => 4],
                    ['label' => 'expert', 'harga_jual' => 25000, 'harga_beli' => 22000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => 'UP29YX',
                'nama_produk' => 'Hanasui Serum Bakuchiol',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 26000, 'harga_beli' => 21000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'L9JBEF',
                'nama_produk' => 'Hanasui Serum Barier',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000, 'harga_beli' => 22000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'K8Z5EQ',
                'nama_produk' => 'Hanasui Serum Acne',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000, 'harga_beli' => 22000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'UY6PKB',
                'nama_produk' => 'Hanasui Serum Minipore',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 26000, 'harga_beli' => 21000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => '75MGTS',
                'nama_produk' => 'Hanasui Nigh Cream',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 28000, 'harga_beli' => 26000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => '4GNTJQ',
                'nama_produk' => 'Hanasui Day Cream',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 28000, 'harga_beli' => 21000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => '57WCRB',
                'nama_produk' => 'Hanasui Essence',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 27000, 'harga_beli' => 20000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'YCR396',
                'nama_produk' => 'Hanasui Gentle Claencer',
                'variants'    => [
                    ['label' => 'pink', 'harga_jual' => 25000, 'harga_beli' => 17000, 'qty' => 7],
                ],
            ],
            [
                'kode_produk' => 'L7F56Q',
                'nama_produk' => 'Hanasui Paketan Isi',
                'variants'    => [
                    ['label' => '5', 'harga_jual' => 140000, 'harga_beli' => 100000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'TKA6B2',
                'nama_produk' => 'Tisha Lip Stain',
                'variants'    => [
                    ['label' => '02', 'harga_jual' => 25000, 'harga_beli' => 18000, 'qty' => 1],
                    ['label' => '03', 'harga_jual' => 25000, 'harga_beli' => 18000, 'qty' => 1],
                    ['label' => '05', 'harga_jual' => 25000, 'harga_beli' => 18000, 'qty' => 1],
                    ['label' => '07', 'harga_jual' => 25000, 'harga_beli' => 18000, 'qty' => 1],
                    ['label' => '09', 'harga_jual' => 25000, 'harga_beli' => 18000, 'qty' => 1],
                    ['label' => '10', 'harga_jual' => 25000, 'harga_beli' => 18000, 'qty' => 1],
                    ['label' => '12', 'harga_jual' => 25000, 'harga_beli' => 18000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'DHPW54',
                'nama_produk' => 'Tisha Lip Cream',
                'variants'    => [
                    ['label' => '01 fresh nude', 'harga_jual' => 30000, 'harga_beli' => 25000, 'qty' => 1],
                    ['label' => '02 coco dream', 'harga_jual' => 30000, 'harga_beli' => 25000, 'qty' => 1],
                    ['label' => '02 Beverly nude', 'harga_jual' => 30000, 'harga_beli' => 25000, 'qty' => 0],
                    ['label' => '03 catton candy', 'harga_jual' => 30000, 'harga_beli' => 25000, 'qty' => 1],
                    ['label' => '05 red berry', 'harga_jual' => 30000, 'harga_beli' => 25000, 'qty' => 1],
                    ['label' => '06 pink swet', 'harga_jual' => 30000, 'harga_beli' => 25000, 'qty' => 1],
                    ['label' => '06 elena red', 'harga_jual' => 30000, 'harga_beli' => 25000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'K7HAR5',
                'nama_produk' => 'Purbasari Lip Balm',
                'variants'    => [
                    ['label' => 'red velvet', 'harga_jual' => 28000, 'harga_beli' => 26000, 'qty' => 1],
                    ['label' => 'strawberry crush', 'harga_jual' => 28000, 'harga_beli' => 26000, 'qty' => 1],
                    ['label' => 'orange blast', 'harga_jual' => 28000, 'harga_beli' => 26000, 'qty' => 3],
                    ['label' => 'avocado', 'harga_jual' => 28000, 'harga_beli' => 26000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'D8C7MN',
                'nama_produk' => 'Facetology Lip Protector',
                'variants'    => [
                    ['label' => 'flavored', 'harga_jual' => 60000, 'harga_beli' => 52000, 'qty' => 1],
                    ['label' => 'unflavored', 'harga_jual' => 60000, 'harga_beli' => 52000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'G6R2PZ',
                'nama_produk' => 'Vaselin Bibir',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 10000, 'harga_beli' => 6000, 'qty' => 21],
                ],
            ],
            [
                'kode_produk' => 'MGSVFX',
                'nama_produk' => 'Tisha Balm Bloom',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 20000, 'harga_beli' => 17000, 'qty' => 6],
                ],
            ],
            [
                'kode_produk' => '3Y69TQ',
                'nama_produk' => 'Lip Arab Lokal',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 7000, 'harga_beli' => 5000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'UVX4MZ',
                'nama_produk' => 'Lip Arab Original',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 10000, 'harga_beli' => 8000, 'qty' => 10],
                ],
            ],
            [
                'kode_produk' => 'BKLRHY',
                'nama_produk' => 'Dolbi Lip 165',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 15000, 'harga_beli' => 10000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'K2XU59',
                'nama_produk' => 'Dolbi Lip 171',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 15000, 'harga_beli' => 10000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'DC8VJT',
                'nama_produk' => 'Dolbi Lip 154',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 15000, 'harga_beli' => 10000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '3JENBW',
                'nama_produk' => 'Brasov Lip Tint',
                'variants'    => [
                    ['label' => '01', 'harga_jual' => 20000, 'harga_beli' => 16000, 'qty' => 3],
                    ['label' => '03', 'harga_jual' => 20000, 'harga_beli' => 16000, 'qty' => 0],
                    ['label' => '04', 'harga_jual' => 20000, 'harga_beli' => 16000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => 'YBC3ZM',
                'nama_produk' => 'Lomira Lip Serum',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 20000, 'harga_beli' => 15000, 'qty' => 8],
                ],
            ],
            [
                'kode_produk' => 'HC6MK9',
                'nama_produk' => 'Tazzi Lip Booster',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 60000, 'harga_beli' => 50000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'B9TJZS',
                'nama_produk' => 'Purbasari Lip Serum',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 26000, 'harga_beli' => 21000, 'qty' => 3],
                    ['label' => 'infused 01', 'harga_jual' => 36000, 'harga_beli' => 33000, 'qty' => 1],
                    ['label' => 'infused 02', 'harga_jual' => 36000, 'harga_beli' => 33000, 'qty' => 1],
                    ['label' => 'infused 06', 'harga_jual' => 36000, 'harga_beli' => 33000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '9SWQ4F',
                'nama_produk' => 'Salsa Lip Glow',
                'variants'    => [
                    ['label' => 'serum', 'harga_jual' => 20000, 'harga_beli' => 15000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => 'NVKZSW',
                'nama_produk' => 'Skintifik Peptide',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 100000, 'harga_beli' => 90000, 'qty' => 0],
                    ['label' => 'Default', 'harga_jual' => 95000, 'harga_beli' => 85000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'PA7J6R',
                'nama_produk' => 'Skintifik Lip Serum',
                'variants'    => [
                    ['label' => 'pink berry', 'harga_jual' => 100000, 'harga_beli' => 90000, 'qty' => 1],
                    ['label' => 'peach rose', 'harga_jual' => 100000, 'harga_beli' => 90000, 'qty' => 1],
                    ['label' => 'pink Cherry red', 'harga_jual' => 100000, 'harga_beli' => 90000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '6FZ9DT',
                'nama_produk' => 'Wrdh Serum Perfecty',
                'variants'    => [
                    ['label' => 'VIT C 30 gr', 'harga_jual' => 35000, 'harga_beli' => 27000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'WA3D45',
                'nama_produk' => 'Wrdh Mois Nano',
                'variants'    => [
                    ['label' => 'retinol 28 gr', 'harga_jual' => 53000, 'harga_beli' => 45000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'HMZAVF',
                'nama_produk' => 'Wrdh Body Mist',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 38000, 'harga_beli' => 30000, 'qty' => 9],
                ],
            ],
            [
                'kode_produk' => 'FEM6WR',
                'nama_produk' => 'Wrdh Remover 100',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 38000, 'harga_beli' => 33000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => 'YZP5V6',
                'nama_produk' => 'Wrdh Remover 50',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 28000, 'harga_beli' => 23000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'HWNUAR',
                'nama_produk' => 'Cosrx Fw Merah',
                'variants'    => [
                    ['label' => '150 ml', 'harga_jual' => 125000, 'harga_beli' => 87000, 'qty' => 5],
                    ['label' => '50 ml', 'harga_jual' => 65000, 'harga_beli' => 52000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'MYXLB9',
                'nama_produk' => 'Cosrx Fw Biru',
                'variants'    => [
                    ['label' => '150 ml', 'harga_jual' => 115000, 'harga_beli' => 84000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'SPLDAT',
                'nama_produk' => 'Cetaphil Fw 118',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 85000, 'harga_beli' => 73000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'BXRW3K',
                'nama_produk' => 'Cetaphil Fw 236',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 140000, 'harga_beli' => 122000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => '3YKB5E',
                'nama_produk' => 'Cetaphil Suncreen',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 262000, 'harga_beli' => 252000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'L7MFYP',
                'nama_produk' => 'You Peeling',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 55000, 'harga_beli' => 45000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '9ZASLP',
                'nama_produk' => 'You Fw Hy',
                'variants'    => [
                    ['label' => 'amino niacinamide 100 gr', 'harga_jual' => 35000, 'harga_beli' => 29000, 'qty' => 2],
                    ['label' => 'amino glowing 50 ml', 'harga_jual' => 25000, 'harga_beli' => 16000, 'qty' => 2],
                    ['label' => 'amino wow tery 50 ml', 'harga_jual' => 25000, 'harga_beli' => 17000, 'qty' => 2],
                    ['label' => 'amino acne gel 100 gr', 'harga_jual' => 35000, 'harga_beli' => 28000, 'qty' => 1],
                    ['label' => 'amino ac -Ttack 50 gr', 'harga_jual' => 25000, 'harga_beli' => 17000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '7R9MBE',
                'nama_produk' => 'You Fw By-byeteria',
                'variants'    => [
                    ['label' => '50gr', 'harga_jual' => 23000, 'harga_beli' => 18000, 'qty' => 4],
                    ['label' => '100 gr', 'harga_jual' => 35000, 'harga_beli' => 28000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'T24VSZ',
                'nama_produk' => 'You Fw Oil',
                'variants'    => [
                    ['label' => 'control 100 gr', 'harga_jual' => 35000, 'harga_beli' => 28000, 'qty' => 1],
                    ['label' => 'control 50 gr', 'harga_jual' => 25000, 'harga_beli' => 17000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'PAUX5C',
                'nama_produk' => 'You Fw Centela',
                'variants'    => [
                    ['label' => '100 g', 'harga_jual' => 35000, 'harga_beli' => 28000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'XQCAUN',
                'nama_produk' => 'Labore Gentle Biome',
                'variants'    => [
                    ['label' => '50 ml', 'harga_jual' => 147000, 'harga_beli' => 139000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => '7QWTK5',
                'nama_produk' => 'Labore Biome Repair',
                'variants'    => [
                    ['label' => 'barier', 'harga_jual' => 155000, 'harga_beli' => 144000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'JWCFKT',
                'nama_produk' => 'Labore Milk Clencer',
                'variants'    => [
                    ['label' => '100 ml', 'harga_jual' => 85000, 'harga_beli' => 74000, 'qty' => 1],
                    ['label' => '50 ml', 'harga_jual' => 60000, 'harga_beli' => 51000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'HS6AX7',
                'nama_produk' => 'Labore Isi 3',
                'variants'    => [
                    ['label' => 'mini', 'harga_jual' => 120000, 'harga_beli' => 117000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => '3QFBDX',
                'nama_produk' => 'Labore Physical Suncreen',
                'variants'    => [
                    ['label' => '10 ml', 'harga_jual' => 55000, 'harga_beli' => 46000, 'qty' => 1],
                    ['label' => '30 ml', 'harga_jual' => 145000, 'harga_beli' => 137000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'BMZWLP',
                'nama_produk' => 'Labore Acne &',
                'variants'    => [
                    ['label' => 'oil correct physical suncreen 40 ml', 'harga_jual' => 150000, 'harga_beli' => 143000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'NKQB52',
                'nama_produk' => 'Garnier Fw Bright',
                'variants'    => [
                    ['label' => 'complek 100 ml', 'harga_jual' => 32000, 'harga_beli' => 26000, 'qty' => 6],
                ],
            ],
            [
                'kode_produk' => 'E6DAWZ',
                'nama_produk' => 'Garnier Fw Clear',
                'variants'    => [
                    ['label' => 'dullnes 100 ml', 'harga_jual' => 32000, 'harga_beli' => 24000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => '9LAPD5',
                'nama_produk' => 'Garnier Fw Scrub',
                'variants'    => [
                    ['label' => '50 ml', 'harga_jual' => 23000, 'harga_beli' => 19000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'U4SAXY',
                'nama_produk' => 'Garnier Fw Sakura',
                'variants'    => [
                    ['label' => 'glow ceramide whip 100 ml', 'harga_jual' => 40000, 'harga_beli' => 34000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'CFAQMS',
                'nama_produk' => 'Garnier Sakura Serum',
                'variants'    => [
                    ['label' => 'cream 50 ml', 'harga_jual' => 82000, 'harga_beli' => 77000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'KP6GMJ',
                'nama_produk' => 'Garnier Sakura Sleeping',
                'variants'    => [
                    ['label' => 'mask nigh hyaluron 20 ml', 'harga_jual' => 33000, 'harga_beli' => 28000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => '27NA4K',
                'nama_produk' => 'Garnier Sakura Reparing',
                'variants'    => [
                    ['label' => 'serum 10X ceramide 30 ml', 'harga_jual' => 105000, 'harga_beli' => 98000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'GYH75C',
                'nama_produk' => 'Garnier Bright Anti',
                'variants'    => [
                    ['label' => 'acne booster serum', 'harga_jual' => 110000, 'harga_beli' => 10000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'LSQH2Y',
                'nama_produk' => 'Garnier Bright Complete',
                'variants'    => [
                    ['label' => 'anti acne booster serum', 'harga_jual' => 135000, 'harga_beli' => 129000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'AGDW92',
                'nama_produk' => 'Garnier Nigh Serum',
                'variants'    => [
                    ['label' => '10 % pure VIT c 15 ml', 'harga_jual' => 67000, 'harga_beli' => 61000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'ZTNEK2',
                'nama_produk' => 'Garnier Booster Serum',
                'variants'    => [
                    ['label' => '30x VIT c 15 ml', 'harga_jual' => 82000, 'harga_beli' => 76000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'F37CRJ',
                'nama_produk' => 'Garnier Vit C',
                'variants'    => [
                    ['label' => 'water gel bright komplek 20 ml', 'harga_jual' => 34000, 'harga_beli' => 28000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'PMEHG4',
                'nama_produk' => 'Garnier Bright Complek',
                'variants'    => [
                    ['label' => 'vit c serum 20 ml', 'harga_jual' => 25000, 'harga_beli' => 21000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'TGQPMF',
                'nama_produk' => 'Garnier Nigh Vit',
                'variants'    => [
                    ['label' => 'c sleeping mask 50 ml', 'harga_jual' => 88000, 'harga_beli' => 83000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => 'DM3UC2',
                'nama_produk' => 'Hadalabo Fw Goku',
                'variants'    => [
                    ['label' => 'jyun 50 gr', 'harga_jual' => 27000, 'harga_beli' => 22000, 'qty' => 5],
                    ['label' => 'jyun 100 gr', 'harga_jual' => 40000, 'harga_beli' => 33000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '7R9LU3',
                'nama_produk' => 'Hadalabo Fw Shiro',
                'variants'    => [
                    ['label' => 'jyun 100 gr', 'harga_jual' => 40000, 'harga_beli' => 33000, 'qty' => 0],
                    ['label' => 'jyun 50 gr', 'harga_jual' => 27000, 'harga_beli' => 22000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'ZREDNS',
                'nama_produk' => 'Mentholatum Ad Botanical',
                'variants'    => [
                    ['label' => 'lotion 150 gr', 'harga_jual' => 105000, 'harga_beli' => 95000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'WGNBQV',
                'nama_produk' => 'Mentholatum Ad Lotion',
                'variants'    => [
                    ['label' => '150 gr', 'harga_jual' => 95000, 'harga_beli' => 88000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'FHVQG4',
                'nama_produk' => 'Hadalabo Mois 3d',
                'variants'    => [
                    ['label' => 'gel 40 gr', 'harga_jual' => 102000, 'harga_beli' => 94000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'TH6ABZ',
                'nama_produk' => 'Hadalabo Ultima Whitening',
                'variants'    => [
                    ['label' => 'cream 40 gr', 'harga_jual' => 75000, 'harga_beli' => 69000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'ABG9VE',
                'nama_produk' => 'Salsa Eliner',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 20000, 'harga_beli' => 15000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => 'FEA7DY',
                'nama_produk' => 'Dazzelme Makara',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 35000, 'harga_beli' => 29000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'VY3G9N',
                'nama_produk' => 'My Beline Maskara',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 80000, 'harga_beli' => 59000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => 'V8PCH5',
                'nama_produk' => 'Ql Eliner Spidol',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 50000, 'harga_beli' => 44000, 'qty' => 6],
                ],
            ],
            [
                'kode_produk' => 'F3Y58J',
                'nama_produk' => 'Xi Xiu Eliner',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000, 'harga_beli' => 21000, 'qty' => 6],
                ],
            ],
            [
                'kode_produk' => 'KL89Z7',
                'nama_produk' => 'Bulu Mata Milan',
                'variants'    => [
                    ['label' => 'story', 'harga_jual' => 35000, 'harga_beli' => 29000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'MJGPB4',
                'nama_produk' => 'Bulu Mata Magefy',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 30000, 'harga_beli' => 25000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'ZFE6V8',
                'nama_produk' => 'Ql Dramatik Pensil',
                'variants'    => [
                    ['label' => 'alis black', 'harga_jual' => 12000, 'harga_beli' => 7000, 'qty' => 8],
                    ['label' => 'alis dark brown', 'harga_jual' => 12000, 'harga_beli' => 7000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'NY3QCK',
                'nama_produk' => 'Imflora Pensil Alis',
                'variants'    => [
                    ['label' => 'brown', 'harga_jual' => 8000, 'harga_beli' => 6000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'RBX8JP',
                'nama_produk' => 'Salsa Maskara Everlash',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000, 'harga_beli' => 20000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'FM87VH',
                'nama_produk' => 'Essens Eyeliner',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000, 'harga_beli' => 21000, 'qty' => 11],
                ],
            ],
            [
                'kode_produk' => '7TP3J4',
                'nama_produk' => 'Essens Maskara',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000, 'harga_beli' => 21000, 'qty' => 6],
                ],
            ],
            [
                'kode_produk' => 'V78BQL',
                'nama_produk' => 'Ql Eyeliner Dramatik',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 30000, 'harga_beli' => 23000, 'qty' => 11],
                ],
            ],
            [
                'kode_produk' => 'JK3QGF',
                'nama_produk' => 'Omg Maskara',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 30000, 'harga_beli' => 26000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => '45VSU9',
                'nama_produk' => 'Azzura Maskara',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 50000, 'harga_beli' => 46000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'BL7VYE',
                'nama_produk' => 'O.t.o Maskara 2',
                'variants'    => [
                    ['label' => 'in 1', 'harga_jual' => 40000, 'harga_beli' => 35000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => 'FYJQL7',
                'nama_produk' => 'Salsa 2 In',
                'variants'    => [
                    ['label' => '1 maskara & eyeliner', 'harga_jual' => 25000, 'harga_beli' => 20000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => '7LTP4Y',
                'nama_produk' => 'Timephoria Eyeliner',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 90000, 'harga_beli' => 80000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => '8EB9NZ',
                'nama_produk' => 'Ql Maskara Top',
                'variants'    => [
                    ['label' => 'brand', 'harga_jual' => 50000, 'harga_beli' => 44000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'MGXZE2',
                'nama_produk' => 'Hanasui Maskara',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 35000, 'harga_beli' => 30000, 'qty' => 7],
                ],
            ],
            [
                'kode_produk' => 'R2Y658',
                'nama_produk' => 'Pink Flash Maskara',
                'variants'    => [
                    ['label' => 'pink', 'harga_jual' => 25000, 'harga_beli' => 19000, 'qty' => 12],
                    ['label' => 'hitam', 'harga_jual' => 25000, 'harga_beli' => 21000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'GMSCYQ',
                'nama_produk' => 'Pink Flash Eyebrow',
                'variants'    => [
                    ['label' => 'pensil', 'harga_jual' => 18000, 'harga_beli' => 15000, 'qty' => 6],
                ],
            ],
            [
                'kode_produk' => 'F5N3QL',
                'nama_produk' => 'Salsa Pensil Alis',
                'variants'    => [
                    ['label' => 'black', 'harga_jual' => 5000, 'harga_beli' => 3000, 'qty' => 9],
                    ['label' => 'brown', 'harga_jual' => 5000, 'harga_beli' => 3000, 'qty' => 8],
                ],
            ],
            [
                'kode_produk' => 'TZALX3',
                'nama_produk' => 'Xiu Xiu Pensil',
                'variants'    => [
                    ['label' => 'alis black', 'harga_jual' => 8000, 'harga_beli' => 5000, 'qty' => 5],
                    ['label' => 'alis brown', 'harga_jual' => 8000, 'harga_beli' => 5000, 'qty' => 10],
                ],
            ],
            [
                'kode_produk' => 'RD8BEQ',
                'nama_produk' => 'Tisha Eyeliner',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 30000, 'harga_beli' => 26000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => 'QPF9RB',
                'nama_produk' => 'Wrdh Eyeliner',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 70000, 'harga_beli' => 66000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => 'XQDGY5',
                'nama_produk' => 'Just Misk Pensil',
                'variants'    => [
                    ['label' => 'alis black', 'harga_jual' => 8000, 'harga_beli' => 6000, 'qty' => 2],
                    ['label' => 'alis brown', 'harga_jual' => 8000, 'harga_beli' => 6000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => 'ZCNSK9',
                'nama_produk' => 'Ql Primer',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 45000, 'harga_beli' => 35000, 'qty' => 13],
                ],
            ],
            [
                'kode_produk' => '8QY49K',
                'nama_produk' => 'Wrdh Pensil Alis',
                'variants'    => [
                    ['label' => 'black', 'harga_jual' => 40000, 'harga_beli' => 35000, 'qty' => 1],
                    ['label' => 'eyeexpert brown', 'harga_jual' => 40000, 'harga_beli' => 32000, 'qty' => 2],
                    ['label' => 'eyeexpert hitam', 'harga_jual' => 40000, 'harga_beli' => 32000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'P8A2QJ',
                'nama_produk' => 'Implora Pensil Alis',
                'variants'    => [
                    ['label' => 'black', 'harga_jual' => 8000, 'harga_beli' => 5000, 'qty' => 8],
                    ['label' => 'silver', 'harga_jual' => 8000, 'harga_beli' => 5000, 'qty' => 3],
                    ['label' => 'brown', 'harga_jual' => 8000, 'harga_beli' => 5000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'RMW4XP',
                'nama_produk' => 'Bridney Pensil Alis',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 8000, 'harga_beli' => 5000, 'qty' => 16],
                ],
            ],
            [
                'kode_produk' => 'AHWMS2',
                'nama_produk' => 'Hanasui Pensil Alis',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 15000, 'harga_beli' => 12000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'QWFT27',
                'nama_produk' => 'Silky Girs Pensil',
                'variants'    => [
                    ['label' => 'alis 2in1', 'harga_jual' => 15000, 'harga_beli' => 12000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'PDNX45',
                'nama_produk' => 'My Baby Minyak',
                'variants'    => [
                    ['label' => 'Telon 150 ml lavender', 'harga_jual' => 35000, 'harga_beli' => 32000, 'qty' => 5],
                    ['label' => 'Telon 90ml', 'harga_jual' => 23000, 'harga_beli' => 27000, 'qty' => 10],
                    ['label' => 'Telon 60 ml', 'harga_jual' => 17000, 'harga_beli' => 15000, 'qty' => 10],
                    ['label' => 'Telon 30 ml', 'harga_jual' => 10000, 'harga_beli' => 8000, 'qty' => 12],
                ],
            ],
            [
                'kode_produk' => '7HKMTV',
                'nama_produk' => 'Fresh Care 10',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 13000, 'harga_beli' => 10000, 'qty' => 10],
                ],
            ],
            [
                'kode_produk' => 'BE4QCY',
                'nama_produk' => 'Fresh Care Smash',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 15000, 'harga_beli' => 12000, 'qty' => 12],
                ],
            ],
            [
                'kode_produk' => 'HLGK3W',
                'nama_produk' => 'Mkp Ayam 40',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 20000, 'harga_beli' => 17000, 'qty' => 8],
                ],
            ],
            [
                'kode_produk' => 'EUPGZV',
                'nama_produk' => 'Mkp Ayam 25',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 15000, 'harga_beli' => 11000, 'qty' => 9],
                ],
            ],
            [
                'kode_produk' => 'VZ5367',
                'nama_produk' => 'Mkp Ayam 12',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 10000, 'harga_beli' => 6000, 'qty' => 13],
                ],
            ],
            [
                'kode_produk' => 'PV9YNT',
                'nama_produk' => 'Mkp Cap Lang',
                'variants'    => [
                    ['label' => '120 ml', 'harga_jual' => 43000, 'harga_beli' => 38000, 'qty' => 3],
                    ['label' => '60 ml', 'harga_jual' => 23000, 'harga_beli' => 20000, 'qty' => 4],
                    ['label' => '30 ml', 'harga_jual' => 13000, 'harga_beli' => 11000, 'qty' => 3],
                    ['label' => '15 ml', 'harga_jual' => 7000, 'harga_beli' => 6000, 'qty' => 6],
                ],
            ],
            [
                'kode_produk' => 'NR24AK',
                'nama_produk' => 'Purbasari Minyak Zaitun',
                'variants'    => [
                    ['label' => '150 ml jasmine', 'harga_jual' => 28000, 'harga_beli' => 23000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => '9BE6Q7',
                'nama_produk' => 'Herborist Minyak Zaitun',
                'variants'    => [
                    ['label' => '75 ml', 'harga_jual' => 23000, 'harga_beli' => 20000, 'qty' => 6],
                ],
            ],
            [
                'kode_produk' => '8Y9KEQ',
                'nama_produk' => 'Mustika Ratu Minyak',
                'variants'    => [
                    ['label' => 'zaitun 175 ml', 'harga_jual' => 35000, 'harga_beli' => 33000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '4N3WM8',
                'nama_produk' => 'Mustika Minyak Zaitun',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000, 'harga_beli' => 21000, 'qty' => 6],
                ],
            ],
            [
                'kode_produk' => 'SKCVGY',
                'nama_produk' => 'Shuga Inyak Zaitun',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 43000, 'harga_beli' => 38000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'F5HDR4',
                'nama_produk' => 'Temulawak The Face',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000, 'harga_beli' => 18000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => 'JHKSW7',
                'nama_produk' => 'Salsa Remover Nail',
                'variants'    => [
                    ['label' => '100 gr', 'harga_jual' => 10000, 'harga_beli' => 7000, 'qty' => 6],
                    ['label' => '80 gr', 'harga_jual' => 15000, 'harga_beli' => 12000, 'qty' => 6],
                ],
            ],
            [
                'kode_produk' => 'QWR9ZN',
                'nama_produk' => 'Yu Chun Mai',
                'variants'    => [
                    ['label' => 'serum', 'harga_jual' => 60000, 'harga_beli' => 55000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'FC3R5G',
                'nama_produk' => 'Sh Day',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 30000, 'harga_beli' => 22000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'XMBE56',
                'nama_produk' => 'Sh Sabun',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 10000, 'harga_beli' => 6000, 'qty' => 7],
                ],
            ],
            [
                'kode_produk' => '6Y7J3E',
                'nama_produk' => 'Ta Glowing Serum',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 50000, 'harga_beli' => 43000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'Y92VLP',
                'nama_produk' => 'Ta Glowing Toner',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 50000, 'harga_beli' => 45000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => '5ECVJH',
                'nama_produk' => 'Maxi Suncreen',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 50000, 'harga_beli' => 33000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'KN7VFJ',
                'nama_produk' => 'Hanasui Sun Spf',
                'variants'    => [
                    ['label' => '30 putih', 'harga_jual' => 23000, 'harga_beli' => 18000, 'qty' => 6],
                ],
            ],
            [
                'kode_produk' => 'SF9HAD',
                'nama_produk' => 'Hanasui Spf 50',
                'variants'    => [
                    ['label' => 'biru', 'harga_jual' => 35000, 'harga_beli' => 29000, 'qty' => 13],
                ],
            ],
            [
                'kode_produk' => 'AD5VLR',
                'nama_produk' => 'Scora Spf 40',
                'variants'    => [
                    ['label' => 'sun 40 gr', 'harga_jual' => 40000, 'harga_beli' => 35000, 'qty' => 1],
                    ['label' => 'sun 30 gr', 'harga_jual' => 45000, 'harga_beli' => 39000, 'qty' => 6],
                ],
            ],
            [
                'kode_produk' => 'LFK9XD',
                'nama_produk' => 'Scora Tone Up',
                'variants'    => [
                    ['label' => '30 gr', 'harga_jual' => 35000, 'harga_beli' => 29000, 'qty' => 3],
                    ['label' => '20 gr', 'harga_jual' => 30000, 'harga_beli' => 25000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => 'X3C2ZU',
                'nama_produk' => 'Emina Sunbatle Cica',
                'variants'    => [
                    ['label' => 'spf 35 biru', 'harga_jual' => 30000, 'harga_beli' => 23000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'RPS9BW',
                'nama_produk' => 'Emina Sunbatle Spf',
                'variants'    => [
                    ['label' => '50', 'harga_jual' => 52000, 'harga_beli' => 47000, 'qty' => 1],
                    ['label' => '35 oranye 20 ml', 'harga_jual' => 20000, 'harga_beli' => 13000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => '3BNET8',
                'nama_produk' => 'Emina Sun Airi',
                'variants'    => [
                    ['label' => 'uv spf 50 cica', 'harga_jual' => 34000, 'harga_beli' => 29000, 'qty' => 3],
                    ['label' => 'uv spf 50 pink', 'harga_jual' => 35000, 'harga_beli' => 26000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'TVYD73',
                'nama_produk' => 'Emina Tone Up',
                'variants'    => [
                    ['label' => 'spf 15', 'harga_jual' => 25000, 'harga_beli' => 18000, 'qty' => 9],
                ],
            ],
            [
                'kode_produk' => '2XMKE9',
                'nama_produk' => 'Avione Sunjeju',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 70000, 'harga_beli' => 60000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => 'AH7CUZ',
                'nama_produk' => 'Gloow & Be',
                'variants'    => [
                    ['label' => 'sun spf 40', 'harga_jual' => 40000, 'harga_beli' => 35000, 'qty' => 6],
                    ['label' => 'tone up 15 ml', 'harga_jual' => 40000, 'harga_beli' => 29000, 'qty' => 8],
                ],
            ],
            [
                'kode_produk' => 'YFTUB2',
                'nama_produk' => 'Implora Sun Spf',
                'variants'    => [
                    ['label' => '40 ungu', 'harga_jual' => 35000, 'harga_beli' => 26000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'V8DM4G',
                'nama_produk' => 'Facetology Sun Tintet',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 80000, 'harga_beli' => 70000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => 'Z5LW4E',
                'nama_produk' => 'Facetology Sun Normal',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 70000, 'harga_beli' => 60000, 'qty' => 6],
                ],
            ],
            [
                'kode_produk' => 'RP4SGC',
                'nama_produk' => 'Facetology Sun Oil',
                'variants'    => [
                    ['label' => 'skin', 'harga_jual' => 70000, 'harga_beli' => 63000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => '53J24T',
                'nama_produk' => 'You Sunbrella Pink',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 75000, 'harga_beli' => 64000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'DFW3XS',
                'nama_produk' => 'You Sunbrella Hijau',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 50000, 'harga_beli' => 60000, 'qty' => 2],
                    ['label' => 'kecil', 'harga_jual' => 35000, 'harga_beli' => 35000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '4YZM5K',
                'nama_produk' => 'You Sunbrella Oranye',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 65000, 'harga_beli' => 56000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'EJYB5R',
                'nama_produk' => 'You Sunbrella Biru',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 80000, 'harga_beli' => 72000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => '82HNFM',
                'nama_produk' => 'You Sunbrella Dayly',
                'variants'    => [
                    ['label' => 'defensi', 'harga_jual' => 35000, 'harga_beli' => 35000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => 'U9JHWV',
                'nama_produk' => 'Wrdh Sun Uv',
                'variants'    => [
                    ['label' => 'Shield spf 50 biru', 'harga_jual' => 50000, 'harga_beli' => 42000, 'qty' => 5],
                    ['label' => 'Shield spf 50 oranye', 'harga_jual' => 72000, 'harga_beli' => 62000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => 'VDBUPA',
                'nama_produk' => 'Wrdh Sun Tone',
                'variants'    => [
                    ['label' => 'up pink', 'harga_jual' => 70000, 'harga_beli' => 57000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => '69WAFT',
                'nama_produk' => 'Wrdh Sun Airy',
                'variants'    => [
                    ['label' => 'smooth spf 50 centela', 'harga_jual' => 35000, 'harga_beli' => 25000, 'qty' => 2],
                    ['label' => 'smooth oil control', 'harga_jual' => 52000, 'harga_beli' => 48000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'ANM5DU',
                'nama_produk' => 'Wrdh Sun Active',
                'variants'    => [
                    ['label' => 'protektion spf 50 oranye', 'harga_jual' => 70000, 'harga_beli' => 60000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => 'MDZG8C',
                'nama_produk' => 'Wrdh Sun Tintet',
                'variants'    => [
                    ['label' => '01', 'harga_jual' => 70000, 'harga_beli' => 60000, 'qty' => 7],
                ],
            ],
            [
                'kode_produk' => 'Y85XWK',
                'nama_produk' => 'Wrdh Sun Bright',
                'variants'    => [
                    ['label' => 'c kuning', 'harga_jual' => 35000, 'harga_beli' => 27000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'Q47YAF',
                'nama_produk' => 'Wrdh Sun Acne',
                'variants'    => [
                    ['label' => 'calming spf 50', 'harga_jual' => 35000, 'harga_beli' => 27000, 'qty' => 11],
                    ['label' => 'calming spf 35', 'harga_jual' => 35000, 'harga_beli' => 27000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'KQTB5L',
                'nama_produk' => 'Skin Aqua Sun',
                'variants'    => [
                    ['label' => 'hijau', 'harga_jual' => 50000, 'harga_beli' => 45000, 'qty' => 6],
                    ['label' => 'putih', 'harga_jual' => 50000, 'harga_beli' => 40000, 'qty' => 5],
                    ['label' => 'pink', 'harga_jual' => 50000, 'harga_beli' => 40000, 'qty' => 11],
                    ['label' => 'biru', 'harga_jual' => 50000, 'harga_beli' => 41000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'MWF6DT',
                'nama_produk' => 'You Sunbrella Spray',
                'variants'    => [
                    ['label' => 'Bru', 'harga_jual' => 65000, 'harga_beli' => 56000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'L7CP9Q',
                'nama_produk' => 'Azzura Sun Oranye',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 32000, 'harga_beli' => 27000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'QJBTCR',
                'nama_produk' => 'Omg Sun Spf',
                'variants'    => [
                    ['label' => '50 tone up', 'harga_jual' => 25000, 'harga_beli' => 18000, 'qty' => 5],
                    ['label' => '50 acne oil control', 'harga_jual' => 26000, 'harga_beli' => 22000, 'qty' => 6],
                ],
            ],
            [
                'kode_produk' => 'XHT2QR',
                'nama_produk' => 'Azarine Sun Hijau',
                'variants'    => [
                    ['label' => '30 ml hydrasoothe', 'harga_jual' => 37000, 'harga_beli' => 32000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '6SLBWR',
                'nama_produk' => 'Azarine Sun Calming',
                'variants'    => [
                    ['label' => 'acne hijau', 'harga_jual' => 35000, 'harga_beli' => 30000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'XQJGNZ',
                'nama_produk' => 'Azarine Sun Hydramax-c',
                'variants'    => [
                    ['label' => 'oranye', 'harga_jual' => 55000, 'harga_beli' => 48000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'TKCUSM',
                'nama_produk' => 'Azarine Sun Cica',
                'variants'    => [
                    ['label' => 'mide berier', 'harga_jual' => 35000, 'harga_beli' => 28000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => 'J6UBGC',
                'nama_produk' => 'Amaterasun Spf 35',
                'variants'    => [
                    ['label' => 'all skin tipe', 'harga_jual' => 35000, 'harga_beli' => 27000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'T7QUD5',
                'nama_produk' => 'Amaterasun Spf 50',
                'variants'    => [
                    ['label' => 'physical', 'harga_jual' => 60000, 'harga_beli' => 55000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'P9EJKZ',
                'nama_produk' => 'Amaterasun Skintint Sand',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 68000, 'harga_beli' => 62000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => '2CFAKU',
                'nama_produk' => 'Amaterasun Skintint Light',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 68000, 'harga_beli' => 62000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'N8EB6Y',
                'nama_produk' => 'Kahf Sun Spf',
                'variants'    => [
                    ['label' => '30', 'harga_jual' => 36000, 'harga_beli' => 33000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => '5Z7CSW',
                'nama_produk' => 'Omg Fw Bright',
                'variants'    => [
                    ['label' => '100 ml', 'harga_jual' => 22000, 'harga_beli' => 18000, 'qty' => 2],
                    ['label' => '50 ml', 'harga_jual' => 15000, 'harga_beli' => 12000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'LBG9MT',
                'nama_produk' => 'Omg Fw Peach',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 12000, 'harga_beli' => 12000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'RNFBT7',
                'nama_produk' => 'Omg Fw Salicylacid',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000, 'harga_beli' => 20000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'H4J7RF',
                'nama_produk' => 'Omg Fw Niacinamide',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000, 'harga_beli' => 20000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '6RM24T',
                'nama_produk' => 'Himalaya Oil Control',
                'variants'    => [
                    ['label' => '50 ml', 'harga_jual' => 20000, 'harga_beli' => 16000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'V9MNC8',
                'nama_produk' => 'Himalaya Exfoliating 50',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 20000, 'harga_beli' => 16000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => '5VUTKB',
                'nama_produk' => 'Himalaya Purifying 50',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 25000, 'harga_beli' => 20000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'WP6GLE',
                'nama_produk' => 'Himalaya Vit C',
                'variants'    => [
                    ['label' => '100 ml', 'harga_jual' => 30000, 'harga_beli' => 25000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'EN6HXV',
                'nama_produk' => 'Azzura Fw Whip',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 30000, 'harga_beli' => 25000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'Z27F4A',
                'nama_produk' => 'Azzura Nigh Cream',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 30000, 'harga_beli' => 28000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'EQFJ6T',
                'nama_produk' => 'Azzura Day Cream',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 40000, 'harga_beli' => 35000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'NJX5CS',
                'nama_produk' => 'Azzura Serum',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000, 'harga_beli' => 21000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'MXVEP7',
                'nama_produk' => 'Viva Massage',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 12000, 'harga_beli' => 7000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'S3RHEB',
                'nama_produk' => 'Viva Skinfood',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 12000, 'harga_beli' => 7000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'TMHV3N',
                'nama_produk' => 'Viva Sun Foundation',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 12000, 'harga_beli' => 8000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'WCJAGS',
                'nama_produk' => 'Viva Liquid Fondation',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 10000, 'harga_beli' => 7000, 'qty' => 7],
                ],
            ],
            [
                'kode_produk' => 'GBNR5F',
                'nama_produk' => 'Viva Whitening Cream',
                'variants'    => [
                    ['label' => '15 gr', 'harga_jual' => 15000, 'harga_beli' => 11000, 'qty' => 6],
                    ['label' => '40 gr', 'harga_jual' => 25000, 'harga_beli' => 20000, 'qty' => 9],
                ],
            ],
            [
                'kode_produk' => 'KEH7WZ',
                'nama_produk' => 'Viva Nigh Cream',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 38000, 'harga_beli' => 33000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => 'DLBMH2',
                'nama_produk' => 'Emina Mois Niacinamide',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 38000, 'harga_beli' => 30000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'FKJGD3',
                'nama_produk' => 'Emina Mois Calming',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 38000, 'harga_beli' => 34000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'CKMDRF',
                'nama_produk' => 'Emina Mois Acne',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 38000, 'harga_beli' => 34000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => '5U3V7S',
                'nama_produk' => 'Emina Mois Retinol',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 38000, 'harga_beli' => 34000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'CHJ9G2',
                'nama_produk' => 'Emina Mois Barier',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 38000, 'harga_beli' => 34000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => 'NPLKBE',
                'nama_produk' => 'Emina Fw Niacinamide',
                'variants'    => [
                    ['label' => '50 ml', 'harga_jual' => 22000, 'harga_beli' => 16000, 'qty' => 1],
                    ['label' => '100 ml', 'harga_jual' => 30000, 'harga_beli' => 22000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'NS3G86',
                'nama_produk' => 'Emina Fw Prebiotic',
                'variants'    => [
                    ['label' => '100 ml', 'harga_jual' => 30000, 'harga_beli' => 23000, 'qty' => 1],
                    ['label' => '50 ml', 'harga_jual' => 22000, 'harga_beli' => 16000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => '5W2X4Y',
                'nama_produk' => 'Emina Fw Acne',
                'variants'    => [
                    ['label' => 'hijau', 'harga_jual' => 35000, 'harga_beli' => 27000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => '6XJE3V',
                'nama_produk' => 'Emina Fw Berier',
                'variants'    => [
                    ['label' => 'biru', 'harga_jual' => 35000, 'harga_beli' => 27000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'UA8E6S',
                'nama_produk' => 'Emina Fw Bright',
                'variants'    => [
                    ['label' => 'pink', 'harga_jual' => 35000, 'harga_beli' => 27000, 'qty' => 3],
                    ['label' => 'stuff', 'harga_jual' => 18000, 'harga_beli' => 15000, 'qty' => 7],
                    ['label' => 'for acne', 'harga_jual' => 23000, 'harga_beli' => 18000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'HFBL6Y',
                'nama_produk' => 'Emina Mois A.m',
                'variants'    => [
                    ['label' => 'glow up', 'harga_jual' => 25000, 'harga_beli' => 20000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'UPCE4X',
                'nama_produk' => 'Facetology Fw 100',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 55000, 'harga_beli' => 47000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => '4NX6K5',
                'nama_produk' => 'Facetology Fw 30',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 15000, 'harga_beli' => 10000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'UXLM5Y',
                'nama_produk' => 'Facetology Mois',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 30000, 'harga_beli' => 25000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'UCV8YJ',
                'nama_produk' => 'Scora Fw Panthenol',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 40000, 'harga_beli' => 34000, 'qty' => 9],
                ],
            ],
            [
                'kode_produk' => 'FXRDAK',
                'nama_produk' => 'Scora Fw Salicylic',
                'variants'    => [
                    ['label' => 'acid', 'harga_jual' => 40000, 'harga_beli' => 33000, 'qty' => 11],
                ],
            ],
            [
                'kode_produk' => 'FAPNU2',
                'nama_produk' => 'Emina Mois Bright',
                'variants'    => [
                    ['label' => 'stuff', 'harga_jual' => 25000, 'harga_beli' => 20000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '2S783Z',
                'nama_produk' => 'Pond\'s Fw Biome',
                'variants'    => [
                    ['label' => 'gel', 'harga_jual' => 35000, 'harga_beli' => 30000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'NGB8C3',
                'nama_produk' => 'Pond\'s Fw Niasorcinol',
                'variants'    => [
                    ['label' => '50 gr', 'harga_jual' => 25000, 'harga_beli' => 19000, 'qty' => 0],
                    ['label' => '100 gr', 'harga_jual' => 35000, 'harga_beli' => 30000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'B2TANG',
                'nama_produk' => 'Pond\'s Fw Charcoal',
                'variants'    => [
                    ['label' => '50 gr', 'harga_jual' => 25000, 'harga_beli' => 19000, 'qty' => 2],
                    ['label' => '100 gr', 'harga_jual' => 35000, 'harga_beli' => 30000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'ERUSPL',
                'nama_produk' => 'Pond\'s Serum Doble',
                'variants'    => [
                    ['label' => 'action', 'harga_jual' => 85000, 'harga_beli' => 73000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => '6QFB8C',
                'nama_produk' => 'Pond\'s Serum Night',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 80000, 'harga_beli' => 68000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'ZHRY7W',
                'nama_produk' => 'Pond\'s Whip Cream',
                'variants'    => [
                    ['label' => 'hexy retinol', 'harga_jual' => 170000, 'harga_beli' => 154000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'B3RGTE',
                'nama_produk' => 'Pond\'s Nigh Cream',
                'variants'    => [
                    ['label' => '50 gr', 'harga_jual' => 180000, 'harga_beli' => 160000, 'qty' => 3],
                    ['label' => '9 gr', 'harga_jual' => 38000, 'harga_beli' => 33000, 'qty' => 6],
                ],
            ],
            [
                'kode_produk' => 'UVHD7N',
                'nama_produk' => 'Fair Lovely Cream',
                'variants'    => [
                    ['label' => '23 gr', 'harga_jual' => 23000, 'harga_beli' => 18000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => 'H6G5RU',
                'nama_produk' => 'Fair Lovely Fw',
                'variants'    => [
                    ['label' => 'derma glow 100 gr', 'harga_jual' => 28000, 'harga_beli' => 24000, 'qty' => 3],
                    ['label' => 'derma glow 50 gr', 'harga_jual' => 18000, 'harga_beli' => 15000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'C8HXL3',
                'nama_produk' => 'Fair Lovely Suncreen',
                'variants'    => [
                    ['label' => '40 gr', 'harga_jual' => 30000, 'harga_beli' => 26000, 'qty' => 0],
                    ['label' => '20 gr', 'harga_jual' => 20000, 'harga_beli' => 15000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => 'GA7UYH',
                'nama_produk' => 'Purbasari Exfoliating',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 23000, 'harga_beli' => 18000, 'qty' => 9],
                ],
            ],
            [
                'kode_produk' => 'K6EZ35',
                'nama_produk' => 'Purbasari Scrub 200',
                'variants'    => [
                    ['label' => 'gr', 'harga_jual' => 20000, 'harga_beli' => 14000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'NU47R8',
                'nama_produk' => 'Purbasari Scrub 100',
                'variants'    => [
                    ['label' => 'gr', 'harga_jual' => 15000, 'harga_beli' => 9000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'GEQUB3',
                'nama_produk' => 'Hanasui Scrub',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 22000, 'harga_beli' => 15000, 'qty' => 6],
                ],
            ],
            [
                'kode_produk' => 'Q7P4AZ',
                'nama_produk' => 'Marina Scrub',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 15000, 'harga_beli' => 13000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'GNMXYJ',
                'nama_produk' => 'Hanasui Exfoliating Gel',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 22000, 'harga_beli' => 15000, 'qty' => 6],
                ],
            ],
            [
                'kode_produk' => '83Z597',
                'nama_produk' => 'Ayudya Lulur Pengantin',
                'variants'    => [
                    ['label' => '1000 gr', 'harga_jual' => 47000, 'harga_beli' => 45000, 'qty' => 2],
                    ['label' => '300 gr', 'harga_jual' => 25000, 'harga_beli' => 21000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '2Y3W4C',
                'nama_produk' => 'Purbasari Lulur Pengantin',
                'variants'    => [
                    ['label' => '500 gr', 'harga_jual' => 30000, 'harga_beli' => 26000, 'qty' => 16],
                    ['label' => '1000 gr', 'harga_jual' => 48000, 'harga_beli' => 39000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'QT82BN',
                'nama_produk' => 'Salsa Exfoliating Gel',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000, 'harga_beli' => 19000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'MTBG2C',
                'nama_produk' => 'Azzura Mw',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 27000, 'harga_beli' => 22000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'JHVG9L',
                'nama_produk' => 'Pixy Mw',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000, 'harga_beli' => 18000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'KGUH8S',
                'nama_produk' => 'Facetology Mw 300',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 45000, 'harga_beli' => 41000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'NJDUP9',
                'nama_produk' => 'Scora Mw Biru',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 23000, 'harga_beli' => 19000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'E892AC',
                'nama_produk' => 'Silkygirl Mw 105',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 25000, 'harga_beli' => 20000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'BCGHPL',
                'nama_produk' => 'Omg Mw 300',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 43000, 'harga_beli' => 38000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'GW7VE9',
                'nama_produk' => 'Omg Mw 65',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 16000, 'harga_beli' => 11000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'ATXNK7',
                'nama_produk' => 'Emina Mw 125',
                'variants'    => [
                    ['label' => 'ml hijau', 'harga_jual' => 33000, 'harga_beli' => 28000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'C65Z8W',
                'nama_produk' => 'Emina Mw 300',
                'variants'    => [
                    ['label' => 'ml hijau', 'harga_jual' => 47000, 'harga_beli' => 42000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'VXSHY7',
                'nama_produk' => 'Hanasui Mw 300',
                'variants'    => [
                    ['label' => 'ml hijau', 'harga_jual' => 43000, 'harga_beli' => 38000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'E3MXV6',
                'nama_produk' => 'Hanasui Mw 100',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 27000, 'harga_beli' => 22000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'ECBDXQ',
                'nama_produk' => 'Wrdh Mw Lightening',
                'variants'    => [
                    ['label' => '55 ml', 'harga_jual' => 38000, 'harga_beli' => 25000, 'qty' => 1],
                    ['label' => 'niacinamide 240 ml', 'harga_jual' => 70000, 'harga_beli' => 57000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'AFQ2MK',
                'nama_produk' => 'Wrdh Mw Acne',
                'variants'    => [
                    ['label' => 'derm 100 ml', 'harga_jual' => 30000, 'harga_beli' => 26000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'ZQGACB',
                'nama_produk' => 'Wrdh Mw Nature',
                'variants'    => [
                    ['label' => 'dayly 3x 100 ml', 'harga_jual' => 30000, 'harga_beli' => 26000, 'qty' => 1],
                    ['label' => 'dayly penthanol 100 ml', 'harga_jual' => 30000, 'harga_beli' => 23000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => '6X5B4D',
                'nama_produk' => 'Wrdh Mw Night+tone',
                'variants'    => [
                    ['label' => 'up isi dua pink', 'harga_jual' => 63000, 'harga_beli' => 44000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'NZ5VD3',
                'nama_produk' => 'Posh Body Mist',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000, 'harga_beli' => 19000, 'qty' => 12],
                ],
            ],
            [
                'kode_produk' => '537QYA',
                'nama_produk' => 'Puteri Body Splash',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 20000, 'harga_beli' => 17000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'MG72BJ',
                'nama_produk' => 'Sumber Ayu 90',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 23000, 'harga_beli' => 20000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'WUX83K',
                'nama_produk' => 'Sumber Ayu 50',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 12000, 'harga_beli' => 10000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'YF9TXM',
                'nama_produk' => 'Ovale 2 In',
                'variants'    => [
                    ['label' => '1 luminos 200 ml', 'harga_jual' => 28000, 'harga_beli' => 25000, 'qty' => 3],
                    ['label' => '1 luminos 100 ml', 'harga_jual' => 18000, 'harga_beli' => 15000, 'qty' => 8],
                    ['label' => '1 oil control 100 ml', 'harga_jual' => 18000, 'harga_beli' => 16000, 'qty' => 2],
                    ['label' => '1 oil control 200 ml', 'harga_jual' => 28000, 'harga_beli' => 26000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'DECTVF',
                'nama_produk' => 'Garnier Mw Bha',
                'variants'    => [
                    ['label' => 'biru 125 ml', 'harga_jual' => 35000, 'harga_beli' => 30000, 'qty' => 2],
                    ['label' => 'biru 50 ml', 'harga_jual' => 23000, 'harga_beli' => 19000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'ZXB6WF',
                'nama_produk' => 'Garnier Mw For',
                'variants'    => [
                    ['label' => 'sensitif pink 125 ml', 'harga_jual' => 35000, 'harga_beli' => 24000, 'qty' => 9],
                    ['label' => 'sensitif 50 ml', 'harga_jual' => 23000, 'harga_beli' => 19000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'QSHWG7',
                'nama_produk' => 'Garnier Mw Cleansing',
                'variants'    => [
                    ['label' => 'oil 125 ml', 'harga_jual' => 50000, 'harga_beli' => 46000, 'qty' => 2],
                    ['label' => 'oil 50 ml', 'harga_jual' => 30000, 'harga_beli' => 25000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'C9M4YB',
                'nama_produk' => 'Garnier Mw Rose',
                'variants'    => [
                    ['label' => 'water 400 ml', 'harga_jual' => 95000, 'harga_beli' => 87000, 'qty' => 1],
                    ['label' => 'water 125 ml', 'harga_jual' => 40000, 'harga_beli' => 32000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => 'EVLADG',
                'nama_produk' => 'Garnier Mw Salicilyk',
                'variants'    => [
                    ['label' => 'BHA 400 ml', 'harga_jual' => 105000, 'harga_beli' => 95000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'SAB59D',
                'nama_produk' => 'Garnier Mw Pha+aha',
                'variants'    => [
                    ['label' => '400 ml', 'harga_jual' => 65000, 'harga_beli' => 54000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'NJ9L5D',
                'nama_produk' => 'Garnier Vitamin C',
                'variants'    => [
                    ['label' => '400 ml', 'harga_jual' => 95000, 'harga_beli' => 87000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '3VXCAJ',
                'nama_produk' => 'Viva White Sleeping',
                'variants'    => [
                    ['label' => 'mask 80 gr', 'harga_jual' => 25000, 'harga_beli' => 21000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => '5KQC3D',
                'nama_produk' => 'Viva White Aloe',
                'variants'    => [
                    ['label' => 'gel mois 80 gr', 'harga_jual' => 20000, 'harga_beli' => 15000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'C3QY6E',
                'nama_produk' => 'Purbasari Sirih Kotak',
                'variants'    => [
                    ['label' => '60 ml', 'harga_jual' => 13000, 'harga_beli' => 8000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'HWSVAZ',
                'nama_produk' => 'Purbasari Sirih 60',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 12000, 'harga_beli' => 7000, 'qty' => 8],
                ],
            ],
            [
                'kode_produk' => 'ES7XVN',
                'nama_produk' => 'Purbasari Sirih 125',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 17000, 'harga_beli' => 12000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => 'A6K9VQ',
                'nama_produk' => 'Purbasari Feminim 100',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 20000, 'harga_beli' => 15000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'GP52KH',
                'nama_produk' => 'Purbasari Feminim 60',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 15000, 'harga_beli' => 10000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => 'JHKAWF',
                'nama_produk' => 'Barenbliss Lip Tint',
                'variants'    => [
                    ['label' => '01', 'harga_jual' => 65000, 'harga_beli' => 60000, 'qty' => 1],
                    ['label' => '02', 'harga_jual' => 65000, 'harga_beli' => 60000, 'qty' => 1],
                    ['label' => '03', 'harga_jual' => 65000, 'harga_beli' => 60000, 'qty' => 1],
                    ['label' => '04', 'harga_jual' => 65000, 'harga_beli' => 60000, 'qty' => 1],
                    ['label' => '05', 'harga_jual' => 65000, 'harga_beli' => 60000, 'qty' => 1],
                    ['label' => '06', 'harga_jual' => 65000, 'harga_beli' => 60000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => '2SHV8J',
                'nama_produk' => 'Barenbliss Lip Mois',
                'variants'    => [
                    ['label' => 'tint 01', 'harga_jual' => 65000, 'harga_beli' => 60000, 'qty' => 1],
                    ['label' => 'tint 02', 'harga_jual' => 65000, 'harga_beli' => 60000, 'qty' => 1],
                    ['label' => 'tint 03', 'harga_jual' => 65000, 'harga_beli' => 60000, 'qty' => 1],
                    ['label' => 'tint 04', 'harga_jual' => 65000, 'harga_beli' => 60000, 'qty' => 1],
                    ['label' => 'tint 05', 'harga_jual' => 65000, 'harga_beli' => 60000, 'qty' => 1],
                    ['label' => 'tint 06', 'harga_jual' => 65000, 'harga_beli' => 60000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'KSJVMD',
                'nama_produk' => 'Azzura Lip Matte',
                'variants'    => [
                    ['label' => 'cream', 'harga_jual' => 45000, 'harga_beli' => 37000, 'qty' => 13],
                ],
            ],
            [
                'kode_produk' => 'HFMYV7',
                'nama_produk' => 'Azzura Matte Lipstik',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 35000, 'harga_beli' => 30000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'KGZYJU',
                'nama_produk' => 'Azzura Lip Valvet',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 50000, 'harga_beli' => 42000, 'qty' => 10],
                ],
            ],
            [
                'kode_produk' => 'UE9CD6',
                'nama_produk' => 'Azzura Jelly Lip',
                'variants'    => [
                    ['label' => 'tint 01', 'harga_jual' => 35000, 'harga_beli' => 29000, 'qty' => 2],
                    ['label' => 'tint 07', 'harga_jual' => 35000, 'harga_beli' => 29000, 'qty' => 3],
                    ['label' => 'tint 08', 'harga_jual' => 35000, 'harga_beli' => 29000, 'qty' => 3],
                    ['label' => 'tint 05', 'harga_jual' => 35000, 'harga_beli' => 29000, 'qty' => 2],
                    ['label' => 'tint 06', 'harga_jual' => 35000, 'harga_beli' => 29000, 'qty' => 1],
                    ['label' => 'tint 04', 'harga_jual' => 35000, 'harga_beli' => 29000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'GCXKDW',
                'nama_produk' => 'Timephoria Lip Gloss',
                'variants'    => [
                    ['label' => '001', 'harga_jual' => 90000, 'harga_beli' => 79000, 'qty' => 2],
                    ['label' => '005', 'harga_jual' => 90000, 'harga_beli' => 79000, 'qty' => 1],
                    ['label' => '012', 'harga_jual' => 90000, 'harga_beli' => 79000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => '8WZ9HX',
                'nama_produk' => 'Timephoria Lip Stain',
                'variants'    => [
                    ['label' => '07', 'harga_jual' => 105000, 'harga_beli' => 95000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'Y4698M',
                'nama_produk' => 'Timephoria Lip Matte',
                'variants'    => [
                    ['label' => '06', 'harga_jual' => 105000, 'harga_beli' => 90000, 'qty' => 1],
                    ['label' => '05', 'harga_jual' => 105000, 'harga_beli' => 90000, 'qty' => 4],
                ],
            ],
            [
                'kode_produk' => '93EDZS',
                'nama_produk' => 'Wrdh Lip Balm',
                'variants'    => [
                    ['label' => 'pink', 'harga_jual' => 28000, 'harga_beli' => 22000, 'qty' => 2],
                    ['label' => 'orange', 'harga_jual' => 28000, 'harga_beli' => 22000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'GKHN2M',
                'nama_produk' => 'Lt Pro Lip',
                'variants'    => [
                    ['label' => 'matte 01', 'harga_jual' => 105000, 'harga_beli' => 95000, 'qty' => 1],
                    ['label' => 'matte 02', 'harga_jual' => 105000, 'harga_beli' => 95000, 'qty' => 1],
                    ['label' => 'matte 04', 'harga_jual' => 105000, 'harga_beli' => 95000, 'qty' => 1],
                    ['label' => 'matte 05', 'harga_jual' => 105000, 'harga_beli' => 95000, 'qty' => 1],
                    ['label' => 'matte 10', 'harga_jual' => 105000, 'harga_beli' => 95000, 'qty' => 1],
                    ['label' => 'matte 08', 'harga_jual' => 105000, 'harga_beli' => 95000, 'qty' => 1],
                    ['label' => 'matte 07', 'harga_jual' => 105000, 'harga_beli' => 95000, 'qty' => 1],
                    ['label' => 'matte 09', 'harga_jual' => 105000, 'harga_beli' => 95000, 'qty' => 1],
                    ['label' => 'matte 13', 'harga_jual' => 105000, 'harga_beli' => 95000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'NAUMLT',
                'nama_produk' => 'Pixy Lip Vinyl',
                'variants'    => [
                    ['label' => '06', 'harga_jual' => 60000, 'harga_beli' => 52000, 'qty' => 2],
                    ['label' => '08', 'harga_jual' => 60000, 'harga_beli' => 52000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'JT9ZBM',
                'nama_produk' => 'Dazzelme Lop Vinyl',
                'variants'    => [
                    ['label' => 'ink 099', 'harga_jual' => 38000, 'harga_beli' => 33000, 'qty' => 1],
                    ['label' => 'ink 035', 'harga_jual' => 38000, 'harga_beli' => 33000, 'qty' => 1],
                    ['label' => 'ink 008', 'harga_jual' => 38000, 'harga_beli' => 33000, 'qty' => 1],
                    ['label' => 'ink 555', 'harga_jual' => 38000, 'harga_beli' => 33000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'WT6K75',
                'nama_produk' => 'Azzura Lip Long',
                'variants'    => [
                    ['label' => 'lasting 03', 'harga_jual' => 32000, 'harga_beli' => 26000, 'qty' => 2],
                    ['label' => 'lasting 12', 'harga_jual' => 32000, 'harga_beli' => 26000, 'qty' => 3],
                    ['label' => 'lasting 06', 'harga_jual' => 32000, 'harga_beli' => 26000, 'qty' => 2],
                    ['label' => 'lasting 11', 'harga_jual' => 32000, 'harga_beli' => 26000, 'qty' => 1],
                    ['label' => 'lasting 09', 'harga_jual' => 32000, 'harga_beli' => 26000, 'qty' => 2],
                    ['label' => 'lasting 08', 'harga_jual' => 32000, 'harga_beli' => 26000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'QNDCGK',
                'nama_produk' => 'Emina Jelly Stain',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 50000, 'harga_beli' => 45000, 'qty' => 3],
                ],
            ],
            [
                'kode_produk' => 'EM8463',
                'nama_produk' => 'Wrdh Colorfit Lip',
                'variants'    => [
                    ['label' => 'mousse', 'harga_jual' => 65000, 'harga_beli' => 57000, 'qty' => 11],
                ],
            ],
            [
                'kode_produk' => 'D62EHT',
                'nama_produk' => 'Wrdh Lip Glasting',
                'variants'    => [
                    ['label' => '01', 'harga_jual' => 75000, 'harga_beli' => 63000, 'qty' => 1],
                    ['label' => '02', 'harga_jual' => 75000, 'harga_beli' => 63000, 'qty' => 1],
                    ['label' => '03', 'harga_jual' => 75000, 'harga_beli' => 63000, 'qty' => 0],
                    ['label' => '07', 'harga_jual' => 75000, 'harga_beli' => 63000, 'qty' => 1],
                    ['label' => '09', 'harga_jual' => 75000, 'harga_beli' => 63000, 'qty' => 2],
                    ['label' => '10', 'harga_jual' => 75000, 'harga_beli' => 63000, 'qty' => 3],
                    ['label' => '18', 'harga_jual' => 75000, 'harga_beli' => 63000, 'qty' => 1],
                    ['label' => '17', 'harga_jual' => 75000, 'harga_beli' => 63000, 'qty' => 1],
                    ['label' => '15', 'harga_jual' => 75000, 'harga_beli' => 63000, 'qty' => 1],
                    ['label' => '14', 'harga_jual' => 75000, 'harga_beli' => 63000, 'qty' => 2],
                ],
            ],
            [
                'kode_produk' => 'T82PYH',
                'nama_produk' => 'Wrdh Every Day',
                'variants'    => [
                    ['label' => 'lip shot 02', 'harga_jual' => 40000, 'harga_beli' => 33000, 'qty' => 1],
                    ['label' => 'lip shot 03', 'harga_jual' => 40000, 'harga_beli' => 33000, 'qty' => 2],
                    ['label' => 'lip shot 04', 'harga_jual' => 40000, 'harga_beli' => 33000, 'qty' => 2],
                    ['label' => 'lip shot 05', 'harga_jual' => 40000, 'harga_beli' => 33000, 'qty' => 2],
                    ['label' => 'lip shot 01', 'harga_jual' => 40000, 'harga_beli' => 33000, 'qty' => 0],
                ],
            ],
            [
                'kode_produk' => 'Y7HE29',
                'nama_produk' => 'Wrdh Colorfit Ultra',
                'variants'    => [
                    ['label' => 'light matte 13', 'harga_jual' => 42000, 'harga_beli' => 39000, 'qty' => 3],
                    ['label' => 'light matte 12', 'harga_jual' => 42000, 'harga_beli' => 39000, 'qty' => 2],
                    ['label' => 'light matte 11', 'harga_jual' => 42000, 'harga_beli' => 39000, 'qty' => 1],
                    ['label' => 'light matte 10', 'harga_jual' => 42000, 'harga_beli' => 39000, 'qty' => 1],
                    ['label' => 'light matte 09', 'harga_jual' => 42000, 'harga_beli' => 39000, 'qty' => 1],
                ],
            ],
            [
                'kode_produk' => 'Z28XFB',
                'nama_produk' => 'Make Over Lip',
                'variants'    => [
                    ['label' => 'glazed', 'harga_jual' => 115000, 'harga_beli' => 96000, 'qty' => 7],
                ],
            ],
            [
                'kode_produk' => 'TKLX8R',
                'nama_produk' => 'Make Over Color',
                'variants'    => [
                    ['label' => 'hypnose lip matte', 'harga_jual' => 80000, 'harga_beli' => 75000, 'qty' => 5],
                ],
            ],
            [
                'kode_produk' => 'QYMD5U',
                'nama_produk' => 'Imflora Lipstik',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 20000, 'harga_beli' => 17000, 'qty' => 14],
                ],
            ],
            [
                'kode_produk' => 'HDYJF3',
                'nama_produk' => 'Hanasui Lip Cream',
                'variants'    => [
                    ['label' => '01', 'harga_jual' => 23000, 'harga_beli' => 20000, 'qty' => 7],
                    ['label' => '02', 'harga_jual' => 23000, 'harga_beli' => 20000, 'qty' => 10],
                    ['label' => '03', 'harga_jual' => 23000, 'harga_beli' => 20000, 'qty' => 7],
                    ['label' => '04', 'harga_jual' => 23000, 'harga_beli' => 20000, 'qty' => 9],
                    ['label' => '05', 'harga_jual' => 23000, 'harga_beli' => 20000, 'qty' => 12],
                    ['label' => '06', 'harga_jual' => 23000, 'harga_beli' => 20000, 'qty' => 14],
                    ['label' => '07', 'harga_jual' => 23000, 'harga_beli' => 20000, 'qty' => 2],
                    ['label' => '08', 'harga_jual' => 23000, 'harga_beli' => 20000, 'qty' => 8],
                    ['label' => '09', 'harga_jual' => 23000, 'harga_beli' => 20000, 'qty' => 22],
                    ['label' => '10', 'harga_jual' => 23000, 'harga_beli' => 20000, 'qty' => 3],
                    ['label' => '11', 'harga_jual' => 23000, 'harga_beli' => 20000, 'qty' => 9],
                    ['label' => '12', 'harga_jual' => 23000, 'harga_beli' => 20000, 'qty' => 11],
                    ['label' => '13', 'harga_jual' => 23000, 'harga_beli' => 20000, 'qty' => 12],
                    ['label' => '14', 'harga_jual' => 0, 'harga_beli' => 0, 'qty' => 0],
                ],
            ],
        ];

        $po = PurchaseOrder::create([
            'store_id' => '1',
            'po_number'    => 'PO-20260518-TP4Z',
            'vendor_id'  => '1',
            'notes'      => 'Stok Awal',
            'request_date' => '2026-05-18',
            'expected_date' => '2026-05-18',
            'status'       => 'DRAFT',
            'requested_by' => '1',
        ]);
        $total = 0;

        foreach ($products as $p) {
            $kode = $p['kode_produk'];
            if (Product::withoutGlobalScopes()->where('kode_produk', $kode)->exists()) continue;

            $len_variant = count($p['variants']);
            $nama_produk = $p['nama_produk'];
            if ($len_variant == 1) {
                $nama_produk .= ' - ' . $p['variants'][0]['label'];
            }
            $product = Product::create([
                'store_id'    => 1,
                'kode_produk' => $kode,
                'nama_produk' => $nama_produk,
            ]);


            foreach ($p['variants'] as $v) {
                $sku     = $kode . '-' . str_pad($seq, 5, '0', STR_PAD_LEFT);
                $barcode = 'PRD' . str_pad($seq, 9, '0', STR_PAD_LEFT);

                $variant = ProductVariant::create([
                    'store_id'    => 1,
                    'product_id'  => $product->id,
                    'sku'         => $sku,
                    'barcode'     => $barcode,
                    'harga_jual'  => $v['harga_jual'],
                    'is_active'   => 'Y',
                ]);

                $variant->barcodes()->create(['barcode' => $barcode]);

                // Stok awal di warehouse
                if ($v['qty'] > 0) {
                    $subtotal = $v['qty'] * $v['harga_beli'];

                    PurchaseOrderItem::create([
                        'purchase_order_id'     => $po->id,
                        'product_variant_id'    => $variant->id,
                        'qty_order'             => $v['qty'],
                        'price'                 => $v['harga_beli'],
                        'subtotal'              => $subtotal,
                    ]);

                    $total += $subtotal;
                }

                // Label varian → AttributeValue + VariantAttribute
                if ($len_variant > 1) {
                    $attrValue = AttributeValue::where('attribute_id', $attrVarian->id)
                        ->where('nama', $v['label'])
                        ->first();
                    if (!$attrValue) {
                        $attrValue = AttributeValue::create([
                            'attribute_id' => $attrVarian->id,
                            'store_id'     => 1,
                            'kode'         => 'V' . str_pad($seq, 6, '0', STR_PAD_LEFT),
                            'nama'         => $v['label'],
                            'urutan'       => $seq,
                        ]);
                    }

                    VariantAttribute::create([
                        'product_variant_id' => $variant->id,
                        'attribute_id'       => $attrVarian->id,
                        'attribute_value_id' => $attrValue->id,
                    ]);
                }

                $seq++;
            }
        }
        $po->update([
            'subtotal' => $total,
            'tax_total' => 0,
            'discount_total' => 0,
            'grand_total' => $total,
            'status' => 'APPROVED',
            'approved_by' => '1',
            'approved_at' => now(),
        ]);
    }
}
