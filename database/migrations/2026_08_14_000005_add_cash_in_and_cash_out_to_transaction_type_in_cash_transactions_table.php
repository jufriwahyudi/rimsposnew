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
        DB::statement("ALTER TABLE cash_transactions MODIFY COLUMN transaction_type ENUM(
            'sale',
            'nse',
            'refund',
            'exchange_additional',
            'exchange_refund',
            'adjustment',
            'tip',
            'cash_in',
            'cash_out'
        ) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE cash_transactions MODIFY COLUMN transaction_type ENUM(
            'sale',
            'nse',
            'refund',
            'exchange_additional',
            'exchange_refund',
            'adjustment',
            'tip'
        ) NOT NULL");
    }
};
