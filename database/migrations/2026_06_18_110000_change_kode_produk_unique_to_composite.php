<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Hapus unique index lama pada kode_produk saja
            $table->dropUnique(['kode_produk']);

            // Tambah composite unique: kode_produk + store_id
            $table->unique(['kode_produk', 'store_id'], 'products_kode_produk_store_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Kembalikan ke unique tunggal
            $table->dropUnique('products_kode_produk_store_id_unique');
            $table->unique('kode_produk');
        });
    }
};
