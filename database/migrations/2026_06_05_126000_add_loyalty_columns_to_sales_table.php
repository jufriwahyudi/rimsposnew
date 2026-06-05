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
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('member_id')->nullable()->after('customer_id')->constrained('members')->onDelete('set null');
            $table->integer('points_earned')->default(0)->after('grand_total');
            $table->integer('points_redeemed')->default(0)->after('points_earned');
            $table->decimal('point_discount_amount', 15, 2)->default(0.00)->after('points_redeemed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->dropColumn(['member_id', 'points_earned', 'points_redeemed', 'point_discount_amount']);
        });
    }
};
