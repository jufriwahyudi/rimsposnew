<div class="card rounded-4 p-2">
    <div class="card-header mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0" style="color: #7c3aed">Penerimaan Kas</h5>
            <small class="text-muted">Tanggal: {{ $tanggal }}</small>
        </div>
    </div>
    <div class="card-body">
        <ul class="nav nav-tabs mb-3" id="penerimaanKasTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="cash-tab" data-bs-toggle="tab" data-bs-target="#cash-content"
                    type="button" role="tab" aria-controls="cash-content" aria-selected="true">
                    <i class="bi bi-cash-coin"></i> Cash
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="transfer-tab" data-bs-toggle="tab" data-bs-target="#transfer-content"
                    type="button" role="tab" aria-controls="transfer-content" aria-selected="false">
                    <i class="bi bi-credit-card"></i> Transfer
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="penerimaanKasTabContent">
            {{-- TAB CASH --}}
            <div class="tab-pane fade show active" id="cash-content" role="tabpanel" aria-labelledby="cash-tab">
                {{-- Summary Cards & Actions --}}
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h6 class="fw-bold mb-0"><i class="bi bi-cash"></i> Rekapan Cash</h6>
                    <div class="d-flex gap-2">
                        <a href="{{ route('laporan.penerimaan_kas.export', ['tanggal' => $tanggal, 'user_id' => $userId ?? '', 'payment_method' => 'cash']) }}"
                            class="btn btn-sm btn-success">
                            <i class="bi bi-file-earmark-excel"></i> Export Excel
                        </a>
                        <a href="{{ route('laporan.cetak_penerimaan_kas', ['tanggal' => $tanggal, 'user_id' => $userId ?? '', 'payment_method' => 'cash']) }}"
                            target="_blank"
                            class="btn btn-sm btn-danger">
                            <i class="bi bi-file-earmark-pdf"></i> Cetak PDF
                        </a>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 text-center bg-light shadow-sm">
                            <small class="text-muted d-block mb-1">Total Kas Masuk (Cash)</small>
                            <h5 class="fw-bold text-success mb-0">Rp {{ number_format($totalCashMasuk, 0, ',', '.') }}</h5>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 text-center bg-light shadow-sm">
                            <small class="text-muted d-block mb-1">Total Kas Keluar (Cash)</small>
                            <h5 class="fw-bold text-danger mb-0">Rp {{ number_format($totalCashKeluar, 0, ',', '.') }}</h5>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 text-center bg-light shadow-sm">
                            <small class="text-muted d-block mb-1">Saldo Kas (Cash)</small>
                            <h5 class="fw-bold mb-0" style="color: #7c3aed">Rp {{ number_format($saldoCash, 0, ',', '.') }}</h5>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle">
                        <thead class="table-light text-center">
                            <tr>
                                <th>No</th>
                                <th>Waktu</th>
                                <th>Tipe Transaksi</th>
                                <th>Referensi</th>
                                <th>Keterangan</th>
                                <th>Kas Masuk</th>
                                <th>Kas Keluar</th>
                                <th>Petugas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (isset($cashRows) && count($cashRows))
                                @foreach ($cashRows as $row)
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
                                    <td class="text-end text-success">{{ number_format($totalCashMasuk, 0, ',', '.') }}</td>
                                    <td class="text-end text-danger">{{ number_format($totalCashKeluar, 0, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                                <tr class="fw-bold" style="background-color: #f3e8ff;">
                                    <td colspan="5" class="text-center" style="color: #7c3aed">SALDO KAS</td>
                                    <td colspan="2" class="text-end" style="color: #7c3aed; font-size: 1.1rem;">
                                        Rp {{ number_format($saldoCash, 0, ',', '.') }}
                                    </td>
                                    <td></td>
                                </tr>
                            @else
                                <tr>
                                    <td colspan="8" class="text-center py-3">Tidak ada transaksi kas (Cash) pada tanggal ini</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            
            {{-- TAB TRANSFER --}}
            <div class="tab-pane fade" id="transfer-content" role="tabpanel" aria-labelledby="transfer-tab">
                {{-- Summary Cards & Actions --}}
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h6 class="fw-bold mb-0"><i class="bi bi-credit-card"></i> Rekapan Transfer</h6>
                    <div class="d-flex gap-2">
                        <a href="{{ route('laporan.penerimaan_kas.export', ['tanggal' => $tanggal, 'user_id' => $userId ?? '', 'payment_method' => 'transfer']) }}"
                            class="btn btn-sm btn-success">
                            <i class="bi bi-file-earmark-excel"></i> Export Excel
                        </a>
                        <a href="{{ route('laporan.cetak_penerimaan_kas', ['tanggal' => $tanggal, 'user_id' => $userId ?? '', 'payment_method' => 'transfer']) }}"
                            target="_blank"
                            class="btn btn-sm btn-danger">
                            <i class="bi bi-file-earmark-pdf"></i> Cetak PDF
                        </a>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 text-center bg-light shadow-sm">
                            <small class="text-muted d-block mb-1">Total Transfer Masuk</small>
                            <h5 class="fw-bold text-success mb-0">Rp {{ number_format($totalTransferMasuk, 0, ',', '.') }}</h5>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 text-center bg-light shadow-sm">
                            <small class="text-muted d-block mb-1">Total Transfer Keluar</small>
                            <h5 class="fw-bold text-danger mb-0">Rp {{ number_format($totalTransferKeluar, 0, ',', '.') }}</h5>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 text-center bg-light shadow-sm">
                            <small class="text-muted d-block mb-1">Saldo Transfer</small>
                            <h5 class="fw-bold mb-0" style="color: #7c3aed">Rp {{ number_format($saldoTransfer, 0, ',', '.') }}</h5>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle">
                        <thead class="table-light text-center">
                            <tr>
                                <th>No</th>
                                <th>Waktu</th>
                                <th>Tipe Transaksi</th>
                                <th>Referensi</th>
                                <th>Keterangan</th>
                                <th>Transfer Masuk</th>
                                <th>Transfer Keluar</th>
                                <th>Petugas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (isset($transferRows) && count($transferRows))
                                @foreach ($transferRows as $row)
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
                                    <td class="text-end text-success">{{ number_format($totalTransferMasuk, 0, ',', '.') }}</td>
                                    <td class="text-end text-danger">{{ number_format($totalTransferKeluar, 0, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                                <tr class="fw-bold" style="background-color: #f3e8ff;">
                                    <td colspan="5" class="text-center" style="color: #7c3aed">SALDO TRANSFER</td>
                                    <td colspan="2" class="text-end" style="color: #7c3aed; font-size: 1.1rem;">
                                        Rp {{ number_format($saldoTransfer, 0, ',', '.') }}
                                    </td>
                                    <td></td>
                                </tr>
                            @else
                                <tr>
                                    <td colspan="8" class="text-center py-3">Tidak ada transaksi transfer pada tanggal ini</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
