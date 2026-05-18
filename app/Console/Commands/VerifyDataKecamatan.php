<?php

namespace App\Console\Commands;

use App\Models\Alamat;
use Illuminate\Console\Command;

class VerifyDataKecamatan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:verify-data-kecamatan';

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
        $alamat = Alamat::orderBy('kode_kabupaten')->get();
        $kab = null;
        foreach ($alamat as $index => $a) {
            if ($kab !== $a->kode_kabupaten) {
                $response = file_get_contents('https://api.datawilayah.com/api/kecamatan/' . $a->kode_kabupaten . '.json');
                $data = json_decode($response);
                $data = collect($data->data);
                $kab = $a->kode_kabupaten;
            }
            $newkecamatan = $data->firstWhere('kode_wilayah', $a->kode_kecamatan);
            $a->kecamatan = $newkecamatan->nama_wilayah;
            $a->save();
            $this->info("{$index}: {$newkecamatan->kode_wilayah}\t{$newkecamatan->nama_wilayah}");
        }
    }
}
