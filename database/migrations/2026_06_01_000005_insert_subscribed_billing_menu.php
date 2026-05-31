<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('menu_list')) {
            // Find parent menu: 'Pengaturan'
            $parent = DB::table('menu_list')
                ->where('nama', 'Pengaturan')
                ->where('id_parent', 0)
                ->first();

            $parentId = $parent ? $parent->id : 0;

            // Get max urutan under this parent
            $maxUrutan = DB::table('menu_list')
                ->where('id_parent', $parentId)
                ->max('urutan') ?? 0;

            // Insert SaaS Billing menu
            $menuId = DB::table('menu_list')->insertGetId([
                'nama'       => 'SaaS Billing',
                'routename'  => 'subscribed-billing.index',
                'icon'       => '',
                'id_parent'  => $parentId,
                'jnsmenu'    => 'child',
                'urutan'     => $maxUrutan + 1,
                'stts'       => 'Y',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Map the menu only to SUPERADMIN role
            if (Schema::hasTable('menuby_role')) {
                $superadminRoles = DB::table('role_master')
                    ->where('role_type', 'SUPERADMIN')
                    ->get();

                foreach ($superadminRoles as $role) {
                    DB::table('menuby_role')->insert([
                        'role_id' => $role->id,
                        'menu_id' => $menuId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('menu_list')) {
            $menu = DB::table('menu_list')
                ->where('routename', 'subscribed-billing.index')
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
