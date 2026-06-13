@extends('layouts.main.main')
@section('title', 'Laporan Penukaran Poin')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Laporan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Penukaran Poin</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card rounded-4 p-2 shadow-sm border-0">
                <div class="card-header d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 bg-transparent border-0">
                    <div class="d-flex align-items-start">
                        <div class="avatar avatar-md bg-light-primary text-primary rounded-3 me-2 p-1">
                            <i class="material-icons-outlined" style="font-size:28px">card_giftcard</i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-primary">Laporan Penukaran Poin Member</h5>
                            <small class="text-muted">{{ session('store_name') }}</small>
                        </div>
                    </div>
                </div>

                {{-- Filter --}}
                <div class="card-body border-bottom pb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label small mb-1 fw-semibold">Dari Tanggal</label>
                            <input type="date" id="mulai" class="form-control form-control-sm rounded-pill px-3"
                                value="{{ date('Y-m-01') }}">
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label small mb-1 fw-semibold">Sampai Tanggal</label>
                            <input type="date" id="akhir" class="form-control form-control-sm rounded-pill px-3"
                                value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6 d-flex gap-2">
                            <button class="btn btn-primary btn-sm rounded-pill px-3" id="btnTampilkan">
                                <i class="material-icons-outlined" style="font-size:15px;vertical-align:middle">search</i>
                                Tampilkan
                            </button>
                            <button class="btn btn-success btn-sm rounded-pill px-3" id="btnExport">
                                <i class="material-icons-outlined" style="font-size:15px;vertical-align:middle">download</i>
                                Export Excel
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Hasil --}}
                <div id="dataContainer" class="p-3">
                    <div class="text-center text-muted py-5">
                        <i class="material-icons-outlined" style="font-size:48px;opacity:.3">card_giftcard</i>
                        <p class="mt-2">Pilih rentang tanggal dan klik Tampilkan</p>
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
                _token: '{{ csrf_token() }}',
            };
        }

        $('#btnTampilkan').on('click', function() {
            if (!$('#mulai').val() || !$('#akhir').val()) {
                alert('Pilih rentang tanggal terlebih dahulu.');
                return;
            }

            $('#dataContainer').html(
                '<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Memuat data...</p></div>'
            );

            $.ajax({
                url: '{{ route('laporan.penukaran-poin.data') }}',
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
            if (!p.mulai || !p.akhir) {
                alert('Pilih rentang tanggal terlebih dahulu.');
                return;
            }
            const qs = new URLSearchParams({
                mulai: p.mulai,
                akhir: p.akhir,
            });
            window.location.href = '{{ route('laporan.penukaran-poin.export') }}?' + qs.toString();
        });

        // Auto-load on page ready
        $(function() {
            $('#btnTampilkan').trigger('click');
        });
    </script>
@endpush
