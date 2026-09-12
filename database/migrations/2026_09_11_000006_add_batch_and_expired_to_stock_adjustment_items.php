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
        if (Schema::hasTable('stock_adjustment_items')) {
            Schema::table('stock_adjustment_items', function (Blueprint $table) {
                if (!Schema::hasColumn('stock_adjustment_items', 'batch_number')) {
                    $table->string('batch_number', 100)->nullable()->after('stock_batch_id');
                }
                if (!Schema::hasColumn('stock_adjustment_items', 'expired_date')) {
                    $table->date('expired_date')->nullable()->after('batch_number');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('stock_adjustment_items')) {
            Schema::table('stock_adjustment_items', function (Blueprint $table) {
                if (Schema::hasColumn('stock_adjustment_items', 'expired_date')) {
                    $table->dropColumn('expired_date');
                }
                if (Schema::hasColumn('stock_adjustment_items', 'batch_number')) {
                    $table->dropColumn('batch_number');
                }
            });
        }
    }
};
