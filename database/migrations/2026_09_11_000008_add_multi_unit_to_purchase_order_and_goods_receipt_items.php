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
        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_order_items', 'unit_id')) {
                $table->unsignedBigInteger('unit_id')->nullable()->after('product_variant_id');
            }
            if (!Schema::hasColumn('purchase_order_items', 'unit_name')) {
                $table->string('unit_name', 50)->nullable()->after('unit_id');
            }
            if (!Schema::hasColumn('purchase_order_items', 'unit_multiplier')) {
                $table->integer('unit_multiplier')->default(1)->after('unit_name');
            }
        });

        Schema::table('goods_receipt_items', function (Blueprint $table) {
            if (!Schema::hasColumn('goods_receipt_items', 'unit_name')) {
                $table->string('unit_name', 50)->nullable()->after('qty_received');
            }
            if (!Schema::hasColumn('goods_receipt_items', 'unit_multiplier')) {
                $table->integer('unit_multiplier')->default(1)->after('unit_name');
            }
            if (!Schema::hasColumn('goods_receipt_items', 'base_qty_received')) {
                $table->decimal('base_qty_received', 12, 2)->default(0)->after('unit_multiplier');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            if (Schema::hasColumn('goods_receipt_items', 'base_qty_received')) {
                $table->dropColumn('base_qty_received');
            }
            if (Schema::hasColumn('goods_receipt_items', 'unit_multiplier')) {
                $table->dropColumn('unit_multiplier');
            }
            if (Schema::hasColumn('goods_receipt_items', 'unit_name')) {
                $table->dropColumn('unit_name');
            }
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_order_items', 'unit_multiplier')) {
                $table->dropColumn('unit_multiplier');
            }
            if (Schema::hasColumn('purchase_order_items', 'unit_name')) {
                $table->dropColumn('unit_name');
            }
            if (Schema::hasColumn('purchase_order_items', 'unit_id')) {
                $table->dropColumn('unit_id');
            }
        });
    }
};
