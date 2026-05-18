<?php

namespace App\Console\Commands;

use App\Models\Alamat;
use Illuminate\Console\Command;

class VerifyDataKabupaten extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:verify-data-kabupaten';

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
        $alamat = Alamat::orderBy('kode_provinsi')->get();
        $prov = null;
        foreach ($alamat as $index => $a) {
            if ($prov !== $a->kode_provinsi) {
                $response = file_get_contents('https://api.datawilayah.com/api/kabupaten_kota/' . $a->kode_provinsi . '.json');
                $data = json_decode($response);
                $data = collect($data->data);
                $prov = $a->kode_provinsi;
            }
            $newkabupaten = $data->firstWhere('kode_wilayah', $a->kode_kabupaten);
            $a->kabupaten = $newkabupaten->nama_wilayah;
            $a->provinsi  = $newkabupaten->nama_provinsi;
            $a->save();
            $this->info("{$index}: {$newkabupaten->kode_wilayah}\t{$newkabupaten->nama_wilayah}\t{$newkabupaten->nama_provinsi}");
        }
    }
}
