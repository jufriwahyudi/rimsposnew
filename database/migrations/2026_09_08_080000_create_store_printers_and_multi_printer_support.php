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
        // 1. Add addon_multi_printer to stores table
        Schema::table('stores', function (Blueprint $table) {
            if (!Schema::hasColumn('stores', 'addon_multi_printer')) {
                $table->boolean('addon_multi_printer')->default(false)->after('addon_kds');
            }
        });

        // 2. Create store_printers table
        if (!Schema::hasTable('store_printers')) {
            Schema::create('store_printers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
                $table->string('name', 100);
                $table->string('code', 50)->nullable();
                $table->enum('connection_type', ['lan', 'bluetooth'])->default('lan');
                $table->string('ip_address', 50)->nullable();
                $table->integer('port')->default(9100);
                $table->string('mac_address', 100)->nullable();
                $table->enum('paper_size', ['58mm', '80mm'])->default('80mm');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['store_id', 'is_active']);
            });
        }

        // 3. Add printer_id and station to product_categories table
        Schema::table('product_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('product_categories', 'printer_id')) {
                $table->foreignId('printer_id')
                    ->nullable()
                    ->after('icon')
                    ->constrained('store_printers')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('product_categories', 'station')) {
                $table->string('station', 50)->nullable()->default('kitchen')->after('printer_id');
            }
        });

        // 4. Add print tracking columns to sales table
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'kitchen_printed_at')) {
                $table->timestamp('kitchen_printed_at')->nullable();
            }
            if (!Schema::hasColumn('sales', 'bar_printed_at')) {
                $table->timestamp('bar_printed_at')->nullable();
            }
            if (!Schema::hasColumn('sales', 'printed_stations_log')) {
                $table->json('printed_stations_log')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'printed_stations_log')) {
                $table->dropColumn('printed_stations_log');
            }
            if (Schema::hasColumn('sales', 'bar_printed_at')) {
                $table->dropColumn('bar_printed_at');
            }
            if (Schema::hasColumn('sales', 'kitchen_printed_at')) {
                $table->dropColumn('kitchen_printed_at');
            }
        });

        Schema::table('product_categories', function (Blueprint $table) {
            if (Schema::hasColumn('product_categories', 'printer_id')) {
                $table->dropForeign(['printer_id']);
                $table->dropColumn('printer_id');
            }
            if (Schema::hasColumn('product_categories', 'station')) {
                $table->dropColumn('station');
            }
        });

        Schema::dropIfExists('store_printers');

        Schema::table('stores', function (Blueprint $table) {
            if (Schema::hasColumn('stores', 'addon_multi_printer')) {
                $table->dropColumn('addon_multi_printer');
            }
        });
    }
};
