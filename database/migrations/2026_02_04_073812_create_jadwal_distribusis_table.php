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
        Schema::create('jadwal_distribusi', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->unsignedTinyInteger('kuota_harian')->default(10);
            $table->string('keterangan')->nullable();
            $table->enum('is_active', ['Y', 'N'])->default('Y');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_distribusi');
    }
};
