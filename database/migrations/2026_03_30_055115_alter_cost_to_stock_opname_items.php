<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->decimal('harga_beli', 15, 2)->default(0)->after('difference_qty');
            // $table->decimal('cost', 15, 2)->default(0)->after('qty_sisa'); --- IGNORE ---
            // $table->string('source')->default('STOCK_OPNAME')->after('cost'); --- IGNORE ---
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->dropColumn('harga_beli');
            // $table->dropColumn('cost'); --- IGNORE ---
            // $table->dropColumn('source'); --- IGNORE ---
        });
    }
};
