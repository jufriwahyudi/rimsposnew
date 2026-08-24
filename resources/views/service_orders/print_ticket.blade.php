<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tanda Terima Servis - {{ $serviceOrder->order_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .ticket-container {
            max-width: 600px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
        }
        .header {
            text-align: center;
            border-bottom: 2px dashed #999;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .store-name {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .ticket-title {
            font-size: 14px;
            font-weight: bold;
            margin-top: 5px;
            letter-spacing: 1px;
        }
        .order-number {
            font-size: 20px;
            font-weight: bold;
            color: #7c3aed;
            margin: 8px 0;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .info-table td {
            padding: 4px 0;
            vertical-align: top;
        }
        .info-table .label {
            width: 35%;
            color: #666;
        }
        .info-table .value {
            font-weight: 500;
        }
        .box-notes {
            background-color: #f9f9f9;
            border: 1px solid #eee;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .terms {
            font-size: 11px;
            color: #666;
            line-height: 1.4;
            border-top: 1px dashed #ccc;
            padding-top: 10px;
            margin-bottom: 20px;
        }
        .signature-section {
            display: flex;
            justify-content: space-between;
            text-align: center;
            margin-top: 30px;
        }
        .signature-box {
            width: 45%;
        }
        .signature-line {
            margin-top: 50px;
            border-top: 1px solid #333;
        }
        @media print {
            body { padding: 0; }
            .ticket-container { border: none; padding: 0; width: 100%; max-width: 100%; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align:center; margin-bottom: 15px;">
        <button onclick="window.print()" style="padding: 8px 16px; background:#7c3aed; color:#fff; border:none; border-radius:4px; cursor:pointer; font-weight:bold;">
            🖨️ Cetak Tanda Terima
        </button>
    </div>

    <div class="ticket-container">
        <div class="header">
            <div class="store-name">{{ $store->name }}</div>
            @if ($store->address)
                <div>{{ $store->address }}</div>
            @endif
            @if ($store->phone)
                <div>Telp/WA: {{ $store->phone }}</div>
            @endif
            <div class="ticket-title">SURAT TANDA TERIMA SERVIS / WORK ORDER</div>
            <div class="order-number">#{{ $serviceOrder->order_number }}</div>
            <small>Tanggal Masuk: {{ $serviceOrder->created_at->format('d/m/Y H:i') }}</small>
        </div>

        <table class="info-table">
            <tr>
                <td class="label">Nama Pelanggan</td>
                <td class="value">: <strong>{{ $serviceOrder->customer_name }}</strong></td>
            </tr>
            <tr>
                <td class="label">No. Telepon / WhatsApp</td>
                <td class="value">: {{ $serviceOrder->customer_phone ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Unit / Objek Servis</td>
                <td class="value">: <strong>{{ $serviceOrder->target_name }}</strong></td>
            </tr>
            @if ($serviceOrder->target_identifier)
                <tr>
                    <td class="label">No. Identitas (IMEI/SN/Plat)</td>
                    <td class="value">: {{ $serviceOrder->target_identifier }}</td>
                </tr>
            @endif
            <tr>
                <td class="label">Teknisi / Staff PIC</td>
                <td class="value">: {{ $serviceOrder->assignedStaff?->name ?? 'Tim Teknisi' }}</td>
            </tr>
            @if ($serviceOrder->estimated_completed_at)
                <tr>
                    <td class="label">Estimasi Selesai</td>
                    <td class="value">: {{ $serviceOrder->estimated_completed_at->format('d/m/Y H:i') }}</td>
                </tr>
            @endif
        </table>

        <div class="box-notes">
            <strong>Keluhan Pelanggan:</strong><br>
            {{ $serviceOrder->complaint_notes }}
        </div>

        @if ($serviceOrder->diagnosis_notes)
            <div class="box-notes">
                <strong>Catatan Awal / Kelengkapan:</strong><br>
                {{ $serviceOrder->diagnosis_notes }}
            </div>
        @endif

        @if ($serviceOrder->down_payment > 0)
            <div style="margin-bottom: 15px; font-size: 14px;">
                <strong>Uang Muka (DP):</strong> Rp {{ number_format($serviceOrder->down_payment, 0, ',', '.') }}
            </div>
        @endif

        <div class="terms">
            <strong>Syarat & Ketentuan:</strong>
            <ol style="margin: 5px 0 0 15px; padding: 0;">
                <li>Surat tanda terima ini wajib dibawa saat pengambilan unit servis.</li>
                <li>Barang servis yang tidak diambil lebih dari 30 hari di luar tanggung jawab pihak toko.</li>
                <li>Garansi pengerjaan berlaku {{ $serviceOrder->warranty_days }} hari sejak tanggal pengambilan unit.</li>
            </ol>
        </div>

        <div class="signature-section">
            <div class="signature-box">
                Pelanggan,
                <div class="signature-line">({{ $serviceOrder->customer_name }})</div>
            </div>
            <div class="signature-box">
                Petugas / Teknisi,
                <div class="signature-line">({{ $serviceOrder->assignedStaff?->name ?? auth()->user()->name ?? 'Petugas' }})</div>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            // Uncomment if auto-print is desired
            // window.print();
        };
    </script>
</body>
</html>
