<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kitchen Display System (KDS)</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts: Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #0b0f19;
            --card-bg-pending: #161e31;
            --card-bg-cooking: #28200e;
            --text-color: #f3f4f6;
            --accent-color: #6366f1;
            --accent-hover: #4f46e5;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --border-color: #2e3b56;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Outfit', sans-serif;
            overflow-x: hidden;
            margin: 0;
            padding: 0;
            min-vh: 100vh;
        }

        .kds-header {
            background: rgba(17, 24, 39, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
            padding: 15px 30px;
        }

        .kds-title {
            font-weight: 700;
            letter-spacing: 1px;
            background: linear-gradient(135deg, #a5b4fc, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .order-grid {
            padding: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
        }

        .order-card {
            background: var(--card-bg-pending);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        }

        .order-card.cooking-state {
            background: var(--card-bg-cooking);
            border-color: var(--warning-color);
        }

        .order-card-header {
            padding: 16px 20px;
            border-bottom: 1px dashed var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-badge {
            background: var(--accent-color);
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
            padding: 6px 14px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.4);
        }

        .order-info {
            text-align: right;
        }

        .order-no {
            font-size: 0.85rem;
            color: #9ca3af;
            font-family: monospace;
        }

        .order-time {
            font-size: 0.8rem;
            color: var(--warning-color);
            font-weight: 600;
        }

        .order-card-body {
            padding: 20px;
            flex-grow: 1;
        }

        .item-row {
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-qty {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--accent-color);
            background: rgba(99, 102, 241, 0.15);
            padding: 2px 10px;
            border-radius: 6px;
            margin-right: 12px;
        }

        .item-name {
            font-weight: 500;
            font-size: 1.05rem;
            flex-grow: 1;
        }

        .item-status-btn {
            padding: 6px 12px;
            font-size: 0.85rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .order-card-footer {
            padding: 16px 20px;
            background: rgba(0, 0, 0, 0.15);
            border-top: 1px solid var(--border-color);
        }

        .btn-ready-all {
            width: 100%;
            border-radius: 8px;
            font-weight: 600;
            padding: 10px;
        }

        /* Empty State */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 60vh;
            color: #9ca3af;
        }

        .empty-icon {
            font-size: 5rem;
            color: var(--border-color);
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        /* Sound Alert Toggle */
        .sound-toggle {
            cursor: pointer;
            padding: 8px 16px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .sound-toggle.active {
            border-color: var(--success-color);
            color: var(--success-color);
            background: rgba(16, 185, 129, 0.1);
        }
    </style>
</head>
<body>

    <!-- KDS Header -->
    <header class="kds-header d-flex justify-content-between align-items-center">
        <div>
            <h3 class="m-0 kds-title"><i class="bi bi-fire me-2"></i>KITCHEN DISPLAY SYSTEM</h3>
            <p class="m-0 text-muted small">Toko: {{ session('store_name') }} | Role: {{ activeRole()?->nama_role ?? 'Stelling' }}</p>
        </div>
        
        <div class="d-flex align-items-center gap-3">
            <div id="soundToggle" class="sound-toggle active" onclick="toggleSound()">
                <i class="bi bi-volume-up-fill" id="soundIcon"></i>
                <span>Suara: Aktif</span>
            </div>
            
            <div class="text-end d-none d-md-block">
                <h5 class="m-0" id="liveClock">00:00:00</h5>
                <p class="m-0 text-muted small" id="liveDate">Kamis, 30 Mei 2026</p>
            </div>
            
            <a href="{{ route('dashboard') }}" class="btn btn-outline-danger btn-sm rounded-3">
                <i class="bi bi-box-arrow-left"></i> Keluar
            </a>
        </div>
    </header>

    <!-- Main Container -->
    <main class="container-fluid">
        <div id="kdsContainer" class="order-grid">
            <!-- Order cards will be dynamically loaded here -->
        </div>
        
        <div id="emptyState" class="empty-state d-none">
            <i class="bi bi-inboxes empty-icon"></i>
            <h3>Tidak Ada Antrean Pesanan</h3>
            <p class="text-muted">Semua pesanan stelling telah disajikan!</p>
        </div>
    </main>

    <!-- Audio Element for alert -->
    <audio id="alertSound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-84.wav" preload="auto"></audio>

    <!-- Bootstrap + JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let isSoundEnabled = true;
        let knownOrderIds = new Set();
        let pollInterval = null;

        // Clock Update
        function updateClock() {
            const now = new Date();
            document.getElementById('liveClock').textContent = now.toLocaleTimeString('id-ID');
            document.getElementById('liveDate').textContent = now.toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Sound settings toggle
        function toggleSound() {
            isSoundEnabled = !isSoundEnabled;
            const toggle = document.getElementById('soundToggle');
            const icon = document.getElementById('soundIcon');
            const text = toggle.querySelector('span');

            if (isSoundEnabled) {
                toggle.classList.add('active');
                icon.className = 'bi bi-volume-up-fill';
                text.textContent = 'Suara: Aktif';
            } else {
                toggle.classList.remove('active');
                icon.className = 'bi bi-volume-mute-fill';
                text.textContent = 'Suara: Senyap';
            }
        }

        // Fetch and Render Orders
        async function fetchKdsOrders() {
            try {
                const response = await fetch('/kitchen/orders');
                if (!response.ok) throw new Error('Network error');
                
                const result = await response.json();
                const orders = result.data || [];
                
                renderOrders(orders);
                
                // Play notification if a new order arrives
                let hasNewOrder = false;
                orders.forEach(order => {
                    if (!knownOrderIds.has(order.id)) {
                        knownOrderIds.add(order.id);
                        hasNewOrder = true;
                    }
                });

                if (hasNewOrder && isSoundEnabled) {
                    const audio = document.getElementById('alertSound');
                    audio.play().catch(e => console.log('Sound playback prevented by browser settings'));
                }

                // Remove orders from known Set if they are no longer in KDS list
                const currentOrderIds = new Set(orders.map(o => o.id));
                knownOrderIds.forEach(id => {
                    if (!currentOrderIds.has(id)) {
                        knownOrderIds.delete(id);
                    }
                });

            } catch (error) {
                console.error('Error fetching KDS orders:', error);
            }
        }

        // Render cards
        function renderOrders(orders) {
            const container = document.getElementById('kdsContainer');
            const emptyState = document.getElementById('emptyState');
            
            if (orders.length === 0) {
                container.innerHTML = '';
                emptyState.classList.remove('d-none');
                return;
            }

            emptyState.classList.add('d-none');
            
            let html = '';
            orders.forEach(order => {
                // Determine card cooking state
                const isAnyItemCooking = order.items.some(i => i.kds_status === 'cooking');
                const cardClass = isAnyItemCooking ? 'order-card cooking-state' : 'order-card';

                html += `
                <div class="${cardClass}" id="order-card-${order.id}">
                    <div class="order-card-header">
                        <span class="table-badge">MEJA ${order.table_number}</span>
                        <div class="order-info">
                            <div class="order-no">${order.invoice_number}</div>
                            <div class="order-time"><i class="bi bi-clock me-1"></i>${order.time_ago}</div>
                        </div>
                    </div>
                    
                    <div class="order-card-body">
                        <p class="text-muted small mb-2"><i class="bi bi-person"></i> ${order.customer_name}</p>
                        <div class="items-list">
                `;

                order.items.forEach(item => {
                    let actionBtn = '';
                    let statusBadge = '';
                    
                    if (item.kds_status === 'pending') {
                        actionBtn = `<button id="btn-item-ready-${item.id}" class="btn btn-outline-warning btn-sm item-status-btn" onclick="updateItemStatus(${item.id}, 'cooking')">Masak</button>`;
                    } else if (item.kds_status === 'cooking') {
                        statusBadge = `<span class="badge bg-warning text-dark mb-1 d-block"><i class="spinner-border spinner-border-sm me-1" role="status" style="width: 10px; height: 10px;"></i>Masak</span>`;
                        actionBtn = `<button id="btn-item-ready-${item.id}" class="btn btn-success btn-sm item-status-btn" onclick="updateItemStatus(${item.id}, 'ready')">Selesai</button>`;
                    } else if (item.kds_status === 'ready') {
                        statusBadge = `<span class="badge bg-success mb-1 d-block"><i class="bi bi-check-circle-fill me-1"></i>Selesai</span>`;
                    }

                    html += `
                        <div class="item-row">
                            <div class="d-flex align-items-center">
                                <span class="item-qty">${item.qty}x</span>
                                <span class="item-name">${item.name}</span>
                            </div>
                            <div class="text-end">
                                ${statusBadge}
                                ${actionBtn}
                            </div>
                        </div>
                    `;
                });

                html += `
                        </div>
                    </div>
                    
                    <div class="order-card-footer">
                        <button id="btn-ready-all-${order.id}" class="btn btn-outline-success btn-ready-all" onclick="markAllReady(${order.id})">
                            <i class="bi bi-check-all me-1"></i> Selesaikan Semua
                        </button>
                    </div>
                </div>
                `;
            });

            container.innerHTML = html;
        }

        // Action: Update single item status
        async function updateItemStatus(itemId, status) {
            const btn = document.getElementById(`btn-item-ready-${itemId}`);
            if (btn) btn.disabled = true;

            try {
                const response = await fetch(`/kitchen/orders/${itemId}/ready`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ status })
                });

                if (response.ok) {
                    fetchKdsOrders();
                } else {
                    alert('Gagal memperbarui status');
                    if (btn) btn.disabled = false;
                }
            } catch (error) {
                console.error(error);
                if (btn) btn.disabled = false;
            }
        }

        // Action: Mark all items in an order ready
        async function markAllReady(saleId) {
            const btn = document.getElementById(`btn-ready-all-${saleId}`);
            if (btn) btn.disabled = true;

            try {
                const response = await fetch(`/kitchen/sales/${saleId}/ready`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                if (response.ok) {
                    fetchKdsOrders();
                } else {
                    alert('Gagal menyelesaikan pesanan');
                    if (btn) btn.disabled = false;
                }
            } catch (error) {
                console.error(error);
                if (btn) btn.disabled = false;
            }
        }

        // Initialize Polling
        fetchKdsOrders();
        setInterval(fetchKdsOrders, 5000); // Poll every 5 seconds
    </script>
</body>
</html>
