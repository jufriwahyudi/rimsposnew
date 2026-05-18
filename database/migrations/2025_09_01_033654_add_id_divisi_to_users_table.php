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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('id_divisi')->nullable()->after('nik');
            $table->unsignedBigInteger('id_pegawai')->nullable()->after('id_divisi');
            $table->integer('id_user_finance')->nullable()->after('id_pegawai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('id_divisi');
            $table->dropColumn('id_pegawai');
            $table->dropColumn('id_user_finance');
        });
    }
};
