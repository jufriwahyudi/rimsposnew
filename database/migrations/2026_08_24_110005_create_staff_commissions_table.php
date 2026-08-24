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
        if (!Schema::hasTable('staff_commissions')) {
            Schema::create('staff_commissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('store_id')->index();
                $table->unsignedBigInteger('staff_user_id')->index();
                $table->string('source_type', 30); // service_order | pos_sale
                
                $table->unsignedBigInteger('service_order_id')->nullable()->index();
                $table->unsignedBigInteger('sale_id')->nullable()->index();
                $table->unsignedBigInteger('sale_item_id')->nullable()->index();
                
                $table->string('item_name');
                $table->decimal('item_price', 15, 2)->default(0);
                $table->string('commission_type', 20)->default('none');
                $table->decimal('commission_rate', 15, 2)->default(0);
                $table->decimal('commission_amount', 15, 2)->default(0);
                
                $table->string('status', 20)->default('pending')->index(); // pending | paid | cancelled
                $table->unsignedBigInteger('expense_id')->nullable()->index(); // linked to expenses table when paid
                $table->dateTime('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_commissions');
    }
};
