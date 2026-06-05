<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pesanan #{{ $sale->invoice_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f8fafc;
            --surface-color: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --primary-color: #6366f1;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            padding: 30px 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .status-container {
            width: 100%;
            max-width: 480px;
            background: white;
            border-radius: 28px;
            padding: 32px 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
            border: 1px solid #f1f5f9;
        }

        .status-card {
            text-align: center;
            margin-bottom: 30px;
        }

        .status-icon-wrap {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .status-pending {
            background-color: #fffbeb;
            color: #d97706;
            animation: pulse-amber 2s infinite;
        }

        .status-preparing {
            background-color: #f5f3ff;
            color: #6366f1;
            animation: pulse-indigo 2s infinite;
        }

        .status-completed {
            background-color: #f0fdf4;
            color: #16a34a;
        }

        .status-cancelled {
            background-color: #fef2f2;
            color: #dc2626;
        }

        @keyframes pulse-amber {
            0% { box-shadow: 0 0 0 0 rgba(217, 119, 6, 0.2); }
            70% { box-shadow: 0 0 0 12px rgba(217, 119, 6, 0); }
            100% { box-shadow: 0 0 0 0 rgba(217, 119, 6, 0); }
        }

        @keyframes pulse-indigo {
            0% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.2); }
            70% { box-shadow: 0 0 0 12px rgba(99, 102, 241, 0); }
            100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); }
        }

        /* Timeline steps */
        .timeline {
            position: relative;
            padding-left: 32px;
            list-style: none;
            margin: 20px 0 0;
        }

        .timeline:before {
            content: "";
            position: absolute;
            left: 11px;
            top: 4px;
            bottom: 4px;
            width: 2px;
            background: #e2e8f0;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 24px;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-dot {
            position: absolute;
            left: -32px;
            top: 2px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: white;
            border: 2px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            color: #cbd5e1;
            z-index: 2;
            transition: all 0.3s;
        }

        .timeline-item.active .timeline-dot {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: white;
            box-shadow: 0 0 10px rgba(99, 102, 241, 0.3);
        }

        .timeline-item.done .timeline-dot {
            border-color: #16a34a;
            background: #16a34a;
            color: white;
        }

        .timeline-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 2px;
        }

        .timeline-desc {
            font-size: 12px;
            color: var(--text-muted);
        }

        .order-summary-box {
            background: #f8fafc;
            border-radius: 16px;
            padding: 16px;
            margin-top: 24px;
            border: 1px solid #f1f5f9;
        }
    </style>
