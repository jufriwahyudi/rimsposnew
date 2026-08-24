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
        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'staff_user_id')) {
                $table->unsignedBigInteger('staff_user_id')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('sale_items', 'staff_commission_type')) {
                $table->string('staff_commission_type', 20)->nullable()->after('staff_user_id'); // none | percentage | fixed
            }
            if (!Schema::hasColumn('sale_items', 'staff_commission_rate')) {
                $table->decimal('staff_commission_rate', 15, 2)->default(0)->after('staff_commission_type');
            }
            if (!Schema::hasColumn('sale_items', 'staff_commission_amount')) {
                $table->decimal('staff_commission_amount', 15, 2)->default(0)->after('staff_commission_rate');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['staff_user_id', 'staff_commission_type', 'staff_commission_rate', 'staff_commission_amount'] as $col) {
                if (Schema::hasColumn('sale_items', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
