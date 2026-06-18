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
                        <th class="text-center">Jenis</th>
                        <th class="text-center">Tanggal</th>
                        <th class="text-center">Nama Pelanggan</th>
                        <th class="text-center">Total</th>
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
                                <td>{{ $row->no }}</td>
                                <td>{{ ucfirst($row->sale_type ?? '-') }}</td>
                                <td>{{ optional($row->sale_date)->format('Y-m-d H:i') ?? '-' }}</td>
                                <td>{{ $row->customer_name }}</td>
                                <td class="text-end">{{ number_format($row->jumlah_penjualan, 2, ',', '.') }}</td>
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
                            <td colspan="11" class="text-center">Tidak ada data</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
