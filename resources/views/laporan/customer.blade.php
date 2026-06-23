@extends('layouts.main.main')
@section('title', 'Laporan Customer')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Laporan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active">Laporan Customer</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card rounded-4 p-2">
                <div class="card-header d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-start">
                        <img src="{{ asset('assets/images/alazca_logo.png') }}" alt="Logo"
                            style="width:35px;height:35px;" class="me-2 mt-1">
                        <div>
                            <h5 class="fw-bold mb-0" style="color:#7c3aed">Laporan Customer / Mitra</h5>
                            <small class="text-muted">{{ session('store_name') }}</small>
                        </div>
                    </div>
                </div>

                {{-- Filter --}}
                <div class="card-body border-bottom pb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label small mb-1">Cari Pelanggan</label>
                            <input type="text" id="search" class="form-control form-control-sm"
                                placeholder="Nama, Telepon, atau Alamat...">
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <label class="form-label small mb-1">Dari Tanggal Registrasi</label>
                            <input type="date" id="mulai" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <label class="form-label small mb-1">Sampai Tanggal Registrasi</label>
                            <input type="date" id="akhir" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-5 d-flex gap-2">
                            <button class="btn btn-primary btn-sm px-3" id="btnTampilkan">
                                <i class="material-icons-outlined" style="font-size:15px;vertical-align:middle">search</i>
                                Tampilkan
                            </button>
                            <button class="btn btn-success btn-sm px-3" id="btnExport">
                                <i class="material-icons-outlined" style="font-size:15px;vertical-align:middle">download</i>
                                Export Excel
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Hasil --}}
                <div id="dataContainer" class="p-3">
                    <div class="text-center text-muted py-5">
                        <i class="material-icons-outlined" style="font-size:48px;opacity:.3">people</i>
                        <p class="mt-2">Klik Tampilkan untuk memuat data pelanggan</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function getParams() {
            return {
                mulai: $('#mulai').val(),
                akhir: $('#akhir').val(),
                search: $('#search').val(),
                _token: '{{ csrf_token() }}',
            };
        }

        $('#btnTampilkan').on('click', function() {
            $('#dataContainer').html(
                '<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Memuat data...</p></div>'
            );

            $.ajax({
                url: '{{ route('laporan.customer.data') }}',
                type: 'POST',
                data: getParams(),
                success: function(html) {
                    $('#dataContainer').html(html);
                },
                error: function(xhr) {
                    $('#dataContainer').html(
                        '<div class="alert alert-danger m-3">Gagal memuat data: ' + xhr.statusText +
                        '</div>'
                    );
                }
            });
        });

        $('#btnExport').on('click', function() {
            const p = getParams();
            const qs = new URLSearchParams({
                mulai: p.mulai,
                akhir: p.akhir,
                search: p.search,
            });
            window.location.href = '{{ route('laporan.customer.export') }}?' + qs.toString();
        });

        // Auto-load on page ready
        $(function() {
            $('#btnTampilkan').trigger('click');
        });
    </script>
@endpush
