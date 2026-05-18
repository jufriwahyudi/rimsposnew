@extends('layouts.main.main')

@section('title', 'Dashboard')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Dashboard</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Welcome to POS</a></li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card rounded-4 border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                        style="width:50px;height:50px;background:#ede9fe;">
                        <i class="bx bx-package fs-4" style="color:#7c3aed;"></i>
                    </div>
                    <div>
                        <small class="text-muted">Total Produk</small>
                        <h4 class="fw-bold mb-0">{{ number_format($totalProducts) }}</h4>
                        <small class="text-muted">{{ number_format($totalVariants) }} varian aktif</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card rounded-4 border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                        style="width:50px;height:50px;background:#dbeafe;">
                        <i class="bx bx-store fs-4" style="color:#2563eb;"></i>
                    </div>
                    <div>
                        <small class="text-muted">Stok Gudang / Store</small>
                        <h4 class="fw-bold mb-0">{{ number_format($stokGudang) }} <span class="text-muted fs-6">/</span>
                            {{ number_format($stokStore) }}</h4>
                        <small class="text-muted">Total: {{ number_format($stokGudang + $stokStore) }} unit</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card rounded-4 border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                        style="width:50px;height:50px;background:#d1fae5;">
                        <i class="bx bx-cart fs-4" style="color:#059669;"></i>
                    </div>
                    <div>
                        <small class="text-muted">Penjualan Hari Ini</small>
                        <h4 class="fw-bold mb-0">Rp {{ number_format($salesToday->total ?? 0, 0, ',', '.') }}</h4>
                        <small class="text-muted">{{ $salesToday->jumlah ?? 0 }} transaksi</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card rounded-4 border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                        style="width:50px;height:50px;background:#fef3c7;">
                        <i class="bx bx-trending-up fs-4" style="color:#d97706;"></i>
                    </div>
                    <div>
                        <small class="text-muted">Penjualan Bulan Ini</small>
                        <h4 class="fw-bold mb-0">Rp {{ number_format($salesMonth->total ?? 0, 0, ',', '.') }}</h4>
                        <small class="text-muted">{{ $salesMonth->jumlah ?? 0 }} transaksi</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart & Top Products --}}
    <div class="row g-3 mb-4">
        {{-- Trend Penjualan --}}
        <div class="col-xl-8">
            <div class="card rounded-4 border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-3 pb-0">
                    <h6 class="fw-bold mb-0" style="color:#7c3aed;">
                        <i class="bx bx-line-chart me-1"></i> Trend Penjualan 30 Hari Terakhir
                    </h6>
                </div>
                <div class="card-body">
                    <div id="chartPenjualan"></div>
                </div>
            </div>
        </div>

        {{-- Metode Pembayaran --}}
        <div class="col-xl-4">
            <div class="card rounded-4 border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-3 pb-0">
                    <h6 class="fw-bold mb-0" style="color:#7c3aed;">
                        <i class="bx bx-credit-card me-1"></i> Metode Pembayaran Bulan Ini
                    </h6>
                </div>
                <div class="card-body">
                    <div id="chartPayment"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        {{-- Top Produk --}}
        <div class="col-xl-6">
            <div class="card rounded-4 border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-3 pb-0">
                    <h6 class="fw-bold mb-0" style="color:#7c3aed;">
                        <i class="bx bx-trophy me-1"></i> Top 10 Produk Terlaris Bulan Ini
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">#</th>
                                    <th>Produk</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end pe-3">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topProducts as $i => $item)
                                    <tr>
                                        <td class="ps-3">{{ $i + 1 }}</td>
                                        <td>{{ $item->product_name }}</td>
                                        <td class="text-end">{{ number_format($item->total_qty) }}</td>
                                        <td class="text-end pe-3">Rp {{ number_format($item->total_nilai, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">Belum ada data</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Low Stock --}}
        <div class="col-xl-6">
            <div class="card rounded-4 border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-3 pb-0">
                    <h6 class="fw-bold mb-0 text-danger">
                        <i class="bx bx-error-circle me-1"></i> Produk Stok Hampir Habis (≤ 5)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">#</th>
                                    <th>SKU</th>
                                    <th>Produk</th>
                                    <th class="text-end">Gudang</th>
                                    <th class="text-end">Store</th>
                                    <th class="text-end pe-3">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lowStock as $i => $v)
                                    <tr>
                                        <td class="ps-3">{{ $loop->iteration }}</td>
                                        <td><code>{{ $v->sku }}</code></td>
                                        <td>{{ $v->product->nama_produk ?? '-' }}
                                            {{ $v->variant_label ? '- ' . $v->variant_label : '' }}</td>
                                        <td class="text-end">{{ $v->stok_warehouse }}</td>
                                        <td class="text-end">{{ $v->stok_store }}</td>
                                        <td class="text-end pe-3">
                                            <span
                                                class="badge {{ $v->stok_total <= 0 ? 'bg-danger' : 'bg-warning text-dark' }}">{{ $v->stok_total }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">Semua stok aman</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stok Keluar --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card rounded-4 border-0 shadow-sm">
                <div
                    class="card-header bg-transparent border-0 pt-3 pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <h6 class="fw-bold mb-0" style="color:#7c3aed;">
                        <i class="bx bx-log-out-circle me-1"></i> Produk Stok Keluar
                    </h6>
                    <div class="d-flex align-items-center gap-2">
                        <input type="date" id="filterTanggalStockOut" class="form-control form-control-sm"
                            style="width:180px;" value="{{ date('Y-m-d') }}">
                        <button class="btn btn-sm btn-primary" id="btnFilterStockOut"><i
                                class="fa fa-search"></i></button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">#</th>
                                    <th>SKU</th>
                                    <th>Produk</th>
                                    <th>Lokasi</th>
                                    <th>Referensi</th>
                                    <th class="text-end pe-3">Qty Keluar</th>
                                </tr>
                            </thead>
                            <tbody id="stockOutBody">
                                @forelse($stockOutToday as $i => $so)
                                    <tr>
                                        <td class="ps-3">{{ $i + 1 }}</td>
                                        <td><code>{{ $so['sku'] }}</code></td>
                                        <td>{{ $so['produk'] }}</td>
                                        <td>{{ $so['posisi'] }}</td>
                                        <td>{{ $so['ref_type'] }}</td>
                                        <td class="text-end pe-3"><span
                                                class="badge bg-danger">{{ number_format($so['total_qty']) }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">Tidak ada stok keluar</td>
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
        // Trend Penjualan Harian
        var dailyData = @json($dailySales);
        var categories = dailyData.map(d => d.tanggal);
        var totals = dailyData.map(d => parseFloat(d.total));
        var counts = dailyData.map(d => parseInt(d.jumlah));

        new ApexCharts(document.querySelector("#chartPenjualan"), {
            chart: {
                type: 'area',
                height: 320,
                toolbar: {
                    show: false
                },
                fontFamily: 'Noto Sans, sans-serif'
            },
            series: [{
                    name: 'Omzet (Rp)',
                    data: totals
                },
                {
                    name: 'Transaksi',
                    data: counts
                }
            ],
            xaxis: {
                categories: categories,
                labels: {
                    rotate: -45,
                    style: {
                        fontSize: '11px'
                    }
                }
            },
            yaxis: [{
                    title: {
                        text: 'Omzet (Rp)'
                    },
                    labels: {
                        formatter: v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v)
                    }
                },
                {
                    opposite: true,
                    title: {
                        text: 'Transaksi'
                    },
                    labels: {
                        formatter: v => Math.round(v)
                    }
                }
            ],
            colors: ['#7c3aed', '#059669'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.4,
                    opacityTo: 0.1
                }
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            dataLabels: {
                enabled: false
            },
            tooltip: {
                y: {
                    formatter: function(val, {
                        seriesIndex
                    }) {
                        return seriesIndex === 0 ? 'Rp ' + new Intl.NumberFormat('id-ID').format(val) : val +
                            ' trx';
                    }
                }
            },
            grid: {
                borderColor: '#f1f1f1'
            }
        }).render();

        // Metode Pembayaran
        var paymentData = @json($paymentMethods);
        var payLabels = paymentData.map(p => (p.payment_method || 'Lainnya').charAt(0).toUpperCase() + (p.payment_method ||
            'lainnya').slice(1));
        var payValues = paymentData.map(p => parseFloat(p.total));

        new ApexCharts(document.querySelector("#chartPayment"), {
            chart: {
                type: 'donut',
                height: 320,
                fontFamily: 'Noto Sans, sans-serif'
            },
            series: payValues,
            labels: payLabels,
            colors: ['#7c3aed', '#2563eb', '#059669', '#d97706', '#dc2626'],
            legend: {
                position: 'bottom'
            },
            tooltip: {
                y: {
                    formatter: v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v)
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '55%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                formatter: w => 'Rp ' + new Intl.NumberFormat('id-ID').format(w.globals.seriesTotals
                                    .reduce((a, b) => a + b, 0))
                            }
                        }
                    }
                }
            }
        }).render();

        // Filter Stok Keluar
        $('#btnFilterStockOut').on('click', function() {
            var tanggal = $('#filterTanggalStockOut').val();
            if (!tanggal) return;

            var $body = $('#stockOutBody');
            $body.html(
                '<tr><td colspan="6" class="text-center py-3"><i class="fa fa-spinner fa-spin"></i> Memuat...</td></tr>'
            );

            $.ajax({
                url: "{{ route('dashboard.stockout') }}",
                type: "GET",
                data: {
                    tanggal: tanggal
                },
                success: function(data) {
                    if (data.length === 0) {
                        $body.html(
                            '<tr><td colspan="6" class="text-center text-muted py-3">Tidak ada stok keluar</td></tr>'
                        );
                        return;
                    }
                    var html = '';
                    data.forEach(function(item, i) {
                        html += '<tr>' +
                            '<td class="ps-3">' + (i + 1) + '</td>' +
                            '<td><code>' + item.sku + '</code></td>' +
                            '<td>' + item.produk + '</td>' +
                            '<td>' + item.posisi + '</td>' +
                            '<td>' + item.ref_type + '</td>' +
                            '<td class="text-end pe-3"><span class="badge bg-danger">' +
                            new Intl.NumberFormat('id-ID').format(item.total_qty) +
                            '</span></td>' +
                            '</tr>';
                    });
                    $body.html(html);
                },
                error: function() {
                    $body.html(
                        '<tr><td colspan="6" class="text-center text-danger py-3">Gagal memuat data</td></tr>'
                    );
                }
            });
        });
    </script>
@endpush
