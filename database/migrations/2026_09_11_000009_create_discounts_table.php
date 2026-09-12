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
        if (!Schema::hasTable('discounts')) {
            Schema::create('discounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('store_id')->index();
                $table->string('code', 50)->nullable();
                $table->string('name', 150);
                $table->enum('discount_type', ['percentage', 'nominal'])->default('percentage');
                $table->decimal('discount_value', 12, 2)->default(0);
                $table->decimal('min_purchase_amount', 15, 2)->default(0);
                $table->decimal('max_discount_amount', 15, 2)->nullable();
                $table->enum('target_type', ['all', 'member_only'])->default('all');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();

                $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                if (!Schema::hasColumn('sales', 'discount_id')) {
                    $table->unsignedBigInteger('discount_id')->nullable()->after('trans_discount');
                }
                if (!Schema::hasColumn('sales', 'discount_name')) {
                    $table->string('discount_name', 150)->nullable()->after('discount_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                if (Schema::hasColumn('sales', 'discount_name')) {
                    $table->dropColumn('discount_name');
                }
                if (Schema::hasColumn('sales', 'discount_id')) {
                    $table->dropColumn('discount_id');
                }
            });
        }

        Schema::dropIfExists('discounts');
    }
};
