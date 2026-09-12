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
            // Expand business_type to support 'pharmacy' alongside 'retail' and 'fnb'
            if (Schema::hasColumn('stores', 'business_type')) {
                $table->string('business_type', 30)->default('retail')->change();
            }

            // New modular addons
            if (!Schema::hasColumn('stores', 'addon_sales_person')) {
                $table->boolean('addon_sales_person')->default(false)->after('addon_multi_printer');
            }
            if (!Schema::hasColumn('stores', 'addon_multi_unit')) {
                $table->boolean('addon_multi_unit')->default(false)->after('addon_sales_person');
            }
            if (!Schema::hasColumn('stores', 'addon_fefo')) {
                $table->boolean('addon_fefo')->default(false)->after('addon_multi_unit');
            }
            if (!Schema::hasColumn('stores', 'addon_concoction')) {
                $table->boolean('addon_concoction')->default(false)->after('addon_fefo');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $columns = ['addon_sales_person', 'addon_multi_unit', 'addon_fefo', 'addon_concoction'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('stores', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
