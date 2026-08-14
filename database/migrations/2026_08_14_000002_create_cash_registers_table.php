<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->decimal('opening_cash', 15, 2)->default(0);
            $table->decimal('total_cash_sales', 15, 2)->default(0);
            $table->decimal('total_non_cash_sales', 15, 2)->default(0);
            $table->decimal('total_refund_cash', 15, 2)->default(0);
            $table->decimal('total_cash_in', 15, 2)->default(0);
            $table->decimal('total_cash_out', 15, 2)->default(0);
            $table->decimal('expected_cash', 15, 2)->nullable();
            $table->decimal('actual_cash', 15, 2)->nullable();
            $table->decimal('cash_difference', 15, 2)->nullable();
            $table->json('denominations')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};
