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
            $table->string('commission_type')->nullable()->after('subtotal');
            $table->decimal('commission_rate', 15, 2)->default(0)->after('commission_type');
            $table->decimal('commission_amount', 15, 2)->default(0)->after('commission_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['commission_type', 'commission_rate', 'commission_amount']);
        });
    }
};
