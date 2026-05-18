<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {

            $variantId = 1; // BJP-M-LK-SD

            /*
            |--------------------------------------------------------------------------
            | BATCH 1 - Purchase
            |--------------------------------------------------------------------------
            */
            $batchId = DB::table('stock_batches')->insertGetId([
                'product_variant_id' => $variantId,
                'tanggal_masuk'      => Carbon::now()->subDays(10),
                'qty_awal'           => 100,
                'qty_sisa'           => 100,
                'harga_beli'        => 50000,
                'posisi'             => 'warehouse',
                'created_at'         => Carbon::now()->subDays(10),
                'updated_at'         => Carbon::now()->subDays(10),
            ]);

            DB::table('stock_movements')->insert([
                'tanggal'            => Carbon::now()->subDays(10),
                'product_variant_id' => $variantId,
                'stock_batch_id'     => $batchId,
                'tipe'               => 'in',
                'direction'          => 'in',
                'qty'                => 100,
                'posisi'             => 'warehouse',
                'ref_type'           => 'purchase',
                'ref_id'             => 1,
                'created_at'         => Carbon::now()->subDays(10),
                'updated_at'         => Carbon::now()->subDays(10),
            ]);

            /*
            |--------------------------------------------------------------------------
            | TRANSFER Gudang -> Toko (60)
            |--------------------------------------------------------------------------
            */

            // Gudang OUT
            DB::table('stock_movements')->insert([
                'tanggal'            => Carbon::now()->subDays(7),
                'product_variant_id' => $variantId,
                'stock_batch_id'     => $batchId,
                'tipe'               => 'transfer',
                'direction'          => 'out',
                'qty'                => 60,
                'posisi'             => 'warehouse',
                'ref_type'           => 'transfer',
                'ref_id'             => 1,
                'created_at'         => Carbon::now()->subDays(7),
                'updated_at'         => Carbon::now()->subDays(7),
            ]);

            // Batch clone untuk STORE
            $storeBatchId = DB::table('stock_batches')->insertGetId([
                'product_variant_id' => $variantId,
                'tanggal_masuk'      => Carbon::now()->subDays(7),
                'qty_awal'           => 60,
                'qty_sisa'           => 60,
                'harga_beli'        => 50000,
                'posisi'             => 'store',
                'created_at'         => Carbon::now()->subDays(7),
                'updated_at'         => Carbon::now()->subDays(7),
            ]);

            DB::table('stock_movements')->insert([
                'tanggal'            => Carbon::now()->subDays(7),
                'product_variant_id' => $variantId,
                'stock_batch_id'     => $storeBatchId,
                'tipe'               => 'transfer',
                'direction'          => 'in',
                'qty'                => 60,
                'posisi'             => 'store',
                'ref_type'           => 'transfer',
                'ref_id'             => 1,
                'created_at'         => Carbon::now()->subDays(7),
                'updated_at'         => Carbon::now()->subDays(7),
            ]);

            /*
            |--------------------------------------------------------------------------
            | PENJUALAN dari TOKO (OUT 40)
            |--------------------------------------------------------------------------
            */
            DB::table('stock_movements')->insert([
                'tanggal'            => Carbon::now()->subDays(5),
                'product_variant_id' => $variantId,
                'stock_batch_id'     => $storeBatchId,
                'tipe'               => 'out',
                'direction'          => 'out',
                'qty'                => 40,
                'posisi'             => 'store',
                'ref_type'           => 'penjualan',
                'ref_id'             => 1001,
                'created_at'         => Carbon::now()->subDays(5),
                'updated_at'         => Carbon::now()->subDays(5),
            ]);

            DB::table('stock_batches')
                ->where('id', $storeBatchId)
                ->update(['qty_sisa' => 20]);

            /*
            |--------------------------------------------------------------------------
            | TRANSFER balik Toko -> Gudang (10)
            |--------------------------------------------------------------------------
            */

            // Toko OUT
            DB::table('stock_movements')->insert([
                'tanggal'            => Carbon::now()->subDays(3),
                'product_variant_id' => $variantId,
                'stock_batch_id'     => $storeBatchId,
                'tipe'               => 'transfer',
                'direction'          => 'out',
                'qty'                => 10,
                'posisi'             => 'store',
                'ref_type'           => 'transfer',
                'ref_id'             => 2,
                'created_at'         => Carbon::now()->subDays(3),
                'updated_at'         => Carbon::now()->subDays(3),
            ]);

            // Gudang IN
            DB::table('stock_movements')->insert([
                'tanggal'            => Carbon::now()->subDays(3),
                'product_variant_id' => $variantId,
                'stock_batch_id'     => $batchId,
                'tipe'               => 'transfer',
                'direction'          => 'in',
                'qty'                => 10,
                'posisi'             => 'warehouse',
                'ref_type'           => 'transfer',
                'ref_id'             => 2,
                'created_at'         => Carbon::now()->subDays(3),
                'updated_at'         => Carbon::now()->subDays(3),
            ]);

            /*
            |--------------------------------------------------------------------------
            | ADJUST TOKO (Barang Hilang 2 pcs)
            |--------------------------------------------------------------------------
            */
            DB::table('stock_movements')->insert([
                'tanggal'            => Carbon::now()->subDays(1),
                'product_variant_id' => $variantId,
                'stock_batch_id'     => $storeBatchId,
                'tipe'               => 'adjust',
                'direction'          => 'out',
                'qty'                => 2,
                'posisi'             => 'store',
                'ref_type'           => 'opname',
                'ref_id'             => null,
                'created_at'         => Carbon::now()->subDays(1),
                'updated_at'         => Carbon::now()->subDays(1),
            ]);
        });
    }
}
