<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Gagal - RIMS POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8fafc; font-family: system-ui, -apple-system, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card-box { max-width: 480px; width: 100%; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); border: none; overflow: hidden; background: #fff; text-align: center; padding: 40px 30px; }
        .icon-circle { width: 80px; height: 80px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 40px; margin-bottom: 20px; }
        .icon-danger { background: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>
    <div class="card-box">
        <div class="icon-circle icon-danger">
            <i class="bi bi-x-lg"></i>
        </div>
        <h3 class="fw-bold text-dark mb-2">Pembayaran Gagal</h3>
        <p class="text-muted small mb-4">Terjadi kesalahan atau transaksi dibatalkan. Silakan lakukan pembayaran ulang di kasir.</p>

        @if(!empty($orderId))
        <div class="bg-light p-3 rounded-3 text-start small mb-4">
            <div class="d-flex justify-content-between">
                <span class="text-muted">No. Referensi:</span>
                <span class="fw-bold text-dark font-monospace">{{ $orderId }}</span>
            </div>
        </div>
        @endif

        <p class="small text-muted mb-0">Silakan hubungi kasir atau staf toko untuk bantuan.</p>
    </div>
</body>
</html>
