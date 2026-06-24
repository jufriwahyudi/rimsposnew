<div class="card rounded-4 p-2">
    <div class="card-header mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0" style="color: #7c3aed">Stok Terjual Harian</h5>
            <small class="text-muted">Tanggal: {{ $mulai === $akhir ? $mulai : $mulai . ' s/d ' . $akhir }}</small>
        </div>
        <a href="{{ route('laporan.harian.export', ['mulai' => $mulai, 'akhir' => $akhir]) }}" class="btn btn-sm btn-success">
            <i class="bi bi-file-earmark-excel"></i> Export Excel
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th class="text-center">No</th>
                        <th class="text-center">SKU</th>
                        <th class="text-center">Produk</th>
                        <th class="text-center">Varian</th>
                        <th class="text-center">Harga Jual</th>
                        <th class="text-center">Qty Terjual</th>
                        <th class="text-center">Diskon</th>
                        <th class="text-center">Total Penjualan</th>
                        <th class="text-center">Modal</th>
                        <th class="text-center">Laba / Rugi</th>
                        <th class="text-center">Jml Trx</th>
                    </tr>
                </thead>
                <tbody>
                    @if (isset($rows) && count($rows))
                        @php
                            $sumQty = 0;
                            $sumDiskon = 0;
                            $sumSubtotal = 0;
                            $sumModal = 0;
                            $sumLaba = 0;
                            $sumTrx = 0;
                        @endphp
                        @foreach ($rows as $i => $row)
                            @php
                                $sumQty += $row->total_qty;
                                $sumDiskon += $row->total_diskon;
                                $sumSubtotal += $row->total_subtotal;
                                $sumModal += $row->total_modal;
                                $sumLaba += $row->laba_rugi;
                                $sumTrx += $row->jumlah_trx;
                            @endphp
                            <tr>
                                <td class="text-center">{{ $i + 1 }}</td>
                                <td><code>{{ $row->sku }}</code></td>
                                <td>{{ $row->product_name }}</td>
                                <td>{{ $row->variant_label ?: '-' }}</td>
                                <td class="text-end">{{ number_format($row->harga_jual, 0, ',', '.') }}</td>
                                <td class="text-center">{{ number_format($row->total_qty) }}</td>
                                <td class="text-end">{{ number_format($row->total_diskon, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row->total_subtotal, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row->total_modal, 0, ',', '.') }}</td>
                                <td class="text-end {{ $row->laba_rugi >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($row->laba_rugi, 0, ',', '.') }}
                                </td>
                                <td class="text-center">{{ $row->jumlah_trx }}</td>
                            </tr>
                        @endforeach
                        <tr class="table-dark fw-bold">
                            <td colspan="5" class="text-center">TOTAL</td>
                            <td class="text-center">{{ number_format($sumQty) }}</td>
                            <td class="text-end">{{ number_format($sumDiskon, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($sumSubtotal, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($sumModal, 0, ',', '.') }}</td>
                            <td class="text-end {{ $sumLaba >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($sumLaba, 0, ',', '.') }}
                            </td>
                            <td class="text-center">{{ number_format($sumTrx) }}</td>
                        </tr>
                    @else
                        <tr>
                            <td colspan="11" class="text-center">Tidak ada data penjualan pada periode ini</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
