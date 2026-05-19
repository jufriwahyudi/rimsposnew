@extends('layouts.main.main')
@section('title', 'Laporan Laba / Rugi')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Laporan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active">Laba / Rugi</li>
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
                            <h5 class="fw-bold mb-0" style="color:#7c3aed">Laporan Laba / Rugi</h5>
                            <small class="text-muted">{{ session('store_name') }}</small>
                        </div>
                    </div>
                </div>

                {{-- Filter --}}
                <div class="card-body border-bottom pb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label small mb-1">Dari Tanggal</label>
                            <input type="date" id="mulai" class="form-control form-control-sm"
                                value="{{ date('Y-m-01') }}">
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label small mb-1">Sampai Tanggal</label>
                            <input type="date" id="akhir" class="form-control form-control-sm"
                                value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-auto d-flex gap-2 align-items-end">
                            <button class="btn btn-primary btn-sm" id="btnTampilkan">
                                <i class="material-icons-outlined" style="font-size:15px;vertical-align:middle">search</i>
                                Tampilkan
                            </button>
                            <button class="btn btn-success btn-sm" id="btnExport">
                                <i class="material-icons-outlined" style="font-size:15px;vertical-align:middle">download</i>
                                Export Excel
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Result --}}
                <div id="dataContainer" class="p-3">
                    <div class="text-center text-muted py-5">
                        <i class="material-icons-outlined" style="font-size:48px;opacity:.3">trending_up</i>
                        <p class="mt-2">Pilih rentang tanggal dan klik Tampilkan</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const urlData = "{{ route('laporan.laba_rugi.data') }}";
        const urlExport = "{{ route('laporan.laba_rugi.export') }}";

        $('#btnTampilkan').on('click', function() {
            const mulai = $('#mulai').val();
            const akhir = $('#akhir').val();

            if (!mulai || !akhir) {
                Swal.fire('Perhatian', 'Pilih rentang tanggal terlebih dahulu.', 'warning');
                return;
            }

            $.ajax({
                url: urlData,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    mulai,
                    akhir
                },
                beforeSend: function() {
                    $('#dataContainer').html(
                        '<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Memuat data...</p></div>'
                    );
                },
                success: function(html) {
                    $('#dataContainer').html(html);
                },
                error: function() {
                    $('#dataContainer').html(
                        '<div class="alert alert-danger m-3">Terjadi kesalahan saat memuat data.</div>'
                        );
                },
            });
        });

        $('#btnExport').on('click', function() {
            const mulai = $('#mulai').val();
            const akhir = $('#akhir').val();
            if (!mulai || !akhir) {
                Swal.fire('Perhatian', 'Pilih rentang tanggal terlebih dahulu.', 'warning');
                return;
            }
            window.location = urlExport + '?mulai=' + mulai + '&akhir=' + akhir;
        });
    </script>
@endpush
