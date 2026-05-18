<div class="card mt-2">
    <div class="card-header">
        <h5>Detail Transaksi</h5>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped table-sm">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>No.Jurnal</th>
                    <th>Uraian</th>
                    <th>Jenis Transaksi</th>
                    <th class="text-end">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $rekap = [];
                    $total = 0;
                    foreach ($detailtrx as $dt) {
                        if (!isset($rekap[$dt->nama_trx])) {
                            $rekap[$dt->nama_trx] = ['jumlah' => 0, 'items' => []];
                        }
                        $rekap[$dt->nama_trx]['jumlah'] += $dt->amount;
                        $rekap[$dt->nama_trx]['items'][] = $dt;
                        $total += $dt->amount;
                    }
                    $no = 1;
                @endphp

                @foreach ($rekap as $nama_trx => $data)
                    @foreach ($data['items'] as $dt)
                        <tr>
                            <td>{{ $no++ }}</td>
                            <td>{{ date('d/m/Y', strtotime($dt->tanggal)) }}</td>
                            <td>{{ $dt->voucer }}</td>
                            <td>{{ $dt->uraian }}</td>
                            <td>{{ $dt->nama_trx }}</td>
                            <td class="text-end">{{ number_format($dt->amount) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="5" class="text-end"><b>Subtotal {{ $nama_trx }}</b></td>
                        <td class="text-end"><b>{{ number_format($data['jumlah']) }}</b></td>
                    </tr>
                @endforeach

                <tr>
                    <td colspan="5" class="text-end"><b>Total</b></td>
                    <td class="text-end"><b>{{ number_format($total) }}</b></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
