@extends('layouts.main.main')
@section('title', 'Riwayat Penjualan')

@section('content')
    <div class="card rounded-4">
        <div class="card-header">
            <h5 class="fw-bold my-2">Riwayat Penjualan</h5>
        </div>

        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-2 col-sm-6">
                    <label for="from_date" class="form-label small fw-semibold text-secondary">Dari Tanggal</label>
                    <input type="date" id="from_date" class="form-control form-control-sm">
                </div>

                <div class="col-md-2 col-sm-6">
                    <label for="to_date" class="form-label small fw-semibold text-secondary">Sampai Tanggal</label>
                    <input type="date" id="to_date" class="form-control form-control-sm">
                </div>

                <div class="col-md-2 col-sm-6">
                    <label for="payment_method" class="form-label small fw-semibold text-secondary">Metode Bayar</label>
                    <select id="payment_method" class="form-select form-select-sm">
                        <option value="">Semua Metode</option>
                        @foreach ($paymentMethods ?? [] as $pm)
                            @php
                                $pmLabel = match (strtolower($pm)) {
                                    'cash' => 'Cash / Tunai',
                                    'transfer' => 'Transfer Bank',
                                    'qris' => 'QRIS',
                                    default => strtoupper($pm),
                                };
                            @endphp
                            <option value="{{ $pm }}">{{ $pmLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label for="payment_status" class="form-label small fw-semibold text-secondary">Payment Status</label>
                    <select id="payment_status" class="form-select form-select-sm">
                        <option value="">Semua Payment Status</option>
                        <option value="lunas">Lunas</option>
                        <option value="hutang">Hutang</option>
                        <option value="unpaid">Belum Bayar</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label for="status" class="form-label small fw-semibold text-secondary">Status Transaksi</label>
                    <select id="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="paid">PAID</option>
                        <option value="void">VOID</option>
                        <option value="refund">REFUND</option>
                        <option value="hold">HOLD</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-6 d-flex align-items-end gap-2">
                    <button class="btn btn-sm btn-primary flex-fill" id="btnFilter" type="button">
                        <i class="fa fa-filter me-1"></i> Filter
                    </button>
                    <button class="btn btn-sm btn-outline-secondary flex-fill" id="btnReset" type="button">
                        <i class="fa fa-refresh me-1"></i> Reset
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
                            <th width="10%" class="text-center">Payment Status</th>
                            <th width="10%" class="text-center">Status</th>
                            <th width="8%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection
