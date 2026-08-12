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
        Schema::create('ingredient_unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->foreignId('purchase_unit_id')->constrained('units')->restrictOnDelete();
            $table->string('code', 50); // e.g., 'Pack9', 'Pack10', 'Pack12'
            $table->decimal('conversion_factor', 10, 4);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['ingredient_id', 'purchase_unit_id', 'code'], 'ing_unit_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_unit_conversions');
    }
};
