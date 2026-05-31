@extends('layouts.main.main')
@section('title', 'SaaS Billing')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Pengaturan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active">SaaS Billing</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .billing-card {
            border: none;
            border-radius: 16px;
            transition: transform .2s, box-shadow .2s;
            overflow: hidden;
        }
        .billing-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,.08);
        }
        .billing-card .card-body {
            padding: 1.25rem 1.5rem;
        }
        .billing-card .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }
        .billing-card .card-value {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.2;
        }
        .billing-card .card-label {
            font-size: 0.82rem;
            color: #6c757d;
            margin-top: 2px;
        }
        .badge-status {
            font-size: 0.75rem;
            padding: 5px 10px;
            border-radius: 6px;
            font-weight: 600;
        }
        .table th {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            font-weight: 600;
        }
        .store-name-link {
            color: #333;
            text-decoration: none;
            font-weight: 600;
            transition: color .15s;
        }
        .store-name-link:hover {
            color: #7c3aed;
        }
        .days-badge {
            font-size: 0.72rem;
            border-radius: 20px;
            padding: 3px 8px;
        }
    </style>
@endpush

@section('content')
    <div class="row g-3 mb-4">
        {{-- Card: Total Toko --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card billing-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="card-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bx bx-store"></i>
                    </div>
                    <div>
                        <div class="card-value text-primary">{{ $totalStores }}</div>
                        <div class="card-label">Total Toko</div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Card: Lifetime --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card billing-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="card-icon bg-success bg-opacity-10 text-success">
                        <i class="bx bx-infinity"></i>
                    </div>
                    <div>
                        <div class="card-value text-success">{{ $lifetimeStores }}</div>
                        <div class="card-label">Paket Lifetime</div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Card: Masa Tenggang + Expired --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card billing-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="card-icon bg-danger bg-opacity-10 text-danger">
                        <i class="bx bx-error-circle"></i>
                    </div>
                    <div>
                        <div class="card-value text-danger">{{ $expiredStores + $graceStores }}</div>
                        <div class="card-label">Expired / Masa Tenggang</div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Card: Total Tagihan Belum Dibayar --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card billing-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="card-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bx bx-receipt"></i>
                    </div>
                    <div>
                        <div class="card-value text-warning">Rp {{ number_format($unpaidInvoices, 0, ',', '.') }}</div>
                        <div class="card-label">Tagihan Belum Dibayar</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card rounded-4 p-2">
        <div class="card-header d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="d-flex align-items-start">
                <img src="{{ asset('assets/images/alazca_logo.png') }}" alt="Logo"
                    style="width:35px;height:35px;" class="me-2 mt-1">
                <div>
                    <h5 class="fw-bold mb-0" style="color:#7c3aed">SaaS Billing</h5>
                    <small class="text-muted">Monitoring paket langganan dan tagihan toko</small>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle" id="billingTable">
                    <thead>
                        <tr>
                            <th width="4%">No</th>
                            <th>Nama Toko</th>
                            <th>Tipe Paket</th>
                            <th>Tagihan</th>
                            <th>Masa Aktif</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" width="8%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stores as $i => $store)
                            @php
                                $sub = $store->subscription;
                                $status = $sub ? $sub->subscription_status : 'active';
                                $packageType = $sub ? $sub->package_type : 'lifetime';
                            @endphp
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <a href="{{ route('subscribed-billing.show', $store->id) }}" class="store-name-link">
                                        {{ $store->name }}
                                    </a>
                                    <br><small class="text-muted">{{ $store->code }}</small>
                                </td>
                                <td>
                                    @if ($packageType === 'lifetime')
                                        <span class="badge bg-success badge-status">Lifetime</span>
                                    @elseif ($packageType === 'monthly')
                                        <span class="badge bg-info badge-status">Bulanan</span>
                                    @else
                                        <span class="badge bg-primary badge-status">Tahunan</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($sub && $packageType !== 'lifetime')
                                        <strong>Rp {{ number_format($sub->billing_amount, 0, ',', '.') }}</strong>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($packageType === 'lifetime')
                                        <span class="text-success fw-semibold"><i class="bx bx-check-circle"></i> Selamanya</span>
                                    @elseif ($sub && $sub->start_date && $sub->end_date)
                                        {{ $sub->start_date->format('d M Y') }}
                                        <br><small class="text-muted">s/d {{ $sub->end_date->format('d M Y') }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($status === 'active')
                                        <span class="badge bg-success badge-status">Aktif</span>
                                    @elseif ($status === 'grace_period')
                                        <span class="badge bg-warning text-dark badge-status">
                                            <i class="bx bx-time-five"></i> Masa Tenggang
                                        </span>
                                        <br><small class="days-badge badge bg-warning bg-opacity-25 text-dark mt-1">
                                            Sisa {{ $sub->grace_days_left }} hari
                                        </small>
                                    @else
                                        <span class="badge bg-danger badge-status">Expired</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('subscribed-billing.show', $store->id) }}"
                                       class="btn btn-sm btn-outline-primary" title="Detail">
                                        <i class="bx bx-show"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Belum ada data toko.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
