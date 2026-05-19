@php
    $fmt = fn($n) => 'Rp ' . number_format($n, 0, ',', '.');
@endphp

{{-- Summary Cards --}}
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="rounded-3 p-3 text-white" style="background:linear-gradient(135deg,#7c3aed,#4f46e5)">
            <div class="small opacity-75">Total Biaya</div>
            <div class="fs-5 fw-bold">{{ $fmt($total) }}</div>
            <div class="small opacity-75 mt-1">
                {{ $mulai }} &mdash; {{ $akhir }}
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="rounded-3 p-3 text-white" style="background:linear-gradient(135deg,#0891b2,#0e7490)">
            <div class="small opacity-75">Total Cash</div>
            <div class="fs-5 fw-bold">{{ $fmt($totalCash) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="rounded-3 p-3 text-white" style="background:linear-gradient(135deg,#059669,#047857)">
            <div class="small opacity-75">Total Transfer</div>
            <div class="fs-5 fw-bold">{{ $fmt($totalTransfer) }}</div>
        </div>
    </div>
</div>

@if ($rows->isEmpty())
    <div class="alert alert-info">Tidak ada data untuk filter yang dipilih.</div>
@elseif ($jenis === 'rekap')
    {{-- REKAPITULASI per kategori --}}
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Kategori Biaya</th>
                    <th class="text-center">Jml Transaksi</th>
                    <th class="text-end">Total Cash</th>
                    <th class="text-end">Total Transfer</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row->kategori }}</td>
                        <td class="text-center">{{ $row->jumlah_transaksi }}</td>
                        <td class="text-end">{{ $fmt($row->total_cash) }}</td>
                        <td class="text-end">{{ $fmt($row->total_transfer) }}</td>
                        <td class="text-end fw-semibold">{{ $fmt($row->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="table-secondary fw-bold">
                <tr>
                    <td>Total</td>
                    <td class="text-center">{{ $rows->sum('jumlah_transaksi') }}</td>
                    <td class="text-end">{{ $fmt($totalCash) }}</td>
                    <td class="text-end">{{ $fmt($totalTransfer) }}</td>
                    <td class="text-end">{{ $fmt($total) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Bar chart visual --}}
    <div class="mt-3">
        @foreach ($rows as $row)
            @php $pct = $total > 0 ? ($row->total / $total) * 100 : 0; @endphp
            <div class="mb-2">
                <div class="d-flex justify-content-between small mb-1">
                    <span>{{ $row->kategori }}</span>
                    <span class="fw-semibold">{{ $fmt($row->total) }}
                        <span class="text-muted">({{ number_format($pct, 1) }}%)</span>
                    </span>
                </div>
                <div class="progress" style="height:14px">
                    <div class="progress-bar" role="progressbar" style="width:{{ $pct }}%;background:#7c3aed"
                        aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    {{-- DETAIL per transaksi --}}
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th width="40">#</th>
                    <th width="100">Tanggal</th>
                    <th>Kategori</th>
                    <th>Keterangan</th>
                    <th class="text-center" width="100">Metode</th>
                    <th class="text-end" width="140">Jumlah</th>
                    <th>Dicatat Oleh</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row->no }}</td>
                        <td>{{ $row->tanggal }}</td>
                        <td>{{ $row->kategori }}</td>
                        <td>
                            {{ $row->keterangan }}
                            @if ($row->notes)
                                <br><small class="text-muted">{{ $row->notes }}</small>
                            @endif
                        </td>
                        <td class="text-center">
                            @if ($row->metode === 'Cash')
                                <span class="badge bg-info text-dark">Cash</span>
                            @else
                                <span class="badge bg-success">Transfer</span>
                            @endif
                        </td>
                        <td class="text-end fw-semibold">{{ $fmt($row->jumlah) }}</td>
                        <td>{{ $row->dicatat_oleh }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="table-secondary fw-bold">
                <tr>
                    <td colspan="5" class="text-end">Total</td>
                    <td class="text-end">{{ $fmt($total) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
@endif
