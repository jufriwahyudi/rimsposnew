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
        Schema::create('daily_consignment_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('daily_consignment_id')->index();
            $table->unsignedBigInteger('consignor_item_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable()->index();
            $table->integer('qty_titip')->default(0);
            $table->integer('qty_sisa')->default(0);
            $table->integer('qty_laku')->default(0);
            $table->decimal('harga_setor', 15, 2)->default(0);
            $table->decimal('harga_jual', 15, 2)->default(0);
            $table->decimal('subtotal_payout', 15, 2)->default(0)->comment('qty_laku * harga_setor');
            $table->decimal('subtotal_kantin_margin', 15, 2)->default(0)->comment('qty_laku * (harga_jual - harga_setor)');
            $table->timestamps();

            $table->foreign('daily_consignment_id')->references('id')->on('daily_consignments')->onDelete('cascade');
            $table->foreign('consignor_item_id')->references('id')->on('consignor_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_consignment_items');
    }
};
