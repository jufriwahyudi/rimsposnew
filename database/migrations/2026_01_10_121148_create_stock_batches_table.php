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
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_item_id')->nullable()->constrained('purchase_order_items')->nullOnDelete();
            $table->enum('posisi', ['warehouse', 'store'])->default('warehouse');
            $table->date('tanggal_masuk');
            $table->integer('qty_awal')->default(0);
            $table->integer('qty_sisa')->default(0);
            $table->integer('qty_reserved')->default(0);
            $table->decimal('harga_beli', 15, 2)->default(0);
            $table->enum('sumber', ['purchase', 'transfer', 'opname', 'adjust'])->default('purchase');
            $table->timestamps();

            $table->index(['product_variant_id', 'posisi', 'tanggal_masuk']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
