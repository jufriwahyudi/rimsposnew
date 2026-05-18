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
        Schema::create('jadwal_sesi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_id')
                ->constrained('jadwal_distribusi')
                ->cascadeOnDelete();

            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->unsignedTinyInteger('kuota_sesi')->default(2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_sesi');
    }
};
