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
        Schema::create('consignor_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consignor_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable()->index()->comment('Mapping ke varian POS misal Kue Basah');
            $table->string('nama_item', 150);
            $table->string('kode_display', 50)->nullable()->comment('Kode piring / nampan misal A1, B2');
            $table->decimal('harga_setor', 15, 2)->default(0)->comment('Hak uang yang diterima penitip per pcs');
            $table->decimal('harga_jual', 15, 2)->default(0)->comment('Harga jual resmi ke konsumen di kasir');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('consignor_id')->references('id')->on('consignors')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consignor_items');
    }
};
