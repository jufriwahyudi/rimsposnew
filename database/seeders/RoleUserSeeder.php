<?php

namespace Database\Seeders;

use App\Models\MenubyRole;
use App\Models\MenuList;
use App\Models\RoleMaster;
use App\Models\RoleUser as ModelsRoleUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Empty table first
        MenubyRole::truncate();
        MenuList::truncate();
        RoleMaster::truncate();
        ModelsRoleUser::truncate();

        $role = RoleMaster::create([
            'nama' => 'Super Admin',
            'role_type' => 'ADMIN',
            'can_access_all_divisi' => 'Y',
            'stts' => 'Y',
        ]);
        $menu = [
            ['nama' => 'Dashboard', 'routename' => 'dashboard', 'icon' => 'home', 'id_parent' => 0, 'jnsmenu' => 'menu', 'urutan' => 1, 'stts' => 'Y'],

            ['nama' => 'Pengaturan', 'routename' => '#', 'icon' => 'widgets', 'id_parent' => 0, 'jnsmenu' => 'menu', 'urutan' => 2, 'stts' => 'Y'],
            ['nama' => 'Manajemen Menu', 'routename' => 'menu.index', 'icon' => '', 'id_parent' => 2, 'jnsmenu' => 'child', 'urutan' => 1, 'stts' => 'Y'],
            ['nama' => 'Manajemen Pengguna', 'routename' => 'role.index', 'icon' => '', 'id_parent' => 2, 'jnsmenu' => 'child', 'urutan' => 2, 'stts' => 'Y'],

            ['nama' => 'Produk', 'routename' => '#', 'icon' => 'inventory_2', 'id_parent' => 0, 'jnsmenu' => 'menu', 'urutan' => 3, 'stts' => 'Y'],
            ['nama' => 'Pengaturan Produk', 'routename' => 'produk.index', 'icon' => '', 'id_parent' => 5, 'jnsmenu' => 'child', 'urutan' => 1, 'stts' => 'Y'],
            ['nama' => 'Atribut Produk', 'routename' => 'attributes.index', 'icon' => '', 'id_parent' => 5, 'jnsmenu' => 'child', 'urutan' => 2, 'stts' => 'Y'],
            ['nama' => 'Varian Produk', 'routename' => 'attribute-nilai.index', 'icon' => '', 'id_parent' => 5, 'jnsmenu' => 'child', 'urutan' => 3, 'stts' => 'Y'],

            ['nama' => 'Stok', 'routename' => '#', 'icon' => 'request_quote', 'id_parent' => 0, 'jnsmenu' => 'menu', 'urutan' => 4, 'stts' => 'Y'],
            ['nama' => 'Purchase Order', 'routename' => 'po.index', 'icon' => '', 'id_parent' => 9, 'jnsmenu' => 'child', 'urutan' => 1, 'stts' => 'Y'],

            ['nama' => 'Penjualan', 'routename' => 'pos.index', 'icon' => 'point_of_sale', 'id_parent' => 0, 'jnsmenu' => 'menu', 'urutan' => 5, 'stts' => 'Y'],
            ['nama' => 'POS', 'routename' => 'pos.index', 'icon' => '', 'id_parent' => 11, 'jnsmenu' => 'child', 'urutan' => 1, 'stts' => 'Y'],
            ['nama' => 'Data Penjualan', 'routename' => 'pos.sales', 'icon' => '', 'id_parent' => 11, 'jnsmenu' => 'child', 'urutan' => 2, 'stts' => 'Y'],
        ];
        // Insert menu data into the database
        foreach ($menu as $item) {
            $menu = MenuList::create($item);
            MenuByRole::create([
                'role_id' => $role->id,
                'menu_id' => $menu->id,
            ]);
        }
        ModelsRoleUser::create([
            'user_id' => 1, // Ganti dengan ID user yang sesuai
            'role_id' => $role->id,
        ]);
    }
}
