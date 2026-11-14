<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE discounts MODIFY COLUMN discount_type ENUM('percentage', 'nominal', 'cost_price') NOT NULL DEFAULT 'percentage'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE discounts MODIFY COLUMN discount_type ENUM('percentage', 'nominal') NOT NULL DEFAULT 'percentage'");
    }
};

