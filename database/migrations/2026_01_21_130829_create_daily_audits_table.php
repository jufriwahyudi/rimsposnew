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
        Schema::create('daily_audits', function (Blueprint $table) {
            $table->id();
            $table->date('audit_date');
            $table->unsignedBigInteger('store_id')->nullable();

            $table->decimal('opening_stock_value', 15, 2)->default(0);
            $table->decimal('closing_stock_value', 15, 2)->default(0);

            $table->decimal('total_sales', 15, 2)->default(0);
            $table->decimal('total_purchase', 15, 2)->default(0);

            $table->decimal('total_cash_in', 15, 2)->default(0);
            $table->decimal('total_cash_out', 15, 2)->default(0);

            $table->decimal('stock_difference_value', 15, 2)->default(0);
            $table->decimal('cash_difference', 15, 2)->default(0);

            $table->enum('status', ['OK', 'WARNING', 'ERROR'])->default('OK');

            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->unique(['audit_date', 'store_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_audits');
    }
};
