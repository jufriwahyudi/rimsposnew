@extends('layouts.main.main')
@section('title', 'Pengaturan Seragam NSE - Tambah Seragam')

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card rounded-4 p-2">
                <div class="card-header d-flex align-items-center mb-3">
                    <img src="{{ asset('assets/images/alazca_logo.png') }}" width="35" class="me-2">
                    <div>
                        <h5 class="fw-bold mb-0" style="color:#7c3aed">Pengaturan Seragam NSE</h5>
                        <small class="text-muted">Al-Azhar Cairo Banda Aceh</small>
                    </div>
                </div>

                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <h4>Tambah Seragam NSE</h4>
                        <a href="{{ route('seragam.index') }}" class="btn btn-outline-primary mb-3">Kembali</a>
                    </div>
                    <form action="{{ route('seragam.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label>Divisi</label>
                            <select name="id_divisi" class="form-select">
                                <option value="0">Umum</option>
                                @foreach ($divisis as $d)
                                    <option value="{{ $d->id }}">{{ $d->nama }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label>Nama Seragam</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <label>Gender</label>
                                <select name="jk" class="form-select">
                                    <option value="U">Umum</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label>Hari</label>
                                <select name="hari" class="form-select">
                                    <option value="0">Umum</option>
                                    <option value="1">Senin</option>
                                    <option value="2">Selasa</option>
                                    <option value="3">Rabu</option>
                                    <option value="4">Kamis</option>
                                    <option value="5">Jumat</option>
                                    <option value="6">Sabtu</option>
                                    <option value="7">Minggu</option>
                                    <option value="8">Jam Pelajaran</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label>Jumlah (pcs)</label>
                                <input type="number" name="pcs" class="form-control" value="1" required>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <label>Jenis Seragam</label>
                                    <select name="jenis" class="form-select">
                                        <option value="baju">Baju</option>
                                        <option value="celana">Celana</option>
                                        <option value="lengkap">Lengkap</option>
                                        <option value="jilbab">Jilbab</option>
                                        <option value="dasi">Dasi</option>
                                        <option value="stiker">Stiker</option>
                                        <option value="godybag">Godybag</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label>Produk Koperasi</label>
                                    <select name="id_produk_koperasi" class="form-select">
                                        <option value="">-- Pilih Produk --</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->kode_produk }} -
                                                {{ $product->nama_produk }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                        </div>

                        <div class="mt-3">
                            <label>
                                <input type="checkbox" name="wajib" value="Y" checked> Wajib dikasih
                            </label>
                        </div>
                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-success">Simpan Seragam</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
