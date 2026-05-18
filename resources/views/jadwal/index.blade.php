@extends('layouts.main.main')
@section('title', 'Laporan Penjualan')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Jadwal</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Jadwal Sesi</a></li>
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
                                <h5 class="fw-bold mb-0" style="color: #7c3aed">Menentukan Jadwal Sesi</h5>
                                <small class="text-muted">{{ session('store_name') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="searchForm" method="POST">
                            @csrf
                            <label class="fw-bold">Tanggal Distribusi NSE</label>
                            <div class="row g-2 align-items-end mb-4">
                                <div class="col-md-6 col-sm-12">
                                    <div class="input-group">
                                        <input type="date" name="date_filter" id="datefilter" class="form-control"
                                            required value="{{ old('date_filter') ?? date('Y-m-d') }}"></input>
                                    </div>
                                </div>
                                <div class="col-auto ms-auto">
                                    <button class="btn btn-success" type="button" id="tambahSesiBtn">
                                        + Tambah Sesi
                                    </button>
                                </div>
                            </div>
                        </form>
                        <hr class="my-4">

                        <!-- Container untuk list jadwal -->
                        <div id="list-jadwal-container"></div>

                    </div>


                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('tambahSesiBtn').addEventListener('click', function() {
            fetch('/jadwal/list')
                .then(response => response.text())
                .then(html => {
                    document.getElementById('list-jadwal-container').innerHTML = html;
                })
                .catch(error => {
                    console.error(error);
                    alert('Gagal memuat data');
                });
        });
    </script>
@endpush
