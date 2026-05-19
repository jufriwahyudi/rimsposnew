@extends('layouts.main.main')
@section('title', 'Laporan Biaya Operasional')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Laporan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active">Biaya Operasional</li>
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
                            <h5 class="fw-bold mb-0" style="color:#7c3aed">Laporan Biaya Operasional</h5>
                            <small class="text-muted">{{ session('store_name') }}</small>
                        </div>
                    </div>
                </div>

                {{-- Filter --}}
                <div class="card-body border-bottom pb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-2 col-sm-6">
                            <label class="form-label small mb-1">Dari Tanggal</label>
                            <input type="date" id="mulai" class="form-control form-control-sm"
                                value="{{ date('Y-m-01') }}">
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <label class="form-label small mb-1">Sampai Tanggal</label>
                            <input type="date" id="akhir" class="form-control form-control-sm"
                                value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <label class="form-label small mb-1">Metode Pembayaran</label>
                            <select id="metode" class="form-select form-select-sm">
                                <option value="semua">Semua Metode</option>
                                <option value="cash">Cash</option>
                                <option value="transfer">Transfer</option>
                            </select>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label small mb-1">Kategori</label>
                            <select id="category_id" class="form-select form-select-sm">
                                <option value="">Semua Kategori</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Tampilan</label>
                            <div class="d-flex gap-2">
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="jenis" id="jenis_rekap" value="rekap"
                                        checked>
                                    <label class="btn btn-outline-primary btn-sm" for="jenis_rekap">
                                        <i class="material-icons-outlined"
                                            style="font-size:14px;vertical-align:middle">bar_chart</i>
                                        Rekapitulasi
                                    </label>
                                    <input type="radio" class="btn-check" name="jenis" id="jenis_detail" value="detail">
                                    <label class="btn btn-outline-primary btn-sm" for="jenis_detail">
                                        <i class="material-icons-outlined"
                                            style="font-size:14px;vertical-align:middle">list</i>
                                        Detail
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 d-flex gap-2 mt-1">
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

                {{-- Hasil --}}
                <div id="dataContainer" class="p-3">
                    <div class="text-center text-muted py-5">
                        <i class="material-icons-outlined" style="font-size:48px;opacity:.3">receipt_long</i>
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
                metode: $('#metode').val(),
                category_id: $('#category_id').val(),
                jenis: $('input[name="jenis"]:checked').val(),
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
                url: '{{ route('laporan.biaya_operasional.data') }}',
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
                metode: p.metode,
                category_id: p.category_id,
                jenis: p.jenis,
            });
            window.location.href = '{{ route('laporan.biaya_operasional.export') }}?' + qs.toString();
        });

        // Auto-load on page ready
        $(function() {
            $('#btnTampilkan').trigger('click');
        });
    </script>
@endpush
