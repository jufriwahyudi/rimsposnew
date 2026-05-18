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
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            // Referensi dokumen
            $table->string('ref_type'); // sale, refund, exchange, adjustment
            $table->unsignedBigInteger('ref_id');

            // Jenis transaksi kas
            $table->enum('transaction_type', [
                'sale',              // penjualan
                'nse',               // penjualan NSE
                'refund',            // refund penjualan
                'exchange_additional', // tambahan bayar exchange
                'exchange_refund',   // pengembalian exchange
                'adjustment'
            ]);

            // Metode pembayaran
            $table->enum('payment_method', [
                'cash',
                'transfer'
            ]);

            // Kode akun (untuk jurnal)
            $table->string('account_code', 8)
                ->comment('Kode akun kas/bank');

            // Nilai
            $table->decimal('amount', 15, 2);

            // Arah kas
            $table->enum('direction', [
                'in',
                'out'
            ]);

            // Waktu transaksi kas
            $table->timestamp('transaction_date');

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users');

            $table->text('notes')->nullable();
            $table->integer('nojurnal')->nullable();

            $table->timestamps();

            $table->index(['ref_type', 'ref_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
