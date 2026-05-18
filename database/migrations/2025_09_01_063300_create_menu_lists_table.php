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
        Schema::create('menu_list', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 200)->nullable();
            $table->string('routename', 255)->nullable();
            $table->string('icon', 50)->nullable();
            $table->integer('id_parent')->default(0);
            $table->enum('jnsmenu', ['menu', 'child'])->default('menu');
            $table->integer('urutan')->default(0);
            $table->enum('stts', ['Y', 'N'])->default('Y');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_list');
    }
};
