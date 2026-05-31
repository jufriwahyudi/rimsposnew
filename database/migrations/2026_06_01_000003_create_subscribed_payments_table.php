<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscribed_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscribed_invoice_id')->constrained('subscribed_invoices')->cascadeOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method'); // Transfer, Cash, etc.
            $table->string('payment_proof')->nullable(); // Path to proof file
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscribed_payments');
    }
};
