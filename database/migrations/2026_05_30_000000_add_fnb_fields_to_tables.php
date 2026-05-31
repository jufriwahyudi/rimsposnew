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
        // 1. Create tenants table
        if (!Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('store_id')->nullable();
                $table->string('kode_tenant')->unique();
                $table->string('nama_tenant');
                $table->string('telepon')->nullable();
                $table->text('alamat')->nullable();
                $table->decimal('commission_rate', 5, 2)->default(0); // global percentage rate, e.g. 15.00%
                $table->enum('stts', ['Y', 'N'])->default('Y');
                $table->timestamps();

                $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
            });
        }

        // 2. Add columns to product_variants
        Schema::table('product_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('product_variants', 'track_stock')) {
                $table->boolean('track_stock')->default(true)->after('is_active');
            }
            if (!Schema::hasColumn('product_variants', 'cost_price_manual')) {
                $table->decimal('cost_price_manual', 15, 2)->default(0)->after('harga_jual');
            }
            if (!Schema::hasColumn('product_variants', 'commission_type')) {
                $table->enum('commission_type', ['global', 'percentage', 'nominal'])->default('global')->after('cost_price_manual');
            }
            if (!Schema::hasColumn('product_variants', 'commission_rate')) {
                $table->decimal('commission_rate', 15, 2)->default(0)->after('commission_type');
            }
        });

        // 3. Add columns to sales and alter status enum
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'table_number')) {
                $table->string('table_number')->nullable()->after('invoice_number');
            }
        });
        DB::statement("ALTER TABLE sales MODIFY COLUMN status ENUM('paid', 'void', 'hold') NOT NULL DEFAULT 'paid'");

        // 4. Add columns to stores
        Schema::table('stores', function (Blueprint $table) {
            if (!Schema::hasColumn('stores', 'business_type')) {
                $table->enum('business_type', ['retail', 'fnb'])->default('retail')->after('is_active');
            }
        });

        // 5. Add columns to products
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'image')) {
                $table->string('image')->nullable()->after('deskripsi');
            }
            if (!Schema::hasColumn('products', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('store_id');
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('set null');
            }
        });

        // 6. Add columns to users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('password');
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('set null');
            }
        });

        // 7. Alter role_master role_type enum
        DB::statement("ALTER TABLE role_master MODIFY COLUMN role_type ENUM('STORE', 'WAREHOUSE', 'ADMIN', 'SUPERADMIN', 'STELLING') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Alter role_master back
        DB::statement("ALTER TABLE role_master MODIFY COLUMN role_type ENUM('STORE', 'WAREHOUSE', 'ADMIN', 'SUPERADMIN') NOT NULL");

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn(['image', 'tenant_id']);
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('business_type');
        });

        DB::statement("ALTER TABLE sales MODIFY COLUMN status ENUM('paid', 'void') NOT NULL DEFAULT 'paid'");
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('table_number');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['track_stock', 'cost_price_manual', 'commission_type', 'commission_rate']);
        });

        Schema::dropIfExists('tenants');
    }
};
