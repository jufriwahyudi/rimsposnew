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
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->date('effective_date')->comment('Tanggal efektif penyesuaian stok, sesuai dengan tanggal opname_periods');

            $table->enum('posisi', ['warehouse', 'store'])->default('warehouse'); // warehouse / store

            $table->enum('reason_type', [
                'OPNAME',
                'DAMAGED',
                'LOST',
                'FOUND',
                'CORRECTION'
            ]);

            $table->text('notes')->nullable();

            $table->enum('status', [
                'DRAFT',
                'POSTED',
                'CANCELLED'
            ])->default('DRAFT');
            $table->integer('nojurnal')->nullable();

            $table->foreignId('stock_opname_id')->nullable()->constrained();

            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('posted_at')->nullable();

            $table->timestamps();

            $table->index(['effective_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
