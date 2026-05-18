@extends('layouts.main.main')
@section('title', 'Manajemen Menu')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Pengaturan Produk</div>
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
                            <h5 class="fw-bold mb-0" style="color: #7c3aed">Pengaturan Produk</h5>
                            <small class="text-muted">Al-Azhar Cairo Banda Aceh</small>
                        </div>
                    </div>
                    <a href="{{ route('produk.create') }}" class="btn btn-success btn-sm mb-3"><i class="bi bi-plus"></i>
                        Tambah Produk</a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th width="10%" class="text-center">Varian</th>
                                <th width="10%" class="text-center">Stok Gudang</th>
                                <th width="10%" class="text-center">Stok Toko</th>
                                <th width="12%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $p)
                                <tr>
                                    <td>{{ $p->kode_produk }}</td>
                                    <td>{{ $p->nama_produk }}</td>
                                    <td class="text-end">{{ $p->variants_count }}</td>
                                    <td class="text-end">{{ $p->stock_warehouse ?? 0 }}</td>
                                    <td class="text-end">{{ $p->stock_store ?? 0 }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('produk.edit', $p) }}" class="btn btn-sm btn-warning">
                                            Edit
                                        </a>

                                        <a href="{{ route('produk.show', $p) }}" class="btn btn-sm btn-info">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {!! $products->withQueryString()->links('pagination::bootstrap-5') !!}
                </div>
            </div>
        </div>
    </div>

@endsection
