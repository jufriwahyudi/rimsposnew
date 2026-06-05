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
        Schema::table('stores', function (Blueprint $table) {
            if (!Schema::hasColumn('stores', 'addon_self_service')) {
                $table->boolean('addon_self_service')->default(false)->after('business_type');
            }
            if (!Schema::hasColumn('stores', 'addon_kds')) {
                $table->boolean('addon_kds')->default(false)->after('addon_self_service');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['addon_self_service', 'addon_kds']);
        });
    }
};
