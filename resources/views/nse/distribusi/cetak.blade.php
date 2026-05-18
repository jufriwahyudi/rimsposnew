<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Invoice</title>
    @if (!$cetakpdf)
        <style>
            .layout {
                position: relative;
                margin: auto;
                padding: 0;
                width: 21cm;
                height: 33cm;
                background: white;
                background-image: linear-gradient(rgba(255, 255, 255, 0.85), rgba(255, 255, 255, 0.85)),
                    url("{{ $background }}");
                background-repeat: no-repeat;
                background-position: center 12cm;
                background-size: 65%;
                background-attachment: local;
                -webkit-print-color-adjust: exact;
            }
        </style>
    @else
        <style>
            .layout {
                position: relative;
                background: none;
            }

            .watermark {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: url('{{ $background }}') no-repeat center 12cm;
                background-size: 65%;
                opacity: 0.20;
                /* ubah nilai ini 0.1–0.4 sesuai kebutuhan */
                z-index: 0;
            }

            .content {
                position: relative;
                z-index: 1;
            }
        </style>
    @endif
    <style>
        @font-face {
            font-family: 'DejaVu Sans';
            font-style: normal;
            font-weight: normal;
            src: url("{{ storage_path('font/DejaVuSans.ttf') }}") format('truetype');
        }

        @page {
            margin: 0;
            /* margin-right: 1.5cm; */
        }

        /* @page :first {
            margin-bottom: 0;
        } */

        body {
            font-family: 'Times New Roman', Times, serif;
            /* font-family: 'xbriyaz'; */
            /* direction: rtl; */
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        .container {
            margin-top: -20px;
            margin-left: auto;
            margin-right: auto;
            width: 80%;
            /* margin: 0 auto; */
            padding: 5px;
            text-align: center;
            /* Pusatkan semua konten dalam container */
        }

        .container h5 {
            margin: 0;
            padding: 0;
        }

        .content {
            width: 80%;
            margin: 0 auto;
            padding: 5px;
            font-size: 15px;
        }

        .content p {
            text-align: justify;
            margin: 0;
            padding: 0;
        }

        .content ol {
            margin-top: 5px;
            padding-left: 60px;
            text-align: justify;
        }

        .spacer {
            height: 20px;
        }

        .page-break {
            page-break-before: always;
        }

        .page-break-avoid {
            page-break-inside: avoid;
            /* Hindari pemutusan halaman di dalam elemen ini */
        }
    </style>
</head>

<body>
    <div class="layout">
        <div class="watermark"></div>
        <div class="header">
            <img src="{{ $kop_surat }}" alt="Logo" style="width: 100%; height: auto; display: block;">
        </div>
        <div class="container" style="margin-top: 5px;">
            <h5>BERITA ACARA SERAH TERIMA SERAGAM</h5>
            <h5>{{ $biodata->divisi->divisifinance->nama }} ISLAM AL-AZHAR CAIRO BANDA ACEH</h5>
            <h5>TAHUN PELAJARAN {{ $thnajaran }}</h5>
        </div>
        <div class="content">
            <table border="0" style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                <tr>
                    <td width="70%" valign="top">
                        <p>{{ $gelombang }}</p>
                        <table border="0"
                            style="width: 100%; border-collapse: collapse; margin-top: 5px; vertical-align: top;">
                            <tr>
                                <td width="35%">Nama Lengkap</td>
                                <td width="2%">:</td>
                                <td>{{ strtoupper($biodata->nama_lengkap) }}</td>
                            </tr>
                            <tr>
                                <td>NIK/NISN</td>
                                <td>:</td>
                                <td>{{ $biodata->nik }}/{{ $biodata->nisn }}</td>
                            </tr>
                            <tr>
                                <td>Tempat/Tanggal Lahir</td>
                                <td>:</td>
                                <td>{{ $biodata->tempat_lahir }},
                                    {{ \Carbon\Carbon::parse($biodata->tgl_lahir)->locale('id')->translatedFormat('d F Y') }}
                                </td>
                            </tr>
                            <tr>
                                <td>Jenis Kelamin</td>
                                <td>:</td>
                                <td>{{ $biodata->jk }}</td>
                            </tr>
                            <tr>
                                <td>No.Telp</td>
                                <td>:</td>
                                <td>{{ $alamat->no_telp }}</td>
                            </tr>
                            <tr>
                                <td>Jadwal Pilih Seragam</td>
                                <td>:</td>
                                <td>
                                    {{ $jadwal?->jadwal?->tanggal ? date('d/m/Y', strtotime($jadwal->jadwal->tanggal)) : '-' }}
                                </td>
                            </tr>
                            <tr>
                                <td>Pukul/Sesi</td>
                                <td>:</td>
                                <td>
                                    {{ $jadwal?->sesi?->jam_mulai && $jadwal?->sesi?->jam_selesai
                                        ? $jadwal->sesi->jam_mulai . ' - ' . $jadwal->sesi->jam_selesai
                                        : '-' }}
                                </td>
                            </tr>
                            <tr>
                                <td>No.Invoice</td>
                                <td>:</td>
                                <td>{{ $sale->invoice_number ?? '-' }}</td>
                            </tr>
                        </table>
                    </td>
                    <td align="right" valign="top">
                        <p style="text-align: right;">No. Reg: {{ $noreg }}</p>
                        <img src="{{ $foto }}" alt="Foto"
                            style="width: 100px; height: auto; border: 1px solid #000;">
                    </td>
                </tr>
            </table>
            <p style="margin-top: 10px;"> DIVISI : {{ strtoupper($biodata->divisi->divisifinance->nama) }} </p>
            <table border="1" cellspacing="0"
                style="width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px;">
                <thead>
                    <tr>
                        <th style="padding: 5px; text-align: center;">No.</th>
                        <th style="padding: 5px; text-align: center;">Hari</th>
                        <th style="padding: 5px; text-align: center;">Seragam</th>
                        <th style="padding: 5px; text-align: center;">Kode</th>
                        <th style="padding: 5px; text-align: center;">Jumlah</th>
                        <th style="padding: 5px; text-align: center;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $no = 1;
                    @endphp
                    @php
                        $grouped = $seragam
                            ->sortBy(fn($item) => $item->seragam->hari == 0 ? 8 : $item->seragam->hari)
                            ->groupBy(fn($item) => $item->seragam->hari);
                    @endphp
                    @foreach ($grouped as $hari => $items)
                        @foreach ($items as $i => $item)
                            <tr>
                                <td style="padding:2px; text-align:center;">{{ $no++ }}</td>

                                {{-- Kolom HARI (rowspan) --}}
                                @if ($i == 0)
                                    <td style="padding:2px; text-align:center;" rowspan="{{ $items->count() }}">
                                        {{ strtoupper($item->seragam->hari_label) }}
                                    </td>
                                @endif

                                <td style="padding:2px;">
                                    {{ $item->seragam->nama }}
                                </td>

                                <td style="padding:2px; text-align:center;">
                                    {{ $item->productVariant->sku ?? '-' }}
                                </td>

                                <td style="padding:2px; text-align:center;">
                                    {{ $item->qty }}
                                </td>

                                <td style="padding:2px; text-align:center;">
                                    {{ $item->status === 'completed' ? 'Lengkap' : '' }}
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
            <div class="spacer"></div>
            <p>Catatan:</p>
            <ol>
                <li>Seragam hari kamis batik bebas.</li>
                <li>Seragam ananda yang telah Ayah/Bunda terima, mohon dicek kembali untuk memastikan kesesuaian ukuran
                    dan kelengkapannya.</li>
            </ol>
            <table border="0" style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                <tr>
                    <td style="text-align: center;" width="60%">
                        <p>&nbsp;
                        <p>Petugas</p>
                        <div class="spacer"></div>
                        <div class="spacer"></div>
                        <div class="spacer"></div>
                        <p>{{ $sale->cashier->name ?? '__________________________' }}</p>
                    </td>
                    <td style="text-align: center;">
                        <p>Banda Aceh,
                            {{ Carbon\Carbon::parse($sale->sale_date)->locale('id')->translatedFormat('d F Y') }}</p>
                        <p>Orang Tua/Wali</p>
                        <div class="spacer"></div>
                        <div class="spacer"></div>
                        <div class="spacer"></div>
                        <p>{{ $sale->receipt_name ?? '__________________________' }}</p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>
