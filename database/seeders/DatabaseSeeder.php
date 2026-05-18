<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\SiswaSeeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $attribute = [
            'ukuran' => [
                'S' => 'Small',
                'M' => 'Medium',
                'L' => 'Large',
                'XL' => 'Extra Large',
            ],
            'warna' => [
                'MERAH' => 'Merah',
                'BIRU'  => 'Biru',
                'HIJAU' => 'Hijau',
                'KUNING' => 'Kuning',
            ],
            'gender' => [
                'LK'    => 'Laki-Laki',
                'PR'    => 'Perempuan',
                'US'    => 'Unisex',
            ],
            'divisi' => [
                'TK'    => 'Taman Kanak-Kanak',
                'SD'    => 'Sekolah Dasar',
                'SMP'   => 'Sekolah Menengah Pertama',
            ],
        ];
        foreach ($attribute as $attrName => $values) {
            $attr = \App\Models\Attribute::create([
                'kode' => $attrName,
                'nama' => ucfirst($attrName),
            ]);
            foreach ($values as $code => $valName) {
                \App\Models\AttributeValue::create([
                    'attribute_id' => $attr->id,
                    'kode'         => $code,
                    'nama'         => $valName,
                ]);
            }
        }

        $this->call(RoleUserSeeder::class);
    }
}
