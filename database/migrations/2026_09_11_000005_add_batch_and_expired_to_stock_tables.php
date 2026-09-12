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
            if (!Schema::hasColumn('stock_batches', 'batch_number')) {
                $table->string('batch_number', 100)->nullable()->after('posisi')->index();
            }
            if (!Schema::hasColumn('stock_batches', 'expired_date')) {
                $table->date('expired_date')->nullable()->after('batch_number')->index();
            }
        });

        if (Schema::hasTable('goods_receipt_items')) {
            Schema::table('goods_receipt_items', function (Blueprint $table) {
                if (!Schema::hasColumn('goods_receipt_items', 'batch_number')) {
                    $table->string('batch_number', 100)->nullable()->after('qty_received');
                }
                if (!Schema::hasColumn('goods_receipt_items', 'expired_date')) {
                    $table->date('expired_date')->nullable()->after('batch_number');
                }
            });
        }

        if (Schema::hasTable('sale_item_batches')) {
            Schema::table('sale_item_batches', function (Blueprint $table) {
                if (!Schema::hasColumn('sale_item_batches', 'batch_number')) {
                    $table->string('batch_number', 100)->nullable()->after('stock_batch_id');
                }
                if (!Schema::hasColumn('sale_item_batches', 'expired_date')) {
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
        Schema::table('stock_batches', function (Blueprint $table) {
            if (Schema::hasColumn('stock_batches', 'expired_date')) {
                $table->dropColumn('expired_date');
            }
            if (Schema::hasColumn('stock_batches', 'batch_number')) {
                $table->dropColumn('batch_number');
            }
        });

        if (Schema::hasTable('goods_receipt_items')) {
            Schema::table('goods_receipt_items', function (Blueprint $table) {
                if (Schema::hasColumn('goods_receipt_items', 'expired_date')) {
                    $table->dropColumn('expired_date');
                }
                if (Schema::hasColumn('goods_receipt_items', 'batch_number')) {
                    $table->dropColumn('batch_number');
                }
            });
        }

        if (Schema::hasTable('sale_item_batches')) {
            Schema::table('sale_item_batches', function (Blueprint $table) {
                if (Schema::hasColumn('sale_item_batches', 'expired_date')) {
                    $table->dropColumn('expired_date');
                }
                if (Schema::hasColumn('sale_item_batches', 'batch_number')) {
                    $table->dropColumn('batch_number');
                }
            });
        }
    }
};
