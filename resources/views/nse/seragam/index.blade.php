@extends('layouts.main.main')
@section('title', 'Pengaturan Seragam NSE - Daftar Seragam')

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
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
                    <div class="d-flex justify-content-between mb-2">
                        <h4>Daftar Seragam NSE</h4>
                        <a href="{{ route('seragam.create') }}" class="btn btn-primary mb-3">+ Tambah Seragam</a>
                    </div>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Divisi</th>
                                <th>Nama</th>
                                <th>Produk Koperasi</th>
                                <th>Gender</th>
                                <th>Hari</th>
                                <th>Jenis</th>
                                <th class="text-center">Pcs</th>
                                <th class="text-center">Wajib</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($seragams as $i => $s)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $s->divisi->nama ?? 'Umum' }}</td>
                                    <td>{{ $s->nama }}</td>
                                    <td>{{ $s->ukuranSeragam->product->nama_produk ?? 'Belum dipilih' }}</td>
                                    <td>{{ $s->jk_label }}</td>
                                    <td>{{ $s->hari_label }}</td>
                                    <td>{{ $s->jenis_label }}</td>
                                    <td class="text-center">{{ $s->pcs }}</td>
                                    <td class="text-center">
                                        {!! $s->wajib == 'Y'
                                            ? '<span class="badge bg-success">Ya</span>'
                                            : '<span class="badge bg-secondary">Tidak</span>' !!}
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('seragam.edit', $s->id) }}" class="btn btn-sm btn-warning"><i
                                                class="bi bi-pencil"></i></a>
                                        <form action="{{ route('seragam.destroy', $s->id) }}" method="POST"
                                            class="d-inline">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
