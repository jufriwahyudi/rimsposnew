@extends('layouts.main.main')
@section('title', 'Detail Stock Batch')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex justify-content-between align-items-center">
            <h4 class="page-title">Detail Stock Batch #{{ $batch->id }}</h4>
            <a href="{{ route('stock-batches.index') }}" class="btn btn-secondary btn-sm"><i class="mdi mdi-arrow-left"></i> Kembali</a>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="header-title mb-3">Informasi Batch</h5>
                <table class="table table-sm table-borderless">
                    <tr>
                        <th width="40%">Produk</th>
                        <td>{{ $batch->variant?->product?->nama_produk ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Variant (SKU)</th>
                        <td>{{ $batch->variant?->variant_name ?? '-' }} ({{ $batch->variant?->sku }})</td>
                    </tr>
                    <tr>
                        <th>Tanggal Masuk</th>
                        <td>{{ $batch->tanggal_masuk?->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <th>Sumber</th>
                        <td><span class="badge bg-secondary">{{ strtoupper($batch->sumber) }}</span></td>
                    </tr>
                    <tr>
                        <th>Posisi</th>
                        <td>
                            @if($batch->posisi == 'warehouse')
                                <span class="badge bg-info">Gudang Utama</span>
                            @else
                                <span class="badge bg-primary">Toko</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Qty Awal</th>
                        <td>{{ number_format($batch->qty_awal) }}</td>
                    </tr>
                    <tr>
                        <th>Qty Sisa</th>
                        <td>{{ number_format($batch->qty_sisa) }}</td>
                    </tr>
                    <tr>
                        <th>Harga Modal (Rp)</th>
                        <td class="fw-bold fs-4 text-primary">{{ number_format($batch->harga_beli, 0, ',', '.') }}</td>
                    </tr>
                </table>

                <hr>
                
                <form action="{{ route('stock-batches.update-harga', $batch->id) }}" method="POST" id="form-update-harga">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label text-danger">Ubah Harga Modal</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" step="0.01" min="0" class="form-control" name="harga_beli" value="{{ floatval($batch->harga_beli) }}" required>
                        </div>
                        <small class="text-muted mt-1 d-block">
                            <i class="mdi mdi-information-outline"></i> Mengubah harga ini akan otomatis memperbarui nilai HPP pada <b>{{ $saleItemBatches->total() }}</b> item transaksi penjualan yang terkait di toko Anda.
                        </small>
                    </div>
                    <button type="button" class="btn btn-danger w-100" onclick="confirmUpdate()">
                        <i class="mdi mdi-content-save"></i> Update Harga & Cascade
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h5 class="header-title mb-3">Histori Penjualan Terkait Batch Ini</h5>
                <p class="text-muted font-13">Daftar item penjualan yang memotong stok dari batch ini. Nilai HPP di sini akan otomatis berubah jika Anda mengupdate Harga Modal di form sebelah kiri.</p>
                
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-centered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Waktu Transaksi</th>
                                <th>No Invoice</th>
                                <th>Qty Terjual</th>
                                <th>Cost Price (Rp)</th>
                                <th>Sell Price (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($saleItemBatches as $sib)
                            <tr>
                                <td>{{ $sib->saleItem?->sale?->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td>
                                    @if($sib->saleItem?->sale)
                                        <a href="{{ route('sales.show', $sib->saleItem->sale->id) }}" target="_blank">
                                            {{ $sib->saleItem->sale->invoice_number }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ number_format($sib->qty) }}</td>
                                <td class="text-end">{{ number_format($sib->cost_price, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($sib->sell_price, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">Belum ada transaksi penjualan yang menggunakan stok dari batch ini di toko Anda.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    {{ $saleItemBatches->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmUpdate() {
    Swal.fire({
        title: 'Anda yakin?',
        html: "Anda akan mengubah harga modal batch ini.<br>Nilai HPP (Cost Price) pada <b>{{ $saleItemBatches->total() }}</b> item transaksi yang terkait di toko Anda akan ikut berubah!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Update & Cascade!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('form-update-harga').submit();
        }
    })
}
</script>
@endpush
