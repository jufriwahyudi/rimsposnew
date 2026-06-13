@extends('layouts.main.main')
@section('title', 'Detail Mitra')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Pengaturan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Mitra</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $customer->name }}</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <!-- Customer Profile Card -->
        <div class="col-md-4">
            <div class="card rounded-4 border-0 shadow-sm mb-4">
                <div class="card-body text-center py-4">
                    <div class="avatar-container mb-3 d-inline-block p-2 rounded-circle bg-light">
                        <i class="material-icons-outlined text-primary" style="font-size: 56px; vertical-align: middle;">person</i>
                    </div>
                    <h5 class="fw-bold mb-1">{{ $customer->name }}</h5>
                    <p class="text-muted small mb-3"><i class="bi bi-telephone me-1"></i>{{ $customer->phone ?? 'Tidak ada nomor telepon' }}</p>
                    
                    <div class="text-start border-top pt-3 mt-3">
                        <label class="form-label fw-bold text-muted small mb-1">Alamat</label>
                        <p class="mb-3 text-dark small" style="white-space: pre-line;">{{ $customer->alamat ?? '-' }}</p>
                    </div>

                    <div class="border-top pt-3">
                        <label class="form-label fw-bold text-muted small mb-1">Akumulasi Hutang Belum Lunas</label>
                        <h3 class="fw-bold text-danger">Rp {{ number_format($totalDebt, 0, ',', '.') }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales & Debt History Tabs -->
        <div class="col-md-8">
            <div class="card rounded-4 border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-3 px-3">
                    <ul class="nav nav-tabs card-header-tabs" id="mitraTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold" id="debts-tab" data-bs-toggle="tab" data-bs-target="#debts-content" type="button" role="tab">
                                Hutang Aktif ({{ $debts->count() }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-content" type="button" role="tab">
                                Semua Transaksi ({{ $sales->count() }})
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-3">
                    <div class="tab-content" id="mitraTabsContent">
                        
                        <!-- Tab 1: Hutang Aktif -->
                        <div class="tab-pane fade show active" id="debts-content" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-striped align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Tanggal</th>
                                            <th class="text-end">Total</th>
                                            <th class="text-end">Terbayar</th>
                                            <th class="text-end text-danger">Sisa Hutang</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($debts as $sale)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('sales.show', $sale->id) }}" class="fw-bold text-primary">
                                                        {{ $sale->invoice_number }}
                                                    </a>
                                                </td>
                                                <td>{{ $sale->sale_date->format('d M Y H:i') }}</td>
                                                <td class="text-end">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</td>
                                                <td class="text-end text-success">Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</td>
                                                <td class="text-end fw-bold text-danger">
                                                    Rp {{ number_format($sale->grand_total - $sale->paid_amount, 0, ',', '.') }}
                                                </td>
                                                <td class="text-center">
                                                    <a href="{{ route('sales.show', $sale->id) }}" class="btn btn-sm btn-outline-primary py-1">
                                                        Pelunasan
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">
                                                    <i class="bi bi-check-circle text-success fs-3 d-block mb-2"></i>
                                                    Tidak ada hutang aktif untuk mitra ini.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab 2: Semua Transaksi -->
                        <div class="tab-pane fade" id="history-content" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Tanggal</th>
                                            <th class="text-end">Total</th>
                                            <th class="text-center">Status Bayar</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($sales as $sale)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('sales.show', $sale->id) }}" class="fw-bold text-primary">
                                                        {{ $sale->invoice_number }}
                                                    </a>
                                                </td>
                                                <td>{{ $sale->sale_date->format('d M Y H:i') }}</td>
                                                <td class="text-end">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</td>
                                                <td class="text-center">
                                                    @switch($sale->payment_status)
                                                        @case('lunas')
                                                            <span class="badge bg-success">Lunas</span>
                                                            @break
                                                        @case('hutang')
                                                            <span class="badge bg-danger">Hutang</span>
                                                            @break
                                                        @case('unpaid')
                                                            <span class="badge bg-warning text-dark">Belum Bayar</span>
                                                            @break
                                                        @default
                                                            <span class="badge bg-secondary">{{ ucfirst($sale->payment_status) }}</span>
                                                    @endswitch
                                                </td>
                                                <td class="text-center">
                                                    @switch($sale->status)
                                                        @case('void')
                                                            <span class="badge bg-danger">VOID</span>
                                                            @break
                                                        @case('hold')
                                                            <span class="badge bg-warning text-dark">HOLD</span>
                                                            @break
                                                        @case('paid')
                                                            <span class="badge bg-success">PAID</span>
                                                            @break
                                                        @default
                                                            <span class="badge bg-secondary">{{ strtoupper($sale->status) }}</span>
                                                    @endswitch
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat transaksi.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
