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
        DB::statement("ALTER TABLE sales MODIFY COLUMN payment_status ENUM('lunas', 'hutang', 'unpaid') NOT NULL DEFAULT 'lunas'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // To safely rollback, we must ensure any 'unpaid' records are handled first,
        // but here we just restore the enum definition.
        DB::statement("ALTER TABLE sales MODIFY COLUMN payment_status ENUM('lunas', 'hutang') NOT NULL DEFAULT 'lunas'");
    }
};
