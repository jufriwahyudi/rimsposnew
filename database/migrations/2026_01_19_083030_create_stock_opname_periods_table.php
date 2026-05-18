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
        Schema::create('stock_opname_periods', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // contoh: OPN-2025-12
            $table->date('period_date'); // 2025-12-31

            $table->string('description')->nullable();

            $table->enum('status', [
                'OPEN',
                'CLOSED'
            ])->default('OPEN');

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('closed_by')->nullable()->constrained('users');
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->unique(['period_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname_periods');
    }
};
