<?php

namespace Database\Seeders;

use App\Models\JadwalDistribusi;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JadwalDistribusiNse extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Jadwal untuk Divisi KB/TK
        $jadwalTk = [
            '2026-05-04',
            '2026-05-05',
            '2026-05-06',
            '2026-05-07',
            '2026-05-08',
            '2026-05-11',
            '2026-05-12',
            '2026-05-13'
        ];
        $sesi = [
            ['jam_mulai' => '08:00:00', 'jam_selesai' => '09:00:00', 'kuota_sesi' => 3],
            ['jam_mulai' => '09:00:00', 'jam_selesai' => '10:00:00', 'kuota_sesi' => 3],
            ['jam_mulai' => '10:00:00', 'jam_selesai' => '11:00:00', 'kuota_sesi' => 3],
            ['jam_mulai' => '11:00:00', 'jam_selesai' => '12:00:00', 'kuota_sesi' => 3],
        ];
        foreach ($jadwalTk as $tanggal) {
            $jadwal = JadwalDistribusi::create([
                'tanggal' => $tanggal,
                'kuota_harian' => 12,
                'keterangan' => 'Distribusi NSE untuk Divisi KB/TK',
                'id_divisi' => 1, // Divisi KB/TK
                'is_active' => 'Y',
            ]);
            foreach ($sesi as $s) {
                $jadwal->sesi()->create([
                    'jam_mulai' => $s['jam_mulai'],
                    'jam_selesai' => $s['jam_selesai'],
                    'kuota_sesi' => $s['kuota_sesi'],
                ]);
            }
        }

        // Jadwal untuk Divisi SD Tanggal 18, 19, 20, 21, 22 Mei dan 2, 3, 4, 5 Juni 2026 (12 org/hari) 
        $jadwalSd = [
            '2026-05-18',
            '2026-05-19',
            '2026-05-20',
            '2026-05-21',
            '2026-05-22',
            '2026-06-02',
            '2026-06-03',
            '2026-06-04',
            '2026-06-05'
        ];
        foreach ($jadwalSd as $tanggal) {
            $jadwal = JadwalDistribusi::create([
                'tanggal' => $tanggal,
                'kuota_harian' => 12,
                'keterangan' => 'Distribusi NSE untuk Divisi SD',
                'id_divisi' => 2, // Divisi SD
                'is_active' => 'Y',
            ]);
            foreach ($sesi as $s) {
                $jadwal->sesi()->create([
                    'jam_mulai' => $s['jam_mulai'],
                    'jam_selesai' => $s['jam_selesai'],
                    'kuota_sesi' => $s['kuota_sesi'],
                ]);
            }
        }

        // Jadwal untuk Divisi SMP Tanggal 8, 9, 10, 11, 12, 15, 17, 18, 19 Juni 2026 (9 org/hari) 
        $jadwalSmp = [
            '2026-06-08',
            '2026-06-09',
            '2026-06-10',
            '2026-06-11',
            '2026-06-12',
            '2026-06-15',
            '2026-06-17',
            '2026-06-18',
            '2026-06-19'
        ];
        foreach ($jadwalSmp as $tanggal) {
            $jadwal = JadwalDistribusi::create([
                'tanggal' => $tanggal,
                'kuota_harian' => 9,
                'keterangan' => 'Distribusi NSE untuk Divisi SMP',
                'id_divisi' => 3, // Divisi SMP
                'is_active' => 'Y',
            ]);
            // ambil 3 sesi pertama untuk SMP
            foreach (array_slice($sesi, 0, 3) as $s) {
                $jadwal->sesi()->create([
                    'jam_mulai' => $s['jam_mulai'],
                    'jam_selesai' => $s['jam_selesai'],
                    'kuota_sesi' => $s['kuota_sesi'],
                ]);
            }
        }
    }
}
