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
        Schema::table('role_master', function (Blueprint $table) {
            $table->enum('role_type', ['STORE', 'WAREHOUSE', 'ADMIN'])
                ->after('nama')
                ->default('STORE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('role_master', function (Blueprint $table) {
            $table->dropColumn('role_type');
        });
    }
};
