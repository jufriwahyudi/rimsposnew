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
            // Check if parent menu 'Barang Titipan' already exists
            $parent = DB::table('menu_list')
                ->where('routename', 'consignments.index')
                ->orWhere('nama', 'Barang Titipan')
                ->first();

            if (!$parent) {
                $maxUrutan = DB::table('menu_list')
                    ->where('id_parent', 0)
                    ->max('urutan') ?? 0;

                $parentId = DB::table('menu_list')->insertGetId([
                    'nama'       => 'Barang Titipan',
                    'routename'  => '#',
                    'icon'       => 'handshake',
                    'id_parent'  => 0,
                    'jnsmenu'    => 'parent',
                    'urutan'     => $maxUrutan + 1,
                    'stts'       => 'Y',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Children:
                // 1. Terima Titipan Pagi & Rekonsiliasi Sore
                DB::table('menu_list')->insert([
                    'nama'       => 'Titipan Harian (Pagi/Sore)',
                    'routename'  => 'consignments.daily',
                    'icon'       => '',
                    'id_parent'  => $parentId,
                    'jnsmenu'    => 'child',
                    'urutan'     => 1,
                    'stts'       => 'Y',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // 2. Master Penitip & Item
                DB::table('menu_list')->insert([
                    'nama'       => 'Data Penitip & Item',
                    'routename'  => 'consignments.index',
                    'icon'       => '',
                    'id_parent'  => $parentId,
                    'jnsmenu'    => 'child',
                    'urutan'     => 2,
                    'stts'       => 'Y',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // 3. Riwayat Pembayaran & Struk
                DB::table('menu_list')->insert([
                    'nama'       => 'Riwayat Bayar Titipan',
                    'routename'  => 'consignments.settlements',
                    'icon'       => '',
                    'id_parent'  => $parentId,
                    'jnsmenu'    => 'child',
                    'urutan'     => 3,
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
            $parent = DB::table('menu_list')->where('nama', 'Barang Titipan')->first();
            if ($parent) {
                DB::table('menu_list')->where('id_parent', $parent->id)->delete();
                DB::table('menu_list')->where('id', $parent->id)->delete();
            }
        }
    }
};
