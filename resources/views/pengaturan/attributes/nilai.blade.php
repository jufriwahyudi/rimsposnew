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
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    <form method="GET" class="row g-2 mb-3">
                        <div class="col-md-4">
                            <select name="attribute_id" class="form-select" onchange="this.form.submit()">
                                <option value="">-- Pilih Attribute --</option>
                                @foreach ($attributes as $attr)
                                    <option value="{{ $attr->id }}" {{ $attributeId == $attr->id ? 'selected' : '' }}>
                                        {{ $attr->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            @if ($attributeId)
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#addModal">
                                    + Tambah
                                </button>
                            @endif
                        </div>
                    </form>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th width="50">No</th>
                                <th width="120">Kode</th>
                                <th>Nama</th>
                                <th width="150" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($values as $i => $val)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $val->kode }}</td>
                                    <td>{{ $val->nama }}</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                            data-bs-target="#edit{{ $val->id }}">
                                            Edit
                                        </button>

                                        <form method="POST" action="{{ route('attribute-nilai.destroy', $val) }}"
                                            class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button onclick="return confirm('Hapus?')" class="btn btn-sm btn-danger">
                                                Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                {{-- MODAL EDIT --}}
                                <div class="modal fade" id="edit{{ $val->id }}">
                                    <div class="modal-dialog">
                                        <form method="POST" action="{{ route('attribute-nilai.update', $val) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5>Edit Attribute Value</h5>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Kode</label>
                                                        <input name="kode" class="form-control mb-2"
                                                            value="{{ $val->kode }}" required>
                                                        <small class="text-muted">Contoh: L, M, S, XL</small>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Nama</label>
                                                        <input name="nama" class="form-control"
                                                            value="{{ $val->nama }}" required>
                                                        <small class="text-muted">Contoh: Large, Medium, Small, Extra
                                                            Large</small>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button class="btn btn-primary">Simpan</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">
                                        Pilih attribute terlebih dahulu
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>
    {{-- Modal Tambah --}}
    @if ($attributeId)
        <div class="modal fade" id="addModal">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('attribute-nilai.store') }}">
                    @csrf
                    <input type="hidden" name="attribute_id" value="{{ $attributeId }}">

                    <div class="modal-content">
                        <div class="modal-header">
                            <h5>Tambah Attribute Value</h5>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Kode</label>
                                <input name="kode" class="form-control mb-2" placeholder="Kode (L)" required>
                                <small class="text-muted">Contoh: L, M, S, XL</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nama</label>
                                <input name="nama" class="form-control" placeholder="Nama (Large)" required>
                                <small class="text-muted">Contoh: Large, Medium, Small, Extra Large</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button class="btn btn-primary">Simpan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif


@endsection
