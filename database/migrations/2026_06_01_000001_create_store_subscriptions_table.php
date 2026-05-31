<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->enum('package_type', ['lifetime', 'monthly', 'yearly'])->default('lifetime');
            $table->decimal('billing_amount', 15, 2)->default(0.00);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->unique('store_id');
        });

        // Create default lifetime subscription for all existing stores
        $stores = DB::table('stores')->whereNull('deleted_at')->get();
        foreach ($stores as $store) {
            DB::table('store_subscriptions')->insert([
                'store_id'       => $store->id,
                'package_type'   => 'lifetime',
                'billing_amount' => 0.00,
                'start_date'     => null,
                'end_date'       => null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('store_subscriptions');
    }
};
