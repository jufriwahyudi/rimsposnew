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
        // Temukan ID parent untuk menu 'Laporan' (id = 18)
        $parentMenu = DB::table('menu_list')->where('nama', 'Laporan')->first();

        if ($parentMenu) {
            $maxUrutan = DB::table('menu_list')->where('id_parent', $parentMenu->id)->max('urutan') ?? 0;

            // Masukkan sub-menu baru 'Laporan Hutang'
            $menuId = DB::table('menu_list')->insertGetId([
                'nama' => 'Laporan Hutang',
                'routename' => 'laporan.hutang',
                'icon' => '',
                'id_parent' => $parentMenu->id,
                'jnsmenu' => 'child',
                'urutan' => $maxUrutan + 1,
                'stts' => 'Y',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $menu = DB::table('menu_list')->where('routename', 'laporan.hutang')->first();
        if ($menu) {
            DB::table('menu_list')->where('id', $menu->id)->delete();
        }
    }
};
