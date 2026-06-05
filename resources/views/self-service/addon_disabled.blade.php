<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layanan Tidak Aktif - {{ $store->name }}</title>
    <!-- Tailwind CSS (optional or basic custom CSS for premium look) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f9fafb;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.04);
            border-radius: 20px;
            padding: 40px 30px;
            max-width: 450px;
            text-align: center;
            background: #ffffff;
        }
        .icon-wrap {
            width: 80px;
            height: 80px;
            background-color: #fef2f2;
            color: #ef4444;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 38px;
            margin-bottom: 24px;
        }
    </style>
</head>
<body>
    <div class="card mx-3">
        <div class="icon-wrap">
            <i class="bi bi-shield-slash"></i>
        </div>
        <h4 class="fw-bold mb-2" style="color: #1f2937;">Layanan Tidak Aktif</h4>
        <p class="text-muted mb-4" style="font-size: 14px; line-height: 1.6;">
            Maaf, fitur **Customer Self-Service (QR Order)** saat ini tidak diaktifkan untuk toko **{{ $store->name }}**. Silakan hubungi pelayan kami untuk melakukan pemesanan secara manual.
        </p>
        <div class="border-top pt-3 text-muted" style="font-size: 12px;">
            RIMS POS &bull; FnB Customer Portal
        </div>
    </div>
</body>
</html>
