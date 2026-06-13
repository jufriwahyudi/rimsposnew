{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card rounded-4 border-0 bg-light-danger text-danger p-3 h-100 shadow-sm">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="mb-1 fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Total Poin Ditukar</h6>
                    <h3 class="mb-0 fw-bold font-monospace">{{ number_format($totalPointsSpent) }}</h3>
                </div>
                <div class="avatar avatar-md bg-danger text-white rounded-3 p-2">
                    <i class="material-icons-outlined" style="font-size:24px">star</i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card rounded-4 border-0 bg-light-primary text-primary p-3 h-100 shadow-sm">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="mb-1 fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Hadiah Fisik</h6>
                    <h3 class="mb-0 fw-bold font-monospace">{{ number_format($totalPhysical) }}</h3>
                </div>
                <div class="avatar avatar-md bg-primary text-white rounded-3 p-2">
                    <i class="material-icons-outlined" style="font-size:24px">inventory_2</i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card rounded-4 border-0 bg-light-success text-success p-3 h-100 shadow-sm">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="mb-1 fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Voucher Persentase (%)</h6>
                    <h3 class="mb-0 fw-bold font-monospace">{{ number_format($totalVoucherPercent) }}</h3>
                </div>
                <div class="avatar avatar-md bg-success text-white rounded-3 p-2">
                    <i class="material-icons-outlined" style="font-size:24px">percent</i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card rounded-4 border-0 bg-light-info text-info p-3 h-100 shadow-sm">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="mb-1 fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Voucher Nominal (Rp)</h6>
                    <h3 class="mb-0 fw-bold font-monospace">{{ number_format($totalVoucherNominal) }}</h3>
                </div>
                <div class="avatar avatar-md bg-info text-white rounded-3 p-2">
                    <i class="material-icons-outlined" style="font-size:24px">payments</i>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Data Table --}}
<div class="table-responsive">
    <table class="table table-bordered table-striped align-middle table-hover" id="tbl-redemptions">
        <thead class="table-light">
            <tr>
                <th width="50" class="text-center">No</th>
                <th>Tanggal Penukaran</th>
                <th>Member</th>
                <th>Hadiah / Voucher</th>
                <th class="text-center">Poin Ditukar</th>
                <th>Kode Voucher</th>
                <th class="text-center">Status Voucher</th>
                <th>Digunakan Pada</th>
                <th>Invoice Terkait</th>
            </tr>
        </thead>
        <tbody>
            @forelse($redemptions as $index => $r)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <span class="fw-semibold">{{ $r->created_at->format('d-M-Y') }}</span>
                        <div class="small text-muted">{{ $r->created_at->format('H:i') }}</div>
                    </td>
                    <td>
                        <div class="fw-bold text-primary">{{ $r->member->name ?? '-' }}</div>
                        <div class="small text-muted font-monospace">{{ $r->member->phone ?? '-' }}</div>
                    </td>
                    <td>
                        <div class="fw-bold">{{ $r->rewardItem->name }}</div>
                        <div class="small text-muted">
                            @switch($r->rewardItem->reward_type)
                                @case('physical')
                                    <span class="text-muted"><i class="bi bi-box me-1"></i> Fisik</span>
                                    @break
                                @case('voucher_percent')
                                    <span class="text-success"><i class="bi bi-percent me-1"></i> Diskon Persen</span>
                                    @break
                                @case('voucher_nominal')
                                    <span class="text-info"><i class="bi bi-wallet2 me-1"></i> Potongan Nominal</span>
                                    @break
                            @endswitch
                        </div>
                    </td>
                    <td class="text-center font-monospace fw-bold text-danger">
                        -{{ number_format($r->points_spent) }}
                    </td>
                    <td class="font-monospace fw-semibold">{{ $r->voucher_code ?? '-' }}</td>
                    <td class="text-center">
                        @if($r->rewardItem->reward_type === 'physical')
                            <span class="badge bg-light-primary text-primary border border-primary-subtle rounded-pill">Selesai (Fisik)</span>
                        @else
                            @if($r->is_used)
                                <span class="badge bg-light-success text-success border border-success-subtle rounded-pill">Terpakai</span>
                            @else
                                <span class="badge bg-light-warning text-warning border border-warning-subtle rounded-pill">Aktif (Belum Dipakai)</span>
                            @endif
                        @endif
                    </td>
                    <td>
                        @if($r->used_at)
                            <span class="fw-semibold">{{ $r->used_at->format('d-M-Y') }}</span>
                            <div class="small text-muted">{{ $r->used_at->format('H:i') }}</div>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($r->sale)
                            <a href="{{ route('sales.show', $r->sale_id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-bold">
                                <i class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">receipt</i>
                                {{ $r->sale->invoice_number }}
                            </a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center py-4 text-muted">
                        <i class="material-icons-outlined" style="font-size:32px; opacity:0.5; vertical-align: middle;">info</i>
                        <span class="ms-1">Tidak ada data penukaran poin dalam periode ini.</span>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
