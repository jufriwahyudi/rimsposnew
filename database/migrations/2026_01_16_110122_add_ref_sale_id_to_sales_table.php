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
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('ref_sale_id')
                ->nullable()
                ->after('id') // opsional, bisa setelah kolom lain
                ->constrained('sales')
                ->nullOnDelete()
                ->comment('sale yang direfund');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['ref_sale_id']);
            $table->dropColumn('ref_sale_id');
        });
    }
};
