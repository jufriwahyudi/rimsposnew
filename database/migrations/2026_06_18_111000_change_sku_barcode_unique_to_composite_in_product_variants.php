<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // Hapus unique index lama (tunggal)
            $table->dropUnique('product_variants_sku_unique');
            $table->dropUnique('product_variants_barcode_unique');

            // Tambah composite unique: sku + store_id
            $table->unique(['sku', 'store_id'], 'product_variants_sku_store_id_unique');

            // Tambah composite unique: barcode + store_id
            $table->unique(['barcode', 'store_id'], 'product_variants_barcode_store_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique('product_variants_sku_store_id_unique');
            $table->dropUnique('product_variants_barcode_store_id_unique');

            $table->unique('sku');
            $table->unique('barcode');
        });
    }
};
