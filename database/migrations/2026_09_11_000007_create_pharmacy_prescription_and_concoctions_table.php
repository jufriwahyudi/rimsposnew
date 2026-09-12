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
        // 1. Kolom Resep Dokter & Pasien pada tabel sales
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'doctor_name')) {
                $table->string('doctor_name', 100)->nullable()->after('sales_person_id');
                $table->string('doctor_sip', 50)->nullable()->after('doctor_name');
                $table->string('patient_name', 100)->nullable()->after('doctor_sip');
                $table->string('patient_age', 30)->nullable()->after('patient_name');
                $table->string('patient_gender', 10)->nullable()->after('patient_age');
                $table->string('patient_phone', 30)->nullable()->after('patient_gender');
                $table->string('prescription_number', 50)->nullable()->after('patient_phone');
                $table->date('prescription_date')->nullable()->after('prescription_number');
                $table->decimal('total_tuslah', 12, 2)->default(0)->after('prescription_date');
                $table->decimal('total_embalase', 12, 2)->default(0)->after('total_tuslah');
            }
        });

        // 2. Kolom Racikan & Aturan Pakai pada tabel sale_items
        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'is_concoction')) {
                $table->boolean('is_concoction')->default(false)->after('unit_multiplier');
                $table->string('concoction_name', 150)->nullable()->after('is_concoction');
                $table->string('concoction_form', 50)->nullable()->after('concoction_name');
                $table->string('dosage_instruction', 255)->nullable()->after('concoction_form');
                $table->string('usage_type', 20)->default('oral')->after('dosage_instruction');
                $table->decimal('tuslah_fee', 12, 2)->default(0)->after('usage_type');
                $table->decimal('embalase_fee', 12, 2)->default(0)->after('tuslah_fee');
            }
        });

        // 3. Tabel Bahan Baku Penyusun Racikan Obat
        if (!Schema::hasTable('sale_concoction_items')) {
            Schema::create('sale_concoction_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_item_id')->constrained('sale_items')->onDelete('cascade');
                $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
                $table->string('product_name', 150);
                $table->string('dosage_per_package', 50)->nullable();
                $table->decimal('quantity', 10, 2)->default(1);
                $table->string('unit_name', 50)->default('Tablet');
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_concoction_items');

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn([
                'is_concoction',
                'concoction_name',
                'concoction_form',
                'dosage_instruction',
                'usage_type',
                'tuslah_fee',
                'embalase_fee',
            ]);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'doctor_name',
                'doctor_sip',
                'patient_name',
                'patient_age',
                'patient_gender',
                'patient_phone',
                'prescription_number',
                'prescription_date',
                'total_tuslah',
                'total_embalase',
            ]);
        });
    }
};
