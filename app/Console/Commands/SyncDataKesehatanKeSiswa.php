<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncDataKesehatanKeSiswa extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-data-kesehatan-ke-siswa';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi data kesehatan dari tabel kesehatan ke tabel siswa';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // ambil semua data kesehatan dari database sumber (nsedb)
        $kesehatansiswa = DB::connection('nsedb')->table('kesehatan')->get();

        // loop tiap data kesehatan
        foreach ($kesehatansiswa as $data) {
            // update tabel siswa di database utama (mysql)
            DB::connection('mysql')->table('siswa')
                ->where('id', $data->id_siswa) // update berdasarkan id_siswa
                ->update([
                    'tinggi_bdn'      => $data->tinggi_bdn,
                    'berat_bdn'       => $data->berat_bdn,
                    'lingkar_kepala'  => $data->lingkar_kepala,
                    'updated_at'      => now()
                ]);

            // kasih info di terminal
            $this->info("Data kesehatan untuk siswa ID {$data->id_siswa} berhasil disinkron.");
        }

        $this->info("Sinkronisasi data kesehatan selesai");
    }
}
