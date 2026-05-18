<div class="card rounded-4 p-2">
    <div class="card-header mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0" style="color: #7c3aed">Data Penjualan NSE</h5>
            <small class="text-muted">Tanggal: {{ $mulai }} - {{ $akhir }}</small>
        </div>
        <a href="{{ route('laporanpenjualanNSE.export', ['mulai' => $mulai, 'akhir' => $akhir, 'id_divisi' => $id_divisi]) }}"
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
                        <th class="text-center">Tanggal</th>
                        <th class="text-center">Divisi</th>
                        <th class="text-center">Nama Siswa</th>
                        <th class="text-center">Gender</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">Modal</th>
                        <th class="text-center">Laba / Rugi</th>
                        <th class="text-center">Metode Pembayaran</th>
                        <th class="text-center">No POS</th>
                        <th class="text-center">Petugas</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="small">
                    @if (isset($rows) && count($rows))
                        @foreach ($rows as $row)
                            <tr>
                                <td>{{ $row->no }}</td>
                                <td>{{ optional($row->sale_date)->format('Y-m-d H:i') ?? '-' }}</td>
                                <td>{{ $row->biodata->divisi->nama ?? '-' }}</td>
                                <td>{{ $row->biodata->nama_lengkap ?? '-' }}</td>
                                <td>{{ $row->biodata->jk ?? '-' }}</td>
                                <td class="text-end">{{ number_format($row->jumlah_penjualan, 2, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row->modal, 2, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row->laba_rugi, 2, ',', '.') }}</td>
                                <td>{{ ucfirst($row->metode_pembayaran ?? '-') }}</td>
                                <td>{{ $row->no_pos ?? '-' }}</td>
                                <td>{{ $row->kasir ?? '-' }}</td>
                                <td>{{ ucfirst($row->status ?? '-') }}</td>
                            </tr>
                        @endforeach

                        <tr class="fw-bold bg-light">
                            <td colspan="5" class="text-center">TOTAL</td>
                            <td class="text-end">{{ number_format($totalPenjualan ?? 0, 2, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($totalModal ?? 0, 2, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($totalLabaRugi ?? 0, 2, ',', '.') }}</td>
                            <td colspan="3"></td>
                        </tr>
                    @else
                        <tr>
                            <td colspan="9" class="text-center">Tidak ada data</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
