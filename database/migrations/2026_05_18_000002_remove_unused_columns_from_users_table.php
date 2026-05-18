<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nik', 'id_divisi', 'id_pegawai', 'id_user_finance']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nik')->unique()->nullable()->after('name');
            $table->unsignedBigInteger('id_divisi')->nullable()->after('nik');
            $table->unsignedBigInteger('id_pegawai')->nullable()->after('id_divisi');
            $table->integer('id_user_finance')->nullable()->after('id_pegawai');
        });
    }
};
