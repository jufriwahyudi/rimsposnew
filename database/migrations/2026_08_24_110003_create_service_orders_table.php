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
        if (!Schema::hasTable('service_orders')) {
            Schema::create('service_orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('store_id')->index();
                $table->string('order_number', 50)->index();
                
                // Customer details
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('customer_name')->nullable();
                $table->string('customer_phone', 50)->nullable();
                
                // Target unit / device / vehicle / item
                $table->string('target_name')->nullable(); // e.g. "iPhone 13", "Yamaha NMAX", "Laptop Asus"
                $table->string('target_identifier')->nullable()->index(); // e.g. IMEI / Plat Nomor / SN
                $table->json('target_attributes')->nullable(); // flexible specs, password, color, accessories
                
                // Problem & diagnosis
                $table->text('complaint_notes')->nullable();
                $table->text('diagnosis_notes')->nullable();
                
                // Assignment & estimates
                $table->unsignedBigInteger('assigned_staff_id')->nullable()->index();
                $table->decimal('estimated_cost', 15, 2)->default(0);
                $table->dateTime('estimated_completed_at')->nullable();
                $table->integer('warranty_days')->default(0);
                
                // Status workflow
                // received | diagnosing | waiting_parts | in_progress | completed | delivered | cancelled
                $table->string('status', 30)->default('received')->index();
                
                // Payment & POS link
                $table->string('payment_status', 30)->default('unpaid'); // unpaid | partial | paid
                $table->decimal('down_payment', 15, 2)->default(0);
                $table->unsignedBigInteger('sale_id')->nullable()->index();
                
                $table->unsignedBigInteger('created_by_user_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_orders');
    }
};
