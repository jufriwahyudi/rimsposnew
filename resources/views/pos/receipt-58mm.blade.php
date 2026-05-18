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
            font-size: 10px;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            padding: 16px;
        }

        .receipt {
            background: #fff;
            width: 58mm;
            padding: 8px 10px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .header {
            text-align: center;
            margin-bottom: 6px;
        }

        .header h2 {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header p {
            font-size: 9px;
            color: #555;
            line-height: 1.4;
        }

        .divider {
            border-top: 1px dashed #999;
            margin: 5px 0;
        }

        .divider-solid {
            border-top: 1px solid #333;
            margin: 5px 0;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            line-height: 1.6;
        }

        .meta td:last-child {
            text-align: right;
        }

        /* Items: nama full-width, qty×harga rata kanan di baris ke-2 */
        .items {
            width: 100%;
            border-collapse: collapse;
        }

        .item-row-name td {
            font-size: 9.5px;
            font-weight: bold;
            padding: 3px 0 0;
        }

        .item-row-detail td {
            font-size: 9px;
            color: #444;
            padding: 0 0 3px;
        }

        .item-row-detail .right {
            text-align: right;
        }

        .item-sku {
            font-size: 8.5px;
            color: #888;
        }

        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        .summary td {
            font-size: 9px;
            padding: 1px 0;
        }

        .summary td:last-child {
            text-align: right;
        }

        .summary .total-row td {
            font-size: 11px;
            font-weight: bold;
            padding-top: 4px;
            border-top: 1px solid #333;
        }

        .badge-status {
            display: inline-block;
            padding: 1px 4px;
            font-size: 8px;
            font-weight: bold;
            border: 1px solid #333;
            border-radius: 2px;
            text-transform: uppercase;
        }

        .footer {
            text-align: center;
            font-size: 8.5px;
            color: #777;
            margin-top: 8px;
            line-height: 1.5;
        }

        .btn-print {
            display: block;
            width: 58mm;
            margin: 14px auto 0;
            padding: 7px;
            background: #4f46e5;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
        }

        .btn-print:hover {
            background: #3730a3;
        }

        @media print {
            @page {
                size: 58mm auto;
                margin: 0;
            }

            html,
            body {
                width: 58mm;
                height: auto;
                margin: 0;
                padding: 0;
                overflow: hidden;
            }

            .receipt {
                width: 58mm;
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
            <table class="meta">
                <tr>
                    <td>Invoice</td>
                    <td>{{ $transaction['invoice'] }}</td>
                </tr>
                <tr>
                    <td>Tgl</td>
                    <td>{{ $transaction['date'] }}</td>
                </tr>
                <tr>
                    <td>Kasir</td>
                    <td>{{ $transaction['cashier'] }}</td>
                </tr>
                <tr>
                    <td>Pelanggan</td>
                    <td>{{ $transaction['customer'] }}</td>
                </tr>
                <tr>
                    <td>Status</td>
                    <td><span class="badge-status">{{ $transaction['status'] }}</span></td>
                </tr>
            </table>

            <div class="divider"></div>

            {{-- ITEMS: 2-baris per item agar muat di 58mm --}}
            <table class="items">
                <tbody>
                    @foreach ($items as $item)
                        {{-- Baris 1: nama produk + SKU --}}
                        <tr class="item-row-name">
                            <td colspan="2">
                                {{ $item['name'] }}
                                <span class="item-sku">({{ $item['sku'] }})</span>
                            </td>
                        </tr>
                        {{-- Baris 2: qty x harga = subtotal --}}
                        <tr class="item-row-detail">
                            <td>{{ $item['qty'] }} x {{ number_format($item['price'], 0, ',', '.') }}</td>
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
                    <td>{{ number_format($summary['subtotal'], 0, ',', '.') }}</td>
                </tr>
                @if ($summary['discount'] > 0)
                    <tr>
                        <td>Diskon</td>
                        <td>-{{ number_format($summary['discount'], 0, ',', '.') }}</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td>TOTAL</td>
                    <td>{{ number_format($summary['total'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Bayar</td>
                    <td>{{ number_format($summary['paid'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Kembali</td>
                    <td>{{ number_format($summary['change'], 0, ',', '.') }}</td>
                </tr>
            </table>

            <div class="divider-solid"></div>

            {{-- FOOTER --}}
            <div class="footer">
                <p>Terima kasih atas kunjungan Anda</p>
                <p>Barang yang dibeli tidak dapat ditukar/dikembalikan</p>
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
