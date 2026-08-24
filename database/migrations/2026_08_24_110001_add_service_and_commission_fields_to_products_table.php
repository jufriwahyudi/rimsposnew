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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'default_commission_type')) {
                $table->string('default_commission_type', 20)->default('none')->after('product_type'); // none | percentage | fixed
            }
            if (!Schema::hasColumn('products', 'default_commission_rate')) {
                $table->decimal('default_commission_rate', 15, 2)->default(0)->after('default_commission_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('products', 'default_commission_type')) {
                $columnsToDrop[] = 'default_commission_type';
            }
            if (Schema::hasColumn('products', 'default_commission_rate')) {
                $columnsToDrop[] = 'default_commission_rate';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
