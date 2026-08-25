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
                <div class="card-header d-flex align-items-center mb-3">
                    <img src="{{ asset('assets/images/alazca_logo.png') }}" alt="Logo" style="width:35px;height:35px;" class="me-2">
                    <div>
                        <h5 class="fw-bold mb-0" style="color:#7c3aed">Manajemen Resep Menu</h5>
                        <small class="text-muted">Hubungkan produk menu jual dengan bahan baku pendukung</small>
                    </div>
                </div>
                <div class="card-body p-2 p-md-3">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle w-100 mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="40" class="text-center text-nowrap">#</th>
                                    <th class="text-nowrap" style="min-width: 100px;">Kode Produk</th>
                                    <th class="text-nowrap" style="min-width: 180px;">Nama Menu Jual</th>
                                    <th class="text-center text-nowrap" style="min-width: 120px;">Tipe Produk</th>
                                    <th class="text-nowrap" style="min-width: 220px;">Jumlah Bahan Baku</th>
                                    <th class="text-center text-nowrap" style="min-width: 130px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($products as $i => $product)
                                    @php
                                        $defaultCount = $product->recipes->count();
                                        $variantWithCustom = $product->variants->filter(fn($v) => $v->recipes->isNotEmpty())->count();
                                        $totalVariants = $product->variants->count();
                                    @endphp
                                    <tr>
                                        <td class="text-center fw-bold">{{ $i + 1 }}</td>
                                        <td class="text-nowrap"><span class="badge bg-secondary">{{ $product->kode_produk }}</span></td>
                                        <td>
                                            <strong class="text-dark">{{ $product->nama_produk }}</strong>
                                            @if ($totalVariants > 0)
                                                <small class="text-muted d-block">{{ $totalVariants }} Varian</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($product->product_type === 'RECIPE')
                                                <span class="badge bg-success">Resep (BOM)</span>
                                            @else
                                                <span class="badge bg-light text-dark border">Produk Tunggal</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($defaultCount > 0 || $variantWithCustom > 0)
                                                <div class="d-flex flex-column gap-1">
                                                    @if ($defaultCount > 0)
                                                        <div>
                                                            <span class="badge" style="background:#7c3aed">{{ $defaultCount }} Bahan Baku (Default)</span>
                                                        </div>
                                                    @endif
                                                    @if ($variantWithCustom > 0)
                                                        <div>
                                                            <span class="badge bg-success">{{ $variantWithCustom }} Varian Beresep Khusus</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <small class="text-muted">Tidak ada resep (Dipotong dari stok produk jadi)</small>
                                            @endif
                                        </td>
                                        <td class="text-center text-nowrap">
                                            <a href="{{ route('recipes.manage', $product->id) }}" class="btn btn-sm btn-primary rounded-3 text-nowrap">
                                                <i class="bi bi-gear-fill me-1"></i> Atur Resep
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">Belum ada produk jualan terdaftar.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
