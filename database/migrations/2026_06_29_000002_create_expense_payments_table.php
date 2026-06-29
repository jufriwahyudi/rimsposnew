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
        Schema::create('expense_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expense_id');
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', ['cash', 'transfer'])->default('cash');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->foreign('expense_id')->references('id')->on('expenses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_payments');
    }
};
