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
            table-layout: fixed;
        }

        /* Lebar kolom hanya untuk baris detail (qty×harga | subtotal) */
        .item-row-detail td:first-child {
            width: 60%;
        }

        .item-row-detail td:last-child {
            width: 40%;
        }

        /* Untuk item panjang pada 58mm: nama dan SKU baris terpisah, full width */
        .item-row-name td {
            word-break: break-word;
            white-space: normal;
            padding: 4px 0 0;
            font-size: 9.5px;
            font-weight: bold;
        }

        .item-row-sku td {
            word-break: break-word;
            white-space: normal;
            padding: 0;
            font-size: 8.5px;
            color: #888;
        }

        .item-row-detail td {
            overflow-wrap: break-word;
            word-break: break-word;
            white-space: normal;
            padding: 1px 0 4px;
            font-size: 9px;
            color: #444;
        }

        .item-row-detail .right {
            text-align: right;
        }

        .item-subtotal {
            white-space: nowrap;
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

            /* Nonaktifkan subpixel antialiasing — penyebab utama blur di thermal printer */
            * {
                -webkit-font-smoothing: none !important;
                -moz-osx-font-smoothing: unset !important;
                font-smooth: never !important;
                text-rendering: optimizeSpeed !important;
                color: #000 !important;
                background: transparent !important;
                box-shadow: none !important;
                text-shadow: none !important;
                /* Thermal printer: stroke tipis tidak tercetak jelas, paksa bold */
                font-weight: bold !important;
            }

            html,
            body {
                width: 58mm;
                height: auto;
                margin: 0;
                padding: 0;
                overflow: hidden;
                font-size: 7pt;
                -webkit-text-size-adjust: 100%;
                text-size-adjust: 100%;
            }

            .receipt {
                width: 58mm;
                padding: 5mm;
                margin: 0;
                border: none;
                box-shadow: none;
                page-break-after: avoid;
            }

            .header h2 {
                font-size: 9pt;
            }

            .header p {
                font-size: 7pt;
            }

            .meta {
                font-size: 7pt;
            }

            /* Print-specific untuk baris nama, SKU, dan detail */
            .item-row-name td {
                white-space: normal !important;
                word-break: break-word !important;
                font-size: 7.5pt !important;
                padding: 4px 0 0 !important;
            }

            .item-row-sku td {
                white-space: normal !important;
                word-break: break-word !important;
                font-size: 6.5pt !important;
                padding: 0 !important;
            }

            .item-row-detail td {
                overflow-wrap: break-word !important;
                word-break: break-word !important;
                white-space: normal !important;
                font-size: 7pt !important;
                padding: 1px 0 4px !important;
            }

            /* Subtotal tidak boleh dibungkus, override white-space: normal di atas */
            .item-subtotal {
                white-space: nowrap !important;
                text-align: right !important;
            }

            .summary td {
                font-size: 7pt;
            }

            .summary .total-row td {
                font-size: 9pt;
            }

            .footer {
                font-size: 6.5pt;
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
                @if (isset($summary['payment_status']) && $summary['payment_status'] === 'hutang')
                <tr>
                    <td>Status Bayar</td>
                    <td><span class="badge-status" style="border-color: #dc3545; color: #dc3545; font-weight: bold;">HUTANG</span></td>
                </tr>
                @endif
            </table>

            <div class="divider"></div>

            {{-- ITEMS: 2-baris per item agar muat di 58mm --}}
            <table class="items">
                <tbody>
                    @foreach ($items as $item)
                        {{-- Baris 1: nama produk --}}
                        <tr class="item-row-name">
                            <td colspan="2">{{ $item['name'] }}</td>
                        </tr>
                        {{-- Baris 2: SKU --}}
                        <tr class="item-row-sku">
                            <td colspan="2">({{ $item['sku'] }})</td>
                        </tr>
                        {{-- Baris 3: qty x harga = subtotal --}}
                        <tr class="item-row-detail">
                            <td>{{ $item['qty'] }} x {{ number_format($item['price'], 0, ',', '.') }}</td>
                            <td class="right item-subtotal">
                                {{ number_format($item['qty'] * $item['price'], 0, ',', '.') }}</td>
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
                @if (isset($summary['payment_status']) && $summary['payment_status'] === 'hutang')
                <tr class="total-row" style="color: #dc3545;">
                    <td>SISA HUTANG</td>
                    <td>{{ number_format($summary['remaining_debt'], 0, ',', '.') }}</td>
                </tr>
                @endif
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
