@extends('layouts.main.main')

@section('title', 'Laporan Stok & Alert')

@section('content')
    <div class="container-fluid">

        {{-- HEADER / BREADCRUMB --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1 fw-bold">Laporan Stok</h4>
                <p class="text-muted mb-0 small">Pantau posisi persediaan barang serta peringatan stok menipis dan habis.</p>
            </div>
        </div>

        {{-- NAVIGATION TABS --}}
        <ul class="nav nav-pills mb-3 gap-2" id="stokReportTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link px-3 py-2 fw-semibold d-flex align-items-center gap-2 {{ $activeTab !== 'alert' ? 'active' : '' }}" 
                    id="posisi-tab" 
                    data-bs-toggle="pill" 
                    data-bs-target="#tab-posisi" 
                    type="button" 
                    role="tab" 
                    aria-controls="tab-posisi" 
                    aria-selected="{{ $activeTab !== 'alert' ? 'true' : 'false' }}">
                    <i class="bx bx-layer"></i>
                    <span>Posisi Stok</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link px-3 py-2 fw-semibold d-flex align-items-center gap-2 {{ $activeTab === 'alert' ? 'active' : '' }}" 
                    id="alert-tab" 
                    data-bs-toggle="pill" 
                    data-bs-target="#tab-alert" 
                    type="button" 
                    role="tab" 
                    aria-controls="tab-alert" 
                    aria-selected="{{ $activeTab === 'alert' ? 'true' : 'false' }}">
                    <i class="bx bx-bell"></i>
                    <span>Stok Alert</span>
                    @php $totalAlert = $totalHabis + $totalMenipis; @endphp
                    @if ($totalAlert > 0)
                        <span class="badge rounded-pill bg-danger ms-1">
                            {{ $totalAlert }}
                        </span>
                    @endif
                </button>
            </li>
        </ul>

        <div class="tab-content" id="stokReportTabsContent">

            {{-- ==================== TAB 1: POSISI STOK ==================== --}}
            <div class="tab-pane fade {{ $activeTab !== 'alert' ? 'show active' : '' }}" id="tab-posisi" role="tabpanel" aria-labelledby="posisi-tab">
                {{-- FILTER POSISI --}}
                <div class="card rounded-4 border-0 shadow-sm mb-3">
                    <div class="card-body p-3">
                        <form method="GET" action="{{ route('laporan.stok') }}" class="row g-2 align-items-end">
                            <input type="hidden" name="tab" value="posisi">
                            <div class="col-auto">
                                <label class="form-label small fw-semibold text-muted mb-1">Tanggal</label>
                                <input type="date" name="tanggal" class="form-control form-control-sm" value="{{ $tanggal }}" max="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary btn-sm px-3">
                                    <i class="bx bx-search-alt me-1"></i> Tampilkan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- TABLE POSISI STOK --}}
                <div class="card rounded-4 border-0 shadow-sm">
                    <div class="card-body table-responsive p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <div>
                                <h6 class="mb-0 fw-bold">
                                    Posisi Stok per {{ \Carbon\Carbon::parse($tanggal)->format('d M Y') }}
                                </h6>
                                <small class="text-muted">Rincian stok fisik di Gudang, Toko, dan Nilai Persediaan.</small>
                            </div>
                            <a href="{{ route('laporan.stok.excel', ['tanggal' => $tanggal]) }}" class="btn btn-success btn-sm d-flex align-items-center gap-1">
                                <i class="bx bx-download"></i> Export Excel
                            </a>
                        </div>

                        <table class="table table-bordered table-hover align-middle mb-0">
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

                                    {{-- HEADER PRODUK: tampil jika varian > 1 --}}
                                    @if ($varianCount > 1)
                                        <tr class="table-secondary fw-bold">
                                            <td colspan="8">
                                                <i class="bi bi-box-seam me-2"></i>{{ $product->nama_produk }}
                                            </td>
                                        </tr>
                                    @endif

                                    {{-- VARIANT ROWS --}}
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
                                                    <div class="fw-semibold">
                                                        <i class="bi bi-box-seam me-1 text-secondary"></i>{{ $product->nama_produk }}
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
                                                {{ number_format($variant->harga_jual * $totalStok, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach

                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            Tidak ada data produk
                                        </td>
                                    </tr>
                                @endforelse
                                @if ($products->isNotEmpty())
                                    <tr class="table-secondary fw-bold">
                                        <td colspan="6" class="text-end">
                                            Total Nilai Persediaan
                                        </td>
                                        <td class="text-end">
                                            {{ number_format($totalNilaiPersediaan, 0, ',', '.') }}
                                        </td>
                                        <td></td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ==================== TAB 2: STOK ALERT ==================== --}}
            <div class="tab-pane fade {{ $activeTab === 'alert' ? 'show active' : '' }}" id="tab-alert" role="tabpanel" aria-labelledby="alert-tab">
                
                {{-- KPI SUMMARY CARDS --}}
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-4">
                        <div class="card rounded-4 border-0 shadow-sm bg-danger text-white">
                            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-white-50 text-uppercase fw-semibold" style="font-size: 11px;">Stok Habis (≤ 0)</span>
                                    <h3 class="mb-0 fw-bold mt-1">{{ number_format($totalHabis) }}</h3>
                                    <small class="text-white-50">Perlu reorder mendesak</small>
                                </div>
                                <div class="bg-white bg-opacity-25 rounded-circle p-3 text-white">
                                    <i class="bx bx-error-circle fs-3"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card rounded-4 border-0 shadow-sm bg-warning text-dark">
                            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-black-50 text-uppercase fw-semibold" style="font-size: 11px;">Stok Menipis (≤ {{ $threshold }})</span>
                                    <h3 class="mb-0 fw-bold mt-1">{{ number_format($totalMenipis) }}</h3>
                                    <small class="text-black-50">Mendekati ambang batas minimum</small>
                                </div>
                                <div class="bg-dark bg-opacity-10 rounded-circle p-3 text-dark">
                                    <i class="bx bx-time-five fs-3"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card rounded-4 border-0 shadow-sm bg-primary text-white">
                            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-white-50 text-uppercase fw-semibold" style="font-size: 11px;">Total Terpantau</span>
                                    <h3 class="mb-0 fw-bold mt-1">{{ number_format($totalTracked) }}</h3>
                                    <small class="text-white-50">Varian aktif dengan lacak stok</small>
                                </div>
                                <div class="bg-white bg-opacity-25 rounded-circle p-3 text-white">
                                    <i class="bx bx-check-shield fs-3"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- FILTER STOK ALERT --}}
                <div class="card rounded-4 border-0 shadow-sm mb-3">
                    <div class="card-body p-3">
                        <form method="GET" action="{{ route('laporan.stok') }}" class="row g-2 align-items-end">
                            <input type="hidden" name="tab" value="alert">
                            
                            <div class="col-12 col-sm-6 col-md-3">
                                <label class="form-label small fw-semibold text-muted mb-1">Tanggal Data</label>
                                <input type="date" name="tanggal" class="form-control form-control-sm" value="{{ $tanggal }}" max="{{ date('Y-m-d') }}">
                            </div>

                            <div class="col-12 col-sm-6 col-md-2">
                                <label class="form-label small fw-semibold text-muted mb-1">Batas Minimum (≤)</label>
                                <input type="number" name="threshold" class="form-control form-control-sm" value="{{ $threshold }}" min="0" step="1">
                            </div>

                            <div class="col-12 col-sm-6 col-md-3">
                                <label class="form-label small fw-semibold text-muted mb-1">Status Alert</label>
                                <select name="alert_type" class="form-select form-select-sm">
                                    <option value="all" {{ $alertType === 'all' ? 'selected' : '' }}>Semua Alert (Habis & Menipis)</option>
                                    <option value="out_of_stock" {{ $alertType === 'out_of_stock' ? 'selected' : '' }}>Hanya Stok Habis (≤ 0)</option>
                                    <option value="low_stock" {{ $alertType === 'low_stock' ? 'selected' : '' }}>Hanya Stok Menipis (1 - {{ $threshold }})</option>
                                </select>
                            </div>

                            <div class="col-12 col-sm-6 col-md-2">
                                <label class="form-label small fw-semibold text-muted mb-1">Acuan Lokasi</label>
                                <select name="posisi_stok" class="form-select form-select-sm">
                                    <option value="total" {{ $posisiStok === 'total' ? 'selected' : '' }}>Total (Store + Gudang)</option>
                                    <option value="store" {{ $posisiStok === 'store' ? 'selected' : '' }}>Stok Toko (Store)</option>
                                    <option value="warehouse" {{ $posisiStok === 'warehouse' ? 'selected' : '' }}>Stok Gudang (Warehouse)</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-2 d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm flex-fill">
                                    <i class="bx bx-filter-alt me-1"></i> Filter
                                </button>
                                <a href="{{ route('laporan.stok', ['tab' => 'alert']) }}" class="btn btn-outline-secondary btn-sm" title="Reset Filter">
                                    <i class="bx bx-reset"></i>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- TABLE STOK ALERT --}}
                <div class="card rounded-4 border-0 shadow-sm">
                    <div class="card-body table-responsive p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <div>
                                <h6 class="mb-0 fw-bold">
                                    Daftar Stok Alert per {{ \Carbon\Carbon::parse($tanggal)->format('d M Y') }}
                                </h6>
                                <small class="text-muted">
                                    Menampilkan {{ $alertItems->count() }} varian yang memerlukan perhatian 
                                    (Acuan: <strong>{{ $posisiStok === 'store' ? 'Stok Toko' : ($posisiStok === 'warehouse' ? 'Stok Gudang' : 'Total Stok') }}</strong> ≤ {{ $threshold }}).
                                </small>
                            </div>
                            <a href="{{ route('laporan.stok.alert.excel', ['tanggal' => $tanggal, 'threshold' => $threshold, 'alert_type' => $alertType, 'posisi_stok' => $posisiStok]) }}" class="btn btn-success btn-sm d-flex align-items-center gap-1">
                                <i class="bx bx-download"></i> Export Excel Alert
                            </a>
                        </div>

                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" width="5%">No</th>
                                    <th>Produk / Varian</th>
                                    <th>SKU / Barcode</th>
                                    <th class="text-center" width="10%">Gudang</th>
                                    <th class="text-center" width="10%">Toko</th>
                                    <th class="text-center" width="10%">Total</th>
                                    <th class="text-center" width="12%">Status Alert</th>
                                    <th class="text-end" width="11%">Harga Jual</th>
                                    <th class="text-center" width="12%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($alertItems as $index => $item)
                                    <tr class="{{ $item->status === 'out_of_stock' ? 'table-danger bg-opacity-25' : '' }}">
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="fw-semibold text-dark">
                                                {{ $item->display_name }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">{{ $item->sku }}</span>
                                            @if ($item->barcode && $item->barcode !== '-')
                                                <br><small class="text-muted"><i class="bx bx-barcode"></i> {{ $item->barcode }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center {{ $posisiStok === 'warehouse' ? 'fw-bold table-active' : '' }}">
                                            {{ $item->stok_warehouse }}
                                        </td>
                                        <td class="text-center {{ $posisiStok === 'store' ? 'fw-bold table-active' : '' }}">
                                            {{ $item->stok_store }}
                                        </td>
                                        <td class="text-center {{ $posisiStok === 'total' ? 'fw-bold table-active' : '' }}">
                                            {{ $item->stok_total }}
                                        </td>
                                        <td class="text-center">
                                            @if ($item->status === 'out_of_stock')
                                                <span class="badge bg-danger py-1 px-2 d-inline-flex align-items-center gap-1">
                                                    <i class="bx bx-x-circle"></i> HABIS ({{ $item->checked_stock }})
                                                </span>
                                            @else
                                                <span class="badge bg-warning text-dark py-1 px-2 d-inline-flex align-items-center gap-1">
                                                    <i class="bx bx-error"></i> MENIPIS ({{ $item->checked_stock }})
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            Rp {{ number_format($item->harga_jual, 0, ',', '.') }}
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                @if ($item->stok_warehouse > 0 && $item->stok_store <= 0)
                                                    <a href="{{ route('stock-transfers.create') }}" class="btn btn-outline-primary btn-sm" title="Transfer dari Gudang ke Toko">
                                                        <i class="bx bx-transfer-alt"></i> Transfer
                                                    </a>
                                                @else
                                                    <a href="{{ route('po.create') }}" class="btn btn-outline-success btn-sm" title="Buat Purchase Order Baru">
                                                        <i class="bx bx-cart-add"></i> Buat PO
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-5">
                                            <div class="text-success mb-2">
                                                <i class="bx bx-check-shield" style="font-size: 48px;"></i>
                                            </div>
                                            <h6 class="fw-bold mb-1">Semua Stok Terpantau Aman!</h6>
                                            <p class="text-muted small mb-0">
                                                Tidak ada varian barang yang stoknya di bawah atau sama dengan ambang batas (≤ {{ $threshold }}).
                                            </p>
                                        </td>
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Sync active tab in URL so page refresh stays on current tab
        const triggerTabList = document.querySelectorAll('#stokReportTabs button[data-bs-toggle="pill"]');
        triggerTabList.forEach(function (triggerEl) {
            triggerEl.addEventListener('shown.bs.tab', function (event) {
                const targetId = event.target.getAttribute('data-bs-target');
                const tabParam = targetId === '#tab-alert' ? 'alert' : 'posisi';
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabParam);
                window.history.replaceState({}, '', url.toString());
            });
        });
    });
</script>
@endpush