</head>
<body>

    <div class="status-container">
        <!-- Status Card -->
        <div class="status-card">
            <div class="status-icon-wrap status-pending" id="statusIconWrap">
                <i class="bi bi-clock-history" id="statusIcon"></i>
            </div>
            <h4 class="fw-bold mb-1" id="statusTitle">Menunggu Konfirmasi</h4>
            <p class="text-muted small" id="statusSub">Pesanan sedang dikirim ke meja kasir</p>
        </div>

        <!-- Timeline -->
        <ul class="timeline">
            <li class="timeline-item active" id="step1">
                <div class="timeline-dot"><i class="bi bi-check-lg"></i></div>
                <div class="timeline-title">Pesanan Diterima</div>
                <div class="timeline-desc" id="timeReceived">Baru Saja</div>
            </li>
            <li class="timeline-item" id="step2">
                <div class="timeline-dot"><i class="bi bi-fire"></i></div>
                <div class="timeline-title">Sedang Disiapkan</div>
                <div class="timeline-desc">Koki sedang memasak pesanan Anda</div>
            </li>
            <li class="timeline-item" id="step3">
                <div class="timeline-dot"><i class="bi bi-bell"></i></div>
                <div class="timeline-title">Siap Disajikan</div>
                <div class="timeline-desc">Pesanan diantarkan ke meja Anda</div>
            </li>
        </ul>

        <!-- Order Summary -->
        <div class="order-summary-box">
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted small">Nomor Invoice:</span>
                <span class="fw-bold small">#{{ $sale->invoice_number }}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted small">Meja:</span>
                <span class="fw-bold small">{{ $sale->table_number }}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted small">Nama Pemesan:</span>
                <span class="fw-bold small">{{ $sale->customer_name }}</span>
            </div>
            <hr class="my-2">
            <div class="d-flex justify-content-between font-bold">
                <span class="fw-semibold text-muted">Total Pembayaran:</span>
                <span class="fw-bold text-primary">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Cancel Reason Alert -->
        <div class="alert alert-danger mt-3 rounded-4 p-3 d-none" id="cancelAlert" role="alert">
            <h6 class="alert-heading fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill"></i> Pesanan Dibatalkan</h6>
            <p class="small mb-0" id="cancelReason">Maaf, pesanan Anda tidak dapat diproses oleh kasir saat ini.</p>
        </div>

        <div class="text-center mt-4">
            <a href="/order?store_id={{ $sale->store_id }}&table={{ rawurlencode($sale->table_number) }}&hash={{ hash_hmac('sha256', "store_id={$sale->store_id}&table={$sale->table_number}", config('app.key')) }}" class="btn btn-outline-primary rounded-pill px-4 small py-2">
                <i class="bi bi-arrow-left"></i> Pesan Menu Lain
            </a>
        </div>
    </div>

    <!-- Firebase SDK Scripts -->
    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-app.js";
        import { getFirestore, doc, onSnapshot } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-firestore.js";

        const storeId = "{{ $sale->store_id }}";
        const orderId = "{{ $sale->invoice_number }}";

        // Save active order on load
        localStorage.setItem('active_order_invoice_' + storeId, orderId);

        const firebaseConfig = {
            projectId: "rimspos"
        };

        let db;
        try {
            const app = initializeApp(firebaseConfig);
            db = getFirestore(app);
            
            // Listen real-time
            const docRef = doc(db, "stores", storeId, "self_service_orders", orderId);
            onSnapshot(docRef, (docSnap) => {
                if (docSnap.exists()) {
                    const data = docSnap.data();
                    updateStatusUI(data.status, data.status_reason);
                }
            }, (error) => {
                console.error("Firestore onSnapshot error: ", error);
                startPollingFallback();
            });
        } catch (e) {
            console.error("Firebase init failed: ", e);
            startPollingFallback();
        }

        function updateStatusUI(status, reason) {
            const wrap = document.getElementById('statusIconWrap');
            const icon = document.getElementById('statusIcon');
            const title = document.getElementById('statusTitle');
            const sub = document.getElementById('statusSub');
            
            const step1 = document.getElementById('step1');
            const step2 = document.getElementById('step2');
            const step3 = document.getElementById('step3');
            const cancelAlert = document.getElementById('cancelAlert');

            console.log("Status: " + status + " Reason: " + reason);

            // Reset classes
            wrap.className = 'status-icon-wrap';
            cancelAlert.classList.add('d-none');

            if (status === 'pending') {
                wrap.classList.add('status-pending');
                icon.className = 'bi bi-clock-history';
                title.innerText = 'Menunggu Konfirmasi';
                sub.innerText = 'Pesanan Anda sedang dalam antrean kasir';
                
                step1.className = 'timeline-item active';
                step2.className = 'timeline-item';
                step3.className = 'timeline-item';
                
                const step2Desc = step2.querySelector('.timeline-desc');
                if (step2Desc) {
                    step2Desc.innerText = 'Koki sedang memasak pesanan Anda';
                }
            } 
            else if (status === 'confirmed') {
                wrap.classList.add('status-pending');
                icon.className = 'bi bi-clipboard-check';
                title.innerText = 'Pesanan Diterima';
                sub.innerText = 'Pesanan Anda telah diterima oleh kasir';
                
                step1.className = 'timeline-item done';
                step2.className = 'timeline-item active';
                step3.className = 'timeline-item';
                
                const step2Desc = step2.querySelector('.timeline-desc');
                if (step2Desc) {
                    step2Desc.innerText = 'Mengantre di dapur';
                }
            }
            else if (status === 'preparing') {
                wrap.classList.add('status-preparing');
                icon.className = 'bi bi-fire';
                title.innerText = 'Sedang Disiapkan';
                sub.innerText = 'Pesanan Anda sedang dimasak di dapur';
                
                step1.className = 'timeline-item done';
                step2.className = 'timeline-item active';
                step3.className = 'timeline-item';
                
                const step2Desc = step2.querySelector('.timeline-desc');
                if (step2Desc) {
                    step2Desc.innerText = 'Koki sedang memasak pesanan Anda';
                }
            } 
            else if (status === 'served' || status === 'completed') {
                wrap.classList.add('status-completed');
                icon.className = 'bi bi-check2-circle';
                title.innerText = 'Selesai Disajikan';
                sub.innerText = 'Nikmati pesanan hidangan Anda!';
                
                step1.className = 'timeline-item done';
                step2.className = 'timeline-item done';
                step3.className = 'timeline-item done';
            } 
            else if (status === 'cancelled') {
                wrap.classList.add('status-cancelled');
                icon.className = 'bi bi-x-circle';
                title.innerText = 'Pesanan Dibatalkan';
                sub.innerText = 'Pesanan Anda dibatalkan atau ditolak';
                
                step1.className = 'timeline-item';
                step2.className = 'timeline-item';
                step3.className = 'timeline-item';
                
                if (reason) {
                    document.getElementById('cancelReason').innerText = `Alasan: ${reason}`;
                }
                cancelAlert.classList.remove('d-none');
            }

            if (status === 'served' || status === 'completed' || status === 'cancelled') {
                localStorage.removeItem('active_order_invoice_' + storeId);
            }
        }

        // Polling fallback
        let pollingInterval = null;
        function startPollingFallback() {
            if (pollingInterval) return;
            console.log("Starting REST fallback polling...");
            pollingInterval = setInterval(() => {
                fetch(`/api/order/status/${orderId}`)
                    .then(r => r.json())
                    .then(res => {
                        updateStatusUI(res.status, res.status_reason);
                    })
                    .catch(err => console.error("Polling error: ", err));
            }, 8000);
        }
    </script>
</body>
</html>
