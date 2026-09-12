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
        if (!Schema::hasTable('sales_persons')) {
            Schema::create('sales_persons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
                $table->string('code', 30)->nullable();
                $table->string('name', 100);
                $table->string('phone', 30)->nullable();
                $table->decimal('commission_rate', 5, 2)->nullable()->default(0);
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['store_id', 'is_active']);
            });
        }

        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'sales_person_id')) {
                $table->foreignId('sales_person_id')->nullable()->after('user_id')->constrained('sales_persons')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'sales_person_id')) {
                $table->dropForeign(['sales_person_id']);
                $table->dropColumn('sales_person_id');
            }
        });

        Schema::dropIfExists('sales_persons');
    }
};
