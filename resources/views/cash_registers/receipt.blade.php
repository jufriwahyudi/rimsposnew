<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Tutup Kasir #{{ $register->id }} - {{ $store->name }}</title>
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
            width: {{ ($store->printer_type ?? '80mm') === '58mm' ? '58mm' : '80mm' }};
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
            line-height: 1.4;
        }

        .divider {
            border-top: 1px dashed #777;
            margin: 6px 0;
        }

        .divider-solid {
            border-top: 1px solid #333;
            margin: 6px 0;
        }

        .row-item {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 3px;
        }

        .row-item.bold {
            font-weight: bold;
            font-size: 12px;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            color: #777;
            margin-top: 12px;
        }

        .no-print {
            text-align: center;
            margin-bottom: 15px;
        }

        .btn-print {
            padding: 8px 16px;
            background: #7c3aed;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .receipt {
                border: none;
                box-shadow: none;
                width: 100%;
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div style="display: flex; flex-direction: column; align-items: center;">
        <div class="no-print">
            <button class="btn-print" onclick="window.print()">🖨️ Cetak Struk</button>
            <button class="btn-print" style="background:#6c757d; margin-left: 6px;" onclick="window.close()">Tutup</button>
        </div>

        <div class="receipt">
            <div class="header">
                <h2>{{ $store->name }}</h2>
                @if($store->address)
                    <p>{{ $store->address }}</p>
                @endif
                @if($store->phone)
                    <p>Telp: {{ $store->phone }}</p>
                @endif
                <div class="divider-solid"></div>
                <h3 style="font-size: 12px; font-weight: bold; margin: 4px 0;">LAPORAN TUTUP KASIR (SHIFT)</h3>
                <p>Shift #{{ $register->id }}</p>
            </div>

            <div class="divider"></div>

            <div class="row-item">
                <span>Kasir Buka</span>
                <span>{{ $register->cashier?->name ?? '-' }}</span>
            </div>
            <div class="row-item">
                <span>Waktu Buka</span>
                <span>{{ $register->opened_at ? $register->opened_at->format('d/m/y H:i') : '-' }}</span>
            </div>
            <div class="row-item">
                <span>Waktu Tutup</span>
                <span>{{ $register->closed_at ? $register->closed_at->format('d/m/y H:i') : 'Masih Buka' }}</span>
            </div>
            @if($register->closedBy)
                <div class="row-item">
                    <span>Ditutup Oleh</span>
                    <span>{{ $register->closedBy->name }}</span>
                </div>
            @endif

            <div class="divider-solid"></div>

            <div class="row-item">
                <span>Modal Kas Awal:</span>
                <span>Rp {{ number_format($register->opening_cash, 0, ',', '.') }}</span>
            </div>
            <div class="row-item">
                <span>Penjualan Tunai (+):</span>
                <span>Rp {{ number_format($register->total_cash_sales, 0, ',', '.') }}</span>
            </div>
            <div class="row-item">
                <span>Kas Masuk Manual (+):</span>
                <span>Rp {{ number_format($register->total_cash_in, 0, ',', '.') }}</span>
            </div>
            <div class="row-item">
                <span>Kas Keluar Toko (-):</span>
                <span>Rp {{ number_format($register->total_cash_out, 0, ',', '.') }}</span>
            </div>
            <div class="row-item">
                <span>Refund Tunai (-):</span>
                <span>Rp {{ number_format($register->total_refund_cash, 0, ',', '.') }}</span>
            </div>

            <div class="divider"></div>

            <div class="row-item bold">
                <span>TOTAL KAS SEHARUSNYA:</span>
                <span>Rp {{ number_format($register->expected_cash, 0, ',', '.') }}</span>
            </div>
            <div class="row-item bold">
                <span>KAS FISIK AKTUAL:</span>
                <span>Rp {{ number_format($register->actual_cash, 0, ',', '.') }}</span>
            </div>

            <div class="divider-solid"></div>

            <div class="row-item bold" style="font-size: 13px;">
                <span>SELISIH KAS:</span>
                <span>
                    @if($register->cash_difference == 0)
                        PAS (Rp 0)
                    @elseif($register->cash_difference > 0)
                        +Rp {{ number_format($register->cash_difference, 0, ',', '.') }} (LEBIH)
                    @else
                        -Rp {{ number_format(abs($register->cash_difference), 0, ',', '.') }} (KURANG)
                    @endif
                </span>
            </div>

            <div class="divider"></div>

            <div class="row-item">
                <span>Penjualan Non-Tunai:</span>
                <span>Rp {{ number_format($register->total_non_cash_sales, 0, ',', '.') }}</span>
            </div>

            @if($register->notes)
                <div class="divider"></div>
                <div style="font-size: 11px; margin-top: 4px;">
                    <strong>Catatan:</strong>
                    <p style="color: #333; margin-top: 2px;">{{ $register->notes }}</p>
                </div>
            @endif

            <div class="footer">
                <p>Dicetak: {{ date('d/m/Y H:i:s') }}</p>
                <p>-- Terima Kasih --</p>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('load', () => {
            // Optional auto print
            // window.print();
        });
    </script>
</body>
</html>
