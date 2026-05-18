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
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->foreignId('stock_opname_period_id')
                ->constrained('stock_opname_periods')
                ->cascadeOnDelete();

            $table->enum('posisi', ['warehouse', 'store'])->default('warehouse'); // warehouse / store
            $table->date('input_date');

            $table->enum('status', [
                'DRAFT',
                'COUNTED',
                'APPROVED',
                'CANCELLED'
            ])->default('DRAFT');

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->unique(['stock_opname_period_id', 'posisi']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opnames');
    }
};
