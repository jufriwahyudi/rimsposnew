<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Penerimaan Kas</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .title {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
        }

        .subtitle {
            text-align: center;
            font-size: 12px;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #000;
            padding: 5px;
        }

        th {
            background-color: #E9ECEF;
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .total {
            background-color: #D1E7DD;
            font-weight: bold;
        }

        .saldo {
            background-color: #F3E8FF;
            font-weight: bold;
            font-size: 13px;
        }
    </style>
</head>

<body>

    <div class="title">Laporan Penerimaan Kas ({{ ucfirst($paymentMethod ?? 'cash') }})</div>
    <div class="subtitle">Tanggal: {{ $tanggal }}</div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Waktu</th>
                <th>Tipe Transaksi</th>
                <th>Referensi</th>
                <th>Keterangan</th>
                <th>{{ ($paymentMethod ?? 'cash') === 'transfer' ? 'Transfer Masuk' : 'Kas Masuk' }}</th>
                <th>{{ ($paymentMethod ?? 'cash') === 'transfer' ? 'Transfer Keluar' : 'Kas Keluar' }}</th>
                <th>Petugas</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp

            @foreach($transactions as $trx)
                @php
                    $masuk = $trx->direction === 'in' ? $trx->amount : 0;
                    $keluar = $trx->direction === 'out' ? $trx->amount : 0;

                    $typeLabel = match ($trx->transaction_type) {
                        'sale' => 'Penjualan',
                        'refund' => 'Refund',
                        'expense' => 'Pengeluaran',
                        'purchase' => 'Pembelian',
                        'adjustment' => 'Penyesuaian',
                        default => ucfirst($trx->transaction_type),
                    };
                @endphp

                <tr>
                    <td class="text-center">{{ $no++ }}</td>
                    <td class="text-center">
                        {{ \Carbon\Carbon::parse($trx->transaction_date)->format('H:i:s') }}
                    </td>
                    <td>{{ $typeLabel }}</td>
                    <td>
                        {{ $trx->ref_type ? $trx->ref_type . '#' . $trx->ref_id : '-' }}
                    </td>
                    <td>{{ $trx->notes ?? '-' }}</td>
                    <td class="text-right">{{ number_format($masuk, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($keluar, 0, ',', '.') }}</td>
                    <td>{{ optional($trx->user)->name ?? '-' }}</td>
                </tr>
            @endforeach

            <!-- TOTAL -->
            <tr class="total">
                <td colspan="5" class="text-center">TOTAL</td>
                <td class="text-right">{{ number_format($totalMasuk, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($totalKeluar, 0, ',', '.') }}</td>
                <td></td>
            </tr>

            <!-- SALDO -->
            <tr class="saldo">
                <td colspan="5" class="text-center">SALDO {{ ($paymentMethod ?? 'cash') === 'transfer' ? 'TRANSFER' : 'KAS' }}</td>
                <td class="text-right">{{ number_format($saldo, 0, ',', '.') }}</td>
                <td></td>
                <td></td>
            </tr>

        </tbody>
    </table>

</body>
</html>