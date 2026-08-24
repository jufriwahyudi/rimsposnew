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
            // 1. Create or Find Parent Menu 'Layanan Servis'
            $parentService = DB::table('menu_list')
                ->where('nama', 'Layanan Servis')
                ->where('id_parent', 0)
                ->first();

            if (!$parentService) {
                $maxParentUrutan = DB::table('menu_list')
                    ->where('id_parent', 0)
                    ->max('urutan') ?? 0;

                $parentServiceId = DB::table('menu_list')->insertGetId([
                    'nama'       => 'Layanan Servis',
                    'routename'  => '#',
                    'icon'       => 'build',
                    'id_parent'  => 0,
                    'jnsmenu'    => 'menu',
                    'urutan'     => $maxParentUrutan + 1,
                    'stts'       => 'Y',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $parentServiceId = $parentService->id;
            }

            // Insert child menus under Layanan Servis
            $serviceChildMaxUrutan = DB::table('menu_list')
                ->where('id_parent', $parentServiceId)
                ->max('urutan') ?? 0;

            $serviceMenus = [
                [
                    'nama'       => 'Tiket Servis / Work Order',
                    'routename'  => 'service-orders.index',
                    'icon'       => '',
                    'id_parent'  => $parentServiceId,
                    'jnsmenu'    => 'child',
                    'urutan'     => ++$serviceChildMaxUrutan,
                    'stts'       => 'Y',
                ],
                [
                    'nama'       => 'Rekap Komisi Staff',
                    'routename'  => 'staff-commissions.index',
                    'icon'       => '',
                    'id_parent'  => $parentServiceId,
                    'jnsmenu'    => 'child',
                    'urutan'     => ++$serviceChildMaxUrutan,
                    'stts'       => 'Y',
                ],
            ];

            $roles = DB::table('role_master')->get();

            foreach ($serviceMenus as $menuData) {
                if (!DB::table('menu_list')->where('routename', $menuData['routename'])->exists()) {
                    $menuData['created_at'] = now();
                    $menuData['updated_at'] = now();
                    $menuId = DB::table('menu_list')->insertGetId($menuData);

                    // if (Schema::hasTable('menuby_role')) {
                    //     foreach ($roles as $role) {
                    //         DB::table('menuby_role')->insert([
                    //             'role_id' => $role->id,
                    //             'menu_id' => $menuId,
                    //         ]);
                    //     }
                    // }
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
            $routenames = ['service-orders.index', 'staff-commissions.index'];
            $menus = DB::table('menu_list')->whereIn('routename', $routenames)->get();

            foreach ($menus as $menu) {
                if (Schema::hasTable('menuby_role')) {
                    DB::table('menuby_role')->where('menu_id', $menu->id)->delete();
                }
                DB::table('menu_list')->where('id', $menu->id)->delete();
            }

            // Also delete parent menu if empty
            $parent = DB::table('menu_list')->where('nama', 'Layanan Servis')->where('id_parent', 0)->first();
            if ($parent) {
                DB::table('menu_list')->where('id', $parent->id)->delete();
            }
        }
    }
};
