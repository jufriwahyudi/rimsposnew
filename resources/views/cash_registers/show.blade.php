@extends('layouts.main.main')
@section('title', 'Detail Shift Kasir #' . $register->id)

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Laporan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('cash-registers.index') }}">Laporan Shift Kasir</a></li>
                    <li class="breadcrumb-item active">Detail Sesi #{{ $register->id }}</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        {{-- LEFT: SHIFT SUMMARY & RECONCILIATION --}}
        <div class="col-md-5">
            <div class="card rounded-4 shadow-sm mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0" style="color: #7c3aed;">
                        <i class="bi bi-clock-history me-1"></i> Shift Kasir #{{ $register->id }}
                    </h5>
                    <span class="badge bg-{{ $register->status === 'open' ? 'warning text-dark' : 'secondary' }}">
                        {{ $register->status === 'open' ? 'Sedang Berlangsung' : 'Selesai / Ditutup' }}
                    </span>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless align-middle mb-3">
                        <tr>
                            <td class="text-muted" width="40%">Toko / Cabang</td>
                            <td class="fw-bold">{{ $register->store?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Kasir Buka</td>
                            <td class="fw-semibold">{{ $register->cashier?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Waktu Buka</td>
                            <td>{{ $register->opened_at ? $register->opened_at->format('d/m/Y H:i:s') : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Waktu Tutup</td>
                            <td>{{ $register->closed_at ? $register->closed_at->format('d/m/Y H:i:s') : '-' }}</td>
                        </tr>
                        @if($register->closedBy)
                            <tr>
                                <td class="text-muted">Ditutup Oleh</td>
                                <td>{{ $register->closedBy->name }}</td>
                            </tr>
                        @endif
                    </table>

                    <div class="divider my-2 border-top"></div>

                    <h6 class="fw-bold text-dark my-3"><i class="bi bi-calculator me-1 text-primary"></i> Rincian Rekonsiliasi Kas</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle">
                            <tbody>
                                <tr>
                                    <td>Modal Kas Awal</td>
                                    <td class="text-end fw-semibold">Rp {{ number_format($summary['opening_cash'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Penjualan Tunai (+)</td>
                                    <td class="text-end text-success fw-semibold">+ Rp {{ number_format($summary['cash_sales'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Kas Masuk Tambahan (+)</td>
                                    <td class="text-end text-success fw-semibold">+ Rp {{ number_format($summary['cash_in'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Kas Keluar Toko (-)</td>
                                    <td class="text-end text-danger fw-semibold">- Rp {{ number_format($summary['cash_out'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Refund Tunai (-)</td>
                                    <td class="text-end text-danger fw-semibold">- Rp {{ number_format($summary['refund_cash'], 0, ',', '.') }}</td>
                                </tr>
                                <tr class="table-primary fw-bold">
                                    <td>Kas Seharusnya di Laci (Expected)</td>
                                    <td class="text-end text-primary">Rp {{ number_format($summary['expected_cash'], 0, ',', '.') }}</td>
                                </tr>
                                <tr class="table-light fw-bold">
                                    <td>Kas Fisik Aktual (Dihitung)</td>
                                    <td class="text-end">Rp {{ number_format($summary['actual_cash'] ?? 0, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="fw-bold {{ ($summary['cash_difference'] ?? 0) == 0 ? 'table-success text-success' : (($summary['cash_difference'] ?? 0) > 0 ? 'table-info text-info' : 'table-danger text-danger') }}">
                                    <td>Selisih Kas</td>
                                    <td class="text-end">
                                        @if(($summary['cash_difference'] ?? 0) == 0)
                                            Rp 0 (Pas)
                                        @elseif(($summary['cash_difference'] ?? 0) > 0)
                                            +Rp {{ number_format($summary['cash_difference'], 0, ',', '.') }} (Lebih)
                                        @else
                                            -Rp {{ number_format(abs($summary['cash_difference']), 0, ',', '.') }} (Kurang)
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>Penjualan Non-Tunai</td>
                                    <td class="text-end text-muted">Rp {{ number_format($summary['non_cash_sales'], 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    @if($register->denominations && count($register->denominations) > 0)
                        <h6 class="fw-bold text-dark mt-3 mb-2"><i class="bi bi-cash-stack me-1 text-success"></i> Rincian Pecahan Uang Fisik</h6>
                        <div class="row g-1 mb-3">
                            @foreach($register->denominations as $denom => $count)
                                <div class="col-6">
                                    <div class="p-2 border rounded bg-light d-flex justify-content-between small">
                                        <span>Rp {{ number_format($denom, 0, ',', '.') }} x {{ $count }}</span>
                                        <strong>Rp {{ number_format($denom * $count, 0, ',', '.') }}</strong>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($register->notes)
                        <div class="alert alert-light border mt-3">
                            <strong class="small d-block text-muted">Catatan Shift:</strong>
                            <span>{{ $register->notes }}</span>
                        </div>
                    @endif

                    <div class="d-flex gap-2 mt-4">
                        <a href="{{ route('cash-registers.print', $register->id) }}" target="_blank" class="btn btn-outline-primary w-100">
                            <i class="bi bi-printer me-1"></i> Cetak Struk Tutup Kasir
                        </a>
                        <a href="{{ route('cash-registers.index') }}" class="btn btn-secondary">
                            Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT: TRANSACTIONS IN THIS SHIFT --}}
        <div class="col-md-7">
            {{-- SALES TAB --}}
            <div class="card rounded-4 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-receipt me-1 text-primary"></i> Penjualan Sesi Ini ({{ $register->sales->count() }})
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light small">
                                <tr>
                                    <th>Invoice</th>
                                    <th>Waktu</th>
                                    <th>Pelanggan</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                @forelse($register->sales as $sale)
                                    <tr>
                                        <td>
                                            <a href="{{ route('sales.show', $sale->id) }}" class="fw-bold text-primary">
                                                {{ $sale->invoice_number }}
                                            </a>
                                        </td>
                                        <td>{{ $sale->sale_date ? $sale->sale_date->format('H:i') : '-' }}</td>
                                        <td>{{ $sale->customer_name }}</td>
                                        <td class="text-end fw-semibold">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $sale->payment_status === 'lunas' ? 'success' : 'danger' }}">
                                                {{ ucfirst($sale->payment_status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Belum ada transaksi penjualan pada shift ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- CASH TRANSACTIONS (PETTY CASH) --}}
            <div class="card rounded-4 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-arrow-left-right me-1 text-info"></i> Kas Masuk / Kas Keluar Manual ({{ $register->cashTransactions->where('transaction_type', '!=', 'sale')->count() }})
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light small">
                                <tr>
                                    <th>Waktu</th>
                                    <th>Jenis</th>
                                    <th>Keterangan</th>
                                    <th class="text-end">Nominal</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                @forelse($register->cashTransactions->where('transaction_type', '!=', 'sale') as $tx)
                                    <tr>
                                        <td>{{ $tx->transaction_date ? $tx->transaction_date->format('H:i') : '-' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $tx->direction === 'in' ? 'success' : 'danger' }}">
                                                {{ $tx->direction === 'in' ? 'Kas Masuk' : 'Kas Keluar' }}
                                            </span>
                                        </td>
                                        <td>{{ $tx->notes ?? '-' }}</td>
                                        <td class="text-end fw-semibold {{ $tx->direction === 'in' ? 'text-success' : 'text-danger' }}">
                                            {{ $tx->direction === 'in' ? '+' : '-' }} Rp {{ number_format($tx->amount, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">Tidak ada pergerakan kas manual.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
