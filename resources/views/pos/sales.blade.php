@extends('layouts.main.main')
@section('title', 'Riwayat Penjualan')

@section('content')
    <div class="card rounded-4">
        <div class="card-header">
            <h5 class="fw-bold my-2">Riwayat Penjualan</h5>
        </div>

        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label>Dari Tanggal</label>
                    <input type="date" id="from_date" class="form-control">
                </div>

                <div class="col-md-3">
                    <label>Sampai Tanggal</label>
                    <input type="date" id="to_date" class="form-control">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100" id="btnFilter">
                        Filter
                    </button>
                    <button class="btn btn-secondary w-100 ms-2" id="btnReset" onclick="location.reload()">
                        Reset
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped w-100" id="salesTable">
                    <thead class="table-light">
                        <tr>
                            <th width="4%">No</th>
                            <th>Tanggal</th>
                            <th>Invoice</th>
                            <th>Kasir</th>
                            <th width="10%" class="text-center">Total</th>
                            <th width="10%" class="text-center">Metode</th>
                            <th width="10%" class="text-center">Status</th>
                            <th width="8%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection
