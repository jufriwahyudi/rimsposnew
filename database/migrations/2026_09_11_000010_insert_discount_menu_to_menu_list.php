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
            $parent = DB::table('menu_list')
                ->where(function ($q) {
                    $q->where('nama', 'Master Data')
                      ->orWhere('nama', 'Pengaturan');
                })
                ->where('id_parent', 0)
                ->orderByRaw("CASE WHEN nama = 'Master Data' THEN 1 ELSE 2 END")
                ->first();

            $parentId = $parent ? $parent->id : 0;

            $maxUrutan = DB::table('menu_list')
                ->where('id_parent', $parentId)
                ->max('urutan') ?? 0;

            $exists = DB::table('menu_list')
                ->where('routename', 'discounts.index')
                ->first();

            if (!$exists) {
                $menuId = DB::table('menu_list')->insertGetId([
                    'nama'       => 'Diskon & Promosi',
                    'routename'  => 'discounts.index',
                    'icon'       => 'ticket',
                    'id_parent'  => $parentId,
                    'jnsmenu'    => 'child',
                    'urutan'     => $maxUrutan + 1,
                    'stts'       => 'Y',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if (Schema::hasTable('menuby_role')) {
                    $roles = DB::table('role_master')->get();
                    foreach ($roles as $role) {
                        DB::table('menuby_role')->insertOrIgnore([
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
            $menu = DB::table('menu_list')->where('routename', 'discounts.index')->first();
            if ($menu) {
                if (Schema::hasTable('menuby_role')) {
                    DB::table('menuby_role')->where('menu_id', $menu->id)->delete();
                }
                DB::table('menu_list')->where('id', $menu->id)->delete();
            }
        }
    }
};
