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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->foreignId('stock_batch_id')->constrained()->restrictOnDelete();
            $table->enum('posisi', ['warehouse', 'store'])->default('warehouse');
            $table->dateTime('tanggal');
            $table->enum('tipe', ['in', 'out', 'transfer', 'adjust', 'exchange'])->default('in');
            $table->enum('direction', ['in', 'out'])->default('in');
            $table->integer('qty')->default(0);
            $table->string('ref_type', 50)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->timestamps();

            $table->index(['product_variant_id', 'tanggal']);
            $table->index(['ref_type', 'ref_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
