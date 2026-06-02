@extends('layouts.main.main')

@section('title', 'Laporan Konsolidasi')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3 text-indigo fw-bold">Superadmin</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i> Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Laporan Konsolidasi</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    {{-- Tabs Navigation --}}
    <ul class="nav nav-tabs nav-primary mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active fw-bold" data-bs-toggle="tab" href="#laba-rugi-tab" role="tab" aria-selected="true">
                <div class="d-flex align-items-center">
                    <div class="tab-icon"><i class="bx bx-wallet fs-5 me-1"></i></div>
                    <div class="tab-title">Laba Rugi Gabungan</div>
                </div>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link fw-bold" data-bs-toggle="tab" href="#stok-kritis-tab" role="tab" aria-selected="false">
                <div class="d-flex align-items-center">
                    <div class="tab-icon"><i class="bx bx-error fs-5 me-1"></i></div>
                    <div class="tab-title">Stok Kritis Global</div>
                </div>
            </a>
        </li>
    </ul>

    {{-- Tabs Content --}}
    <div class="tab-content">
        {{-- Tab 1: Laba Rugi Gabungan --}}
        <div class="tab-pane fade show active" id="laba-rugi-tab" role="tabpanel">
            <div class="card rounded-4 border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-3 text-indigo"><i class="bx bx-filter-alt me-1"></i> Filter Laba Rugi</h5>
                    <form id="formFilterLabaRugi" class="row g-3">
                        @csrf
                        {{-- Store list filter --}}
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Pilih Toko (Multi-select)</label>
                            <select name="store_ids[]" id="storeIdsFilter" class="form-select select2-input" multiple data-placeholder="Semua Toko">
                                @foreach ($stores as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- Date range start --}}
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Tanggal Mulai</label>
                            <input type="date" name="mulai" id="lrMulai" class="form-control" value="{{ now()->startOfMonth()->toDateString() }}">
                        </div>
                        {{-- Date range end --}}
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Tanggal Selesai</label>
                            <input type="date" name="akhir" id="lrAkhir" class="form-control" value="{{ now()->toDateString() }}">
                        </div>
                        {{-- Submit button --}}
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" id="btnFilterLabaRugi" class="btn btn-indigo fw-bold w-100 py-2">
                                <i class="bx bx-search me-1"></i> Tampilkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card rounded-4 border-0 shadow-sm">
                <div class="card-body p-0" id="containerLabaRugi">
                    <div class="text-center py-5 text-muted">
                        <i class="bx bx-loader-alt bx-spin fs-2 mb-2 d-block text-indigo"></i>
                        Memuat Laporan Laba Rugi...
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab 2: Stok Kritis Global --}}
        <div class="tab-pane fade" id="stok-kritis-tab" role="tabpanel">
            <div class="card rounded-4 border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-3 text-indigo"><i class="bx bx-filter-alt me-1"></i> Filter Stok Kritis</h5>
                    <form id="formFilterStokKritis" class="row g-3">
                        @csrf
                        {{-- Store filter --}}
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Pilih Toko</label>
                            <select name="store_id" id="skStoreId" class="form-select select2-input">
                                <option value="">Semua Toko</option>
                                @foreach ($stores as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- Threshold input --}}
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Ambang Batas Stok (<=)</label>
                            <input type="number" name="threshold" id="skThreshold" class="form-control" value="10" min="0">
                        </div>
                        {{-- Submit button --}}
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" id="btnFilterStokKritis" class="btn btn-indigo fw-bold w-100 py-2">
                                <i class="bx bx-search me-1"></i> Tampilkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card rounded-4 border-0 shadow-sm">
                <div class="card-body p-0" id="containerStokKritis">
                    <div class="text-center py-5 text-muted">
                        <i class="bx bx-loader-alt bx-spin fs-2 mb-2 d-block text-indigo"></i>
                        Memuat Laporan Stok Kritis...
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            // Initialize select2 if available
            if ($.fn.select2) {
                $('.select2-input').select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });
            }

            // Load default Laba Rugi
            loadLabaRugi();

            // Load default Stok Kritis on tab change or initial click
            $('a[href="#stok-kritis-tab"]').on('shown.bs.tab', function () {
                if ($('#containerStokKritis').children('.text-muted').length > 0) {
                    loadStokKritis();
                }
            });

            // Laba Rugi filter handler
            $('#btnFilterLabaRugi').on('click', function () {
                loadLabaRugi();
            });

            // Stok Kritis filter handler
            $('#btnFilterStokKritis').on('click', function () {
                loadStokKritis();
            });

            function loadLabaRugi() {
                var container = $('#containerLabaRugi');
                container.html(
                    '<div class="text-center py-5 text-muted"><i class="bx bx-loader-alt bx-spin fs-2 mb-2 d-block text-indigo"></i> Memuat Laporan Laba Rugi...</div>'
                );

                $.ajax({
                    url: "{{ route('superadmin.consolidated-reports.laba-rugi') }}",
                    type: "POST",
                    data: $('#formFilterLabaRugi').serialize(),
                    success: function (html) {
                        container.html(html);
                    },
                    error: function () {
                        container.html(
                            '<div class="text-center py-5 text-danger"><i class="bx bx-error fs-1 mb-2 d-block"></i> Gagal memuat Laporan Laba Rugi. Silakan coba kembali.</div>'
                        );
                    }
                });
            }

            function loadStokKritis() {
                var container = $('#containerStokKritis');
                container.html(
                    '<div class="text-center py-5 text-muted"><i class="bx bx-loader-alt bx-spin fs-2 mb-2 d-block text-indigo"></i> Memuat Laporan Stok Kritis...</div>'
                );

                $.ajax({
                    url: "{{ route('superadmin.consolidated-reports.stok-kritis') }}",
                    type: "POST",
                    data: $('#formFilterStokKritis').serialize(),
                    success: function (html) {
                        container.html(html);
                    },
                    error: function () {
                        container.html(
                            '<div class="text-center py-5 text-danger"><i class="bx bx-error fs-1 mb-2 d-block"></i> Gagal memuat Laporan Stok Kritis. Silakan coba kembali.</div>'
                        );
                    }
                });
            }
        });
    </script>
@endpush

@push('styles')
    <style>
        .text-indigo { color: #4f46e5 !important; }
        .btn-indigo {
            color: #fff;
            background-color: #4f46e5;
            border-color: #4f46e5;
        }
        .btn-indigo:hover {
            color: #fff;
            background-color: #4338ca;
            border-color: #4338ca;
        }
        .nav-tabs.nav-primary .nav-link.active {
            color: #4f46e5 !important;
            border-bottom: 3px solid #4f46e5 !important;
        }
        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
            background-color: #4f46e5 !important;
            color: #fff !important;
            border: 0 !important;
        }
        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove {
            color: #fff !important;
        }
    </style>
@endpush
