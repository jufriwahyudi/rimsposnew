<div class="card rounded-4 p-2">
    <div class="card-header mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0" style="color: #7c3aed">Settlement Tenant</h5>
            <small class="text-muted">Tanggal: {{ $mulai }} - {{ $akhir }}</small>
        </div>
        <a href="{{ route('laporan.tenant.export', ['mulai' => $mulai, 'akhir' => $akhir]) }}"
            class="btn btn-success btn-sm">
            <i class="fa fa-file-excel"></i> Export Excel
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th class="text-center" width="50">No</th>
                        <th class="text-center">Kode Tenant</th>
                        <th class="text-center">Nama Tenant</th>
                        <th class="text-center">Total Qty Terjual</th>
                        <th class="text-center">Total Penjualan (Gross)</th>
                        <th class="text-center">Komisi Toko</th>
                        <th class="text-center">Hak Tenant (Net Payout)</th>
                    </tr>
                </thead>
                <tbody class="small">
                    @if (isset($rows) && count($rows))
                        @foreach ($rows as $i => $row)
                            <tr>
                                <td class="text-center">{{ $i + 1 }}</td>
                                <td class="text-center">
                                    <span class="badge rounded-pill" style="background:#7c3aed;font-size:12px">
                                        {{ $row->kode_tenant }}
                                    </span>
                                </td>
                                <td><strong>{{ $row->nama_tenant }}</strong></td>
                                <td class="text-center">{{ number_format($row->total_qty) }}</td>
                                <td class="text-end">Rp {{ number_format($row->gross_sales, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($row->commission, 0, ',', '.') }}</td>
                                <td class="text-end text-success fw-bold">Rp {{ number_format($row->net_payout, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        <tr class="table-light fw-bold">
                            <td colspan="3" class="text-center">TOTAL</td>
                            <td class="text-center">{{ number_format($rows->sum('total_qty')) }}</td>
                            <td class="text-end">Rp {{ number_format($totalPenjualan, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($totalKomisi, 0, ',', '.') }}</td>
                            <td class="text-end text-success">Rp {{ number_format($totalHakTenant, 0, ',', '.') }}</td>
                        </tr>
                    @else
                        <tr>
                            <td colspan="7" class="text-center text-muted">Tidak ada data transaksi tenant</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
