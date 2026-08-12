@extends('layouts.main.main')
@section('title', 'Laporan Aktivitas Stok')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Stok</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('ingredient-stocks.index') }}">Stok Bahan Baku</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Laporan Aktivitas</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    {{-- Filter Card --}}
    <div class="card rounded-4 p-2 mb-4">
        <div class="card-header bg-transparent border-0 mb-2">
            <h5 class="fw-bold text-dark mb-0"><span class="material-icons-outlined" style="vertical-align:middle;font-size:20px">filter_alt</span> Filter Laporan</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('ingredient-stocks.report') }}" method="GET" class="row g-3">
                <div class="col-12 col-md-3">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Bahan Baku</label>
                    <select class="form-select" name="ingredient_id">
                        <option value="">-- Semua Bahan --</option>
                        @foreach($ingredients as $ing)
                            <option value="{{ $ing->id }}" {{ $selectedIngredientId == $ing->id ? 'selected' : '' }}>
                                {{ $ing->name }} ({{ $ing->sku }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Lokasi</label>
                    <select class="form-select" name="location_type">
                        <option value="">-- Semua Lokasi --</option>
                        <option value="WAREHOUSE" {{ $selectedLocationType == 'WAREHOUSE' ? 'selected' : '' }}>GUDANG (WAREHOUSE)</option>
                        <option value="STORE" {{ $selectedLocationType == 'STORE' ? 'selected' : '' }}>TOKO (STORE)</option>
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                    <a href="{{ route('ingredient-stocks.report') }}" class="btn btn-secondary px-3 btn-sm">Reset</a>
                    <button type="submit" class="btn btn-primary px-4 btn-sm text-white">Tampilkan Laporan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Period Summaries --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card rounded-4 border-0 border-start border-success border-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <p class="mb-0 text-secondary">Total Stock In (Beli)</p>
                            <h4 class="my-1 text-success">{{ number_format($summary['total_purchase'], 2, ',', '.') }}</h4>
                            <small class="text-muted">Total kuantitas dibeli</small>
                        </div>
                        <div class="widgets-icons-2 rounded-circle bg-light-success text-success ms-auto">
                            <span class="material-icons-outlined">shopping_cart</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card rounded-4 border-0 border-start border-primary border-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <p class="mb-0 text-secondary">Total Transfer Toko</p>
                            <h4 class="my-1 text-primary">{{ number_format($summary['total_transfer'], 2, ',', '.') }}</h4>
                            <small class="text-muted">Dikirim dari Gudang</small>
                        </div>
                        <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary ms-auto">
                            <span class="material-icons-outlined">local_shipping</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card rounded-4 border-0 border-start border-danger border-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <p class="mb-0 text-secondary">Total Terpakai (Sale)</p>
                            <h4 class="my-1 text-danger">{{ number_format($summary['total_sale'], 2, ',', '.') }}</h4>
                            <small class="text-muted">Dikutip otomatis resep</small>
                        </div>
                        <div class="widgets-icons-2 rounded-circle bg-light-danger text-danger ms-auto">
                            <span class="material-icons-outlined">restaurant</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card rounded-4 border-0 border-start border-warning border-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <p class="mb-0 text-secondary">Total Rusak (Wastage)</p>
                            <h4 class="my-1 text-warning">{{ number_format($summary['total_wastage'], 2, ',', '.') }}</h4>
                            <small class="text-muted">Barang gosong/rusak/busuk</small>
                        </div>
                        <div class="widgets-icons-2 rounded-circle bg-light-warning text-warning ms-auto">
                            <span class="material-icons-outlined">delete_outline</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Movements Table --}}
    <div class="card rounded-4 p-2">
        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-0 text-dark">Detail Pergerakan Stok</h5>
                <small class="text-muted">Periode: <strong>{{ date('d-m-Y', strtotime($startDate)) }}</strong> s.d. <strong>{{ date('d-m-Y', strtotime($endDate)) }}</strong></small>
            </div>
            <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                <i class="material-icons-outlined" style="font-size:16px;vertical-align:middle">print</i> Cetak Laporan
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Waktu & Tanggal</th>
                            <th>SKU</th>
                            <th>Bahan Baku</th>
                            <th>Area Lokasi</th>
                            <th>Tipe Aktivitas</th>
                            <th class="text-end">Jumlah Selisih</th>
                            <th>Referensi</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $mov)
                            @php
                                $badgeColor = 'bg-secondary';
                                if($mov->type === 'PURCHASE' || $mov->type === 'TRANSFER_IN') $badgeColor = 'bg-success';
                                if($mov->type === 'TRANSFER_OUT' || $mov->type === 'SALE' || $mov->type === 'WASTAGE') $badgeColor = 'bg-danger';
                            @endphp
                            <tr>
                                <td>{{ date('d-m-Y H:i', strtotime($mov->tanggal)) }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $mov->ingredient?->sku }}</span></td>
                                <td><strong>{{ $mov->ingredient?->name }}</strong></td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $mov->location_type }}
                                    </span>
                                </td>
                                <td><span class="badge {{ $badgeColor }}">{{ $mov->type }}</span></td>
                                <td class="text-end fw-bold {{ $mov->quantity_change > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $mov->quantity_change > 0 ? '+' : '' }}{{ number_format($mov->quantity_change, 2, ',', '.') }} {{ $mov->ingredient?->baseUnit?->symbol }}
                                </td>
                                <td><small class="text-muted">{{ $mov->reference_id ?? '-' }}</small></td>
                                <td><small>{{ $mov->notes }}</small></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">Tidak ada aktivitas pergerakan stok ditemukan pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
