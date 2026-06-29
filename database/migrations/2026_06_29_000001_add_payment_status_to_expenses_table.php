<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->decimal('paid_amount', 15, 2)->default(0.00)->after('amount');
            $table->enum('payment_status', ['lunas', 'sebagian', 'belum_dibayar'])->default('lunas')->after('paid_amount');
        });

        // Set existing records to have paid_amount equal to amount and payment_status = 'lunas'
        DB::table('expenses')->update([
            'paid_amount' => DB::raw('amount'),
            'payment_status' => 'lunas',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'payment_status']);
        });
    }
};
