<div class="card rounded-4 p-2">
    <div class="card-header mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0" style="color: #7c3aed">Data Penjualan</h5>
            <small class="text-muted">Tanggal: {{ $mulai }} - {{ $akhir }}</small>
        </div>
        <a href="{{ route('laporanpenjualan.export', ['mulai' => $mulai, 'akhir' => $akhir]) }}"
            class="btn btn-success btn-sm">
            <i class="fa fa-file-excel"></i> Export Excel
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th class="text-center">No</th>
                        <th class="text-center">Invoice</th>
                        <th class="text-center">Tanggal</th>
                        <th class="text-center">Pelanggan / Meja</th>
                        <th class="text-center">Subtotal</th>
                        <th class="text-center">Diskon</th>
                        <th class="text-center">Total (Net)</th>
                        <th class="text-center">Modal</th>
                        <th class="text-center">Laba / Rugi</th>
                        <th class="text-center">Tip</th>
                        <th class="text-center">Metode Pembayaran</th>
                        <th class="text-center">Petugas</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="small">
                    @if (isset($rows) && count($rows))
                    @foreach ($rows as $row)
                    <tr>
                        <td class="text-center">{{ $row->no }}</td>
                        <td>
                            <a href="{{ route('sales.show', $row->sale_id) }}" target="_blank" class="fw-semibold text-decoration-none">
                                {{ $row->invoice_number ?? '-' }}
                            </a>
                            <div><small class="text-muted">{{ ucfirst($row->sale_type ?? '-') }}</small></div>
                        </td>
                        <td>{{ optional($row->sale_date)->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>
                            <div>{{ $row->customer_name }}</div>
                            @if (!empty($row->table_number))
                            <span class="badge bg-light text-dark border">Meja: {{ $row->table_number }}</span>
                            @endif
                        </td>
                        <td class="text-end">{{ number_format($row->subtotal ?? $row->jumlah_penjualan, 2, ',', '.') }}</td>
                        <td class="text-end {{ ($row->diskon ?? 0) > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                            @if (($row->diskon ?? 0) > 0)
                            -{{ number_format($row->diskon, 0, ',', '.') }}
                            @if (!empty($row->discount_name))
                            <div><small class="text-muted">({{ $row->discount_name }})</small></div>
                            @endif
                            @else
                            -
                            @endif
                        </td>
                        <td class="text-end fw-semibold">{{ number_format($row->jumlah_penjualan, 2, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($row->modal, 2, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($row->laba_rugi, 2, ',', '.') }}</td>
                        <td class="text-end {{ ($row->tip ?? 0) > 0 ? 'text-success fw-semibold' : 'text-muted' }}">
                            {{ ($row->tip ?? 0) > 0 ? number_format($row->tip, 0, ',', '.') : '-' }}
                        </td>
                        <td>{{ ucfirst($row->metode_pembayaran ?? '-') }}</td>
                        <td>{{ $row->kasir ?? '-' }}</td>
                        <td>{{ ucfirst($row->status ?? '-') }}</td>
                    </tr>
                    @endforeach
                    {{-- FOOTER TOTAL --}}
                    <tr class="table-secondary fw-bold">
                        <td colspan="4" class="text-end">TOTAL</td>
                        <td class="text-end">{{ number_format($totalSubtotal ?? $totalPenjualan, 2, ',', '.') }}</td>
                        <td class="text-end {{ ($totalDiskon ?? 0) > 0 ? 'text-danger' : '' }}">
                            {{ ($totalDiskon ?? 0) > 0 ? '-' . number_format($totalDiskon, 0, ',', '.') : '-' }}
                        </td>
                        <td class="text-end">{{ number_format($totalPenjualan, 2, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($totalModal, 2, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($totalLabaRugi, 2, ',', '.') }}</td>
                        <td class="text-end {{ ($totalTip ?? 0) > 0 ? 'text-success' : '' }}">
                            {{ ($totalTip ?? 0) > 0 ? number_format($totalTip, 0, ',', '.') : '-' }}
                        </td>
                        <td colspan="3"></td>
                    </tr>
                    @else
                    <tr>
                        <td colspan="13" class="text-center">Tidak ada data</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>