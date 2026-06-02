@extends('layouts.main.main')

@section('title', 'Superadmin Dashboard')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3 text-indigo fw-bold">Superadmin</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Global Oversight Dashboard</a></li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Top KPI Cards --}}
    <div class="row g-3 mb-4">
        {{-- Total Toko --}}
        <div class="col-xl-3 col-md-6">
            <div class="card rounded-4 border-0 shadow-sm h-100" style="border-left: 4px solid #4f46e5 !important;">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <small class="text-muted fw-semibold">TOTAL TOKO REGISTERED</small>
                            <h3 class="fw-bold mb-0 text-slate-800 mt-1">{{ $totalStores }}</h3>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                            style="width:48px;height:48px;background:#e0e7ff;">
                            <i class="bx bx-store-alt fs-4 text-indigo" style="color:#4f46e5;"></i>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge bg-success-subtle text-success border-0 px-2 py-1"><i class="bx bxs-circle me-1" style="font-size:6px;"></i>{{ $aktifStores }} Aktif</span>
                        <span class="badge bg-warning-subtle text-warning-emphasis border-0 px-2 py-1"><i class="bx bxs-circle me-1" style="font-size:6px;"></i>{{ $graceStores }} Trial</span>
                        <span class="badge bg-danger-subtle text-danger border-0 px-2 py-1"><i class="bx bxs-circle me-1" style="font-size:6px;"></i>{{ $expiredStores }} Expired</span>
                        <span class="badge bg-secondary-subtle text-secondary border-0 px-2 py-1"><i class="bx bxs-circle me-1" style="font-size:6px;"></i>{{ $nonAktifStores }} Off</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Penjualan Hari Ini --}}
        <div class="col-xl-3 col-md-6">
            <div class="card rounded-4 border-0 shadow-sm h-100" style="border-left: 4px solid #10b981 !important;">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                        style="width:50px;height:50px;background:#d1fae5;">
                        <i class="bx bx-cart fs-4" style="color:#059669;"></i>
                    </div>
                    <div>
                        <small class="text-muted fw-semibold d-block">OMZET HARI INI (GLOBAL)</small>
                        <h4 class="fw-bold mb-0 text-slate-800 mt-1">Rp {{ number_format($salesToday->total ?? 0, 0, ',', '.') }}</h4>
                        <small class="text-muted">{{ $salesToday->jumlah ?? 0 }} transaksi berhasil</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Penjualan Bulan Ini --}}
        <div class="col-xl-3 col-md-6">
            <div class="card rounded-4 border-0 shadow-sm h-100" style="border-left: 4px solid #8b5cf6 !important;">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                        style="width:50px;height:50px;background:#f3e8ff;">
                        <i class="bx bx-trending-up fs-4" style="color:#7c3aed;"></i>
                    </div>
                    <div>
                        <small class="text-muted fw-semibold d-block">OMZET BULAN INI (GLOBAL)</small>
                        <h4 class="fw-bold mb-0 text-slate-800 mt-1">Rp {{ number_format($salesMonth->total ?? 0, 0, ',', '.') }}</h4>
                        <small class="text-muted">{{ $salesMonth->jumlah ?? 0 }} transaksi berhasil</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Expenses Bulan Ini --}}
        <div class="col-xl-3 col-md-6">
            <div class="card rounded-4 border-0 shadow-sm h-100" style="border-left: 4px solid #ef4444 !important;">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                        style="width:50px;height:50px;background:#fee2e2;">
                        <i class="bx bx-wallet fs-4" style="color:#dc2626;"></i>
                    </div>
                    <div>
                        <small class="text-muted fw-semibold d-block">OPERASIONAL EXPENSE (GLOBAL)</small>
                        <h4 class="fw-bold mb-0 text-slate-800 mt-1">Rp {{ number_format($expensesMonth ?? 0, 0, ',', '.') }}</h4>
                        <small class="text-muted">Total pengeluaran bulan ini</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SaaS Billing Overview KPI Cards --}}
    <div class="row g-3 mb-4">
        {{-- Total Pendapatan Langganan --}}
        <div class="col-xl-6 col-md-6">
            <div class="card rounded-4 border-0 shadow-sm h-100" style="border-left: 4px solid #6366f1 !important; background: linear-gradient(135deg, #ffffff 0%, #f5f3ff 100%);">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                        style="width:50px;height:50px;background:#e0e7ff;">
                        <i class="bx bx-badge-check fs-4" style="color:#6366f1;"></i>
                    </div>
                    <div>
                        <small class="text-muted fw-semibold d-block">TOTAL PENDAPATAN LANGGANAN SAAS (LUNAS)</small>
                        <h4 class="fw-bold mb-0 text-indigo mt-1">Rp {{ number_format($totalSubscribedRevenue ?? 0, 0, ',', '.') }}</h4>
                        <small class="text-muted">Total dari pembayaran terkonfirmasi</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Tagihan Belum Dibayar --}}
        <div class="col-xl-6 col-md-6">
            <div class="card rounded-4 border-0 shadow-sm h-100" style="border-left: 4px solid #f59e0b !important; background: linear-gradient(135deg, #ffffff 0%, #fffbeb 100%);">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                        style="width:50px;height:50px;background:#fef3c7;">
                        <i class="bx bx-receipt fs-4" style="color:#d97706;"></i>
                    </div>
                    <div>
                        <small class="text-muted fw-semibold d-block">TAGIHAN SAAS BELUM DIBAYAR (PENDING)</small>
                        <h4 class="fw-bold mb-0 text-warning-emphasis mt-1">Rp {{ number_format($unpaidInvoicesSum ?? 0, 0, ',', '.') }}</h4>
                        <small class="text-muted">{{ $unpaidInvoicesCount ?? 0 }} invoice belum lunas</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="row g-3 mb-4">
        {{-- Omzet Trend --}}
        <div class="col-xl-8">
            <div class="card rounded-4 border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-3 pb-0">
                    <h6 class="fw-bold mb-0 text-indigo">
                        <i class="bx bx-line-chart me-1"></i> Tren Omzet Global (30 Hari Terakhir)
                    </h6>
                </div>
                <div class="card-body">
                    <div id="chartPenjualanGlobal"></div>
                </div>
            </div>
        </div>

        {{-- Payment Methods --}}
        <div class="col-xl-4">
            <div class="card rounded-4 border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-3 pb-0">
                    <h6 class="fw-bold mb-0 text-indigo">
                        <i class="bx bx-pie-chart-alt-2 me-1"></i> Metode Pembayaran Global
                    </h6>
                </div>
                <div class="card-body">
                    <div id="chartPaymentGlobal"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Store Comparison & Live Feed --}}
    <div class="row g-3 mb-4">
        {{-- Store Performance Chart --}}
        <div class="col-xl-6">
            <div class="card rounded-4 border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-3 pb-0">
                    <h6 class="fw-bold mb-0 text-indigo">
                        <i class="bx bx-bar-chart-alt-2 me-1"></i> Perbandingan Omzet Antar Toko (Bulan Ini)
                    </h6>
                </div>
                <div class="card-body">
                    <div id="chartStoreComparison"></div>
                </div>
            </div>
        </div>

        {{-- Live Activity Feed --}}
        <div class="col-xl-6">
            <div class="card rounded-4 border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-indigo">
                        <i class="bx bx-rss me-1"></i> Live Activity Feed (Seluruh Toko)
                    </h6>
                    <a href="{{ route('superadmin.activity-logs') }}" class="btn btn-link btn-sm text-indigo p-0">Lihat Semua</a>
                </div>
                <div class="card-body" style="max-height: 380px; overflow-y: auto;">
                    @forelse ($activities as $act)
                        <div class="d-flex align-items-start gap-3 mb-3 border-bottom pb-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                style="width:36px;height:36px;background:var(--bs-{{ $act->color }}-bg-subtle);">
                                <i class="material-icons-outlined text-{{ $act->color }} fs-5">{{ $act->icon }}</i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <span class="badge bg-{{ $act->color }}-subtle text-{{ $act->color }} border-0 px-2 py-0.5 mb-1" style="font-size: 10px;">{{ $act->type }}</span>
                                    <small class="text-muted" style="font-size: 11px;">{{ \Carbon\Carbon::parse($act->timestamp)->diffForHumans() }}</small>
                                </div>
                                <p class="mb-0 text-slate-700" style="font-size: 13px;">{!! $act->message !!}</p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted">
                            <i class="bx bx-loader-alt bx-spin fs-2 mb-2 d-block"></i>
                            Belum ada aktivitas tercatat hari ini.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Top Stores Table --}}
    <div class="row g-3">
        <div class="col-12">
            <div class="card rounded-4 border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-3 pb-2">
                    <h6 class="fw-bold mb-0 text-indigo">
                        <i class="bx bx-list-check me-1"></i> Peringkat Penjualan & Kesehatan Toko (Bulan Ini)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Peringkat</th>
                                    <th>Nama Toko</th>
                                    <th class="text-end">Jumlah Transaksi</th>
                                    <th class="text-end">Total Penjualan</th>
                                    <th class="text-end">Rata-rata Keranjang (Basket Size)</th>
                                    <th class="text-center">Status Langganan</th>
                                    <th class="text-center pe-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($topStores as $i => $store)
                                    <tr>
                                        <td class="ps-4 fw-bold text-indigo" style="font-size: 15px;">#{{ $i + 1 }}</td>
                                        <td>
                                            <div class="fw-semibold text-slate-800">{{ $store->name }}</div>
                                            @if(!$store->is_active)
                                                <small class="text-danger d-block">Toko Dinonaktifkan</small>
                                            @else
                                                <small class="text-muted d-block">Multi-store POS</small>
                                            @endif
                                        </td>
                                        <td class="text-end fw-semibold">{{ number_format($store->count_trx) }}</td>
                                        <td class="text-end fw-semibold text-indigo">Rp {{ number_format($store->total_sales, 0, ',', '.') }}</td>
                                        <td class="text-end">Rp {{ number_format($store->basket_size, 0, ',', '.') }}</td>
                                        <td class="text-center">
                                            @php
                                                $badgeColor = match($store->sub_status) {
                                                    'active' => 'bg-success',
                                                    'grace_period' => 'bg-warning text-dark',
                                                    'expired' => 'bg-danger',
                                                    default => 'bg-secondary'
                                                };
                                                $statusLabel = match($store->sub_status) {
                                                    'active' => 'Aktif',
                                                    'grace_period' => 'Masa Percobaan',
                                                    'expired' => 'Expired',
                                                    default => 'Non-aktif'
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeColor }} border-0 px-3 py-1.5 rounded-pill">{{ $statusLabel }}</span>
                                        </td>
                                        <td class="text-center pe-3">
                                            <a href="{{ route('superadmin.impersonate', $store->id) }}" class="btn btn-outline-indigo btn-sm fw-bold px-3 rounded-2 shadow-sm">
                                                <i class="bx bx-log-in me-1"></i> Akses Toko
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">Belum ada data toko terdaftar</td>
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

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        // 1. Chart Tren Omzet Global (30 Hari Terakhir)
        var dailyData = @json($dailySales);
        var dailyDates = dailyData.map(d => d.tanggal);
        var dailyTotals = dailyData.map(d => parseFloat(d.total));

        new ApexCharts(document.querySelector("#chartPenjualanGlobal"), {
            chart: {
                type: 'area',
                height: 300,
                toolbar: { show: false },
                fontFamily: 'Noto Sans, sans-serif'
            },
            series: [{
                name: 'Total Omzet (Rp)',
                data: dailyTotals
            }],
            xaxis: {
                categories: dailyDates,
                labels: { rotate: -45, style: { fontSize: '11px' } }
            },
            yaxis: {
                labels: {
                    formatter: v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v)
                }
            },
            colors: ['#4f46e5'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.35,
                    opacityTo: 0.05
                }
            },
            stroke: { curve: 'smooth', width: 3 },
            dataLabels: { enabled: false },
            tooltip: {
                y: {
                    formatter: v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v)
                }
            },
            grid: { borderColor: '#f1f1f1' }
        }).render();

        // 2. Chart Metode Pembayaran Global
        var paymentData = @json($paymentMethods);
        var payLabels = paymentData.map(p => (p.payment_method || 'Lainnya').toUpperCase());
        var payValues = paymentData.map(p => parseFloat(p.total));

        new ApexCharts(document.querySelector("#chartPaymentGlobal"), {
            chart: {
                type: 'donut',
                height: 300,
                fontFamily: 'Noto Sans, sans-serif'
            },
            series: payValues,
            labels: payLabels,
            colors: ['#4f46e5', '#3b82f6', '#10b981', '#f59e0b', '#ef4444'],
            legend: { position: 'bottom' },
            tooltip: {
                y: {
                    formatter: v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v)
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '60%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total Omzet',
                                formatter: w => 'Rp ' + new Intl.NumberFormat('id-ID').format(w.globals.seriesTotals.reduce((a, b) => a + b, 0))
                            }
                        }
                    }
                }
            }
        }).render();

        // 3. Chart Perbandingan Omzet Antar Toko
        var storeData = @json($storeRevenueComparison);
        var storeLabels = storeData.map(s => s.label);
        var storeTotals = storeData.map(s => s.total);

        new ApexCharts(document.querySelector("#chartStoreComparison"), {
            chart: {
                type: 'bar',
                height: 335,
                toolbar: { show: false },
                fontFamily: 'Noto Sans, sans-serif'
            },
            series: [{
                name: 'Total Penjualan (Rp)',
                data: storeTotals
            }],
            xaxis: {
                categories: storeLabels,
                labels: { style: { fontSize: '11px' } }
            },
            yaxis: {
                labels: {
                    formatter: v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v)
                }
            },
            colors: ['#6366f1'],
            plotOptions: {
                bar: {
                    borderRadius: 8,
                    columnWidth: '45%',
                    distributed: true
                }
            },
            legend: { show: false },
            dataLabels: { enabled: false },
            tooltip: {
                y: {
                    formatter: v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v)
                }
            },
            grid: { borderColor: '#f1f1f1' }
        }).render();
    </script>
@endpush

@push('styles')
    <style>
        .text-indigo { color: #4f46e5 !important; }
        .btn-outline-indigo {
            color: #4f46e5;
            border-color: #4f46e5;
        }
        .btn-outline-indigo:hover {
            color: #fff;
            background-color: #4f46e5;
            border-color: #4f46e5;
        }
    </style>
@endpush
