<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambah printer_id pada tabel tenants
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'printer_id')) {
                $table->foreignId('printer_id')
                    ->nullable()
                    ->after('store_id')
                    ->constrained('store_printers')
                    ->nullOnDelete();
            }
        });

        // 2. Tambah 'WAITER' pada enum role_type di role_master
        DB::statement("ALTER TABLE role_master MODIFY COLUMN role_type ENUM('STORE', 'WAREHOUSE', 'ADMIN', 'SUPERADMIN', 'STELLING', 'WAITER') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'printer_id')) {
                $table->dropForeign(['printer_id']);
                $table->dropColumn('printer_id');
            }
        });

        DB::statement("ALTER TABLE role_master MODIFY COLUMN role_type ENUM('STORE', 'WAREHOUSE', 'ADMIN', 'SUPERADMIN', 'STELLING') NOT NULL");
    }
};
