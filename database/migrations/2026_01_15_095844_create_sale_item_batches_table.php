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
        Schema::create('sale_item_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_item_id')
                ->constrained('sale_items')
                ->cascadeOnDelete();

            $table->foreignId('stock_batch_id')->constrained();

            $table->integer('qty');
            $table->decimal('cost_price', 15, 2); // HPP dari batch
            $table->decimal('sell_price', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_item_batches');
    }
};
