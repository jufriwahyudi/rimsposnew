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

            // Insert Laporan Customer menu
            $menuId = DB::table('menu_list')->insertGetId([
                'nama'       => 'Laporan Customer',
                'routename'  => 'laporan.customer',
                'icon'       => '',
                'id_parent'  => $parentId,
                'jnsmenu'    => 'child',
                'urutan'     => $maxUrutan + 1,
                'stts'       => 'Y',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Map the menu to all existing roles so it shows up
            
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('menu_list')) {
            $menu = DB::table('menu_list')
                ->where('routename', 'laporan.customer')
                ->first();

            if ($menu) {

                DB::table('menu_list')
                    ->where('id', $menu->id)
                    ->delete();
            }
        }
    }
};
