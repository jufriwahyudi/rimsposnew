<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE cash_transactions MODIFY COLUMN payment_method VARCHAR(30) NOT NULL DEFAULT 'cash'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE cash_transactions MODIFY COLUMN payment_method ENUM('cash', 'transfer') NOT NULL DEFAULT 'cash'");
    }
};
