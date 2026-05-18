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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->integer('vendor_id')->default(0);

            $table->date('request_date');
            $table->date('expected_date')->nullable();

            $table->enum('status', [
                'DRAFT',
                'SUBMITTED',
                'REJECTED',
                'APPROVED',
                'PARTIAL_RECEIVED',
                'RECEIVED',
                'CLOSED'
            ])->default('DRAFT');

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);

            $table->unsignedBigInteger('requested_by'); // manager gudang
            $table->string('approved_by', 100)->nullable(); // finance
            $table->timestamp('approved_at')->nullable();

            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
