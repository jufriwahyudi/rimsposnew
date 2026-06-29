<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('menu_list')) {
            // Find parent menu: 'Laporan'
            $parent = DB::table('menu_list')
                ->where('nama', 'Laporan')
                ->where('id_parent', 0)
                ->first();

            $parentId = $parent ? $parent->id : 0;

            // Get max urutan under this parent
            $maxUrutan = DB::table('menu_list')
                ->where('id_parent', $parentId)
                ->max('urutan') ?? 0;

            // Check if menu already exists
            $exists = DB::table('menu_list')->where('routename', 'laporan.member')->exists();

            if (!$exists) {
                DB::table('menu_list')->insert([
                    'nama'       => 'Laporan Member',
                    'routename'  => 'laporan.member',
                    'icon'       => '',
                    'id_parent'  => $parentId,
                    'jnsmenu'    => 'child',
                    'urutan'     => $maxUrutan + 1,
                    'stts'       => 'Y',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('menu_list')) {
            DB::table('menu_list')
                ->where('routename', 'laporan.member')
                ->delete();
        }
    }
};
