<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak Bill Penjualan - {{ $storeName }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 20px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 4px;
        }

        .subtitle {
            font-size: 13px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 4px;
        }

        .meta {
            font-size: 11px;
            color: #4b5563;
        }

        .bill-card {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 14px;
            page-break-inside: avoid;
        }

        .bill-header {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            border-bottom: 1px dashed #9ca3af;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }

        .badge {
            display: inline-block;
            background: #eef2ff;
            color: #3730a3;
            border: 1px solid #c7d2fe;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        th, td {
            border: 1px solid #e5e7eb;
            padding: 5px 8px;
        }

        th {
            background-color: #f3f4f6;
            color: #111827;
            font-weight: bold;
            text-align: left;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .summary-box {
            background-color: #eef2ff;
            border: 1px solid #c7d2fe;
            border-radius: 6px;
            padding: 10px 14px;
            margin-top: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: bold;
            font-size: 13px;
            color: #312e81;
        }

        .print-btn {
            background-color: #4f46e5;
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
                margin: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-btn" onclick="window.print()">Cetak / Print Bill</button>
    </div>

    <div class="header">
        <div class="title">RINCIAN BILL PENJUALAN</div>
        <div class="subtitle">{{ $storeName }}</div>
        <div class="meta">
            Periode: {{ $mulai }} s/d {{ $akhir }}
            @if (!empty($tableNumber))
                | Filter Meja: <strong>{{ $tableNumber }}</strong>
            @endif
            | Dicetak: {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>

    @php
        $grandTotalAll = 0;
    @endphp

    @forelse ($sales as $idx => $sale)
        @php
            $grandTotal = (float) ($sale->grand_total ?? 0);
            $grandTotalAll += $grandTotal;
            $itemDiscount = (float) $sale->items->sum('discount_amount');
            $transDiscount = (float) ($sale->trans_discount ?? 0)
                + (float) ($sale->voucher_discount_amount ?? 0)
                + (float) ($sale->point_discount_amount ?? 0);
            $totalDiscount = $itemDiscount + $transDiscount;
            if ($totalDiscount <= 0 && (float) ($sale->discount_total ?? 0) > 0) {
                $totalDiscount = (float) $sale->discount_total;
            }
            $metode = $cashMethods[$sale->id] ?? '-';
        @endphp

        <div class="bill-card">
            <div class="bill-header">
                <div>
                    <strong>#{{ $idx + 1 }} &mdash; {{ $sale->invoice_number ?? '-' }}</strong>
                    @if (!empty($sale->table_number))
                        <span class="badge">Meja: {{ $sale->table_number }}</span>
                    @endif
                    <div class="meta" style="margin-top: 3px;">
                        Tanggal: {{ optional($sale->sale_date)->format('Y-m-d H:i') ?? '-' }}
                        | Pelanggan: {{ $sale->customer_name ?: 'Umum' }}
                        | Kasir: {{ optional($sale->cashier)->name ?? '-' }}
                    </div>
                </div>
                <div class="text-right">
                    <div>Status: <strong>{{ strtoupper($sale->status ?? '-') }}</strong></div>
                    <div class="meta">Pembayaran: {{ ucfirst($metode) }}</div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;" class="text-center">No</th>
                        <th>Item / Menu</th>
                        <th style="width: 10%;" class="text-center">Qty</th>
                        <th style="width: 18%;" class="text-right">Harga</th>
                        <th style="width: 15%;" class="text-right">Diskon</th>
                        <th style="width: 20%;" class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sale->items as $itemIdx => $item)
                        @php
                            $productName = $item->product_name
                                ?? optional(optional($item->variant)->product)->nama_produk
                                ?? 'Item';
                            $variantName = $item->variant_name ?? optional($item->variant)->variant_name;
                            $notes = $item->notes ?? optional($item->fnbDetail)->notes;
                            $qty = (float) ($item->qty ?? 0);
                            $price = (float) ($item->price ?? 0);
                            $disc = (float) ($item->discount_amount ?? 0);
                            $subtotal = (float) ($item->subtotal ?? (($qty * $price) - $disc));
                        @endphp
                        <tr>
                            <td class="text-center">{{ $itemIdx + 1 }}</td>
                            <td>
                                <strong>{{ $productName }}</strong>
                                @if (!empty($variantName))
                                    <span class="meta">({{ $variantName }})</span>
                                @endif
                                @if (!empty($notes))
                                    <div class="meta">Catatan: {{ $notes }}</div>
                                @endif
                            </td>
                            <td class="text-center">{{ rtrim(rtrim(number_format($qty, 2, ',', '.'), '0'), ',') }}</td>
                            <td class="text-right">Rp {{ number_format($price, 0, ',', '.') }}</td>
                            <td class="text-right">{{ $disc > 0 ? '-Rp ' . number_format($disc, 0, ',', '.') : '-' }}</td>
                            <td class="text-right">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="display: flex; justify-content: flex-end; gap: 20px; font-size: 11px;">
                @if ($totalDiscount > 0)
                    <span>Total Diskon: <strong>-Rp {{ number_format($totalDiscount, 0, ',', '.') }}</strong></span>
                @endif
                @if ((float) ($sale->tip_amount ?? 0) > 0)
                    <span>Tip: <strong>Rp {{ number_format((float) $sale->tip_amount, 0, ',', '.') }}</strong></span>
                @endif
                <span>Grand Total: <strong>Rp {{ number_format($grandTotal, 0, ',', '.') }}</strong></span>
            </div>
        </div>
    @empty
        <p style="text-align: center; color: #6b7280; padding: 20px;">Tidak ada data transaksi pada periode dan filter meja ini.</p>
    @endforelse

    @if ($sales->isNotEmpty())
        <div class="summary-box">
            <span>Total Bill: {{ $sales->count() }} Transaksi</span>
            <span>TOTAL KESELURUHAN: Rp {{ number_format($grandTotalAll, 0, ',', '.') }}</span>
        </div>
    @endif
</body>
</html>
