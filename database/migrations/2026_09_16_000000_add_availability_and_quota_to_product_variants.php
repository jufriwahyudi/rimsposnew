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
        Schema::table('product_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('product_variants', 'is_available')) {
                $table->boolean('is_available')->default(true)->after('is_active')->index();
            }
            if (!Schema::hasColumn('product_variants', 'daily_quota')) {
                $table->integer('daily_quota')->nullable()->default(null)->after('is_available');
            }
            if (!Schema::hasColumn('product_variants', 'quota_date')) {
                $table->date('quota_date')->nullable()->default(null)->after('daily_quota');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['is_available', 'daily_quota', 'quota_date']);
        });
    }
};
