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
        Schema::table('point_settings', function (Blueprint $table) {
            $table->enum('redemption_method', ['point_value', 'item_redemption'])->default('point_value')->after('point_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('point_settings', function (Blueprint $table) {
            $table->dropColumn('redemption_method');
        });
    }
};
