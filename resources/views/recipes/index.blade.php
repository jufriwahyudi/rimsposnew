@extends('layouts.main.main')
@section('title', 'Resep Menu (Bill of Materials)')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Stok</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Resep Menu</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            @if (session('success'))
                <div class="alert alert-success border-0 bg-light-success alert-dismissible fade show py-2">
                    <div class="d-flex align-items-center">
                        <div class="fs-3 text-success"><span class="material-icons-outlined">check_circle</span></div>
                        <div class="ms-3">
                            <div class="text-success">{{ session('success') }}</div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card rounded-4 p-2">
                <div class="card-header d-flex align-items-start mb-3">
                    <img src="{{ asset('assets/images/alazca_logo.png') }}" alt="Logo" style="width:35px;height:35px;" class="me-2 mt-1">
                    <div>
                        <h5 class="fw-bold mb-0" style="color:#7c3aed">Manajemen Resep Menu</h5>
                        <small class="text-muted">Hubungkan produk menu jual dengan bahan baku pendukung</small>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="40">#</th>
                                <th>Kode Produk</th>
                                <th>Nama Menu Jual</th>
                                <th>Tipe Produk</th>
                                <th>Jumlah Bahan Baku</th>
                                <th class="text-center" width="150">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($products as $i => $product)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td><span class="badge bg-secondary">{{ $product->kode_produk }}</span></td>
                                    <td><strong>{{ $product->nama_produk }}</strong></td>
                                    <td>
                                        @if ($product->product_type === 'RECIPE')
                                            <span class="badge bg-success">Resep (BOM)</span>
                                        @else
                                            <span class="badge bg-light text-dark border">Produk Tunggal</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($product->recipes_count > 0)
                                            <span class="badge bg-purple" style="background:#7c3aed">{{ $product->recipes_count }} Bahan Baku</span>
                                        @else
                                            <small class="text-muted">Tidak ada resep (Dipotong dari stok produk jadi)</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('recipes.manage', $product->id) }}" class="btn btn-sm btn-primary">
                                            <i class="material-icons-outlined" style="font-size:15px;vertical-align:middle">settings</i> Atur Resep
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Belum ada produk jualan terdaftar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
