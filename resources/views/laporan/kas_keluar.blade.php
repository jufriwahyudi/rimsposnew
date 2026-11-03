@extends('layouts.main.main')
@section('title', 'Laporan Kas Keluar')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Laporan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Laporan Kas Keluar</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card rounded-4 border-0 shadow-sm p-3 mb-4">
                    <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center flex-wrap gap-2 pb-0">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-icon bg-danger-subtle text-danger rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                <i class="material-icons-outlined fs-3">payments</i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">Laporan Kas Keluar</h5>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                        <i class="bi bi-arrow-up-right-circle me-1"></i> Cash Register (POS)
                                    </span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="bi bi-database-check me-1"></i> Live DB (Read-Only)
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body pt-3">
                        <form id="filterForm">
                            @csrf
                            <div class="row g-3">
                                {{-- Filter Toko --}}
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label fw-semibold text-secondary small mb-1">
                                        <i class="bi bi-shop me-1"></i> Pilih Toko / Store
                                    </label>
                                    <select name="store_id" id="storeFilter" class="form-select select2">
                                        @foreach ($stores as $st)
                                            <option value="{{ $st->id }}" {{ $st->id == $selectedStoreId ? 'selected' : '' }}>
                                                {{ $st->name }} {{ $st->id == 166 ? '⭐ (Contoh Kasir Aktif)' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Filter Periode Tanggal & Jam --}}
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label fw-semibold text-secondary small mb-1">
                                        <i class="bi bi-calendar3 me-1"></i> Periode Tanggal & Jam
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="fa fa-calendar text-muted"></i></span>
                                        <input type="text" name="datefilter" id="datefilter" class="form-control"
                                            placeholder="Pilih rentang tanggal & jam">
                                    </div>
                                </div>

                                {{-- Filter Shift Kasir --}}
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label fw-semibold text-secondary small mb-1">
                                        <i class="bi bi-clock-history me-1"></i> Sesi Shift Kasir
                                    </label>
                                    <select name="shift_id" id="shiftFilter" class="form-select">
                                        <option value="">-- Semua Sesi Shift --</option>
                                        @foreach ($shifts as $sh)
                                            @php
                                                $shiftStart = $sh->opened_at ? \Carbon\Carbon::parse($sh->opened_at)->format('d/m H:i') : '-';
                                                $shiftEnd = $sh->closed_at ? \Carbon\Carbon::parse($sh->closed_at)->format('H:i') : 'Aktif';
                                                $statusBadge = $sh->status === 'open' ? '[OPEN]' : '';
                                            @endphp
                                            <option value="{{ $sh->id }}">
                                                Shift #{{ $sh->id }} {{ $statusBadge }} ({{ $shiftStart }} - {{ $shiftEnd }}) - {{ $sh->cashier_name ?: 'Kasir' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Filter Kasir --}}
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label fw-semibold text-secondary small mb-1">
                                        <i class="bi bi-person-badge me-1"></i> Petugas / Kasir
                                    </label>
                                    <select name="user_id" id="userFilter" class="form-select">
                                        <option value="">-- Semua Kasir --</option>
                                        @foreach ($cashiers as $cs)
                                            <option value="{{ $cs->id }}">{{ $cs->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Pencarian Keterangan & Action Buttons --}}
                                <div class="col-md-9 col-sm-12">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                        <input type="text" name="search" id="searchFilter" class="form-control"
                                            placeholder="Cari kata kunci pada keterangan pengeluaran (contoh: es kristal, nasi, snack, belanja, ro...)">
                                    </div>
                                </div>

                                <div class="col-md-3 col-sm-12 d-flex gap-2">
                                    <button class="btn btn-primary flex-grow-1" type="button" id="searchBtn">
                                        <i class="fa fa-filter me-1"></i> Terapkan Filter
                                    </button>
                                    <button class="btn btn-outline-secondary" type="button" id="resetBtn" title="Reset Filter">
                                        <i class="fa fa-refresh"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Data Container (AJAX Target) --}}
                    <div id="dataContainer" class="mt-2">
                        <div class="text-center py-5">
                            <div class="spinner-border text-danger" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="text-muted mt-2">Menghubungkan ke Live Database & memuat data...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Bukti Bayar --}}
    <div class="modal fade" id="buktiModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-image me-2 text-danger"></i>Bukti Pengeluaran Kas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <img id="buktiImage" src="" alt="Bukti Kas Keluar" class="img-fluid rounded-3 shadow-sm border" style="max-height: 500px;">
                    <p id="buktiDesc" class="text-muted mt-3 mb-0 fw-semibold"></p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <style>
        .stat-card {
            border-radius: 14px;
            transition: all 0.2s ease-in-out;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.06);
        }
        .badge-shift {
            font-size: 0.82rem;
            letter-spacing: 0.3px;
        }
        .table-kas-keluar th {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script>
        $(function() {
            var start = moment().startOf('month').startOf('day');
            var end = moment().endOf('day');

            $('#datefilter').daterangepicker({
                startDate: start,
                endDate: end,
                timePicker: true,
                timePicker24Hour: true,
                timePickerIncrement: 1,
                ranges: {
                    'Hari Ini': [moment().startOf('day'), moment().endOf('day')],
                    'Kemarin': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
                    '7 Hari Terakhir': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
                    '30 Hari Terakhir': [moment().subtract(29, 'days').startOf('day'), moment().endOf('day')],
                    'Bulan Ini': [moment().startOf('month').startOf('day'), moment().endOf('month').endOf('day')],
                    'Bulan Lalu': [moment().subtract(1, 'month').startOf('month').startOf('day'), moment().subtract(1, 'month').endOf('month').endOf('day')]
                },
                locale: {
                    format: 'YYYY-MM-DD HH:mm',
                    separator: ' - ',
                    applyLabel: 'Terapkan',
                    cancelLabel: 'Batal',
                    customRangeLabel: 'Pilih Manual',
                    daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                    monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                                 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                    firstDay: 1
                }
            });

            $('#datefilter').val(start.format('YYYY-MM-DD HH:mm') + ' - ' + end.format('YYYY-MM-DD HH:mm'));

            $('#datefilter').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD HH:mm') + ' - ' + picker.endDate.format('YYYY-MM-DD HH:mm'));
                triggerSearch();
            });

            // Initial load
            triggerSearch();

            // Store change: reload shifts & cashiers dynamically
            $('#storeFilter').on('change', function() {
                var newStoreId = $(this).val();
                loadStoreMeta(newStoreId, function() {
                    triggerSearch();
                });
            });

            $('#searchBtn').click(function() {
                triggerSearch();
            });

            $('#resetBtn').click(function() {
                $('#shiftFilter').val('');
                $('#userFilter').val('');
                $('#searchFilter').val('');
                var defaultStart = moment().startOf('month').startOf('day');
                var defaultEnd = moment().endOf('day');
                $('#datefilter').data('daterangepicker').setStartDate(defaultStart);
                $('#datefilter').data('daterangepicker').setEndDate(defaultEnd);
                $('#datefilter').val(defaultStart.format('YYYY-MM-DD HH:mm') + ' - ' + defaultEnd.format('YYYY-MM-DD HH:mm'));
                triggerSearch();
            });

            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                triggerSearch();
            });

            $('#searchFilter').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    triggerSearch();
                }
            });

            function triggerSearch() {
                var datefilter = $('#datefilter').val();
                if (!datefilter) {
                    alert('Silakan pilih rentang tanggal dan jam terlebih dahulu');
                    return;
                }
                var dates = datefilter.split(' - ');
                var mulai = dates[0] ? dates[0].trim() : '';
                var akhir = dates[1] ? dates[1].trim() : mulai;

                loadData({
                    store_id: $('#storeFilter').val(),
                    mulai: mulai,
                    akhir: akhir,
                    shift_id: $('#shiftFilter').val(),
                    user_id: $('#userFilter').val(),
                    search: $('#searchFilter').val()
                });
            }

            function loadData(params) {
                $('#dataContainer').html(
                    '<div class="text-center py-5"><div class="spinner-border text-danger" role="status"></div><p class="text-muted mt-2">Memuat data dari Live Database...</p></div>'
                );

                $.ajax({
                    url: "{{ route('laporan.kas_keluar.data') }}",
                    type: "POST",
                    data: $.extend({ _token: '{{ csrf_token() }}' }, params),
                    success: function(response) {
                        $('#dataContainer').html(response);
                    },
                    error: function(xhr) {
                        $('#dataContainer').html(
                            '<div class="alert alert-danger rounded-3 p-3"><i class="bi bi-exclamation-triangle-fill me-2"></i>Terjadi kesalahan saat memuat data: ' +
                            (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : xhr.statusText) + '</div>'
                        );
                    }
                });
            }

            function loadStoreMeta(storeId, callback) {
                $.ajax({
                    url: "{{ route('laporan.kas_keluar.shifts') }}",
                    type: "GET",
                    data: { store_id: storeId },
                    success: function(res) {
                        if (res.success) {
                            // Populate Shifts
                            var shiftHtml = '<option value="">-- Semua Sesi Shift --</option>';
                            $.each(res.shifts, function(i, s) {
                                var sStart = s.opened_at ? moment(s.opened_at).format('DD/MM HH:mm') : '-';
                                var sEnd = s.closed_at ? moment(s.closed_at).format('HH:mm') : 'Aktif';
                                var sBadge = s.status === 'open' ? '[OPEN] ' : '';
                                shiftHtml += '<option value="' + s.id + '">Shift #' + s.id + ' ' + sBadge + '(' + sStart + ' - ' + sEnd + ') - ' + (s.cashier_name || 'Kasir') + '</option>';
                            });
                            $('#shiftFilter').html(shiftHtml);

                            // Populate Cashiers
                            var userHtml = '<option value="">-- Semua Kasir --</option>';
                            $.each(res.cashiers, function(i, u) {
                                userHtml += '<option value="' + u.id + '">' + u.name + '</option>';
                            });
                            $('#userFilter').html(userHtml);
                        }
                        if (callback) callback();
                    },
                    error: function() {
                        if (callback) callback();
                    }
                });
            }

            // Image Preview Modal Handler
            $(document).on('click', '.btn-preview-bukti', function() {
                var imgSrc = $(this).data('img');
                var desc = $(this).data('desc');
                $('#buktiImage').attr('src', imgSrc);
                $('#buktiDesc').text(desc);
                var modal = new bootstrap.Modal(document.getElementById('buktiModal'));
                modal.show();
            });
        });
    </script>
@endpush

