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
            // Find parent menu: prefer 'Stok', fallback to 'Pengaturan', fallback to 0
            $parent = DB::table('menu_list')
                ->where('nama', 'Stok')
                ->where('id_parent', 0)
                ->first();

            if (!$parent) {
                $parent = DB::table('menu_list')
                    ->where('nama', 'Pengaturan')
                    ->where('id_parent', 0)
                    ->first();
            }

            $parentId = $parent ? $parent->id : 0;

            // Get max urutan under this parent
            $maxUrutan = DB::table('menu_list')
                ->where('id_parent', $parentId)
                ->max('urutan') ?? 0;

            // Insert Vendor menu
            $menuId = DB::table('menu_list')->insertGetId([
                'nama'       => 'Vendor (Supplier)',
                'routename'  => 'vendors.index',
                'icon'       => '',
                'id_parent'  => $parentId,
                'jnsmenu'    => 'child',
                'urutan'     => $maxUrutan + 1,
                'stts'       => 'Y',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Map the menu to all existing roles so it shows up
            if (Schema::hasTable('menuby_role')) {
                $roles = DB::table('role_master')->get();
                foreach ($roles as $role) {
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
                ->where('routename', 'vendors.index')
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
