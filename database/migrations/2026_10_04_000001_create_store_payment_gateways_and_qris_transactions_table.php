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
        if (!Schema::hasTable('store_payment_gateways')) {
            Schema::create('store_payment_gateways', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
                $table->string('gateway', 50)->default('midtrans'); // 'midtrans', 'xendit', 'tripay', 'duitku', etc.
                $table->boolean('is_active')->default(true);
                $table->boolean('is_production')->default(true);
                $table->string('merchant_id', 191)->nullable();
                $table->string('client_key', 255)->nullable();
                $table->string('server_key', 255)->nullable();
                $table->string('public_key', 255)->nullable();
                $table->string('secret_key', 255)->nullable();
                $table->json('additional_config')->nullable();
                $table->timestamps();

                $table->unique(['store_id', 'gateway']);
            });
        }

        if (!Schema::hasTable('qris_transactions')) {
            Schema::create('qris_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
                $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
                $table->string('gateway', 50)->default('midtrans');
                $table->string('order_id', 191)->unique();
                $table->decimal('gross_amount', 15, 2);
                $table->text('qr_string')->nullable();
                $table->text('qr_url')->nullable();
                $table->string('transaction_id', 191)->nullable()->index();
                $table->string('transaction_status', 50)->default('pending'); // pending, settlement, expire, cancel
                $table->timestamp('settlement_time')->nullable();
                $table->json('raw_response')->nullable();
                $table->json('raw_notification')->nullable();
                $table->timestamps();

                $table->index(['store_id', 'transaction_status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qris_transactions');
        Schema::dropIfExists('store_payment_gateways');
    }
};
