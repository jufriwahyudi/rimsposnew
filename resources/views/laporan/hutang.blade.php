@extends('layouts.main.main')
@section('title', 'Laporan Hutang Mitra')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Laporan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Laporan Hutang Mitra</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif
        <div class="row">
            <div class="col-sm-12">
                <div class="card rounded-4 p-2 shadow-sm border-0">
                    <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center mb-3 flex-wrap">
                        <div class="d-flex align-items-start">
                            <img src="{{ asset('assets/images/alazca_logo.png') }}" alt="Logo"
                                style="width: 35px; height: 35px;" class="me-2 mt-1">
                            <div>
                                <h5 class="fw-bold mb-0" style="color: #7c3aed">Laporan Hutang per Mitra/Pelanggan</h5>
                                <small class="text-muted">{{ session('store_name') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="searchForm" method="POST">
                            @csrf
                            <div class="row align-items-center">
                                <div class="col-md-10 col-sm-9 mb-2 mb-md-0">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="material-icons-outlined" style="font-size: 20px; vertical-align: middle;">calendar_today</i>
                                        </span>
                                        <input type="text" name="datefilter" id="datefilter" class="form-control border-start-0"
                                            placeholder="Pilih rentang tanggal (Opsional - Kosongkan untuk semua periode)" readonly>
                                        <button class="btn btn-outline-secondary" type="button" id="clearBtn">
                                            <i class="fa fa-times"></i> Hapus Filter
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-3 d-grid">
                                    <button class="btn btn-primary" type="button" id="searchBtn">
                                        <i class="fa fa-search me-1"></i> Cari
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div id="dataContainer" class="mt-3">
                        <!-- Data hasil pencarian akan ditampilkan di sini -->
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <style>
        .input-group-text i {
            color: #7c3aed;
        }
        #datefilter {
            background-color: #fff !important;
            cursor: pointer;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <script>
        $(function() {
            var datefilterInput = $('input[name="datefilter"]');

            datefilterInput.daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear',
                    format: 'MM/DD/YYYY'
                }
            });

            datefilterInput.on('apply.daterangepicker', function(ev, picker) {
                $(this).val(
                    picker.startDate.format('MM/DD/YYYY') + ' - ' +
                    picker.endDate.format('MM/DD/YYYY')
                );
            });

            datefilterInput.on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });

            $('#clearBtn').click(function() {
                datefilterInput.val('');
                loadData('', '');
            });

            // Trigger load data pertama kali untuk menampilkan semua hutang lifetime
            loadData('', '');

            // Handle button search
            $('#searchBtn').click(function() {
                var datefilter = datefilterInput.val();
                var mulai = '';
                var akhir = '';

                if (datefilter) {
                    var dates = datefilter.split(' - ');
                    mulai = moment(dates[0], 'MM/DD/YYYY').format('YYYY-MM-DD');
                    akhir = moment(dates[1], 'MM/DD/YYYY').format('YYYY-MM-DD');
                }

                loadData(mulai, akhir);
            });

            function loadData(mulai, akhir) {
                $('#dataContainer').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2 text-muted">Memuat data laporan hutang...</p></div>');

                $.ajax({
                    url: "{{ route('laporan.hutang.data') }}",
                    type: "POST",
                    data: {
                        _token: $('input[name="_token"]').val(),
                        mulai: mulai,
                        akhir: akhir
                    },
                    success: function(response) {
                        $('#dataContainer').html(response);
                    },
                    error: function(xhr) {
                        $('#dataContainer').html('<div class="alert alert-danger m-3">Gagal memuat data: ' + xhr.statusText + '</div>');
                    }
                });
            }
        });
    </script>
@endpush
