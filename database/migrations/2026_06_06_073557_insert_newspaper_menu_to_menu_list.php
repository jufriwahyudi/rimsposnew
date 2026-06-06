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

            // Insert Koran Toko Digital menu
            $menuId = DB::table('menu_list')->insertGetId([
                'nama'       => 'Koran Toko Digital',
                'routename'  => 'newspaper.index',
                'icon'       => '',
                'id_parent'  => $parentId,
                'jnsmenu'    => 'child',
                'urutan'     => $maxUrutan + 1,
                'stts'       => 'Y',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Map the menu to ADMIN and SUPERADMIN roles
            if (Schema::hasTable('menuby_role')) {
                $allowedRoles = DB::table('role_master')
                    ->whereIn('role_type', ['ADMIN', 'SUPERADMIN'])
                    ->get();

                foreach ($allowedRoles as $role) {
                    DB::table('menuby_role')->insert([
                        'role_id' => $role->id,
                        'menu_id' => $menuId,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('menu_list')) {
            $menu = DB::table('menu_list')
                ->where('routename', 'newspaper.index')
                ->first();

            if ($menu) {
                if (Schema::hasTable('menuby_role')) {
                    DB::table('menuby_role')
                        ->where('menu_id', $menu->id)
                        ->delete();
                }

                DB::table('menu_list')
                    ->where('id', $menu->id)
                    ->delete();
            }
        }
    }
};
