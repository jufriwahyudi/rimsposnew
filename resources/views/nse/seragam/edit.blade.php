@extends('layouts.main.main')
@section('title', 'Pengaturan Seragam NSE - Edit Seragam')

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card rounded-4 p-2">
                <div class="card-header d-flex align-items-center mb-3">
                    <img src="{{ asset('assets/images/alazca_logo.png') }}" width="35" class="me-2">
                    <div>
                        <h5 class="fw-bold mb-0" style="color:#7c3aed">Pengaturan Seragam NSE</h5>
                        <small class="text-muted">{{ session('store_name') }}</small>
                    </div>
                </div>

                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <h4>Edit Seragam NSE</h4>
                        <a href="{{ route('seragam.index') }}" class="btn btn-outline-primary mb-3">Kembali</a>
                    </div>
                    <form action="{{ route('seragam.update', $seragam->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label>Divisi</label>
                            <select name="id_divisi" class="form-select">
                                <option value="0">Umum</option>
                                @foreach ($divisis as $d)
                                    <option value="{{ $d->id }}"
                                        {{ $seragam->id_divisi == $d->id ? 'selected' : '' }}>{{ $d->nama }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label>Nama Seragam</label>
                            <input type="text" name="nama" class="form-control" value="{{ $seragam->nama }}" required>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <label>Gender</label>
                                <select name="jk" class="form-select">
                                    <option value="U" {{ $seragam->jk == 'U' ? 'selected' : '' }}>Umum</option>
                                    <option value="L" {{ $seragam->jk == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="P" {{ $seragam->jk == 'P' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label>Hari</label>
                                <select name="hari" class="form-select">
                                    <option value="0" {{ $seragam->hari == 0 ? 'selected' : '' }}>Umum</option>
                                    <option value="1" {{ $seragam->hari == 1 ? 'selected' : '' }}>Senin</option>
                                    <option value="2" {{ $seragam->hari == 2 ? 'selected' : '' }}>Selasa</option>
                                    <option value="3" {{ $seragam->hari == 3 ? 'selected' : '' }}>Rabu</option>
                                    <option value="4" {{ $seragam->hari == 4 ? 'selected' : '' }}>Kamis</option>
                                    <option value="5" {{ $seragam->hari == 5 ? 'selected' : '' }}>Jumat</option>
                                    <option value="6" {{ $seragam->hari == 6 ? 'selected' : '' }}>Sabtu</option>
                                    <option value="7" {{ $seragam->hari == 7 ? 'selected' : '' }}>Minggu</option>
                                    <option value="8" {{ $seragam->hari == 8 ? 'selected' : '' }}>Jam Pelajaran
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label>Jumlah (pcs)</label>
                                <input type="number" name="pcs" class="form-control" value="{{ $seragam->pcs }}"
                                    required>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <label>Jenis Seragam</label>
                                    <select name="jenis" class="form-select">
                                        <option value="baju" {{ $seragam->jenis == 'baju' ? 'selected' : '' }}>Baju
                                        </option>
                                        <option value="celana" {{ $seragam->jenis == 'celana' ? 'selected' : '' }}>Celana
                                        </option>
                                        <option value="lengkap" {{ $seragam->jenis == 'lengkap' ? 'selected' : '' }}>
                                            Lengkap</option>
                                        <option value="jilbab" {{ $seragam->jenis == 'jilbab' ? 'selected' : '' }}>Jilbab
                                        </option>
                                        <option value="dasi" {{ $seragam->jenis == 'dasi' ? 'selected' : '' }}>Dasi
                                        </option>
                                        <option value="stiker" {{ $seragam->jenis == 'stiker' ? 'selected' : '' }}>Stiker
                                        </option>
                                        <option value="godybag" {{ $seragam->jenis == 'godybag' ? 'selected' : '' }}>
                                            Godybag</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label>Produk Koperasi</label>
                                    <select name="id_produk_koperasi" class="form-select">
                                        <option value="">-- Pilih Produk --</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}"
                                                {{ $seragam->ukuranSeragam && $seragam->ukuranSeragam->id_produk_koperasi == $product->id ? 'selected' : '' }}>
                                                {{ $product->kode_produk }} - {{ $product->nama_produk }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                        </div>

                        <div class="mt-3">
                            <label>
                                <input type="checkbox" name="wajib" value="Y"
                                    {{ $seragam->wajib == 'Y' ? 'checked' : '' }}> Wajib dikasih
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
