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
        Schema::create('daily_audit_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_audit_id')->constrained()->cascadeOnDelete();

            $table->string('reference_type'); // BATCH / MOVEMENT / TRANSACTION
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->string('issue_type');
            $table->text('description')->nullable();

            $table->decimal('expected_value', 15, 2)->nullable();
            $table->decimal('actual_value', 15, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_audit_details');
    }
};
