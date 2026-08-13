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
            // Find parent menu: prefer 'Stok', fallback to create 'Stok' parent
            $parent = DB::table('menu_list')
                ->where('nama', 'Stok')
                ->where('id_parent', 0)
                ->first();

            if (!$parent) {
                // Get max urutan of parent menus
                $maxParentUrutan = DB::table('menu_list')
                    ->where('id_parent', 0)
                    ->max('urutan') ?? 0;

                $parentId = DB::table('menu_list')->insertGetId([
                    'nama'       => 'Stok',
                    'routename'  => '#',
                    'icon'       => 'inventory',
                    'id_parent'  => 0,
                    'jnsmenu'    => 'parent',
                    'urutan'     => $maxParentUrutan + 1,
                    'stts'       => 'Y',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $parentId = $parent->id;
            }

            // Get max urutan under this parent
            $maxUrutan = DB::table('menu_list')
                ->where('id_parent', $parentId)
                ->max('urutan') ?? 0;

            // Insert child menus
            $menusToInsert = [
                [
                    'nama'       => 'Bahan Baku',
                    'routename'  => 'ingredients.index',
                    'icon'       => '',
                    'id_parent'  => $parentId,
                    'jnsmenu'    => 'child',
                    'urutan'     => ++$maxUrutan,
                    'stts'       => 'Y',
                ],
                [
                    'nama'       => 'Resep Menu',
                    'routename'  => 'recipes.index',
                    'icon'       => '',
                    'id_parent'  => $parentId,
                    'jnsmenu'    => 'child',
                    'urutan'     => ++$maxUrutan,
                    'stts'       => 'Y',
                ],
                [
                    'nama'       => 'Stok Bahan Baku',
                    'routename'  => 'ingredient-stocks.index',
                    'icon'       => '',
                    'id_parent'  => $parentId,
                    'jnsmenu'    => 'child',
                    'urutan'     => ++$maxUrutan,
                    'stts'       => 'Y',
                ],
            ];

            $roles = DB::table('role_master')->get();

            foreach ($menusToInsert as $menuData) {
                $menuData['created_at'] = now();
                $menuData['updated_at'] = now();

                $menuId = DB::table('menu_list')->insertGetId($menuData);

                // Map to all roles
                if (Schema::hasTable('menuby_role')) {
                    foreach ($roles as $role) {
                        DB::table('menuby_role')->insert([
                            'role_id' => $role->id,
                            'menu_id' => $menuId,
                        ]);
                    }
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
            $routenames = ['ingredients.index', 'recipes.index', 'ingredient-stocks.index'];

            $menus = DB::table('menu_list')
                ->whereIn('routename', $routenames)
                ->get();

            foreach ($menus as $menu) {
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
