@extends('layouts.main.main')
@section('title', 'Detail Shift Kasir #' . $register->id)

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Kasir & Laporan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('cash-registers.index') }}">Daftar Cash Register</a></li>
                    <li class="breadcrumb-item active">Detail Sesi #{{ $register->id }}</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row g-3">
        {{-- LEFT COLUMN: SHIFT INFORMATION & FINANCIAL RECONCILIATION --}}
        <div class="col-lg-5">
            <div class="card rounded-4 shadow-sm border-0 mb-4">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <div class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary me-2">
                            <i class="bi bi-clock-history fs-5"></i>
                        </div>
                        <h6 class="fw-bold mb-0 text-dark">Shift Kasir #{{ $register->id }}</h6>
                    </div>
                    <span class="badge {{ $register->status === 'open' ? 'bg-warning text-dark' : 'bg-secondary' }} px-3 py-2 rounded-pill">
                        <i class="bi bi-{{ $register->status === 'open' ? 'door-open' : 'door-closed' }} me-1"></i>
                        {{ $register->status === 'open' ? 'Sesi Aktif (Buka)' : 'Selesai (Ditutup)' }}
                    </span>
                </div>

                <div class="card-body">
                    {{-- META TABLE --}}
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless align-middle mb-3 small">
                            <tr>
                                <td class="text-secondary" width="40%"><i class="bi bi-shop me-1"></i> Toko / Cabang</td>
                                <td class="fw-bold text-dark">{{ $register->store?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary"><i class="bi bi-person me-1"></i> Kasir Pembuka</td>
                                <td class="fw-semibold text-dark">{{ $register->cashier?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary"><i class="bi bi-calendar-event me-1"></i> Waktu Buka</td>
                                <td>{{ $register->opened_at ? $register->opened_at->format('d/m/Y H:i:s') : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary"><i class="bi bi-calendar-check me-1"></i> Waktu Tutup</td>
                                <td>
                                    @if($register->status === 'open')
                                        <span class="badge bg-success-subtle text-success border border-success">Masih Berjalan</span>
                                    @else
                                        {{ $register->closed_at ? $register->closed_at->format('d/m/Y H:i:s') : '-' }}
                                    @endif
                                </td>
                            </tr>
                            @if($register->closedBy)
                                <tr>
                                    <td class="text-secondary"><i class="bi bi-person-check me-1"></i> Ditutup Oleh</td>
                                    <td class="fw-semibold text-dark">{{ $register->closedBy->name }}</td>
                                </tr>
                            @endif
                        </table>
                    </div>

                    <div class="border-top my-3"></div>

                    {{-- FINANCIAL BREAKDOWN --}}
                    <h6 class="fw-bold text-dark mb-2 d-flex align-items-center small">
                        <i class="bi bi-calculator text-primary me-2 fs-6"></i> Rincian Rekonsiliasi Kas
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle small mb-3">
                            <tbody>
                                <tr>
                                    <td class="text-secondary">Modal Kas Awal</td>
                                    <td class="text-end fw-semibold">Rp {{ number_format($summary['opening_cash'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-secondary">Penjualan Tunai (+)</td>
                                    <td class="text-end text-success fw-semibold">+ Rp {{ number_format($summary['cash_sales'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-secondary">Kas Masuk Manual (+)</td>
                                    <td class="text-end text-success fw-semibold">+ Rp {{ number_format($summary['cash_in'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-secondary">Kas Keluar Toko (-)</td>
                                    <td class="text-end text-danger fw-semibold">- Rp {{ number_format($summary['cash_out'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-secondary">Refund Tunai (-)</td>
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
                                    <td>Selisih Kas Fisik</td>
                                    <td class="text-end">
                                        @if(($summary['cash_difference'] ?? 0) == 0)
                                            <i class="bi bi-check-circle-fill me-1"></i> Pas (Rp 0)
                                        @elseif(($summary['cash_difference'] ?? 0) > 0)
                                            <i class="bi bi-arrow-up-circle-fill me-1"></i> +Rp {{ number_format($summary['cash_difference'], 0, ',', '.') }} (Lebih)
                                        @else
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i> -Rp {{ number_format(abs($summary['cash_difference']), 0, ',', '.') }} (Kurang)
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-secondary">Penjualan Non-Tunai (Transfer / QRIS)</td>
                                    <td class="text-end text-primary fw-semibold">Rp {{ number_format($summary['non_cash_sales'], 0, ',', '.') }}</td>
                                </tr>
                                <tr class="table-secondary fw-bold">
                                    <td>Total Seluruh Penjualan</td>
                                    <td class="text-end">Rp {{ number_format($summary['total_sales_amount'] ?? ($summary['cash_sales'] + $summary['non_cash_sales']), 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- DENOMINATIONS --}}
                    @if($register->denominations && count($register->denominations) > 0)
                        <h6 class="fw-bold text-dark mt-3 mb-2 small d-flex align-items-center">
                            <i class="bi bi-cash-stack text-success me-2 fs-6"></i> Rincian Pecahan Uang Fisik
                        </h6>
                        <div class="row g-1 mb-3">
                            @foreach($register->denominations as $denom => $count)
                                <div class="col-6">
                                    <div class="p-2 border rounded bg-light d-flex justify-content-between" style="font-size: 11px;">
                                        <span class="text-muted">Rp {{ number_format($denom, 0, ',', '.') }} &times; {{ $count }}</span>
                                        <strong class="text-dark">Rp {{ number_format($denom * $count, 0, ',', '.') }}</strong>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- NOTES --}}
                    @if($register->notes)
                        <div class="alert alert-light border rounded-3 p-2 mt-3 mb-3">
                            <strong class="small d-block text-secondary mb-1"><i class="bi bi-chat-left-text me-1"></i> Catatan Kasir:</strong>
                            <p class="small mb-0 text-dark">{{ $register->notes }}</p>
                        </div>
                    @endif

                    {{-- ACTION BUTTONS --}}
                    <div class="d-flex flex-column gap-2 mt-4">
                        <button type="button" class="btn btn-dark w-100 rounded-3 d-flex align-items-center justify-content-center py-2" onclick="previewShiftReceipt({{ $register->id }})">
                            <i class="bi bi-eye me-2"></i> Preview Cetakan Tutup Kasir
                        </button>
                        <div class="d-flex gap-2">
                            <a href="{{ route('cash-registers.print', $register->id) }}" target="_blank" class="btn btn-outline-primary w-100 rounded-3 d-flex align-items-center justify-content-center">
                                <i class="bi bi-printer me-1"></i> Cetak Struk
                            </a>
                            <a href="{{ route('cash-registers.index') }}" class="btn btn-outline-secondary rounded-3 px-3">
                                Kembali
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN: TABBED TRANSACTION DETAILS --}}
        <div class="col-lg-7">
            @php
                $nonCashTxs = $register->cashTransactions->where('transaction_type', 'sale')->where('payment_method', '!=', 'cash');
                $nonCashBreakdown = $reportData['financial']['non_cash_breakdown'] ?? [];
            @endphp

            <div class="card rounded-4 shadow-sm border-0">
                <div class="card-header bg-transparent p-3 border-bottom">
                    <ul class="nav nav-pills card-header-pills gap-2 flex-wrap" id="shiftDetailTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-pill px-3 py-1 small" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales-content" type="button" role="tab">
                                <i class="bi bi-receipt me-1"></i> Seluruh Penjualan ({{ $register->sales->count() }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill px-3 py-1 small" id="non-cash-tab" data-bs-toggle="tab" data-bs-target="#non-cash-content" type="button" role="tab">
                                <i class="bi bi-credit-card-2-front me-1"></i> Transaksi Non-Tunai ({{ $nonCashTxs->count() }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill px-3 py-1 small" id="cash-tab" data-bs-toggle="tab" data-bs-target="#cash-content" type="button" role="tab">
                                <i class="bi bi-arrow-left-right me-1"></i> Kas Masuk/Keluar ({{ $register->cashTransactions->where('transaction_type', '!=', 'sale')->count() }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill px-3 py-1 small" id="menu-tab" data-bs-toggle="tab" data-bs-target="#menu-content" type="button" role="tab">
                                <i class="bi bi-box-seam me-1"></i> Rekap Produk ({{ $reportData['menu_sales']['total_qty'] ?? 0 }})
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body p-0">
                    <div class="tab-content" id="shiftDetailTabContent">
                        {{-- TAB 1: SELURUH PENJUALAN --}}
                        <div class="tab-pane fade show active" id="sales-content" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0 align-middle small">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Waktu</th>
                                            <th>Pelanggan</th>
                                            <th>Metode Bayar</th>
                                            <th class="text-end">Total</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $totalTunaiInTab = 0;
                                            $totalNonTunaiInTab = 0;
                                            $grandTotalInTab = 0;
                                        @endphp
                                        @forelse($register->sales as $sale)
                                            @php
                                                $grandTotalInTab += $sale->grand_total;
                                                $isCash = in_array(strtolower($sale->payment_method ?? 'cash'), ['cash', 'tunai']);
                                                if ($isCash) {
                                                    $totalTunaiInTab += $sale->grand_total;
                                                } else {
                                                    $totalNonTunaiInTab += $sale->grand_total;
                                                }
                                            @endphp
                                            <tr>
                                                <td>
                                                    <a href="{{ route('sales.show', $sale->id) }}" class="fw-bold text-primary text-decoration-none">
                                                        {{ $sale->invoice_number }}
                                                    </a>
                                                </td>
                                                <td>{{ $sale->sale_date ? $sale->sale_date->format('H:i') : '-' }}</td>
                                                <td>{{ $sale->customer_name ?: ($sale->customer?->name ?? 'Umum') }}</td>
                                                <td>
                                                    <span class="badge {{ $isCash ? 'bg-success-subtle text-success border border-success' : 'bg-primary-subtle text-primary border border-primary' }}">
                                                        {{ strtoupper($sale->payment_method ?? 'CASH') }}
                                                    </span>
                                                </td>
                                                <td class="text-end fw-semibold">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</td>
                                                <td class="text-center">
                                                    <span class="badge bg-{{ $sale->payment_status === 'lunas' ? 'success' : 'warning text-dark' }}">
                                                        {{ ucfirst($sale->payment_status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-5 text-muted">
                                                    <i class="bi bi-inbox fs-2 d-block mb-1"></i>
                                                    Belum ada transaksi penjualan pada shift ini.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    @if($register->sales->isNotEmpty())
                                        <tfoot class="table-light">
                                            <tr class="fw-semibold text-secondary">
                                                <td colspan="4" class="text-end">Total Penjualan Tunai:</td>
                                                <td class="text-end text-success">Rp {{ number_format($summary['cash_sales'] ?? $totalTunaiInTab, 0, ',', '.') }}</td>
                                                <td></td>
                                            </tr>
                                            <tr class="fw-semibold text-secondary">
                                                <td colspan="4" class="text-end">Total Penjualan Non-Tunai:</td>
                                                <td class="text-end text-primary">Rp {{ number_format($summary['non_cash_sales'] ?? $totalNonTunaiInTab, 0, ',', '.') }}</td>
                                                <td></td>
                                            </tr>
                                            <tr class="table-secondary fw-bold fs-6">
                                                <td colspan="4" class="text-end text-dark">TOTAL KESELURUHAN PENJUALAN:</td>
                                                <td class="text-end text-dark">Rp {{ number_format($summary['total_sales_amount'] ?? $grandTotalInTab, 0, ',', '.') }}</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    @endif
                                </table>
                            </div>
                        </div>

                        {{-- TAB 2: DETAIL TRANSAKSI NON-TUNAI --}}
                        <div class="tab-pane fade" id="non-cash-content" role="tabpanel">
                            {{-- NON-CASH SUMMARY CARDS --}}
                            @if(!empty($nonCashBreakdown) && count($nonCashBreakdown) > 0)
                                <div class="p-3 bg-light border-bottom">
                                    <h6 class="fw-bold mb-2 small text-dark"><i class="bi bi-pie-chart-fill text-primary me-1"></i> Rekapitulasi per Metode / Bank</h6>
                                    <div class="row g-2">
                                        @foreach($nonCashBreakdown as $nc)
                                            <div class="col-sm-6 col-md-4">
                                                <div class="p-2 border rounded-3 bg-white shadow-sm">
                                                    <small class="text-muted d-block fw-semibold">{{ $nc['name'] }}</small>
                                                    <span class="fs-6 fw-bold text-primary">Rp {{ number_format($nc['amount'], 0, ',', '.') }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0 align-middle small">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Waktu</th>
                                            <th>Invoice / Ref</th>
                                            <th>Metode Bayar</th>
                                            <th>Rekening / Bank</th>
                                            <th>Keterangan</th>
                                            <th class="text-end">Nominal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $totalNonCashList = 0; @endphp
                                        @forelse($nonCashTxs as $tx)
                                            @php
                                                $totalNonCashList += $tx->amount;
                                                $saleRef = $tx->ref_id ? $register->sales->firstWhere('id', $tx->ref_id) : null;
                                                $bankName = $tx->rekening?->bank_rek ?? $tx->rekening?->bank_name ?? '-';
                                                $accName  = $tx->rekening?->nama_rek ?? $tx->rekening?->account_name ?? '';
                                            @endphp
                                            <tr>
                                                <td>{{ $tx->transaction_date ? $tx->transaction_date->format('H:i') : '-' }}</td>
                                                <td>
                                                    @if($saleRef)
                                                        <a href="{{ route('sales.show', $saleRef->id) }}" class="fw-bold text-primary text-decoration-none">
                                                            {{ $saleRef->invoice_number }}
                                                        </a>
                                                        <small class="d-block text-muted">{{ $saleRef->customer_name ?: ($saleRef->customer?->name ?? 'Umum') }}</small>
                                                    @else
                                                        <span class="text-dark fw-semibold">Ref #{{ $tx->ref_id ?? '-' }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary-subtle text-primary border border-primary">
                                                        {{ strtoupper($tx->payment_method ?? 'NON-TUNAI') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-semibold text-dark">{{ $bankName }}</span>
                                                    @if(!empty($accName))
                                                        <small class="d-block text-muted">{{ $accName }}</small>
                                                    @endif
                                                </td>
                                                <td>{{ $tx->notes ?? '-' }}</td>
                                                <td class="text-end fw-bold text-primary">Rp {{ number_format($tx->amount, 0, ',', '.') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-5 text-muted">
                                                    <i class="bi bi-credit-card fs-2 d-block mb-1"></i>
                                                    Tidak ada transaksi non-tunai pada shift ini.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    @if($nonCashTxs->isNotEmpty())
                                        <tfoot class="table-secondary fw-bold fs-6">
                                            <tr>
                                                <td colspan="5" class="text-end text-dark">TOTAL TRANSAKSI NON-TUNAI:</td>
                                                <td class="text-end text-primary">Rp {{ number_format($summary['non_cash_sales'] ?? $totalNonCashList, 0, ',', '.') }}</td>
                                            </tr>
                                        </tfoot>
                                    @endif
                                </table>
                            </div>
                        </div>

                        {{-- TAB 3: KAS MASUK & KAS KELUAR MANUAL --}}
                        <div class="tab-pane fade" id="cash-content" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0 align-middle small">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Waktu</th>
                                            <th>Jenis</th>
                                            <th>Keterangan</th>
                                            <th class="text-end">Nominal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $totalKasMasuk = 0;
                                            $totalKasKeluar = 0;
                                        @endphp
                                        @forelse($register->cashTransactions->where('transaction_type', '!=', 'sale') as $tx)
                                            @php
                                                if ($tx->direction === 'in') {
                                                    $totalKasMasuk += $tx->amount;
                                                } else {
                                                    $totalKasKeluar += $tx->amount;
                                                }
                                            @endphp
                                            <tr>
                                                <td>{{ $tx->transaction_date ? $tx->transaction_date->format('H:i') : '-' }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $tx->direction === 'in' ? 'success' : 'danger' }}">
                                                        <i class="bi bi-arrow-{{ $tx->direction === 'in' ? 'down' : 'up' }}-circle me-1"></i>
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
                                                <td colspan="4" class="text-center py-5 text-muted">
                                                    <i class="bi bi-arrow-left-right fs-2 d-block mb-1"></i>
                                                    Tidak ada pergerakan kas manual pada shift ini.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    @if($register->cashTransactions->where('transaction_type', '!=', 'sale')->isNotEmpty())
                                        <tfoot class="table-light">
                                            <tr class="fw-semibold text-secondary">
                                                <td colspan="3" class="text-end">Total Kas Masuk Manual:</td>
                                                <td class="text-end text-success">+ Rp {{ number_format($summary['cash_in'] ?? $totalKasMasuk, 0, ',', '.') }}</td>
                                            </tr>
                                            <tr class="fw-semibold text-secondary">
                                                <td colspan="3" class="text-end">Total Kas Keluar Toko:</td>
                                                <td class="text-end text-danger">- Rp {{ number_format($summary['cash_out'] ?? $totalKasKeluar, 0, ',', '.') }}</td>
                                            </tr>
                                        </tfoot>
                                    @endif
                                </table>
                            </div>
                        </div>

                        {{-- TAB 4: REKAP PRODUK TERJUAL --}}
                        <div class="tab-pane fade" id="menu-content" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0 align-middle small">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%">No</th>
                                            <th>Nama Produk / Menu</th>
                                            <th class="text-end">Harga Satuan</th>
                                            <th class="text-center">Jumlah Terjual (Qty)</th>
                                            <th class="text-end">Total Harga</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $items = $reportData['menu_sales']['items'] ?? [];
                                            $totalQty = 0;
                                            $totalAmount = 0;
                                        @endphp
                                        @forelse($items as $idx => $item)
                                            @php
                                                $qty = (float) ($item['qty'] ?? 0);
                                                $price = (float) ($item['price'] ?? 0);
                                                $subtotal = isset($item['subtotal']) && $item['subtotal'] > 0 ? (float) $item['subtotal'] : ($qty * $price);
                                                $totalQty += $qty;
                                                $totalAmount += $subtotal;
                                            @endphp
                                            <tr>
                                                <td>{{ $idx + 1 }}</td>
                                                <td class="fw-semibold text-dark">{{ $item['name'] }}</td>
                                                <td class="text-end text-muted">Rp {{ number_format($price, 0, ',', '.') }}</td>
                                                <td class="text-center fw-bold text-dark">{{ number_format($qty, 0) }}</td>
                                                <td class="text-end fw-bold text-primary">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-5 text-muted">
                                                    <i class="bi bi-box fs-2 d-block mb-1"></i>
                                                    Belum ada data penjualan produk.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    @if(!empty($items))
                                        <tfoot class="table-secondary fw-bold fs-6">
                                            <tr>
                                                <td colspan="3" class="text-end text-dark">TOTAL KESELURUHAN ITEM TERJUAL:</td>
                                                <td class="text-center text-dark">{{ number_format($totalQty, 0) }}</td>
                                                <td class="text-end text-primary">Rp {{ number_format($totalAmount, 0, ',', '.') }}</td>
                                            </tr>
                                        </tfoot>
                                    @endif
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL PREVIEW CETAKAN TUTUP KASIR --}}
    <div class="modal fade" id="modalPreviewReceipt" tabindex="-1" aria-labelledby="modalPreviewReceiptLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 480px;">
            <div class="modal-content rounded-4 shadow-lg border-0">
                <div class="modal-header bg-dark text-white border-0 py-3">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-printer-fill fs-5 me-2 text-warning"></i>
                        <div>
                            <h6 class="modal-title fw-bold mb-0" id="modalPreviewReceiptLabel">Preview Cetakan Tutup Kasir</h6>
                            <small class="text-white-50" style="font-size: 11px;">Format Struk Thermal Kasir</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                {{-- PAPER WIDTH TOGGLE --}}
                <div class="px-3 py-2 bg-light border-bottom d-flex justify-content-between align-items-center">
                    <span class="small text-muted fw-bold">Ukuran Kertas:</span>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary active" id="btnPaper80" onclick="setReceiptWidth('80mm')">80mm</button>
                        <button type="button" class="btn btn-outline-secondary" id="btnPaper58" onclick="setReceiptWidth('58mm')">58mm</button>
                    </div>
                </div>

                <div class="modal-body p-3 bg-slate-100" id="previewModalContentContainer" style="background-color: #e2e8f0; min-height: 250px;">
                    <div class="text-center py-5" id="previewLoadingSpinner">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Memuat...</span>
                        </div>
                        <p class="small text-muted mt-2">Memuat struk cetakan tutup kasir...</p>
                    </div>
                </div>

                <div class="modal-footer bg-light border-top d-flex justify-content-between">
                    <a href="{{ route('cash-registers.print', $register->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Buka Tab Baru
                    </a>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="button" class="btn btn-sm btn-primary px-3" onclick="printReceiptFrame()">
                            <i class="bi bi-printer me-1"></i> Cetak Struk
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let currentRegisterId = {{ $register->id }};

        /**
         * Open Thermal Receipt Preview Modal
         */
        function previewShiftReceipt(registerId) {
            currentRegisterId = registerId;
            const modalEl = document.getElementById('modalPreviewReceipt');
            const modal = new bootstrap.Modal(modalEl);
            const container = $('#previewModalContentContainer');

            // Show Spinner
            container.html(`
                <div class="text-center py-5" id="previewLoadingSpinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Memuat...</span>
                    </div>
                    <p class="small text-muted mt-2">Memuat struk cetakan tutup kasir...</p>
                </div>
            `);

            modal.show();

            // Load Partial Preview via AJAX
            $.ajax({
                url: "{{ url('/cash-registers/preview') }}/" + registerId,
                type: 'GET',
                success: function (html) {
                    container.html(html);
                },
                error: function (xhr) {
                    container.html(`
                        <div class="alert alert-danger my-3">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            Gagal memuat pratinjau struk: ${xhr.responseJSON?.message || 'Terjadi kesalahan pada server.'}
                        </div>
                    `);
                }
            });
        }

        /**
         * Switch paper width inside modal preview
         */
        function setReceiptWidth(size) {
            const receipt = $('#printableReceiptContent');
            if (size === '58mm') {
                receipt.removeClass('paper-80mm').addClass('paper-58mm');
                $('#btnPaper58').addClass('active');
                $('#btnPaper80').removeClass('active');
            } else {
                receipt.removeClass('paper-58mm').addClass('paper-80mm');
                $('#btnPaper80').addClass('active');
                $('#btnPaper58').removeClass('active');
            }
        }

        /**
         * Print directly from the modal preview
         */
        function printReceiptFrame() {
            if (!currentRegisterId) return;
            const printUrl = "{{ url('/cash-registers/print') }}/" + currentRegisterId;
            
            const printWindow = window.open(printUrl, '_blank', 'width=450,height=600');
            if (printWindow) {
                printWindow.focus();
            }
        }
    </script>
@endpush
