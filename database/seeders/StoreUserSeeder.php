<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StoreUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $store = \App\Models\Store::firstOrCreate([
            'name' => 'Toko Utama',
            'code' => 'TOKO_UTAMA',
        ], [
            'address' => 'Jl. Contoh Alamat No. 123, Kota Contoh',
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        $store->users()->syncWithoutDetaching([1]); // Asumsikan user dengan ID 1 adalah admin atau pengguna utama
    }
}
