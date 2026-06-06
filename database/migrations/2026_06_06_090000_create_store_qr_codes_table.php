<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_qr_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->string('table_name');
            $table->text('url');
            $table->string('hash');
            $table->timestamps();

            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
            $table->unique(['store_id', 'table_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_qr_codes');
    }
};
