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
        Schema::create('daily_consignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->index();
            $table->unsignedBigInteger('consignor_id')->index();
            $table->date('tanggal')->index();
            $table->enum('status', ['open', 'settled', 'cancelled'])->default('open')->index();
            $table->integer('total_titip')->default(0);
            $table->integer('total_sisa')->default(0);
            $table->integer('total_laku')->default(0);
            $table->decimal('total_payout', 15, 2)->default(0)->comment('Total hak yang dibayarkan ke penitip');
            $table->decimal('total_kantin_margin', 15, 2)->default(0)->comment('Laba kotor kantin');
            $table->timestamp('settled_at')->nullable();
            $table->unsignedBigInteger('settled_by_user_id')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
            $table->foreign('consignor_id')->references('id')->on('consignors')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_consignments');
    }
};
