<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Rekonsiliasi Titipan - {{ $settlement->settlement_number }}</title>
    <style>
        @page {
            margin: 0;
            size: {{ $settlement->store->printer_type === '80mm' ? '80mm' : '58mm' }} auto;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 8px 6px;
            width: {{ $settlement->store->printer_type === '80mm' ? '72mm' : '48mm' }};
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 5px 0; }
        .divider-double { border-top: 2px solid #000; margin: 6px 0; }
        .title { font-size: 13px; font-weight: bold; }
        .subtitle { font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 4px 0; }
        th, td { font-size: 10px; padding: 2px 0; }
        .no-print { margin-top: 15px; text-align: center; }
        .btn-print {
            background: #4f46e5; color: #fff; border: none; padding: 6px 12px;
            border-radius: 4px; font-size: 12px; cursor: pointer;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="text-center">
        <div class="title">{{ strtoupper($settlement->store->name ?? 'KANTIN UNIVERSITAS') }}</div>
        <div class="subtitle">BUKTI REKONSILIASI TITIPAN</div>
        <div class="subtitle">{{ $settlement->settlement_date->translatedFormat('d F Y') }}</div>
    </div>

    <div class="divider-double"></div>

    <table style="width: 100%;">
        <tr>
            <td>No. Bukti</td>
            <td class="text-end fw-bold">{{ $settlement->settlement_number }}</td>
        </tr>
        <tr>
            <td>Penitip</td>
            <td class="text-end fw-bold">{{ $settlement->consignor->nama_penitip ?? '-' }}</td>
        </tr>
        <tr>
            <td>Kasir</td>
            <td class="text-end">{{ $settlement->user->name ?? 'Kasir' }}</td>
        </tr>
        <tr>
            <td>Waktu</td>
            <td class="text-end">{{ $settlement->created_at->format('H:i') }} WIB</td>
        </tr>
    </table>

    <div class="divider"></div>

    <table>
        <thead>
            <tr>
                <th style="text-align: left;">Item</th>
                <th style="text-align: center;">T</th>
                <th style="text-align: center;">S</th>
                <th style="text-align: center;">L</th>
                <th style="text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @if($settlement->dailyConsignment)
                @foreach($settlement->dailyConsignment->items as $item)
                    <tr>
                        <td colspan="5" class="fw-bold">{{ $item->consignorItem->nama_item ?? 'Item' }}</td>
                    </tr>
                    <tr>
                        <td style="color: #444; font-size: 9px;">@ Rp {{ number_format($item->harga_setor, 0, ',', '.') }}</td>
                        <td class="text-center">{{ $item->qty_titip }}</td>
                        <td class="text-center">{{ $item->qty_sisa }}</td>
                        <td class="text-center fw-bold">{{ $item->qty_laku }}</td>
                        <td class="text-end">Rp {{ number_format($item->subtotal_payout, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>

    <div class="divider"></div>

    <table style="width: 100%;">
        <tr>
            <td class="fw-bold">TOTAL DIBAYAR</td>
            <td class="text-end fw-bold" style="font-size: 13px;">Rp {{ number_format($settlement->total_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Metode Bayar</td>
            <td class="text-end fw-bold">{{ $settlement->payment_method }}</td>
        </tr>
        @if($settlement->audit_status)
        <tr>
            <td>Audit POS Kasir</td>
            <td class="text-end fw-bold">
                @if($settlement->audit_status === 'MATCH')
                    KLOP (POS: {{ $settlement->total_pos_qty }})
                @elseif($settlement->audit_status === 'UNDER')
                    SELISIH (POS: {{ $settlement->total_pos_qty }} | Var: {{ $settlement->variance_qty }})
                @else
                    SURPLUS (POS: {{ $settlement->total_pos_qty }} | +{{ $settlement->variance_qty }})
                @endif
            </td>
        </tr>
        @endif
    </table>

    <div class="divider-double"></div>

    <div style="margin-top: 15px; font-size: 9px; text-align: center;">
        <table style="width: 100%;">
            <tr>
                <td style="text-align: center; width: 50%;">
                    Tanda Terima<br><br><br>
                    ( {{ $settlement->consignor->nama_penitip ?? 'Penitip' }} )
                </td>
                <td style="text-align: center; width: 50%;">
                    Kasir<br><br><br>
                    ( {{ $settlement->user->name ?? 'Kasir' }} )
                </td>
            </tr>
        </table>
        <div style="margin-top: 10px; font-style: italic;">
            * T = Titip Pagi, S = Sisa Retur, L = Laku
        </div>
    </div>

    <div class="no-print">
        <button class="btn-print" onclick="window.print()">🖨️ Cetak Struk</button>
    </div>

    <script>
        // Auto print on load if opened in popup
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 300);
        };
    </script>
</body>
</html>
