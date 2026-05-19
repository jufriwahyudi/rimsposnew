@extends('layouts.main.main')
@section('title', 'Detail Produk')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Pengaturan Produk</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Pengaturan</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('produk.index') }}">Produk</a></li>
                    <li class="breadcrumb-item active">Detail</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .stat-card {
            border: none;
            border-radius: 16px;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-card .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-card.warehouse {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stat-card.store {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .stat-card.variant-count {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stat-card .stat-icon {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        .stat-card .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #fff;
        }

        .stat-card .stat-label {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.85);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .product-header-card {
            border: none;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
        }

        .product-title {
            color: #7c3aed;
            font-weight: 700;
            font-size: 1.25rem;
        }

        .product-code {
            display: inline-block;
            background: #f3f0ff;
            color: #7c3aed;
            padding: 2px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .filter-card {
            border: none;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .filter-card .form-control,
        .filter-card .form-select {
            border-radius: 10px;
            border: 1.5px solid #e5e7eb;
            font-size: 0.875rem;
        }

        .filter-card .form-control:focus,
        .filter-card .form-select:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }

        .variant-table-card {
            border: none;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
        }

        .variant-table thead th {
            background: #f8f7ff;
            color: #4a4a6a;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 2px solid #ede9fe;
            padding: 12px 16px;
        }

        .variant-table tbody td {
            vertical-align: middle;
            padding: 12px 16px;
            font-size: 0.875rem;
            border-bottom: 1px solid #f3f4f6;
        }

        .variant-table tbody tr:hover {
            background-color: #faf8ff;
        }

        .variant-table .group-header td {
            background: linear-gradient(90deg, #f3f0ff, #fff);
            font-weight: 700;
            color: #7c3aed;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border-left: 4px solid #7c3aed;
            padding: 10px 16px;
        }

        .badge-attr {
            display: inline-block;
            background: #f0f0ff;
            color: #5b21b6;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            margin: 2px 2px;
            font-weight: 500;
        }

        .badge-sku {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #374151;
        }

        .badge-barcode {
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
            color: #9ca3af;
        }

        .badge-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-status.active {
            background: #ecfdf5;
            color: #059669;
        }

        .badge-status.inactive {
            background: #fef2f2;
            color: #dc2626;
        }

        .btn-action {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 0.85rem;
            border: none;
            transition: all 0.2s;
        }

        .btn-action:hover {
            transform: scale(1.1);
        }

        .btn-action.view {
            background: #fef3c7;
            color: #d97706;
        }

        .btn-action.view:hover {
            background: #fde68a;
            color: #b45309;
        }

        .btn-action.edit {
            background: #d1fae5;
            color: #059669;
        }

        .btn-action.edit:hover {
            background: #a7f3d0;
            color: #047857;
        }

        .btn-action.barcode {
            background: #dbeafe;
            color: #2563eb;
        }

        .btn-action.barcode:hover {
            background: #bfdbfe;
            color: #1d4ed8;
        }

        .filter-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #ede9fe;
            color: #7c3aed;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-badge:hover {
            background: #ddd6fe;
        }

        .filter-badge.active {
            background: #7c3aed;
            color: #fff;
        }

        .variant-count-badge {
            background: #ede9fe;
            color: #7c3aed;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .no-results-row {
            text-align: center;
            padding: 40px !important;
            color: #9ca3af;
        }

        .no-results-row i {
            font-size: 2rem;
            display: block;
            margin-bottom: 8px;
        }
    </style>
@endpush

@section('content')
    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Product Header --}}
    <div class="card product-header-card mb-4">
        <div class="card-body py-3 px-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div
                        style="width:48px; height:48px; background: linear-gradient(135deg, #7c3aed, #a78bfa); border-radius:14px; display:flex; align-items:center; justify-content:center;">
                        <img src="{{ asset('assets/images/alazca_logo.png') }}" alt="Logo"
                            style="width:30px; height:30px; filter:brightness(0) invert(1);">
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h5 class="product-title mb-0">{{ $product->nama_produk }}</h5>
                            <span class="product-code">{{ $product->kode_produk }}</span>
                        </div>
                        <small class="text-muted"><i class="bi bi-building me-1"></i>{{ session('store_name') }}</small>
                    </div>
                </div>
                <a href="{{ route('produk.index') }}" class="btn btn-outline-secondary btn-sm rounded-3 px-3">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card warehouse h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
                    <div>
                        <div class="stat-value">{{ number_format($product->stock_warehouse ?? 0) }}</div>
                        <div class="stat-label">Stok Gudang</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card store h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="stat-icon"><i class="bi bi-shop"></i></div>
                    <div>
                        <div class="stat-value">{{ number_format($product->stock_store ?? 0) }}</div>
                        <div class="stat-label">Stok Toko</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card variant-count h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="stat-icon"><i class="bi bi-layers"></i></div>
                    <div>
                        <div class="stat-value">{{ $product->variants->count() }}</div>
                        <div class="stat-label">Total Varian</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="card filter-card mb-4">
        <div class="card-body py-3 px-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-funnel-fill text-primary"></i>
                <span class="fw-semibold" style="font-size:0.9rem;">Filter Varian</span>
                <button class="btn btn-link btn-sm text-decoration-none p-0 ms-auto" id="btnResetFilter"
                    style="font-size:0.8rem;">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                </button>
            </div>
            <div class="row g-2">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"
                            style="border-radius:10px 0 0 10px; border:1.5px solid #e5e7eb; border-right:none;">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0" id="filterSearch"
                            placeholder="Cari SKU, barcode, atau atribut..."
                            style="border-radius:0 10px 10px 0; border-left:none;">
                    </div>
                </div>
                @php
                    $allAttributes = collect();
                    foreach ($variantsByGroup as $variants) {
                        foreach ($variants as $v) {
                            foreach ($v->variantAttributes as $va) {
                                $attrName = $va->attribute->nama;
                                $valName = $va->value->nama;
                                $valKode = $va->value->kode;
                                if (!$allAttributes->has($attrName)) {
                                    $allAttributes[$attrName] = collect();
                                }
                                $allAttributes[$attrName][$valKode] = $valName;
                            }
                        }
                    }
                @endphp
                @foreach ($allAttributes as $attrName => $values)
                    <div class="col-md">
                        <select class="form-select filter-attribute" data-attribute="{{ $attrName }}">
                            <option value="">{{ $attrName }}</option>
                            @foreach ($values as $kode => $nama)
                                <option value="{{ $kode }}">{{ $nama }} ({{ $kode }})</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
                <div class="col-md-auto">
                    <select class="form-select" id="filterStatus">
                        <option value="">Status</option>
                        <option value="Y">Aktif</option>
                        <option value="N">Nonaktif</option>
                    </select>
                </div>
            </div>
            <div class="mt-2" id="activeFilters"></div>
        </div>
    </div>

    {{-- Variants Table --}}
    <div class="card variant-table-card">
        <div class="card-body p-0">
            <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom"
                style="border-color:#f3f4f6 !important;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-grid-3x3-gap text-primary"></i>
                    <span class="fw-semibold">Daftar Varian</span>
                    <span class="variant-count-badge" id="filteredCount">{{ $product->variants->count() }} varian</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table variant-table mb-0" id="variantTable">
                    <thead>
                        <tr>
                            <th style="min-width:200px;">Varian</th>
                            <th>SKU / Barcode</th>
                            <th class="text-center" style="width:90px;">Gudang</th>
                            <th class="text-center" style="width:90px;">Toko</th>
                            <th class="text-end" style="width:110px;">Harga</th>
                            <th class="text-center" style="width:90px;">Status</th>
                            <th class="text-center" style="width:120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($variantsByGroup as $groupName => $variants)
                            @if ($hasDivisi)
                                <tr class="group-header" data-group="{{ strtolower($groupName) }}">
                                    <td colspan="7">
                                        <i class="bi bi-folder2-open me-1"></i> DIVISI {{ strtoupper($groupName) }}
                                        <span class="ms-2 variant-count-badge">{{ $variants->count() }}</span>
                                    </td>
                                </tr>
                            @endif
                            @foreach ($variants as $v)
                                @php
                                    $variantAttrData = [];
                                    foreach ($v->variantAttributes->sortBy(fn($va) => $va->attribute->urutan) as $va) {
                                        $variantAttrData[] = [
                                            'attr' => $va->attribute->nama,
                                            'value' => $va->value->nama,
                                            'kode' => $va->value->kode,
                                        ];
                                    }
                                @endphp
                                <tr class="variant-row" data-sku="{{ strtolower($v->sku) }}"
                                    data-barcode="{{ strtolower(optional($v->barcodeActive)->barcode) }}"
                                    data-status="{{ $v->is_active }}" data-attrs='@json($variantAttrData)'
                                    data-search="{{ strtolower($v->sku . ' ' . optional($v->barcodeActive)->barcode . ' ' . $v->variant_name . ' ' . collect($variantAttrData)->pluck('value')->join(' ') . ' ' . collect($variantAttrData)->pluck('kode')->join(' ')) }}"
                                    @if ($hasDivisi) data-group="{{ strtolower(collect($variantAttrData)->firstWhere('attr', 'Divisi')['value'] ?? 'lainnya') }}" @endif>
                                    <td>
                                        @if ($v->variant_label)
                                            <span class="badge-attr">{{ $v->variant_label }}</span>
                                        @else
                                            <span class="text-muted fst-italic" style="font-size:0.8rem;">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="badge-sku text-truncate">{{ $v->sku }}</div>
                                        <div class="badge-barcode mt-1"><i
                                                class="bi bi-upc me-1"></i>{{ optional($v->barcodeActive)->barcode ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="text-center fw-semibold">{{ number_format($v->stok_warehouse ?? 0) }}</td>
                                    <td class="text-center fw-semibold">{{ number_format($v->stok_store ?? 0) }}</td>
                                    <td class="text-end fw-semibold text-truncate">Rp
                                        {{ number_format($v->harga_jual, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center text-truncate">
                                        <span class="badge-status {{ $v->is_active == 'Y' ? 'active' : 'inactive' }}">
                                            <i
                                                class="bi {{ $v->is_active == 'Y' ? 'bi-check-circle' : 'bi-x-circle' }} me-1"></i>{{ $v->is_active == 'Y' ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            <a href="{{ route('produk.variants.detail', [$product, $v]) }}"
                                                class="btn-action view" title="Lihat Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <button type="button" class="btn-action edit btn-edit-harga"
                                                data-bs-toggle="modal" data-bs-target="#modalEditHarga"
                                                data-id="{{ $v->id }}" data-sku="{{ $v->sku }}"
                                                data-barcode="{{ optional($v->barcodeActive)->barcode }}"
                                                data-harga="{{ $v->harga_jual }}" title="Edit Harga">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button class="btn-action barcode btn-barcode" data-bs-toggle="modal"
                                                data-bs-target="#barcodeModal" data-sku="{{ $v->sku }}"
                                                data-id="{{ $v->id }}"
                                                data-barcode="{{ optional($v->barcodeActive)->barcode }}"
                                                data-variant="{{ $v->variant_label }}" title="Barcode">
                                                <i class="bi bi-upc-scan"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal Barcode --}}
    <div class="modal fade" id="barcodeModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:16px; border:none;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-upc-scan me-2 text-primary"></i>Barcode Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="p-3 bg-light rounded-3 d-inline-block mb-3">
                        <img id="barcodeImage" src="" class="img-fluid" style="max-height:120px;">
                    </div>
                    <p class="fw-bold mb-1" id="barcodeSku" style="font-family:'Courier New',monospace;"></p>
                    <small class="text-muted" id="barcodeVariant"></small>
                </div>
                <div class="modal-footer border-0 flex-column gap-2 pt-0">
                    <input type="hidden" id="barcodeVariantId">
                    <div class="d-flex gap-2 w-100">
                        <a id="downloadBarcode" href="#" class="btn btn-success flex-fill rounded-3">
                            <i class="bi bi-download me-1"></i> Download
                        </a>
                        <a id="downloadBarcodeNoPrice" href="#"
                            class="btn btn-outline-success flex-fill rounded-3">
                            <i class="bi bi-download me-1"></i> Tanpa Harga
                        </a>
                    </div>
                    <button class="btn btn-outline-primary w-100 rounded-3" id="generateNewBarcode">
                        <i class="bi bi-arrow-repeat me-1"></i> Barcode Tidak Terbaca? Generate Baru
                    </button>
                    <button class="btn btn-light w-100 rounded-3" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Edit Harga --}}
    <div class="modal fade" id="modalEditHarga" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('produk.variants.updateHarga') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="variant_id" id="variant_id">
                <div class="modal-content" style="border-radius:16px; border:none;">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold"><i class="bi bi-tag-fill me-2 text-success"></i>Edit Harga Varian
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;">SKU</label>
                            <input type="text" class="form-control rounded-3 bg-light" id="variant_sku" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;">Barcode</label>
                            <input type="text" class="form-control rounded-3" id="variant_barcode" name="barcode">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;">Harga Jual</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent rounded-start-3">Rp</span>
                                <input type="number" class="form-control rounded-end-3" name="harga_jual"
                                    id="harga_jual" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-3 px-4"
                            data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-3 px-4">
                            <i class="bi bi-check-lg me-1"></i>Simpan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // === Filter Logic ===
            const searchInput = document.getElementById('filterSearch');
            const statusSelect = document.getElementById('filterStatus');
            const attrSelects = document.querySelectorAll('.filter-attribute');
            const btnReset = document.getElementById('btnResetFilter');
            const filteredCountEl = document.getElementById('filteredCount');
            const rows = document.querySelectorAll('.variant-row');
            const groupHeaders = document.querySelectorAll('.group-header');

            function applyFilters() {
                const searchVal = searchInput.value.toLowerCase().trim();
                const statusVal = statusSelect.value;

                const attrFilters = {};
                attrSelects.forEach(sel => {
                    if (sel.value) {
                        attrFilters[sel.dataset.attribute] = sel.value;
                    }
                });

                let visibleCount = 0;
                const visibleGroups = new Set();

                rows.forEach(row => {
                    const matchesSearch = !searchVal || row.dataset.search.includes(searchVal);
                    const matchesStatus = !statusVal || row.dataset.status === statusVal;

                    let matchesAttrs = true;
                    if (Object.keys(attrFilters).length > 0) {
                        const attrs = JSON.parse(row.dataset.attrs);
                        for (const [attrName, attrKode] of Object.entries(attrFilters)) {
                            const found = attrs.find(a => a.attr === attrName && a.kode === attrKode);
                            if (!found) {
                                matchesAttrs = false;
                                break;
                            }
                        }
                    }

                    const visible = matchesSearch && matchesStatus && matchesAttrs;
                    row.style.display = visible ? '' : 'none';
                    if (visible) {
                        visibleCount++;
                        if (row.dataset.group) visibleGroups.add(row.dataset.group);
                    }
                });

                // Show/hide group headers
                groupHeaders.forEach(gh => {
                    gh.style.display = visibleGroups.has(gh.dataset.group) ? '' : 'none';
                });

                filteredCountEl.textContent = visibleCount + ' varian';

                // Show "no results" message
                let noResultsRow = document.getElementById('noResultsRow');
                if (visibleCount === 0) {
                    if (!noResultsRow) {
                        const tbody = document.querySelector('#variantTable tbody');
                        const tr = document.createElement('tr');
                        tr.id = 'noResultsRow';
                        tr.innerHTML =
                            '<td colspan="7" class="no-results-row"><i class="bi bi-search"></i>Tidak ada varian yang cocok dengan filter</td>';
                        tbody.appendChild(tr);
                    }
                    noResultsRow = document.getElementById('noResultsRow');
                    noResultsRow.style.display = '';
                } else if (noResultsRow) {
                    noResultsRow.style.display = 'none';
                }

                updateActiveFilters();
            }

            function updateActiveFilters() {
                const container = document.getElementById('activeFilters');
                let html = '';
                if (searchInput.value) {
                    html +=
                        `<span class="filter-badge active"><i class="bi bi-search"></i>"${searchInput.value}"<i class="bi bi-x-lg ms-1" data-clear="search"></i></span> `;
                }
                if (statusSelect.value) {
                    html +=
                        `<span class="filter-badge active"><i class="bi bi-circle-fill" style="font-size:6px;"></i>${statusSelect.value === 'Y' ? 'Aktif' : 'Nonaktif'}<i class="bi bi-x-lg ms-1" data-clear="status"></i></span> `;
                }
                attrSelects.forEach(sel => {
                    if (sel.value) {
                        html +=
                            `<span class="filter-badge active"><i class="bi bi-tag"></i>${sel.dataset.attribute}: ${sel.options[sel.selectedIndex].text}<i class="bi bi-x-lg ms-1" data-clear="attr" data-attr="${sel.dataset.attribute}"></i></span> `;
                    }
                });
                container.innerHTML = html;

                container.querySelectorAll('[data-clear]').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const type = this.dataset.clear;
                        if (type === 'search') {
                            searchInput.value = '';
                        } else if (type === 'status') {
                            statusSelect.value = '';
                        } else if (type === 'attr') {
                            const attrName = this.dataset.attr;
                            attrSelects.forEach(s => {
                                if (s.dataset.attribute === attrName) s.value = '';
                            });
                        }
                        applyFilters();
                    });
                });
            }

            searchInput.addEventListener('input', applyFilters);
            statusSelect.addEventListener('change', applyFilters);
            attrSelects.forEach(sel => sel.addEventListener('change', applyFilters));

            btnReset.addEventListener('click', function() {
                searchInput.value = '';
                statusSelect.value = '';
                attrSelects.forEach(sel => sel.value = '');
                applyFilters();
            });

            // === Modal Edit Harga ===
            const modalEditHarga = document.getElementById('modalEditHarga');
            modalEditHarga.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                document.getElementById('variant_id').value = button.getAttribute('data-id');
                document.getElementById('harga_jual').value = parseInt(button.getAttribute('data-harga'));
                document.getElementById('variant_sku').value = button.getAttribute('data-sku');
                document.getElementById('variant_barcode').value = button.getAttribute('data-barcode');
            });

            // === Modal Barcode ===
            document.querySelectorAll('.btn-barcode').forEach(btn => {
                btn.addEventListener('click', function() {
                    const barcode = this.dataset.barcode;
                    const variant = this.dataset.variant;
                    const sku = this.dataset.sku;
                    const variantId = this.dataset.id;

                    document.getElementById('barcodeImage').src =
                        `/barcode/image/${encodeURIComponent(barcode)}`;
                    document.getElementById('downloadBarcode').href =
                        `/barcode/label/40x30/${encodeURIComponent(variantId)}`;
                    document.getElementById('downloadBarcodeNoPrice').href =
                        `/barcode/label/40x30/${encodeURIComponent(variantId)}/0`;
                    document.getElementById('barcodeSku').innerText = sku;
                    document.getElementById('barcodeVariant').innerText = variant;
                    document.getElementById('barcodeVariantId').value = variantId;
                });
            });

            // === Generate New Barcode ===
            document.getElementById('generateNewBarcode').addEventListener('click', function() {
                const variantId = document.querySelector('#barcodeVariantId').value;

                fetch(`/produk/variants/${encodeURIComponent(variantId)}/generate-barcode`, {
                        method: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content'),
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Success', 'Barcode baru berhasil digenerate: ' + data.barcode,
                                'success');
                            document.getElementById('barcodeImage').src =
                                `/barcode/image/${encodeURIComponent(data.barcode)}`;
                            document.getElementById('barcodeSku').innerText = data.sku;
                            document.getElementById('downloadBarcode').href =
                                `/barcode/label/40x30/${encodeURIComponent(variantId)}`;
                            document.getElementById('downloadBarcodeNoPrice').href =
                                `/barcode/label/40x30/${encodeURIComponent(variantId)}/0`;
                        } else {
                            Swal.fire('Error', data.message || 'Gagal generate barcode baru', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'Terjadi kesalahan saat generate barcode baru', 'error');
                    });
            });
        });
    </script>
@endpush
