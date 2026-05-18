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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->dateTime('sale_date');
            $table->enum('sale_type', ['retail', 'nse'])->default('retail');

            $table->integer('customer_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->foreignId('user_id')->constrained(); // kasir

            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('trans_discount', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2);

            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('change_amount', 15, 2)->default(0);
            $table->integer('nojurnal')->nullable()->comment('journal number for accounting purposes');

            $table->enum('status', ['paid', 'void'])->default('paid')->comment('paid: completed sale, void: canceled sale');
            $table->enum('has_exchange', ['Y', 'N'])->default('N')->comment('yes: sale has exchange, no: no exchange');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
