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
        if (!Schema::hasTable('service_order_items')) {
            Schema::create('service_order_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('service_order_id')->index();
                $table->string('item_type', 20)->default('service'); // service | product
                
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->unsignedBigInteger('product_variant_id')->nullable()->index();
                $table->string('name');
                $table->decimal('price', 15, 2)->default(0);
                $table->integer('qty')->default(1);
                $table->decimal('discount_amount', 15, 2)->default(0);
                $table->decimal('subtotal', 15, 2)->default(0);
                
                // Staff commission per item
                $table->unsignedBigInteger('staff_user_id')->nullable()->index();
                $table->string('commission_type', 20)->default('none'); // none | percentage | fixed
                $table->decimal('commission_rate', 15, 2)->default(0);
                $table->decimal('commission_amount', 15, 2)->default(0);
                
                $table->text('notes')->nullable();
                $table->timestamps();
                
                $table->foreign('service_order_id')->references('id')->on('service_orders')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_order_items');
    }
};
