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
            background: #f1f5f9;
            display: flex;
            justify-content: center;
            padding: 20px;
            font-family: 'Courier New', Courier, monospace;
        }

        .no-print {
            text-align: center;
            margin-bottom: 15px;
        }

        .btn-print {
            padding: 8px 18px;
            background: #7c3aed;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            font-size: 13px;
        }

        .btn-close-win {
            padding: 8px 18px;
            background: #64748b;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            margin-left: 8px;
            font-size: 13px;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .no-print {
                display: none;
            }
            .thermal-receipt-wrapper {
                padding: 0 !important;
                background: #fff !important;
            }
            .thermal-receipt {
                border: none !important;
                box-shadow: none !important;
                width: 100% !important;
                padding: 0 !important;
            }
        }
    </style>
</head>
<body>
    <div style="display: flex; flex-direction: column; align-items: center; width: 100%;">
        <div class="no-print">
            <button class="btn-print" onclick="window.print()">🖨️ Cetak Struk</button>
            <button class="btn-close-win" onclick="window.close()">Tutup</button>
        </div>

        @include('cash_registers.partials.receipt_preview')
    </div>

    <script>
        window.addEventListener('load', () => {
            // Auto trigger print if desired
            // window.print();
        });
    </script>
</body>
</html>
