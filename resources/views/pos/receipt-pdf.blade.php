<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $transaction['invoice'] }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 13px;
            color: #334155;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        .container {
            padding: 10px;
        }

        /* Layout Grid using tables for DomPDF compatibility */
        .w-full {
            width: 100%;
        }

        .table-layout {
            border-collapse: collapse;
            border: none;
            margin-bottom: 25px;
        }

        .table-layout td {
            border: none;
            padding: 0;
            vertical-align: top;
        }

        .header-left {
            width: 60%;
        }

        .header-right {
            width: 40%;
            text-align: right;
        }

        .logo {
            max-height: 60px;
            margin-bottom: 8px;
        }

        .store-name {
            font-size: 18px;
            font-weight: bold;
            color: #1e293b;
            margin: 0 0 4px 0;
            text-transform: uppercase;
        }

        .store-info {
            font-size: 11px;
            color: #64748b;
            line-height: 1.4;
        }

        .invoice-title {
            font-size: 24px;
            font-weight: 700;
            color: #4f46e5;
            margin: 0 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .meta-table {
            width: 250px;
            border-collapse: collapse;
            margin-left: auto;
            margin-right: 0;
        }

        .meta-table td {
            font-size: 12px;
            padding: 2px 0;
        }

        .meta-label {
            color: #64748b;
            text-align: left;
            width: 45%;
        }

        .meta-value {
            font-weight: 600;
            color: #1e293b;
            text-align: right;
            width: 55%;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 10px;
            font-weight: bold;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .badge-success {
            background-color: #dcfce7;
            color: #15803d;
        }

        .badge-danger {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        .badge-warning {
            background-color: #fef9c3;
            color: #a16207;
        }

        .badge-secondary {
            background-color: #f1f5f9;
            color: #475569;
        }

        /* Divider line */
        .divider {
            border-bottom: 2px solid #e2e8f0;
            margin: 20px 0;
            clear: both;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .items-table th {
            background-color: #f8fafc;
            border-bottom: 2px solid #cbd5e1;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 10px 8px;
            text-align: left;
        }

        .items-table td {
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 8px;
            font-size: 12px;
            color: #334155;
        }

        .items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .item-name {
            font-weight: 600;
            color: #1e293b;
        }

        .item-sku {
            font-size: 10px;
            color: #64748b;
            display: block;
            margin-top: 2px;
        }

        /* Summary area */
        .summary-wrapper {
            margin-top: 20px;
            width: 100%;
        }

        .summary-left {
            width: 55%;
            font-size: 11px;
            color: #64748b;
        }

        .summary-right {
            width: 45%;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 5px 8px;
            font-size: 12px;
        }

        .summary-table tr.total-row td {
            font-size: 14px;
            font-weight: 700;
            border-top: 2px solid #cbd5e1;
            border-bottom: 2px solid #cbd5e1;
            color: #4f46e5;
            background-color: #f5f3ff;
        }

        .summary-table tr.debt-row td {
            font-size: 13px;
            font-weight: 700;
            color: #b91c1c;
            background-color: #fef2f2;
        }

        .footer {
            margin-top: 80px;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }
    </style>
</head>
<body>

<div class="container">
    {{-- HEADER BLOCK --}}
    <table class="w-full table-layout">
        <tr>
            <td class="header-left">
                @php
                    $logoBase64 = null;
                    if (!empty($store['logo'])) {
                        $cleanedPath = str_replace('/storage/', '', $store['logo']);
                        $fullPath = public_path('storage/' . $cleanedPath);
                        if (file_exists($fullPath)) {
                            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($fullPath));
                        }
                    }
                @endphp
                @if ($logoBase64)
                    <img src="{{ $logoBase64 }}" class="logo" alt="Logo Toko">
                @endif
                <h1 class="store-name">{{ $store['name'] }}</h1>
                <div class="store-info">
                    @if (!empty($store['address']))
                        {{ $store['address'] }}<br>
                    @endif
                    @if (!empty($store['city']))
                        {{ $store['city'] }}<br>
                    @endif
                    @if (!empty($store['phone']))
                        Telp: {{ $store['phone'] }}
                    @endif
                </div>
            </td>
            <td class="header-right">
                <h2 class="invoice-title">Invoice</h2>
                <table class="meta-table">
                    <tr>
                        <td class="meta-label">No. Invoice</td>
                        <td class="meta-value">{{ $transaction['invoice'] }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Tanggal</td>
                        <td class="meta-value">{{ $transaction['date'] }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Kasir</td>
                        <td class="meta-value">{{ $transaction['cashier'] }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Pelanggan</td>
                        <td class="meta-value">{{ $transaction['customer'] }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Status</td>
                        <td class="meta-value">
                            @if($transaction['status'] === 'REFUNDED')
                                <span class="badge badge-warning">Refunded</span>
                            @elseif($transaction['status'] === 'PAID')
                                <span class="badge badge-success">Paid</span>
                            @elseif($transaction['status'] === 'VOID')
                                <span class="badge badge-danger">Void</span>
                            @else
                                <span class="badge badge-secondary">{{ $transaction['status'] }}</span>
                            @endif
                        </td>
                    </tr>
                    @if (isset($summary['payment_status']) && $summary['payment_status'] === 'hutang')
                    <tr>
                        <td class="meta-label">Status Bayar</td>
                        <td class="meta-value">
                            <span class="badge badge-danger">Hutang</span>
                        </td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    {{-- ITEMS TABLE --}}
    <table class="items-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 5%;">No</th>
                <th style="width: 15%;">SKU</th>
                <th style="width: 45%;">Nama Produk</th>
                <th class="text-center" style="width: 10%;">Qty</th>
                <th class="text-right" style="width: 12%;">Harga</th>
                <th class="text-right" style="width: 13%;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @foreach ($items as $item)
                <tr>
                    <td class="text-center">{{ $no++ }}</td>
                    <td>{{ $item['sku'] }}</td>
                    <td>
                        <span class="item-name">{{ $item['name'] }}</span>
                    </td>
                    <td class="text-center">{{ $item['qty'] }}</td>
                    <td class="text-right">Rp {{ number_format($item['price'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item['qty'] * $item['price'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- SUMMARY SECTION --}}
    <table class="w-full table-layout summary-wrapper">
        <tr>
            <td class="summary-left">
                {{-- Space for notes, payment details, stamp or signature --}}
                <div style="margin-top: 10px;">
                    <strong>Catatan:</strong><br>
                    - Invoice ini adalah bukti pembayaran yang sah.<br>
                    - Untuk pertanyaan lebih lanjut, silakan hubungi kontak toko kami.
                </div>
            </td>
            <td class="summary-right">
                <table class="summary-table">
                    <tr>
                        <td style="color: #64748b;">Subtotal</td>
                        <td class="text-right">Rp {{ number_format($summary['subtotal'], 0, ',', '.') }}</td>
                    </tr>
                    @if ($summary['discount'] > 0)
                        <tr>
                            <td style="color: #64748b;">Diskon</td>
                            <td class="text-right text-danger">- Rp {{ number_format($summary['discount'], 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr class="total-row">
                        <td>TOTAL</td>
                        <td class="text-right">Rp {{ number_format($summary['total'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b;">Bayar</td>
                        <td class="text-right">Rp {{ number_format($summary['paid'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b;">Kembali</td>
                        <td class="text-right">Rp {{ number_format($summary['change'], 0, ',', '.') }}</td>
                    </tr>
                    @if (isset($summary['tip']) && $summary['tip'] > 0)
                    <tr>
                        <td style="color: #16a34a; font-weight: bold;">Tip</td>
                        <td class="text-right" style="color: #16a34a; font-weight: bold;">Rp {{ number_format($summary['tip'], 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if (isset($summary['payment_status']) && $summary['payment_status'] === 'hutang')
                    <tr class="debt-row">
                        <td>SISA HUTANG</td>
                        <td class="text-right">Rp {{ number_format($summary['remaining_debt'], 0, ',', '.') }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- FOOTER SECTION --}}
    <div class="footer">
        <p>Terima kasih atas kunjungan Anda</p>
        <p style="font-size: 10px; margin-top: 5px;">Barang yang sudah dibeli tidak dapat ditukar atau dikembalikan</p>
    </div>
</div>

</body>
</html>
