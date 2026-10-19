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
        Schema::create('consignment_settlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->index();
            $table->unsignedBigInteger('daily_consignment_id')->nullable()->index();
            $table->unsignedBigInteger('consignor_id')->index();
            $table->unsignedBigInteger('cash_register_id')->nullable()->index()->comment('Shift kasir yang mengeluarkan kas tunai');
            $table->unsignedBigInteger('user_id')->index()->comment('Kasir yang memproses pembayaran');
            $table->string('settlement_number', 50)->unique();
            $table->date('settlement_date')->index();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('payment_method', 30)->default('CASH');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
            $table->foreign('consignor_id')->references('id')->on('consignors')->onDelete('cascade');
            $table->foreign('daily_consignment_id')->references('id')->on('daily_consignments')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consignment_settlements');
    }
};
