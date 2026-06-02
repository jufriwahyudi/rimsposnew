<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('menu_list')) {
            // 1. Insert Activity Logs & Audit under Pengaturan
            $pengaturan = DB::table('menu_list')
                ->where('nama', 'Pengaturan')
                ->where('id_parent', 0)
                ->first();

            $pengaturanId = $pengaturan ? $pengaturan->id : 0;
            $maxUrutanPengaturan = DB::table('menu_list')
                ->where('id_parent', $pengaturanId)
                ->max('urutan') ?? 0;

            $activityLogMenuId = DB::table('menu_list')->insertGetId([
                'nama'       => 'Activity Logs & Audit',
                'routename'  => 'superadmin.activity-logs',
                'icon'       => '',
                'id_parent'  => $pengaturanId,
                'jnsmenu'    => 'child',
                'urutan'     => $maxUrutanPengaturan + 1,
                'stts'       => 'Y',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Insert Laporan Konsolidasi under Laporan
            $laporan = DB::table('menu_list')
                ->where('nama', 'Laporan')
                ->where('id_parent', 0)
                ->first();

            $laporanId = $laporan ? $laporan->id : 0;
            $maxUrutanLaporan = DB::table('menu_list')
                ->where('id_parent', $laporanId)
                ->max('urutan') ?? 0;

            $consolidatedReportMenuId = DB::table('menu_list')->insertGetId([
                'nama'       => 'Laporan Konsolidasi',
                'routename'  => 'superadmin.consolidated-reports',
                'icon'       => '',
                'id_parent'  => $laporanId,
                'jnsmenu'    => 'child',
                'urutan'     => $maxUrutanLaporan + 1,
                'stts'       => 'Y',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 3. Map both menus to SUPERADMIN role
            if (Schema::hasTable('menuby_role') && Schema::hasTable('role_master')) {
                $superadminRoles = DB::table('role_master')
                    ->where('role_type', 'SUPERADMIN')
                    ->get();

                foreach ($superadminRoles as $role) {
                    DB::table('menuby_role')->insert([
                        ['role_id' => $role->id, 'menu_id' => $activityLogMenuId],
                        ['role_id' => $role->id, 'menu_id' => $consolidatedReportMenuId],
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('menu_list')) {
            $menus = DB::table('menu_list')
                ->whereIn('routename', [
                    'superadmin.activity-logs',
                    'superadmin.consolidated-reports'
                ])
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
