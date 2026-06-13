<div class="card border-0 shadow-none m-0">
    <div class="card-header bg-transparent border-0 px-0 mb-3 d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h6 class="fw-bold mb-0 text-dark">Rincian Saldo Hutang</h6>
            <small class="text-muted">
                @if ($mulai && $akhir)
                    Periode: {{ date('d M Y', strtotime($mulai)) }} s/d {{ date('d M Y', strtotime($akhir)) }}
                @else
                    Periode: Semua Waktu (Lifetime)
                @endif
            </small>
        </div>
        <a href="{{ route('laporan.hutang.export', ['mulai' => $mulai, 'akhir' => $akhir]) }}"
            class="btn btn-success btn-sm d-flex align-items-center">
            <i class="fa fa-file-excel me-1"></i> Export Excel
        </a>
    </div>
    <div class="card-body px-0 py-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle border">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" width="50">No</th>
                        <th>Nama Pelanggan / Mitra</th>
                        <th class="text-center">Nomor Telepon</th>
                        <th>Alamat</th>
                        <th class="text-center" width="120">Jumlah Nota</th>
                        <th class="text-end" width="150">Total Hutang</th>
                        <th class="text-end" width="150">Terbayar</th>
                        <th class="text-end text-danger" width="160">Sisa Hutang</th>
                        <th class="text-center" width="100">Aksi</th>
                    </tr>
                </thead>
                <tbody class="small">
                    @if (isset($rows) && count($rows) > 0)
                        @foreach ($rows as $i => $row)
                            <tr>
                                <td class="text-center">{{ $i + 1 }}</td>
                                <td>
                                    <strong>{{ $row->name }}</strong>
                                </td>
                                <td class="text-center">{{ $row->phone }}</td>
                                <td class="text-wrap" style="max-width: 200px;">{{ $row->alamat }}</td>
                                <td class="text-center">
                                    <span class="badge bg-secondary rounded-pill">
                                        {{ $row->total_invoices }} Nota
                                    </span>
                                </td>
                                <td class="text-end">Rp {{ number_format($row->total_debt, 0, ',', '.') }}</td>
                                <td class="text-end text-success">Rp {{ number_format($row->total_paid, 0, ',', '.') }}</td>
                                <td class="text-end text-danger fw-bold">Rp {{ number_format($row->remaining, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <a href="{{ route('customers.show', $row->customer_id) }}" 
                                       class="btn btn-sm btn-outline-primary py-1 px-2 d-inline-flex align-items-center">
                                        <i class="material-icons-outlined me-1" style="font-size: 16px;">visibility</i> Detail
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        <tr class="table-light fw-bold" style="border-top: 2px solid #dee2e6;">
                            <td colspan="4" class="text-center">TOTAL KESELURUHAN</td>
                            <td class="text-center">{{ $rows->sum('total_invoices') }} Nota</td>
                            <td class="text-end">Rp {{ number_format($totalHutang, 0, ',', '.') }}</td>
                            <td class="text-end text-success">Rp {{ number_format($totalTerbayar, 0, ',', '.') }}</td>
                            <td class="text-end text-danger">Rp {{ number_format($totalSisaHutang, 0, ',', '.') }}</td>
                            <td></td>
                        </tr>
                    @else
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="material-icons-outlined text-muted fs-2 d-block mb-2">assignment_late</i>
                                Tidak ditemukan data hutang aktif untuk periode terpilih.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
