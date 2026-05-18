<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $transaction['invoice'] }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            padding: 20px;
        }

        .receipt {
            background: #fff;
            width: 80mm;
            padding: 12px 14px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .header {
            text-align: center;
            margin-bottom: 8px;
        }

        .header h2 {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .header p {
            font-size: 11px;
            color: #555;
            line-height: 1.5;
        }

        .divider {
            border-top: 1px dashed #999;
            margin: 6px 0;
        }

        .divider-solid {
            border-top: 1px solid #333;
            margin: 6px 0;
        }

        .meta {
            font-size: 11px;
            line-height: 1.7;
        }

        .meta tr td:last-child {
            text-align: right;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        .items th {
            font-size: 11px;
            border-bottom: 1px dashed #999;
            padding: 3px 2px;
            text-align: left;
        }

        .items th.right,
        .items td.right {
            text-align: right;
        }

        .items td {
            font-size: 11px;
            padding: 3px 2px;
            vertical-align: top;
        }

        .items .item-name {
            font-weight: bold;
        }

        .items .item-detail {
            color: #666;
            font-size: 10px;
        }

        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .summary td {
            font-size: 11px;
            padding: 2px 0;
        }

        .summary td:last-child {
            text-align: right;
        }

        .summary .total-row td {
            font-size: 13px;
            font-weight: bold;
            padding-top: 4px;
            border-top: 1px solid #333;
        }

        .badge-status {
            display: inline-block;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: bold;
            border: 1px solid #333;
            border-radius: 3px;
            text-transform: uppercase;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            color: #777;
            margin-top: 10px;
            line-height: 1.6;
        }

        .btn-print {
            display: block;
            width: 80mm;
            margin: 16px auto 0;
            padding: 8px;
            background: #4f46e5;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
        }

        .btn-print:hover {
            background: #3730a3;
        }

        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }

            html,
            body {
                width: 80mm;
                height: auto;
                margin: 0;
                padding: 0;
                overflow: hidden;
            }

            .receipt {
                width: 80mm;
                padding: 5mm;
                margin: 0;
                border: none;
                box-shadow: none;
                page-break-after: avoid;
            }

            .btn-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div>
        <div class="receipt">
            {{-- HEADER --}}
            <div class="header">
                @if (!empty($store['logo']))
                    <img src="{{ $store['logo'] }}" alt="Logo" style="max-width: 50px; margin-bottom: 4px;">
                @endif
                <h2>{{ $store['name'] }}</h2>
                @if (!empty($store['address']))
                    <p>{{ $store['address'] }}</p>
                @endif
                @if (!empty($store['phone']))
                    <p>Telp: {{ $store['phone'] }}</p>
                @endif
            </div>

            <div class="divider-solid"></div>

            {{-- META --}}
            <table class="meta" style="width:100%; border-collapse:collapse;">
                <tr>
                    <td>No. Invoice</td>
                    <td style="text-align:right;">{{ $transaction['invoice'] }}</td>
                </tr>
                <tr>
                    <td>Tanggal</td>
                    <td style="text-align:right;">{{ $transaction['date'] }}</td>
                </tr>
                <tr>
                    <td>Kasir</td>
                    <td style="text-align:right;">{{ $transaction['cashier'] }}</td>
                </tr>
                <tr>
                    <td>Pelanggan</td>
                    <td style="text-align:right;">{{ $transaction['customer'] }}</td>
                </tr>
                <tr>
                    <td>Status</td>
                    <td style="text-align:right;">
                        <span class="badge-status">{{ $transaction['status'] }}</span>
                    </td>
                </tr>
            </table>

            <div class="divider"></div>

            {{-- ITEMS --}}
            <table class="items">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th class="right">Qty</th>
                        <th class="right">Harga</th>
                        <th class="right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td>
                                <div class="item-name">{{ $item['name'] }}</div>
                                <div class="item-detail">{{ $item['sku'] }}</div>
                            </td>
                            <td class="right">{{ $item['qty'] }}</td>
                            <td class="right">{{ number_format($item['price'], 0, ',', '.') }}</td>
                            <td class="right">{{ number_format($item['qty'] * $item['price'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="divider"></div>

            {{-- SUMMARY --}}
            <table class="summary">
                <tr>
                    <td>Subtotal</td>
                    <td>Rp {{ number_format($summary['subtotal'], 0, ',', '.') }}</td>
                </tr>
                @if ($summary['discount'] > 0)
                    <tr>
                        <td>Diskon</td>
                        <td>- Rp {{ number_format($summary['discount'], 0, ',', '.') }}</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td>TOTAL</td>
                    <td>Rp {{ number_format($summary['total'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Bayar</td>
                    <td>Rp {{ number_format($summary['paid'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Kembali</td>
                    <td>Rp {{ number_format($summary['change'], 0, ',', '.') }}</td>
                </tr>
            </table>

            <div class="divider-solid"></div>

            {{-- FOOTER --}}
            <div class="footer">
                <p>Terima kasih atas kunjungan Anda</p>
                <p>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan</p>
            </div>
        </div>

        <button class="btn-print" onclick="window.print()">
            &#128438; Cetak Struk
        </button>
    </div>
    <script type="text/javascript">
        // Fungsi untuk mencetak struk
        window.onload = function() {
            window.print();
        }

        window.onafterprint = function() {
            window.close();
        }
    </script>
</body>

</html>
