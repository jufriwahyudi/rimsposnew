<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            // Hapus unique index lama pada code saja
            $table->dropUnique('stock_adjustments_code_unique');

            // Tambah composite unique: code + store_id
            $table->unique(['code', 'store_id'], 'stock_adjustments_code_store_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropUnique('stock_adjustments_code_store_id_unique');
            $table->unique('code', 'stock_adjustments_code_unique');
        });
    }
};
