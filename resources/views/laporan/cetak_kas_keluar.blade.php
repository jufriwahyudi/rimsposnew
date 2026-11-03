<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Kas Keluar - {{ $storeName }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 20px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #dc2626;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 4px;
        }

        .subtitle {
            font-size: 13px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 4px;
        }

        .meta {
            font-size: 10px;
            color: #6b7280;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
        }

        th {
            background-color: #fee2e2;
            color: #991b1b;
            font-weight: bold;
            text-align: center;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }

        .total-row {
            background-color: #fee2e2;
            font-weight: bold;
            color: #991b1b;
        }

        .badge-shift {
            display: inline-block;
            background: #e5e7eb;
            color: #374151;
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
        }

        .print-btn {
            background-color: #dc2626;
            color: white;
            border: none;
            padding: 8px 16px;
            font-size: 12px;
            border-radius: 6px;
            cursor: pointer;
            margin-bottom: 15px;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: right;">
        <button class="print-btn" onclick="window.print()">🖨️ Cetak Dokumen</button>
    </div>

    <div class="header">
        <div class="title">LAPORAN KAS KELUAR</div>
        <div class="subtitle">{{ $storeName }}</div>
        <div class="meta">
            Periode: {{ \Carbon\Carbon::parse($startDateTime)->format('d/m/Y H:i') }} s/d {{ \Carbon\Carbon::parse($endDateTime)->format('d/m/Y H:i') }}
            &bull; Dicetak: {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 110px;">Waktu Transaksi</th>
                <th style="width: 110px;">Shift</th>
                <th style="width: 120px;">Petugas / Kasir</th>
                <th>Keperluan / Keterangan</th>
                <th style="width: 120px;" class="text-right">Nominal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $index => $trx)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">
                        {{ \Carbon\Carbon::parse($trx->transaction_date)->format('d/m/Y H:i') }}
                    </td>
                    <td class="text-center">
                        @if ($trx->cash_register_id)
                            <span class="badge-shift">Shift #{{ $trx->cash_register_id }}</span>
                            @if ($trx->shift_opened_by)
                                <div style="font-size: 9px; color: #6b7280; margin-top: 2px;">{{ $trx->shift_opened_by }}</div>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $trx->cashier_name ?: '-' }}</td>
                    <td>
                        {{ $trx->notes ?: '-' }}
                        <div style="font-size: 9px; color: #9ca3af;">#{{ $trx->id }}</div>
                    </td>
                    <td class="text-right" style="font-weight: bold; color: #dc2626;">
                        Rp {{ number_format($trx->amount, 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 20px; color: #6b7280;">
                        Tidak ada transaksi kas keluar pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if ($transactions->isNotEmpty())
            <tfoot>
                <tr class="total-row">
                    <td colspan="5" class="text-right">TOTAL KAS KELUAR ({{ $transactions->count() }} Transaksi):</td>
                    <td class="text-right" style="font-size: 13px;">
                        Rp {{ number_format($totalNominal, 0, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>

    <table style="border: none; margin-top: 40px; width: 100%;">
        <tr style="border: none;">
            <td style="border: none; width: 50%; text-align: center;">
                <div>Dibuat Oleh:</div>
                <div style="height: 60px;"></div>
                <div>( ........................................ )</div>
                <div style="font-size: 9px; color: #6b7280;">Kasir / Petugas</div>
            </td>
            <td style="border: none; width: 50%; text-align: center;">
                <div>Mengetahui:</div>
                <div style="height: 60px;"></div>
                <div>( ........................................ )</div>
                <div style="font-size: 9px; color: #6b7280;">Manager / Supervisor Store</div>
            </td>
        </tr>
    </table>

</body>
</html>

