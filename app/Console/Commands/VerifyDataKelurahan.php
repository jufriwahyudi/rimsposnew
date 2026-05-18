<?php

namespace App\Console\Commands;

use App\Models\Alamat;
use Illuminate\Console\Command;

class VerifyDataKelurahan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:verify-data-kelurahan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $alamat = Alamat::orderBy('kode_kecamatan')->get();
        $kec = null;
        foreach ($alamat as $index => $a) {
            if ($kec !== $a->kode_kecamatan) {
                $response = file_get_contents('https://api.datawilayah.com/api/desa_kelurahan/' . $a->kode_kecamatan . '.json');
                $data = json_decode($response);
                $data = collect($data->data);
                $kec = $a->kode_kecamatan;
            }
            $newkelurahan = $data->firstWhere('kode_wilayah', $a->kode_kelurahan);
            $a->kelurahan = $newkelurahan->nama_wilayah;
            $a->kode_kecamatan = $newkelurahan->kode_kecamatan;
            $a->kode_kabupaten = $newkelurahan->kode_kabkota;
            $a->kode_provinsi = $newkelurahan->kode_provinsi;
            $a->save();
            $this->info("{$index}: {$newkelurahan->kode_wilayah}\t{$newkelurahan->kode_kecamatan}\t{$newkelurahan->nama_wilayah}");
        }
    }
}
