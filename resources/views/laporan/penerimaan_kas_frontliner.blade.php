@extends('layouts.main.main')
@section('title', 'Penerimaan Kas Harian')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Laporan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Penerimaan Kas Harian</a></li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <div class="row">
            <div class="col-sm-12">
                <div class="card rounded-4 p-2">
                    <div class="card-header d-flex justify-content-between align-items-center mb-3 flex-wrap">
                        <div class="d-flex align-items-start">
                            <img src="{{ asset('assets/images/alazca_logo.png') }}" alt="Logo"
                                style="width: 35px; height: 35px;" class="me-2 mt-1">
                            <div>
                                <h5 class="fw-bold mb-0" style="color: #7c3aed">Penerimaan Kas Harian (Cash)</h5>
                                <small class="text-muted">{{ session('store_name') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="searchForm" method="POST">
                            @csrf
                            <div class="row g-2">
                                <div class="col-md-6 col-sm-12">
                                    <div class="input-group">
                                        <label for="datefilter" class="input-group-text">Tanggal</label>
                                        <input type="date" name="date_filter" id="datefilter" class="form-control"
                                            required max="{{ date('Y-m-d') }}"
                                            value="{{ old('date_filter') ?? date('Y-m-d') }}">
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-12">
                                    <select name="user_id" id="userFilter" class="form-select">
                                        <option value="">-- Semua Kasir --</option>
                                        @if (isset($frontliners))
                                            @foreach ($frontliners as $fl)
                                                <option value="{{ $fl->id }}">{{ $fl->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-2 col-sm-12">
                                    <button class="btn btn-primary w-100" type="button" id="searchBtn">
                                        <i class="fa fa-search"></i> Cari
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div id="dataContainer" style="margin-top: 15px;">
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            loadData($('#datefilter').val());

            $('#searchBtn').click(function() {
                var tanggal = $('#datefilter').val();
                if (!tanggal) {
                    alert('Silakan pilih tanggal terlebih dahulu');
                    return;
                }
                loadData(tanggal);
            });

            function loadData(tanggal) {
                $('#dataContainer').html(
                    '<div class="text-center py-4"><i class="fa fa-spinner fa-spin fa-2x"></i></div>');

                $.ajax({
                    url: "{{ route('laporan.penerimaan_kas.data') }}",
                    type: "POST",
                    data: {
                        _token: $('input[name="_token"]').val(),
                        tanggal: tanggal,
                        user_id: $('#userFilter').val()
                    },
                    success: function(response) {
                        $('#dataContainer').html(response);
                    },
                    error: function(xhr) {
                        $('#dataContainer').html('<div class="alert alert-danger">Terjadi kesalahan: ' +
                            xhr.statusText + '</div>');
                    }
                });
            }
        });
    </script>
@endpush
