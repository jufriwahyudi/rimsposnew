<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Status Pembayaran' }} - RIMS POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8fafc; font-family: system-ui, -apple-system, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card-box { max-width: 480px; width: 100%; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); border: none; overflow: hidden; background: #fff; text-align: center; padding: 40px 30px; }
        .icon-circle { width: 80px; height: 80px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 40px; margin-bottom: 20px; }
        .icon-success { background: #dcfce7; color: #16a34a; }
        .icon-warning { background: #fef3c7; color: #d97706; }
        .icon-danger { background: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>
    <div class="card-box">
        <div class="icon-circle icon-success">
            <i class="bi bi-check-lg"></i>
        </div>
        <h3 class="fw-bold text-dark mb-2">Pembayaran Berhasil!</h3>
        <p class="text-muted small mb-4">Terima kasih, transaksi Anda telah diverifikasi oleh sistem RIMS POS.</p>

        @if(!empty($orderId))
        <div class="bg-light p-3 rounded-3 text-start small mb-4">
            <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">No. Referensi:</span>
                <span class="fw-bold text-dark font-monospace">{{ $orderId }}</span>
            </div>
            @if(!empty($qrisTx) && $qrisTx->gross_amount)
            <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Total Bayar:</span>
                <span class="fw-bold text-success">Rp {{ number_format($qrisTx->gross_amount, 0, ',', '.') }}</span>
            </div>
            @endif
            <div class="d-flex justify-content-between">
                <span class="text-muted">Metode:</span>
                <span class="badge bg-primary">QRIS Dinamis</span>
            </div>
        </div>
        @endif

        <p class="small text-muted mb-0">Pesanan telah diteruskan ke kasir dan dapur. Anda dapat menutup halaman ini.</p>
    </div>
</body>
</html>
