<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert child menu under parent Laporan (ID 18)
        $menuId = DB::table('menu_list')->insertGetId([
            'nama' => 'Laporan Penukaran Poin',
            'routename' => 'laporan.penukaran-poin',
            'icon' => '',
            'id_parent' => 18,
            'jnsmenu' => 'child',
            'urutan' => 7,
            'stts' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Find roles that have access to the parent menu "Laporan" (ID 18)
        $roles = DB::table('menuby_role')
            ->where('menu_id', 18)
            ->pluck('role_id');

        // Link new menu to those roles
        foreach ($roles as $roleId) {
            DB::table('menuby_role')->insert([
                'menu_id' => $menuId,
                'role_id' => $roleId,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $menu = DB::table('menu_list')->where('routename', 'laporan.penukaran-poin')->first();
        if ($menu) {
            DB::table('menuby_role')->where('menu_id', $menu->id)->delete();
            DB::table('menu_list')->where('id', $menu->id)->delete();
        }
    }
};
