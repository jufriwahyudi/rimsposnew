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
        // 1. Tambah kolom base_unit ke tabel products
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'base_unit')) {
                $table->string('base_unit', 30)->default('Pcs')->after('nama_produk');
            }
        });

        // 2. Buat tabel product_units untuk satuan kemasan bertingkat
        if (!Schema::hasTable('product_units')) {
            Schema::create('product_units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
                $table->string('name', 50); // e.g. Lusin, Box, Dus, Kodi, Strip, Pack
                $table->integer('multiplier'); // e.g. 12, 20, 24, 100
                $table->decimal('price', 15, 2); // Harga jual khusus satuan ini
                $table->string('barcode', 100)->nullable()->index(); // Barcode fisik kemasan ini
                $table->boolean('is_default_purchase')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. Tambah kolom pencatatan unit di sale_items
        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'unit_id')) {
                $table->unsignedBigInteger('unit_id')->nullable()->after('qty');
            }
            if (!Schema::hasColumn('sale_items', 'unit_name')) {
                $table->string('unit_name', 50)->nullable()->after('unit_id');
            }
            if (!Schema::hasColumn('sale_items', 'unit_multiplier')) {
                $table->integer('unit_multiplier')->default(1)->after('unit_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_items', 'unit_multiplier')) {
                $table->dropColumn('unit_multiplier');
            }
            if (Schema::hasColumn('sale_items', 'unit_name')) {
                $table->dropColumn('unit_name');
            }
            if (Schema::hasColumn('sale_items', 'unit_id')) {
                $table->dropColumn('unit_id');
            }
        });

        Schema::dropIfExists('product_units');

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'base_unit')) {
                $table->dropColumn('base_unit');
            }
        });
    }
};
