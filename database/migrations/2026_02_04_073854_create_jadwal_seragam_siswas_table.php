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
        Schema::create('jadwal_seragam_siswa', function (Blueprint $table) {
            $table->id();

            $table->foreignId('jadwal_id')
                ->constrained('jadwal_distribusi')
                ->cascadeOnDelete();

            $table->foreignId('sesi_id')
                ->constrained('jadwal_sesi')
                ->cascadeOnDelete();

            $table->integer('id_biodata')->unsigned()->default(0);

            $table->enum('status', [
                'booked',
                'hadir',
                'batal',
                'selesai'
            ])->default('booked');

            $table->timestamps();

            // ❗ 1 siswa hanya boleh 1 booking per jadwal
            $table->unique(['jadwal_id', 'id_biodata']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_seragam_siswa');
    }
};
