@extends('layouts.main.main')
@section('title', 'Laporan Penjualan')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Laporan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Laporan Neraca Lajur</a></li>
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
                <div class="card rounded-4 p-2">
                    <div class="card-header d-flex justify-content-between align-items-center mb-3 flex-wrap">
                        <div class="d-flex align-items-start">
                            <img src={{ asset('assets/images/alazca_logo.png') }} alt="Logo"
                                style="width: 35px; height: 35px;" class="me-2 mt-1">
                            <div>
                                <h5 class="fw-bold mb-0" style="color: #7c3aed">Laporan Neraca Lajur</h5>
                                <small class="text-muted">Al-Azhar Cairo Banda Aceh</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="searchForm" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-12 col-sm-12">
                                    <div class="input-group">
                                        <input type="text" name="datefilter" id="datefilter" class="form-control" placeholder="Pilih tanggal">
                                        <button class="btn btn-primary" type="button" id="searchBtn"><i class="fa fa-search"></i> </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div id="dataContainer" style="margin-top: 30px;">
                        <!-- Data hasil pencarian akan ditampilkan di sini -->
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <script>
        $(function() {
            $('input[name="datefilter"]').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear'
                }
            });

            $('input[name="datefilter"]').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(
                    picker.startDate.format('MM/DD/YYYY') + ' - ' +
                    picker.endDate.format('MM/DD/YYYY')
                );
            });

            $('input[name="datefilter"]').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });

            // Handle button search
            $('#searchBtn').click(function() {
                var datefilter = $('#datefilter').val();

                if (!datefilter) {
                    alert('Silakan pilih tanggal terlebih dahulu');
                    return;
                }

                // Parse date range
                var dates = datefilter.split(' - ');
                var mulai = moment(dates[0], 'MM/DD/YYYY').format('YYYY-MM-DD');
                var akhir = moment(dates[1], 'MM/DD/YYYY').format('YYYY-MM-DD');

                // AJAX request ke controller
                // $.ajax({
                //     url: "{{ route('laporanpenjualan.getpenjualan') }}",
                //     type: "POST",
                //     data: {
                //         _token: $('input[name="_token"]').val(),
                //         mulai: mulai,
                //         akhir: akhir,
                //         id_divisi: ''
                //     },
                //     success: function(response) {
                //         // Tampilkan data hasil pencarian
                //         $('#dataContainer').html(response);
                //     },
                //     error: function(xhr) {
                //         alert('Terjadi kesalahan: ' + xhr.statusText);
                //     }
                // });
            });
        });
    </script>
@endpush

