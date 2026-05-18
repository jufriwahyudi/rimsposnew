<?php

namespace App\Exports;

use App\Models\BiodataDiri;
use App\Models\NseBiodataCalonSiswa;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
// Created by Alazca Developer
class ObservasiExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $gelombang;
    protected $divisi;
    protected $kategori;

    public function __construct($gelombang, $divisi, $kategori)
    {
        $this->gelombang = $gelombang;
        $this->divisi = $divisi;
        $this->kategori = $kategori;
    }

    public function collection()
    {
        $datas = NseBiodataCalonSiswa::getDataByGelombangWithHasil($this->gelombang)
            ->with([
                'hasilObservasi' => function ($q) {
                    $q->whereHas('kategori', function ($q2) {
                        $q2->where('jenis_observasi', 'anak');
                    })->with('kategori');
                },
                'hasilUjianKompetensi' => function ($q) {
                    $q->select('id', 'id_biodata', 'skor', 'rata_rata')
                        ->where('status', 'selesai');
                },
                'putusanObservasi'
            ])
            ->get();
        // Ambil data sesuai filter
        // $datas = NseBiodataCalonSiswa::with([
        //     'hasilObservasi',
        //     'hasilUjianKompetensi',
        //     'putusanObservasi'
        // ])
        //     ->where('idgelombang', $this->gelombang)   // ← FIX
        //     ->where('id_divisi', $this->divisi)        // ← pastikan kolom ini ada
        //     ->get();

        return $datas->map(function ($data) {
            $row = [
                'Nama Lengkap' => $data->nama_lengkap,
                'No Registrasi' => $data->no_reg ?? '-',   // hati-hati ini juga mungkin NULL
                'Kategori' => $data->kategori ?? '-',
            ];

            // Tambah nilai kategori dinamis
            $totalNilai = 0;

            foreach ($this->kategori as $kat) {
                $hasil = $data->hasilObservasi->where('kategori_id', $kat->id)->first();
                $nilai = $hasil ? $hasil->total_skor : 0;

                $row[$kat->nama_kategori] = $nilai;
                $totalNilai += $nilai;
            }

            // Divisi 5 → tambah ujian kompetensi
            if ($this->divisi == 5) {
                $nilaiUjian = $data->hasilUjianKompetensi->rata_rata ?? 0;
                $row['Ujian Kompetensi'] = $nilaiUjian;
                $rata2 = round(($totalNilai + $nilaiUjian) / (count($this->kategori) + 1), 2);
            } else {
                $rata2 = round($totalNilai / count($this->kategori), 2);
            }

            $row['Total'] = $rata2;

            // Putusan
            $row['Putusan'] = $data->putusanObservasi->putusan_label ?? '-';
            $row['Catatan Khusus'] = $data->putusanObservasi->catatan_khusus ?? '-';
            $row['Catatan Frontliner'] = $data->putusanObservasi->catatan_frontliner ?? '-';

            return $row;
        });
    }

    public function headings(): array
    {
        $heads = [
            'Nama Lengkap',
            'No Registrasi',
            'Kategori',
        ];

        // Tambah nama kategori dinamis
        foreach ($this->kategori as $kat) {
            $heads[] = $kat->nama_kategori;
        }

        // Tambah kolom ujian kompetensi jika divisi 5
        if ($this->divisi == 5) {
            $heads[] = 'Ujian Kompetensi';
        }

        $heads[] = 'Total';
        $heads[] = 'Putusan';
        $heads[] = 'Catatan Khusus';
        $heads[] = 'Catatan Frontliner';

        return $heads;
    }
}
