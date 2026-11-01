@extends('layouts.main.app')

@section('title', 'Data Penjualan')

@section('breadcrumb')
<div class="page-breadcrumb">
    <div class="row align-items-center">
        <div class="col-6">
            <h4 class="page-title">Data Penjualan</h4>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="container">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Daftar Penjualan</h5>

            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Invoice</th>
                            <th>Tanggal</th>
                            <th>Pelanggan / Meja</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">Diskon</th>
                            <th class="text-end">Jumlah Penjualan</th>
                            <th class="text-end">Modal</th>
                            <th class="text-end">Laba / Rugi</th>
                            <th>Metode Pembayaran</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($rows) && count($rows))
                        @foreach($rows as $row)
                        <tr>
                            <td>{{ $row->no }}</td>
                            <td>{{ $row->invoice_number ?? '-' }}</td>
                            <td>{{ optional($row->sale_date)->format('Y-m-d H:i') ?? '-' }}</td>
                            <td>
                                {{ $row->customer_name }}
                                @if(!empty($row->table_number))
                                <small class="text-muted d-block">Meja: {{ $row->table_number }}</small>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($row->subtotal ?? $row->jumlah_penjualan, 2, ',', '.') }}</td>
                            <td class="text-end {{ ($row->diskon ?? 0) > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                {{ ($row->diskon ?? 0) > 0 ? '-' . number_format($row->diskon, 0, ',', '.') : '-' }}
                            </td>
                            <td class="text-end">{{ number_format($row->jumlah_penjualan, 2, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($row->modal, 2, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($row->laba_rugi, 2, ',', '.') }}</td>
                            <td>{{ $row->metode_pembayaran ?? '-' }}</td>
                            <td>{{ $row->status }}</td>
                        </tr>
                        @endforeach
                        @else
                        <tr>
                            <td colspan="11" class="text-center">Tidak ada data</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection