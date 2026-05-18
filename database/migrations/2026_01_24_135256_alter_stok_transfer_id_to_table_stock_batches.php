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
        Schema::table('stock_batches', function (Blueprint $table) {
            Schema::table('stock_batches', function (Blueprint $table) {
                $table->unsignedBigInteger('stock_transfer_id')
                    ->nullable()
                    ->after('purchase_item_id');
            });

            Schema::table('stock_batches', function (Blueprint $table) {
                $table->foreign('stock_transfer_id')
                    ->references('id')
                    ->on('stock_transfers')
                    ->nullOnDelete();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            $table->dropForeign(['stock_transfer_id']);
            $table->dropColumn('stock_transfer_id');
        });
    }
};
