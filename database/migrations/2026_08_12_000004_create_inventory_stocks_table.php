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
        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->string('location_type', 20); // WAREHOUSE | STORE
            $table->foreignId('location_id')->constrained('stores')->cascadeOnDelete();
            $table->decimal('qty_original', 12, 4)->default(0.0000); // Original batch quantity
            $table->decimal('quantity', 12, 4)->default(0.0000); // Remaining quantity (qty_sisa)
            $table->decimal('cost_per_unit', 12, 2)->default(0.00); // Purchase cost per base unit
            $table->dateTime('tanggal')->useCurrent(); // Purchase/transaction date for FIFO ordering
            $table->string('reference_id', 100)->nullable(); // Invoice, PO, or Transfer ID
            $table->text('notes')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('inventory_stocks')->nullOnDelete(); // Source Warehouse batch link
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['ingredient_id', 'location_type', 'location_id', 'tanggal'], 'idx_ing_loc_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_stocks');
    }
};
