<div class="card rounded-4 border-0 shadow-sm p-3">
    @php
        $isSameDay = false;
        try {
            $isSameDay = \Carbon\Carbon::parse($startDateTime)->isSameDay(\Carbon\Carbon::parse($endDateTime));
        } catch (\Throwable $e) {}

        $exportParams = [
            'store_id' => $storeId,
            'mulai' => $startDateTime,
            'akhir' => $endDateTime,
            'shift_id' => $shiftId ?? '',
            'user_id' => $userId ?? '',
            'search' => $search ?? '',
        ];
    @endphp

    {{-- Header & Export Actions --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 border-bottom pb-3">
        <div>
            <h5 class="fw-bold mb-1 text-danger">
                <i class="bi bi-wallet2 me-2"></i>Rekapitulasi Kas Keluar: {{ $storeName }}
            </h5>
            <div class="d-flex align-items-center gap-2 flex-wrap text-muted small">
                <span>
                    <i class="bi bi-calendar-event me-1"></i>Periode: 
                    <strong>{{ \Carbon\Carbon::parse($startDateTime)->format('d M Y H:i') }}</strong> s/d 
                    <strong>{{ \Carbon\Carbon::parse($endDateTime)->format('d M Y H:i') }}</strong>
                </span>
                @if (!empty($shiftId))
                    <span class="badge bg-secondary-subtle text-secondary border">Shift #{{ $shiftId }}</span>
                @endif
                @if (!empty($userId))
                    <span class="badge bg-info-subtle text-info border">Kasir ID: {{ $userId }}</span>
                @endif
                @if (!empty($search))
                    <span class="badge bg-warning-subtle text-dark border">Kata Kunci: "{{ $search }}"</span>
                @endif
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('laporan.kas_keluar.export', $exportParams) }}"
                class="btn btn-sm btn-success d-flex align-items-center gap-1 shadow-sm px-3">
                <i class="bi bi-file-earmark-excel fs-6"></i>
                <span>Export Excel</span>
            </a>
            <a href="{{ route('laporan.kas_keluar.cetak', $exportParams) }}"
                target="_blank"
                class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1 shadow-sm px-3">
                <i class="bi bi-printer fs-6"></i>
                <span>Cetak / PDF</span>
            </a>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="row g-3 mb-4">
        {{-- Total Kas Keluar --}}
        <div class="col-xl-3 col-md-6 col-12">
            <div class="stat-card border border-danger-subtle bg-danger-subtle p-3 h-100 shadow-sm d-flex align-items-center gap-3">
                <div class="rounded-circle bg-danger text-white p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                    <i class="material-icons-outlined fs-2">arrow_outward</i>
                </div>
                <div>
                    <span class="text-danger-emphasis small fw-semibold text-uppercase tracking-wider">Total Kas Keluar</span>
                    <h4 class="fw-bold text-danger mb-0 mt-1">Rp {{ number_format($totalNominal, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>

        {{-- Total Frekuensi Transaksi --}}
        <div class="col-xl-3 col-md-6 col-12">
            <div class="stat-card border border-primary-subtle bg-primary-subtle p-3 h-100 shadow-sm d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary text-white p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                    <i class="material-icons-outlined fs-2">receipt_long</i>
                </div>
                <div>
                    <span class="text-primary-emphasis small fw-semibold text-uppercase tracking-wider">Frekuensi Transaksi</span>
                    <h4 class="fw-bold text-primary mb-0 mt-1">{{ number_format($totalTransaksi, 0, ',', '.') }} <small class="fs-6 fw-normal">kali</small></h4>
                </div>
            </div>
        </div>

        {{-- Rata-Rata per Transaksi --}}
        <div class="col-xl-3 col-md-6 col-12">
            <div class="stat-card border border-purple-subtle p-3 h-100 shadow-sm d-flex align-items-center gap-3" style="background-color: #f3e8ff;">
                <div class="rounded-circle text-white p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; background-color: #7c3aed;">
                    <i class="material-icons-outlined fs-2">analytics</i>
                </div>
                <div>
                    <span class="small fw-semibold text-uppercase tracking-wider" style="color: #6b21a8;">Rata-Rata Transaksi</span>
                    <h4 class="fw-bold mb-0 mt-1" style="color: #7c3aed;">Rp {{ number_format($rataRata, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>

        {{-- Pengeluaran Terbesar --}}
        <div class="col-xl-3 col-md-6 col-12">
            <div class="stat-card border border-warning-subtle bg-warning-subtle p-3 h-100 shadow-sm d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning text-dark p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                    <i class="material-icons-outlined fs-2">trending_up</i>
                </div>
                <div>
                    <span class="text-warning-emphasis small fw-semibold text-uppercase tracking-wider">Pengeluaran Max</span>
                    <h4 class="fw-bold text-dark mb-0 mt-1">Rp {{ number_format($maxNominal, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- Collapsible Breakdown: Ringkasan Kasir & Shift --}}
    @if ($transactions->isNotEmpty())
        <div class="accordion mb-4" id="accordionSummary">
            <div class="accordion-item rounded-3 border">
                <h2 class="accordion-header" id="headingSummary">
                    <button class="accordion-button collapsed py-2 px-3 bg-light text-dark fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSummary" aria-expanded="false" aria-controls="collapseSummary">
                        <i class="bi bi-pie-chart-fill text-danger me-2"></i> Lihat Ringkasan per Kasir & per Sesi Shift ({{ $byCashier->count() }} Kasir, {{ $byShift->count() }} Shift)
                    </button>
                </h2>
                <div id="collapseSummary" class="accordion-collapse collapse" aria-labelledby="headingSummary" data-bs-parent="#accordionSummary">
                    <div class="accordion-body p-3">
                        <div class="row g-3">
                            {{-- Ringkasan per Kasir --}}
                            <div class="col-md-6 col-12">
                                <h6 class="fw-bold text-secondary mb-2"><i class="bi bi-people me-1"></i> Pengeluaran per Kasir</h6>
                                <div class="table-responsive border rounded-3">
                                    <table class="table table-sm table-striped align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nama Kasir</th>
                                                <th class="text-center">Jml Transaksi</th>
                                                <th class="text-end">Total (Rp)</th>
                                                <th class="text-center" style="width: 80px;">Porsi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($byCashier as $c)
                                                <tr>
                                                    <td class="fw-medium">{{ $c->name }}</td>
                                                    <td class="text-center">{{ $c->count }}x</td>
                                                    <td class="text-end fw-semibold text-danger">Rp {{ number_format($c->total, 0, ',', '.') }}</td>
                                                    <td class="text-center">
                                                        <span class="badge bg-danger-subtle text-danger">{{ $c->percentage }}%</span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Ringkasan per Shift --}}
                            <div class="col-md-6 col-12">
                                <h6 class="fw-bold text-secondary mb-2"><i class="bi bi-clock-history me-1"></i> Pengeluaran per Sesi Shift</h6>
                                <div class="table-responsive border rounded-3" style="max-height: 250px; overflow-y: auto;">
                                    <table class="table table-sm table-striped align-middle mb-0">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th>Shift</th>
                                                <th>Waktu Shift</th>
                                                <th class="text-center">Jml</th>
                                                <th class="text-end">Total (Rp)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($byShift as $s)
                                                <tr>
                                                    <td>
                                                        <strong>#{{ $s->shift_id }}</strong>
                                                        @if ($s->shift_status === 'open')
                                                            <span class="badge bg-success" style="font-size: 0.65rem;">OPEN</span>
                                                        @endif
                                                        <br><small class="text-muted">{{ $s->shift_cashier }}</small>
                                                    </td>
                                                    <td class="small">
                                                        {{ $s->shift_opened_at ? \Carbon\Carbon::parse($s->shift_opened_at)->format('d/m H:i') : '-' }} s/d 
                                                        {{ $s->shift_closed_at ? \Carbon\Carbon::parse($s->shift_closed_at)->format('H:i') : 'Aktif' }}
                                                    </td>
                                                    <td class="text-center">{{ $s->count }}x</td>
                                                    <td class="text-end fw-semibold text-danger">Rp {{ number_format($s->total, 0, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Detail Table --}}
    <div class="table-responsive">
        <table class="table table-hover table-bordered table-kas-keluar align-middle mb-0">
            <thead class="table-light text-center">
                <tr>
                    <th style="width: 50px;">No</th>
                    <th style="width: 140px;">Waktu Transaksi</th>
                    <th style="width: 160px;">Sesi Shift</th>
                    <th style="width: 150px;">Petugas / Kasir</th>
                    <th>Keperluan / Keterangan</th>
                    <th style="width: 80px;">Bukti</th>
                    <th style="width: 160px;" class="text-end">Nominal (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $index => $trx)
                    <tr>
                        <td class="text-center text-muted">{{ $index + 1 }}</td>
                        <td class="text-center">
                            <span class="fw-semibold text-dark">
                                {{ \Carbon\Carbon::parse($trx->transaction_date)->format('d/m/Y') }}
                            </span>
                            <br>
                            <span class="badge bg-light text-secondary border font-monospace">
                                {{ \Carbon\Carbon::parse($trx->transaction_date)->format('H:i:s') }}
                            </span>
                        </td>
                        <td>
                            @if ($trx->cash_register_id)
                                <div class="d-flex align-items-center gap-1 mb-1">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                        Shift #{{ $trx->cash_register_id }}
                                    </span>
                                    @if ($trx->shift_status === 'open')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 0.7rem;">
                                            AKTIF
                                        </span>
                                    @endif
                                </div>
                                <div class="small text-muted">
                                    <i class="bi bi-clock me-1"></i>
                                    {{ $trx->shift_opened_at ? \Carbon\Carbon::parse($trx->shift_opened_at)->format('H:i') : '-' }} s/d 
                                    {{ $trx->shift_closed_at ? \Carbon\Carbon::parse($trx->shift_closed_at)->format('H:i') : 'Sekarang' }}
                                </div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $trx->cashier_name ?: 'Kasir #' . $trx->user_id }}</div>
                            @if ($trx->shift_opened_by && $trx->shift_opened_by !== $trx->cashier_name)
                                <small class="text-muted d-block" style="font-size: 0.75rem;">
                                    Shift oleh: {{ $trx->shift_opened_by }}
                                </small>
                            @endif
                        </td>
                        <td>
                            <span class="text-dark fw-medium">{{ $trx->notes ?: '-' }}</span>
                            <div class="text-muted small mt-1 font-monospace" style="font-size: 0.72rem;">
                                Trx ID: #{{ $trx->id }} | Metode: {{ strtoupper($trx->payment_method ?: 'CASH') }}
                            </div>
                        </td>
                        <td class="text-center">
                            @if (!empty($trx->bukti_bayar))
                                @php
                                    $imgUrl = str_starts_with($trx->bukti_bayar, 'http') ? $trx->bukti_bayar : asset('storage/' . $trx->bukti_bayar);
                                @endphp
                                <button type="button" class="btn btn-sm btn-outline-danger btn-preview-bukti py-0 px-2"
                                    data-img="{{ $imgUrl }}"
                                    data-desc="{{ $trx->notes }} (Rp {{ number_format($trx->amount, 0, ',', '.') }})"
                                    title="Lihat Bukti Foto">
                                    <i class="bi bi-image fs-6"></i>
                                </button>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <span class="fw-bold text-danger fs-6">
                                Rp {{ number_format($trx->amount, 0, ',', '.') }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted">
                                <i class="material-icons-outlined text-secondary opacity-50" style="font-size: 48px;">receipt_long</i>
                                <p class="mt-2 mb-0 fw-semibold">Tidak ada data transaksi kas keluar untuk filter ini.</p>
                                <small class="text-muted">Coba ubah rentang tanggal, store, atau reset filter.</small>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if ($transactions->isNotEmpty())
                <tfoot class="table-light">
                    <tr>
                        <th colspan="6" class="text-end text-uppercase fw-bold py-3 fs-6">
                            TOTAL KAS KELUAR ({{ $transactions->count() }} Transaksi):
                        </th>
                        <th class="text-end fw-bold text-danger py-3 fs-5">
                            Rp {{ number_format($totalNominal, 0, ',', '.') }}
                        </th>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

