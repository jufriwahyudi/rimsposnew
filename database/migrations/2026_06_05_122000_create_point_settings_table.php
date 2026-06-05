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
        Schema::create('point_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade');
            $table->boolean('is_active')->default(false);
            $table->enum('earning_method', ['transaction', 'product', 'hybrid'])->default('transaction');
            
            // Earning configuration
            $table->decimal('earning_threshold', 15, 2)->default(10000.00);
            $table->integer('earning_points')->default(1);
            
            // Exclusions
            $table->boolean('exclude_tax')->default(true);
            $table->boolean('exclude_service_charge')->default(true);
            $table->boolean('exclude_delivery_fee')->default(true);
            $table->boolean('exclude_promo_items')->default(false);
            $table->text('excluded_categories')->nullable(); // JSON list of category IDs
            
            // Redemption configuration
            $table->decimal('point_value', 15, 2)->default(100.00); // 1 point = Rp 100
            $table->integer('min_points_to_redeem')->default(0);
            $table->decimal('max_redeem_percentage', 5, 2)->default(100.00); // percentage limit
            $table->decimal('max_redeem_amount', 15, 2)->default(0.00); // max currency discount per trans (0 = unlimited)
            
            // Expiration configuration
            $table->enum('expiration_type', ['never', 'duration', 'fixed_date'])->default('never');
            $table->integer('expiration_duration_months')->default(12);
            $table->string('expiration_fixed_date', 5)->default('12-31'); // MM-DD format
            
            // Incentives
            $table->integer('welcome_points')->default(0);
            $table->decimal('birthday_multiplier', 3, 2)->default(1.00);
            $table->integer('birthday_gift_points')->default(0);
            
            $table->timestamps();
            
            // Limit to 1 global config and 1 config per store override
            $table->unique(['business_id', 'store_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_settings');
    }
};
