<div class="card rounded-4 p-2">
    <div class="card-header mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0" style="color: #7c3aed">Penerimaan Kas (Cash)</h5>
            <small class="text-muted">Tanggal: {{ $tanggal }}</small>
        </div>
        <a href="{{ route('laporan.penerimaan_kas.export', ['tanggal' => $tanggal, 'user_id' => $userId ?? '']) }}"
            class="btn btn-sm btn-success">
            <i class="bi bi-file-earmark-excel"></i> Export Excel
        </a>
        <a href="{{ route('laporan.cetak_penerimaan_kas', ['tanggal' => $tanggal, 'user_id' => $userId ?? '']) }}"
            target="_blank"
            class="btn btn-sm btn-danger">
            <i class="bi bi-file-earmark-pdf"></i> Cetak PDF
        </a>
    </div>
    <div class="card-body">
        {{-- Summary Cards --}}
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="border rounded-3 p-3 text-center bg-light">
                    <small class="text-muted">Total Kas Masuk</small>
                    <h5 class="fw-bold text-success mb-0">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</h5>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded-3 p-3 text-center bg-light">
                    <small class="text-muted">Total Kas Keluar</small>
                    <h5 class="fw-bold text-danger mb-0">Rp {{ number_format($totalKeluar, 0, ',', '.') }}</h5>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded-3 p-3 text-center bg-light">
                    <small class="text-muted">Saldo Kas</small>
                    <h5 class="fw-bold mb-0" style="color: #7c3aed">Rp {{ number_format($saldo, 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th class="text-center">No</th>
                        <th class="text-center">Waktu</th>
                        <th class="text-center">Tipe Transaksi</th>
                        <th class="text-center">Referensi</th>
                        <th class="text-center">Keterangan</th>
                        <th class="text-center">Kas Masuk</th>
                        <th class="text-center">Kas Keluar</th>
                        <th class="text-center">Petugas</th>
                    </tr>
                </thead>
                <tbody>
                    @if (isset($rows) && count($rows))
                        @foreach ($rows as $row)
                            <tr>
                                <td class="text-center">{{ $row->no }}</td>
                                <td class="text-center">
                                    {{ \Carbon\Carbon::parse($row->transaction_date)->format('H:i:s') }}</td>
                                <td>
                                    @php
                                        $typeLabel = match ($row->transaction_type) {
                                            'sale' => 'Penjualan',
                                            'refund' => 'Refund',
                                            'expense' => 'Pengeluaran',
                                            'purchase' => 'Pembelian',
                                            'adjustment' => 'Penyesuaian',
                                            default => ucfirst($row->transaction_type),
                                        };
                                    @endphp
                                    {{ $typeLabel }}
                                </td>
                                <td class="text-center">
                                    <code>{{ $row->ref_type ? $row->ref_type . '#' . $row->ref_id : '-' }}</code>
                                </td>
                                <td>{{ $row->notes ?: '-' }}</td>
                                <td class="text-end {{ $row->masuk > 0 ? 'text-success fw-bold' : '' }}">
                                    {{ $row->masuk > 0 ? number_format($row->masuk, 0, ',', '.') : '-' }}
                                </td>
                                <td class="text-end {{ $row->keluar > 0 ? 'text-danger fw-bold' : '' }}">
                                    {{ $row->keluar > 0 ? number_format($row->keluar, 0, ',', '.') : '-' }}
                                </td>
                                <td>{{ $row->petugas }}</td>
                            </tr>
                        @endforeach
                        <tr class="table-dark fw-bold">
                            <td colspan="5" class="text-center">TOTAL</td>
                            <td class="text-end text-success">{{ number_format($totalMasuk, 0, ',', '.') }}</td>
                            <td class="text-end text-danger">{{ number_format($totalKeluar, 0, ',', '.') }}</td>
                            <td></td>
                        </tr>
                        <tr class="fw-bold" style="background-color: #f3e8ff;">
                            <td colspan="5" class="text-center" style="color: #7c3aed">SALDO KAS</td>
                            <td colspan="2" class="text-end" style="color: #7c3aed; font-size: 1.1rem;">
                                Rp {{ number_format($saldo, 0, ',', '.') }}
                            </td>
                            <td></td>
                        </tr>
                    @else
                        <tr>
                            <td colspan="8" class="text-center">Tidak ada transaksi kas pada tanggal ini</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
