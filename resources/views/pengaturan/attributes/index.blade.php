@extends('layouts.main.main')
@section('title', 'Manajemen Menu')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Pengaturan Attribute Produk</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Pengaturan</a></li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card rounded-4 p-2">
                <div class="card-header d-flex justify-content-between align-items-center mb-3 flex-wrap">
                    <div class="d-flex align-items-start">
                        <img src={{ asset('assets/images/alazca_logo.png') }} alt="Logo"
                            style="width: 35px; height: 35px;" class="me-2 mt-1">
                        <div>
                            <h5 class="fw-bold mb-0" style="color: #7c3aed">Pengaturan Attribute Produk</h5>
                            <small class="text-muted">Al-Azhar Cairo Banda Aceh</small>
                        </div>
                    </div>
                    <a href="javascript:void(0);" class="btn btn-success btn-sm mb-3" data-bs-toggle="modal"
                        data-bs-target="#modalTambah"><i class="bi bi-plus"></i> Tambah Attribute</a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th width="7%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($attributes as $item)
                                <tr>
                                    <td>{{ $item->kode }}</td>
                                    <td>{{ $item->nama }}</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                            data-bs-target="#modalEdit{{ $item->id }}">
                                            Edit
                                        </button>
                                    </td>
                                </tr>

                                {{-- Modal Edit --}}
                                <div class="modal fade" id="modalEdit{{ $item->id }}">
                                    <div class="modal-dialog">
                                        <form method="POST" action="{{ route('attributes.update', $item->id) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5>Edit Attribute</h5>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Kode</label>
                                                        <input name="kode" class="form-control mb-2"
                                                            value="{{ $item->kode }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Nama</label>
                                                        <input name="nama" class="form-control"
                                                            value="{{ $item->nama }}" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button class="btn btn-primary">Simpan</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">Tidak ada data attribute.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    {{-- Modal Tambah --}}
    <div class="modal fade" id="modalTambah">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('attributes.store') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5>Tambah Attribute</h5>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Kode</label>
                            <input name="kode" class="form-control mb-2" placeholder="kode (ukuran)" required>
                            <small class="text-muted">Contoh: ukuran, warna, berat, dll.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input name="nama" class="form-control" placeholder="Nama (Ukuran)" required>
                            <small class="text-muted">Contoh: Ukuran, Warna, Berat, dll.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
