<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();

            $table->string('transfer_code')->unique();

            $table->enum('from_position', ['warehouse', 'store']);
            $table->enum('to_position', ['warehouse', 'store']);
            $table->enum('transfer_type', ['REQUEST', 'RETURN']);
            $table->foreignId('requested_by')
                ->constrained('users');

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users');

            $table->enum('status', [
                'REQUESTED',
                'APPROVED',
                'REJECTED',
                'IN_TRANSIT',
                'PARTIAL_RECEIVED',
                'RECEIVED',
                'CANCELLED'
            ])->default('REQUESTED');

            $table->timestamp('request_date')->nullable();
            $table->timestamp('approve_date')->nullable();
            $table->timestamp('ship_date')->nullable();
            $table->timestamp('receive_date')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['from_position', 'to_position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
