@extends('layouts.main.main')

@section('title', 'Riwayat Pembayaran Titipan')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h4 class="fw-bold mb-1"><i class="material-icons-outlined me-2 align-middle text-success">receipt_long</i>Riwayat Pembayaran Titipan</h4>
                <p class="text-muted mb-0 small">Daftar seluruh pembayaran uang titipan ke vendor/penitip beserta cetak ulang bukti struk rekonsiliasi.</p>
            </div>
            <a href="{{ route('consignments.daily') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                <i class="material-icons-outlined me-1 align-middle" style="font-size: 16px;">today</i> Kembali ke Titipan Harian
            </a>
        </div>

        {{-- Filter Card --}}
        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-body p-3">
                <form method="GET" action="{{ route('consignments.settlements') }}" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Dari Tanggal</label>
                        <input type="date" name="date_from" class="form-control form-control-sm rounded-3" value="{{ $dateFrom }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Sampai Tanggal</label>
                        <input type="date" name="date_to" class="form-control form-control-sm rounded-3" value="{{ $dateTo }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Penitip</label>
                        <select name="consignor_id" class="form-select form-select-sm rounded-3">
                            <option value="">-- Semua Penitip --</option>
                            @foreach($consignors as $consignor)
                                <option value="{{ $consignor->id }}" {{ $consignorId == $consignor->id ? 'selected' : '' }}>
                                    {{ $consignor->nama_penitip }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100 rounded-pill">
                            <i class="material-icons-outlined me-1 align-middle" style="font-size: 16px;">filter_alt</i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Summary Card --}}
        <div class="alert alert-light border border-success-subtle shadow-sm rounded-3 d-flex justify-content-between align-items-center mb-4">
            <div>
                <span class="text-muted small">Total Pembayaran Periode Ini:</span>
                <h4 class="fw-bold text-success mb-0 mt-1">Rp {{ number_format($totalPayout, 0, ',', '.') }}</h4>
            </div>
            <span class="badge bg-light-success text-success border px-3 py-2 rounded-pill fw-bold">
                {{ $settlements->count() }} Transaksi Selesai
            </span>
        </div>

        {{-- Table --}}
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-0">
                @if($settlements->isEmpty())
                    <div class="p-5 text-center text-muted">
                        <span class="material-icons-outlined mb-2" style="font-size: 48px;">money_off</span>
                        <h6>Belum ada riwayat pembayaran konsinyasi pada periode ini.</h6>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">No. Bukti</th>
                                    <th>Tanggal</th>
                                    <th>Penitip</th>
                                    <th>Metode</th>
                                    <th class="text-end">Total Dibayar</th>
                                    <th>Diproses Oleh</th>
                                    <th class="text-end pe-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($settlements as $settlement)
                                    <tr>
                                        <td class="ps-3 fw-bold text-dark">{{ $settlement->settlement_number }}</td>
                                        <td>{{ $settlement->settlement_date->translatedFormat('d M Y') }}</td>
                                        <td>
                                            <strong>{{ $settlement->consignor->nama_penitip ?? '-' }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge {{ $settlement->payment_method === 'CASH' ? 'bg-light-success text-success' : 'bg-light-info text-info' }} px-2 py-1 fw-semibold">
                                                {{ $settlement->payment_method }}
                                            </span>
                                        </td>
                                        <td class="text-end fw-bold text-danger">
                                            Rp {{ number_format($settlement->total_amount, 0, ',', '.') }}
                                        </td>
                                        <td>{{ $settlement->user->name ?? 'Kasir' }}</td>
                                        <td class="text-end pe-3">
                                            <a href="{{ route('consignments.receipt', $settlement->id) }}" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                                                <i class="material-icons-outlined me-1 align-middle" style="font-size: 14px;">print</i> Cetak Struk
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
