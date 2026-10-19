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
        if (Schema::hasTable('digital_newspapers')) {
            Schema::table('digital_newspapers', function (Blueprint $table) {
                if (!Schema::hasColumn('digital_newspapers', 'ai_provider')) {
                    $table->string('ai_provider', 50)->nullable()->after('status');
                }
                if (!Schema::hasColumn('digital_newspapers', 'sections_json')) {
                    $table->json('sections_json')->nullable()->after('content_html');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('digital_newspapers')) {
            Schema::table('digital_newspapers', function (Blueprint $table) {
                if (Schema::hasColumn('digital_newspapers', 'sections_json')) {
                    $table->dropColumn('sections_json');
                }
                if (Schema::hasColumn('digital_newspapers', 'ai_provider')) {
                    $table->dropColumn('ai_provider');
                }
            });
        }
    }
};
