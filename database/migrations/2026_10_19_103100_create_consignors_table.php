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
        Schema::create('consignors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->index();
            $table->string('kode_penitip', 50)->nullable()->index();
            $table->string('nama_penitip', 150);
            $table->string('telepon', 50)->nullable();
            $table->text('alamat')->nullable();
            $table->string('nama_bank', 50)->nullable();
            $table->string('nomor_rekening', 100)->nullable();
            $table->enum('tipe', ['daily', 'periodic'])->default('daily')->comment('daily: kue basah harian, periodic: AICE/freezer mingguan');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consignors');
    }
};
