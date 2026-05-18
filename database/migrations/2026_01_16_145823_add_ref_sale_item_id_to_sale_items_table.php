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
        Schema::table('sale_items', function (Blueprint $table) {
            $table->enum('status', [
                'sold',
                'voided',
                'refunded',
                'exchanged_out',
                'exchanged_in'
            ])->default('sold')->after('subtotal');

            $table->foreignId('ref_sale_item_id')
                ->nullable()
                ->constrained('sale_items')
                ->after('status')
                ->nullOnDelete()
                ->comment('Referensi sale_item lama (refund / exchange)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['ref_sale_item_id']);
            $table->dropColumn('ref_sale_item_id');
            $table->dropColumn('status');
        });
    }
};
