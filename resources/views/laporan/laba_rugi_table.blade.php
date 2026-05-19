@php
    $fmt = fn($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $fmtPct = fn($n, $base) => $base > 0 ? number_format(($n / $base) * 100, 1) . '%' : '0%';
    $isLaba = $labaRugi >= 0;
@endphp

{{-- Header Periode --}}
<div class="text-center mb-4">
    <div class="small text-muted">Periode</div>
    <div class="fw-bold" style="font-size:1rem;">
        {{ \Carbon\Carbon::parse($mulai)->translatedFormat('d F Y') }}
        &mdash;
        {{ \Carbon\Carbon::parse($akhir)->translatedFormat('d F Y') }}
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="rounded-3 p-3 text-white h-100" style="background:linear-gradient(135deg,#7c3aed,#4f46e5)">
            <div class="small opacity-75 mb-1"><i class="bi bi-bag-check me-1"></i>Omset Penjualan</div>
            <div class="fs-6 fw-bold">{{ $fmt($omset) }}</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="rounded-3 p-3 text-white h-100" style="background:linear-gradient(135deg,#0891b2,#0e7490)">
            <div class="small opacity-75 mb-1"><i class="bi bi-box-seam me-1"></i>Modal / HPP</div>
            <div class="fs-6 fw-bold">{{ $fmt($hpp) }}</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="rounded-3 p-3 text-white h-100" style="background:linear-gradient(135deg,#d97706,#b45309)">
            <div class="small opacity-75 mb-1"><i class="bi bi-receipt me-1"></i>Biaya Operasional</div>
            <div class="fs-6 fw-bold">{{ $fmt($totalBiaya) }}</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        @if ($isLaba)
            <div class="rounded-3 p-3 text-white h-100" style="background:linear-gradient(135deg,#059669,#047857)">
                <div class="small opacity-75 mb-1"><i class="bi bi-graph-up-arrow me-1"></i>Laba Bersih</div>
                <div class="fs-6 fw-bold">{{ $fmt($labaRugi) }}</div>
            </div>
        @else
            <div class="rounded-3 p-3 text-white h-100" style="background:linear-gradient(135deg,#dc2626,#991b1b)">
                <div class="small opacity-75 mb-1"><i class="bi bi-graph-down-arrow me-1"></i>Rugi Bersih</div>
                <div class="fs-6 fw-bold">{{ $fmt($labaRugi) }}</div>
            </div>
        @endif
    </div>
</div>

{{-- Statement Table --}}
<div class="table-responsive">
    <table class="table table-bordered align-middle mb-0" style="font-size:.9rem;">

        {{-- ===== PENDAPATAN ===== --}}
        <thead>
            <tr style="background:#f3f0ff;">
                <th colspan="2" class="text-uppercase fw-bold" style="color:#5b21b6;letter-spacing:.5px;">
                    <i class="bi bi-bar-chart-fill me-1"></i> Pendapatan
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="ps-4">Total Omset Penjualan</td>
                <td class="text-end fw-semibold">{{ $fmt($omset) }}</td>
            </tr>
        </tbody>

        {{-- ===== HPP ===== --}}
        <thead>
            <tr style="background:#eff6ff;">
                <th colspan="2" class="text-uppercase fw-bold" style="color:#1d4ed8;letter-spacing:.5px;">
                    <i class="bi bi-box-seam me-1"></i> Beban Pokok Penjualan (HPP)
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="ps-4">Modal / HPP</td>
                <td class="text-end fw-semibold text-danger">{{ $fmt($hpp) }}</td>
            </tr>
        </tbody>

        {{-- ===== PENDAPATAN KOTOR ===== --}}
        <tbody>
            <tr style="background:#ede9fe; font-weight:700;">
                <td class="ps-2">
                    <i class="bi bi-arrow-right-circle me-1" style="color:#7c3aed"></i>
                    Pendapatan Kotor
                </td>
                <td class="text-end" style="color:{{ $pendapatanKotor >= 0 ? '#059669' : '#dc2626' }}">
                    {{ $fmt($pendapatanKotor) }}
                </td>
            </tr>
        </tbody>

        {{-- ===== BIAYA OPERASIONAL ===== --}}
        <thead>
            <tr style="background:#fff7ed;">
                <th colspan="2" class="text-uppercase fw-bold" style="color:#b45309;letter-spacing:.5px;">
                    <i class="bi bi-receipt-cutoff me-1"></i> Biaya Operasional
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse ($biayaPerKategori as $biaya)
                <tr>
                    <td class="ps-4 d-flex justify-content-between align-items-center">
                        <span>{{ $biaya->kategori }}</span>
                        <small class="text-muted">{{ $biaya->jumlah }} transaksi</small>
                    </td>
                    <td class="text-end text-danger">{{ $fmt($biaya->total) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="text-center text-muted fst-italic ps-4">Tidak ada biaya operasional</td>
                </tr>
            @endforelse
            <tr class="table-warning fw-bold">
                <td class="ps-2">Total Biaya Operasional</td>
                <td class="text-end text-danger">{{ $fmt($totalBiaya) }}</td>
            </tr>
        </tbody>

        {{-- ===== LABA / RUGI ===== --}}
        <tbody>
            <tr style="background:{{ $isLaba ? '#064e3b' : '#7f1d1d' }}; font-weight:700; font-size:1rem;">
                <td class="ps-2 text-white">
                    <i class="bi {{ $isLaba ? 'bi-graph-up-arrow' : 'bi-graph-down-arrow' }} me-2"></i>
                    {{ $isLaba ? 'LABA BERSIH' : 'RUGI BERSIH' }}
                </td>
                <td class="text-end text-white">{{ $fmt($labaRugi) }}</td>
            </tr>
        </tbody>

    </table>
</div>

{{-- Margin Indicator --}}
@if ($omset > 0)
    <div class="mt-4">
        <div class="d-flex justify-content-between small mb-1">
            <span class="fw-semibold">Margin Laba Bersih</span>
            <span class="fw-bold {{ $isLaba ? 'text-success' : 'text-danger' }}">
                {{ $fmtPct($labaRugi, $omset) }}
            </span>
        </div>
        @php $pct = min(100, max(0, ($omset > 0 ? ($labaRugi / $omset) * 100 : 0))); @endphp
        <div class="progress" style="height:12px; border-radius:6px;">
            <div class="progress-bar {{ $isLaba ? '' : 'bg-danger' }}"
                style="width:{{ abs($pct) }}%; background:{{ $isLaba ? '#059669' : '' }};" role="progressbar">
            </div>
        </div>

        <div class="row g-3 mt-2 text-center">
            <div class="col-4">
                <div class="small text-muted">Margin Kotor</div>
                <div class="fw-bold {{ $pendapatanKotor >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $fmtPct($pendapatanKotor, $omset) }}
                </div>
            </div>
            <div class="col-4">
                <div class="small text-muted">% Biaya thd Omset</div>
                <div class="fw-bold text-warning">
                    {{ $fmtPct($totalBiaya, $omset) }}
                </div>
            </div>
            <div class="col-4">
                <div class="small text-muted">Margin Bersih</div>
                <div class="fw-bold {{ $isLaba ? 'text-success' : 'text-danger' }}">
                    {{ $fmtPct($labaRugi, $omset) }}
                </div>
            </div>
        </div>
    </div>
@endif
