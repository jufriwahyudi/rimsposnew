<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->after('id')
                ->constrained('stores')->nullOnDelete();
        });

        Schema::table('attributes', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->after('id')
                ->constrained('stores')->nullOnDelete();
        });

        Schema::table('attribute_values', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->after('attribute_id')
                ->constrained('stores')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
        });

        Schema::table('attributes', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
        });

        Schema::table('attribute_values', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
        });
    }
};
