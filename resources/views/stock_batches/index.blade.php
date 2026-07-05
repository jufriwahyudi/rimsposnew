@extends('layouts.main.main')
@section('title', 'Monitor Harga Modal Batch')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="page-title">Monitor Harga Modal Batch</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('stock-batches.index') }}" class="mb-4">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <label>Cari Produk</label>
                            <input type="text" name="product_name" class="form-control" value="{{ request('product_name') }}" placeholder="Nama / Variant Produk">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>Posisi</label>
                            <select name="posisi" class="form-select">
                                <option value="">Semua Posisi</option>
                                <option value="warehouse" {{ request('posisi') == 'warehouse' ? 'selected' : '' }}>Gudang Utama (Warehouse)</option>
                                <option value="store" {{ request('posisi') == 'store' ? 'selected' : '' }}>Toko (Store)</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>Sumber</label>
                            <select name="sumber" class="form-select">
                                <option value="">Semua Sumber</option>
                                <option value="purchase" {{ request('sumber') == 'purchase' ? 'selected' : '' }}>Pembelian</option>
                                <option value="transfer" {{ request('sumber') == 'transfer' ? 'selected' : '' }}>Transfer</option>
                                <option value="opname" {{ request('sumber') == 'opname' ? 'selected' : '' }}>Opname</option>
                                <option value="adjust" {{ request('sumber') == 'adjust' ? 'selected' : '' }}>Adjustment</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100"><i class="mdi mdi-filter"></i> Filter</button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-centered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal Masuk</th>
                                <th>Produk / Variant</th>
                                <th>Posisi</th>
                                <th>Sumber</th>
                                <th>Qty Awal</th>
                                <th>Qty Sisa</th>
                                <th>Harga Modal (Rp)</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($batches as $batch)
                            <tr>
                                <td>{{ $batch->tanggal_masuk?->format('d/m/Y') }}</td>
                                <td>
                                    <strong>{{ $batch->variant?->product?->nama_produk ?? '-' }}</strong><br>
                                    <small class="text-muted">{{ $batch->variant?->variant_name ?? '-' }} ({{ $batch->variant?->sku }})</small>
                                </td>
                                <td>
                                    @if($batch->posisi == 'warehouse')
                                        <span class="badge bg-info">Gudang Utama</span>
                                    @else
                                        <span class="badge bg-primary">Toko</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-secondary">{{ strtoupper($batch->sumber) }}</span></td>
                                <td>{{ number_format($batch->qty_awal) }}</td>
                                <td>{{ number_format($batch->qty_sisa) }}</td>
                                <td class="text-end fw-bold">{{ number_format($batch->harga_beli, 0, ',', '.') }}</td>
                                <td>
                                    <a href="{{ route('stock-batches.show', $batch->id) }}" class="btn btn-sm btn-info">
                                        <i class="mdi mdi-eye"></i> Detail & Edit
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-3">Tidak ada data batch ditemukan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    {{ $batches->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
