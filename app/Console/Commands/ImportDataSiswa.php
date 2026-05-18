<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportDataSiswa extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-data-siswa';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data dari NSE ke Safira';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $siswa = DB::connection('financedb')
        ->table('view_siswa_by_thnajaran')
        ->where('thn_ajaran', '10')
        ->where('status', 'AKTIF')
        ->where('id', 1863)
        ->get();

        foreach ($siswa as $data) {
            $siswanse = DB::connection('nsedb')->table('siswa')->where('id_finance', $data->id)->first();
            if ($siswanse) {
                DB::connection('mysql')->table('siswa')->updateOrInsert(
                    ['id' => $siswanse->id],
                    [
                        'id' => $siswanse->id,
                        'finance_id' => $siswanse->id_finance,
                        'divisi_id' => $siswanse->id_divisi,
                        'kategorisiswa_id' => $siswanse->idkategorisiswa,
                        'nama_lengkap' => $siswanse->nama_lengkap,
                        'nama_panggilan' => $siswanse->nama_panggilan,
                        'jk' => ($siswanse->jk === 'Perempuan' ? 'P' : 'L'),
                        'no_induk' => $siswanse->no_induk,
                        'nis_nasional' => $siswanse->nis_nasional,
                        'nisn' => $siswanse->nisn,
                        'nik' => $siswanse->nik,
                        'kelas' => $data->id_kelas,
                        'tempat_lahir' => $siswanse->tempat_lahir,
                        'tgl_lahir' => ($siswanse->tgl_lahir === '0000-00-00' ? null : $siswanse->tgl_lahir),
                        'agama' => $siswanse->agama,
                        'kewarganegaraan' => $siswanse->kewarganegaraan,
                        'anak_ke' => $siswanse->anak_ke,
                        'jml_saudara_kandung' => $siswanse->jml_saudara_kandung,
                        'jml_saudara_tiri' => $siswanse->jml_saudara_tiri,
                        'ket_yatim' => $siswanse->ket_yatim,
                        'bahasa_sehari_hari' => $siswanse->bahasa_sehari_hari,
                        'anak_guru' => $siswanse->anak_guru,
                        'status_lms' => $siswanse->stts_lms,
                        'status_siswa' => $siswanse->stts_aktif,
                        'tglmasuk' => $siswanse->tglmasuk,
                        'created_at' => $siswanse->created_at,
                        'updated_at' => $siswanse->updated_at,
                    ]
                );

                //import data alamat
                $alamatsiswa = DB::connection('nsedb')->table('alamat')->where('id_siswa', $siswanse->id)->get();
                if ($alamatsiswa) {
                    foreach ($alamatsiswa as $datas) {
                        DB::connection('mysql')->table('alamat')->updateOrInsert(
                            ['id' => $datas->id_alamat],
                            [
                                'id_siswa' => $datas->id_siswa,
                                'jln' => $datas->jl,
                                'no_rumah' => $datas->no_rumah,
                                'rt' => $datas->rt,
                                'rw' => $datas->rw,
                                'kelurahan' => $datas->kelurahan,
                                'kecamatan' => $datas->kecamatan,
                                'kabupaten' => $datas->kotkab,
                                'provinsi' => $datas->provinsi,
                                'no_telp' => $datas->no_telp,
                                'status_alamat' => $datas->stat_alamat,
                                'pemilik_tmpt_tinggal' => $datas->pemilik_tmpt_tinggal,
                                'jarak' => $datas->jarak,
                                'waktu' => $datas->menit,
                                'kendaraan' => $datas->kendaraan,
                                'created_at' => $datas->created_at,
                                'updated_at' => $datas->updated_at,
                            ]
                        );
                    }
                }
                //import data wali
                $walisiswa = DB::connection('nsedb')->table('wali_siswa')->where('id_siswa', $siswanse->id)->get();
                foreach ($walisiswa as $datas) {
                    DB::connection('mysql')->table('wali_siswa')->updateOrInsert(
                        ['id' => $datas->id],
                        [
                            'id_siswa' => $datas->id_siswa,
                            'jenis' => $datas->wali,
                            'nik' => $datas->nik,
                            'nama' => $datas->nama,
                            'jk' => ($datas->jk === 'Perempuan' ? 'Perempuan' : 'Laki-Laki'),
                            'tempat_lahir' => $datas->tempat_lahir,
                            'tgl_lahir' => ($datas->tgl_lahir === '0000-00-00' ? null : $datas->tgl_lahir),
                            'agama' => $datas->agama,
                            'kewarganegaraan' => $datas->kewarganegaraan,
                            'hub_keluarga' => $datas->hub_keluarga,
                            'pendidikan' => $datas->pendidikan,
                            'pekerjaan' => $datas->pekerjaan,
                            'penghasilan' => $datas->penghasilan,
                            'alamat' => $datas->alamat,
                            'kelurahan' => $datas->kelurahan,
                            'kecamatan' => $datas->kecamatan,
                            'kabupaten' => $datas->kabupaten,
                            'provinsi' => $datas->provinsi,
                            'no_hp' => $datas->no_hp,
                            'status_hidup' => $datas->kehidupan,
                            'bin' => $datas->bin,
                            'created_at' => $datas->created_at,
                            'updated_at' => $datas->updated_at
                        ]
                    );
                }
                // import data kesehatan
                $kesehatansiswa = DB::connection('nsedb')->table('kesehatan')->where('id_siswa', $siswanse->id)->get();
                if ($kesehatansiswa) {
                    foreach ($kesehatansiswa as $datas) {
                        DB::connection('mysql')->table('kesehatan')->updateOrInsert(
                            ['id' => $datas->id],
                            [
                                'id_siswa' => $datas->id_siswa,
                                'id_penyakit' => $datas->penyakit,
                                'tempat_perawatan' => $datas->tempat_perawatan,
                                'kelainan_jasmani' => $datas->kelainan_jasmani,
                                'tinggi_bdn' => $datas->tinggi_bdn,
                                'berat_bdn' => $datas->berat_bdn,
                                'lingkar_kepala' => $datas->lingkar_kepala,
                                'created_at' => $datas->created_at,
                                'updated_at' => $datas->updated_at
                            ]
                        );
                    }
                }
                // import data saudara
                $saudarasiswa = DB::connection('nsedb')->table('saudara')->where('id_siswa', $siswanse->id)->get();
                foreach ($saudarasiswa as $datas) {
                    DB::connection('mysql')->table('saudara')->updateOrInsert(
                        ['id' => $datas->id],
                        [
                            'id_siswa' => $datas->id_siswa,
                            'nama' => $datas->nama,
                            'anak_ke' => $datas->anak_ke,
                            'jk' => ($datas->jk === 'Perempuan' ? 'Perempuan' : 'Laki-Laki'),
                            'kelas' => $datas->kelas,
                            'sekolah' => $datas->sekolah,
                            'ket' => $datas->ket,
                            'created_at' => $datas->created_at,
                            'updated_at' => $datas->updated_at
                        ]
                    );
                }
                // import data minat
                $minatsiswa = DB::connection('nsedb')->table('minat')->where('id_siswa', $siswanse->id)->get();
                foreach ($minatsiswa as $datas) {
                    DB::connection('mysql')->table('minat')->updateOrInsert(
                        ['id' => $datas->id],
                        [
                            'id_siswa' => $datas->id_siswa,
                            'id_minat' => $datas->bidang,
                            'prioritas' => $datas->prioritas,
                            'ket' => $datas->ket,
                            'cita' => $datas->cita,
                            'created_at' => $datas->created_at,
                            'updated_at' => $datas->updated_at
                        ]
                    );
                }
                // import data pindahan
                $pindahansiswa = DB::connection('nsedb')->table('pindahan')->where('id_siswa', $siswanse->id)->get();
                foreach ($pindahansiswa as $datas) {
                    DB::connection('mysql')->table('pindahan')->updateOrInsert(
                        ['id' => $datas->id],
                        [
                            'id_siswa' => $datas->id_siswa,
                            'asal_pindahan' => $datas->asal_pindahan,
                            'tgl_diterima' => ($datas->diterima_tgl === '0000-00-00' ? null : $datas->diterima_tgl),
                            'kelas' => $datas->kelas,
                            'alasan_pindah' => $datas->alasan_pindah,
                            'created_at' => $datas->created_at,
                            'updated_at' => $datas->updated_at
                        ]
                    );
                }
                // import data asal sekolah
                $asalsekolahsiswa = DB::connection('nsedb')->table('asal_sekolah')->where('id_siswa', $siswanse->id)->get();
                foreach ($asalsekolahsiswa as $datas) {
                    DB::connection('mysql')->table('asal_sekolah')->updateOrInsert(
                        ['id' => $datas->id_asal_sekolah],
                        [
                            'id_siswa' => $datas->id_siswa,
                            'status_pernah_sekolah' => $datas->sdh_prnh_sklh,
                            'nama_asal_sekolah' => $datas->asal_sekolah,
                            'jenjang' => $datas->jenjang,
                            'tgl_ijazah' => ($datas->tgl_ijazah === '0000-00-00' ? null : $datas->tgl_ijazah),
                            'no_ijazah' => $datas->no_ijazah,
                            'lama_belajar' => $datas->lama_belajar,
                            'created_at' => $datas->created_at,
                            'updated_at' => $datas->updated_at
                        ]
                    );
                }
                $this->info('Data siswa dengan ID ' . $siswanse->id . ' - ' . $siswanse->nama_lengkap . '.');
            }
        }
    }
}
