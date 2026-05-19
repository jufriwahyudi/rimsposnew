@extends('layouts.main.main')

@section('title', 'Laporan Stok')

@section('content')
    <div class="container-fluid">

        {{-- FILTER --}}
        <form method="GET" class="mb-3">
            <div class="row g-2 align-items-center">
                <div class="col-auto">
                    <label class="fw-semibold">Tanggal</label>
                </div>
                <div class="col-auto">
                    <input type="date" name="tanggal" class="form-control" value="{{ $tanggal }}"
                        max="{{ date('Y-m-d') }}">
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary">
                        Tampilkan
                    </button>
                </div>
            </div>
        </form>

        {{-- TABLE --}}
        <div class="card rounded-4">
            <div class="card-body table-responsive">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">
                        Laporan Stok per {{ \Carbon\Carbon::parse($tanggal)->format('d M Y') }}
                    </h6>
                    <a href="{{ route('laporan.stok.excel', ['tanggal' => $tanggal]) }}" class="btn btn-success btn-sm">
                        Export Excel
                    </a>
                </div>

                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Produk / Varian</th>
                            <th class="text-center" width="10%">Warehouse</th>
                            <th class="text-center" width="10%">Store</th>
                            <th class="text-center" width="10%">Total</th>
                            <th class="text-center" width="12%">Harga<br>Modal</th>
                            <th class="text-center" width="12%">Harga<br>Jual</th>
                            <th class="text-center" width="12%">Nilai Persediaan</th>
                            <th class="text-center" width="12%">Nilai Jual</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalNilaiPersediaan = 0; @endphp
                        @forelse($products as $product)
                            @php $varianCount = $product->variants->count(); @endphp

                            {{-- HEADER PRODUK: hanya tampil jika varian > 1 --}}
                            @if ($varianCount > 1)
                                <tr class="table-secondary fw-bold">
                                    <td colspan="8">
                                        <i class="bi bi-box-seam me-2"></i>{{ $product->nama_produk }}
                                    </td>
                                </tr>
                            @endif

                            {{-- VARIANT --}}
                            @foreach ($product->variants as $variant)
                                @php
                                    $stokWarehouse = $variant->stock_warehouse ?? 0;
                                    $stokStore = $variant->stock_store ?? 0;
                                    $totalStok = $stokWarehouse + $stokStore;
                                    $modal = $variant->modalPerTanggal($tanggal);
                                    $nilaiPersediaan = $modal * $totalStok;
                                    $totalNilaiPersediaan += $nilaiPersediaan;
                                    $label = $variant->variant_label ?: 'Tidak ada varian';
                                @endphp
                                <tr>
                                    <td @if ($varianCount > 1) style="padding-left: 32px" @endif>
                                        @if ($varianCount === 1)
                                            {{-- Produk + varian digabung dalam 1 baris --}}
                                            <div class="fw-semibold">
                                                <i
                                                    class="bi bi-box-seam me-1 text-secondary"></i>{{ $product->nama_produk }}
                                                @if ($label !== 'Tidak ada varian' && $label !== 'Default')
                                                    <span class="text-muted fw-normal"> &mdash; {{ $label }}</span>
                                                @endif
                                            </div>
                                            <small class="text-muted">{{ $variant->sku }}</small>
                                        @else
                                            <div class="fw-semibold">{{ $label }}</div>
                                            <small class="text-muted">{{ $variant->sku }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $stokWarehouse }}</td>
                                    <td class="text-center">{{ $stokStore }}</td>
                                    <td class="text-center fw-bold">{{ $totalStok }}</td>
                                    <td class="text-end">{{ number_format($modal, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($variant->harga_jual, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold">{{ number_format($nilaiPersediaan, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold">
                                        {{ number_format($variant->harga_jual * $totalStok, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach

                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    Tidak ada data
                                </td>
                            </tr>
                        @endforelse
                        @if ($products->isNotEmpty())
                            <tr class="table-secondary fw-bold">
                                <td colspan="5" class="text-end">
                                    Total Nilai Persediaan
                                </td>
                                <td class="text-end">
                                    {{ number_format($totalNilaiPersediaan, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
