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
        if (Schema::hasTable('discounts')) {
            Schema::table('discounts', function (Blueprint $table) {
                if (!Schema::hasColumn('discounts', 'scope_type')) {
                    $table->enum('scope_type', ['all', 'product'])->default('all')->after('target_type');
                }
            });
        }

        if (!Schema::hasTable('discount_items')) {
            Schema::create('discount_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();
                $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['discount_id', 'product_variant_id'], 'disc_item_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_items');

        if (Schema::hasTable('discounts')) {
            Schema::table('discounts', function (Blueprint $table) {
                if (Schema::hasColumn('discounts', 'scope_type')) {
                    $table->dropColumn('scope_type');
                }
            });
        }
    }
};
