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
        Schema::create('ingredient_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->string('location_type', 20); // WAREHOUSE | STORE
            $table->foreignId('location_id')->constrained('stores')->cascadeOnDelete();
            $table->string('type', 20); // PURCHASE, TRANSFER_IN, TRANSFER_OUT, SALE, WASTAGE, ADJUSTMENT
            $table->decimal('quantity_change', 12, 4);
            $table->string('reference_id', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('inventory_stock_id')->nullable()->constrained('inventory_stocks')->nullOnDelete(); // Target batch link
            $table->dateTime('tanggal')->useCurrent(); // Transaction date (supports backdating)
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_stock_movements');
    }
};
