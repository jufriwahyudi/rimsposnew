<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'cash_register_id')) {
                $table->foreignId('cash_register_id')->nullable()->after('user_id')->constrained('cash_registers')->nullOnDelete();
            }
        });

        Schema::table('cash_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('cash_transactions', 'cash_register_id')) {
                $table->foreignId('cash_register_id')->nullable()->after('user_id')->constrained('cash_registers')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'cash_register_id')) {
                $table->dropForeign(['cash_register_id']);
                $table->dropColumn('cash_register_id');
            }
        });

        Schema::table('cash_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('cash_transactions', 'cash_register_id')) {
                $table->dropForeign(['cash_register_id']);
                $table->dropColumn('cash_register_id');
            }
        });
    }
};
