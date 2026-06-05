<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Self-Service QR Order - {{ $store->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --accent-color: #f43f5e;
            --bg-color: #f8fafc;
            --surface-color: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            padding-bottom: 90px; /* Space for bottom checkout bar */
            user-select: none;
            -webkit-user-select: none;
        }

        .header-section {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: white;
            padding: 24px 16px 40px;
            border-bottom-left-radius: 28px;
            border-bottom-right-radius: 28px;
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.15);
        }

        .store-logo {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px white solid;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .search-bar {
            margin-top: -24px;
            padding: 0 16px;
        }

        .search-input {
            border: none;
            border-radius: 16px;
            padding: 14px 20px 14px 46px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            font-size: 15px;
            width: 100%;
        }

        .search-icon {
            position: absolute;
            left: 32px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 18px;
        }

        /* Tenant scroll */
        .tenant-tabs {
            display: flex;
            overflow-x: auto;
            white-space: nowrap;
            padding: 16px 16px 4px;
            gap: 10px;
            scrollbar-width: none; /* Firefox */
        }
        .tenant-tabs::-webkit-scrollbar {
            display: none; /* Safari and Chrome */
        }

        .tenant-badge {
            background: white;
            color: var(--text-main);
            border: 1px solid #e2e8f0;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .tenant-badge.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.2);
        }

        /* Product Cards */
        .product-card {
            background: white;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
            border: 1px solid #f1f5f9;
            transition: transform 0.2s;
            display: flex;
            padding: 12px;
            gap: 12px;
            margin-bottom: 12px;
        }

        .product-card:active {
            transform: scale(0.98);
        }

        .product-image {
            width: 85px;
            height: 85px;
            border-radius: 12px;
            object-fit: cover;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #cbd5e1;
            font-size: 28px;
        }

        .product-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .product-name {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 2px;
            color: var(--text-main);
        }

        .product-desc {
            font-size: 11px;
            color: var(--text-muted);
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 4px;
        }

        .product-price {
            font-size: 15px;
            font-weight: 700;
            color: var(--primary-color);
        }

        .add-btn {
            background: #f1f5f9;
            color: var(--primary-color);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            transition: all 0.2s;
        }

        .add-btn:active {
            background: var(--primary-color);
            color: white;
        }

        /* Bottom Cart Bar */
        .bottom-cart-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            box-shadow: 0 -10px 35px rgba(0, 0, 0, 0.08);
            padding: 16px 20px 24px;
            border-top-left-radius: 24px;
            border-top-right-radius: 24px;
            z-index: 1000;
            display: none;
            animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }

        .checkout-btn {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            color: white;
            border: none;
            padding: 14px;
            border-radius: 16px;
            width: 100%;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.3);
            font-size: 15px;
        }

        /* Modals & bottom sheets */
        .bottom-sheet {
            border-top-left-radius: 28px;
            border-top-right-radius: 28px;
            border: none;
        }

        .variant-option-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .variant-option-card.selected {
            border-color: var(--primary-color);
            background-color: #f5f3ff;
            color: var(--primary-dark);
        }

        .sheet-drag-handle {
            width: 40px;
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            margin: 8px auto 16px;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <div class="header-section">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                @if($store->logo)
                    <img src="{{ Storage::url($store->logo) }}" class="store-logo" alt="Logo">
                @else
                    <div class="store-logo"><i class="bi bi-shop text-primary fs-4"></i></div>
                @endif
                <div>
                    <h5 class="fw-bold m-0">{{ $store->name }}</h5>
                    <small class="text-white-50"><i class="bi bi-geo-alt"></i> {{ $store->city ?? 'FnB Store' }}</small>
                </div>
            </div>
            <div class="text-end">
                <span class="badge bg-white text-primary rounded-pill px-3 py-2 fw-bold" style="font-size: 13px;">
                    {{ $table }}
                </span>
            </div>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="search-bar position-relative">
        <i class="bi bi-search search-icon"></i>
        <input type="text" class="search-input" id="searchMenu" placeholder="Cari makanan atau minuman..." oninput="filterMenu()">
    </div>

    <!-- Active Order Banner -->
    <div class="container px-3 mt-3 d-none" id="activeOrderBanner">
        <a href="" id="activeOrderLink" class="text-decoration-none">
            <div class="d-flex align-items-center justify-content-between p-3 rounded-4 text-white" style="background: linear-gradient(135deg, #2563eb, #3b82f6); box-shadow: 0 4px 15px rgba(37, 99, 235, 0.25);">
                <div class="d-flex align-items-center gap-2">
                    <span class="d-inline-block rounded-circle p-2" style="background-color: rgba(255, 255, 255, 0.2); width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="bi bi-receipt fs-5"></i>
                    </span>
                    <div>
                        <div class="fw-bold" style="font-size: 13px;">Anda memiliki pesanan aktif</div>
                        <small class="text-white-50" style="font-size: 11px;">Ketuk untuk memantau status pesanan</small>
                    </div>
                </div>
                <i class="bi bi-arrow-right fs-5"></i>
            </div>
        </a>
    </div>

    <!-- Tenant Tabs -->
    <div class="tenant-tabs" id="tenantTabs">
        <div class="tenant-badge active" data-tenant-id="all" onclick="selectTenant('all')">Semua Stall</div>
        @foreach($tenants as $tenant)
            <div class="tenant-badge" data-tenant-id="{{ $tenant->id }}" onclick="selectTenant({{ $tenant->id }})">
                {{ $tenant->nama_tenant }}
            </div>
        @endforeach
        <div class="tenant-badge" data-tenant-id="umum" onclick="selectTenant('umum')">Menu Utama</div>
    </div>

    <!-- Menu List -->
    <div class="container px-3 mt-3" id="menuContainer">
        @forelse($products as $product)
            <div class="product-item" data-name="{{ strtolower($product->nama_produk) }}" data-tenant="{{ $product->tenant_id ?? 'umum' }}">
                <div class="product-card">
                    <!-- Image -->
                    @if($product->image_url)
                        <img src="{{ $product->image_url }}" class="product-image" alt="{{ $product->nama_produk }}">
                    @else
                        <div class="product-image"><i class="bi bi-egg-fried"></i></div>
                    @endif

                    <!-- Detail Info -->
                    <div class="product-info">
                        <div>
                            <div class="product-name">{{ $product->nama_produk }}</div>
                            <div class="product-desc">{{ $product->deskripsi ?? 'Sajian istimewa dari dapur kami.' }}</div>
                            @if($product->tenant)
                                <small class="text-muted d-block" style="font-size: 10px; margin-bottom: 2px;">
                                    <i class="bi bi-shop-window text-warning"></i> {{ $product->tenant->nama_tenant }}
                                </small>
                            @endif
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="product-price">
                                @if($product->variants->count() == 1)
                                    Rp {{ number_format($product->variants->first()->harga_jual, 0, ',', '.') }}
                                @else
                                    Rp {{ number_format($product->variants->min('harga_jual'), 0, ',', '.') }}+
                                @endif
                            </span>

                            <button class="add-btn" onclick="triggerAddProduct({{ json_encode($product) }})">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-5">
                <i class="bi bi-card-list fs-1 d-block mb-2 text-black-50"></i>
                Belum ada produk aktif yang tersedia.
            </div>
        @endforelse
    </div>

    <!-- Bottom Cart Bar -->
    <div class="bottom-cart-bar" id="cartBar">
        <button class="checkout-btn" onclick="openCartModal()">
            <span>
                <i class="bi bi-bag-check-fill me-2"></i>
                <span id="cartCountBadge">0</span> Item
            </span>
            <span>
                Checkout &bull; <strong>Rp <span id="cartTotalText">0</span></strong>
            </span>
        </button>
    </div>

    <!-- Variant Selector Modal -->
    <div class="modal fade" id="variantModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-end m-0 w-100 position-fixed bottom-0" style="max-height: 80%;">
            <div class="modal-content bottom-sheet">
                <div class="sheet-drag-handle"></div>
                <div class="modal-header pt-0 border-0">
                    <h5 class="fw-bold mb-0" id="variantModalTitle">Pilih Varian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-2">
                    <div id="variantListContainer">
                        <!-- Filled by JS -->
                    </div>
                    
                    <div class="mt-3">
                        <label class="form-label fw-semibold small text-muted">Catatan Khusus (Optional)</label>
                        <input type="text" class="form-control rounded-3" id="variantItemNotes" placeholder="Contoh: Sedikit es, pedas sedang...">
                    </div>
                </div>
                <div class="modal-footer border-0 p-3">
                    <button type="button" class="btn btn-primary w-100 rounded-3 py-3 fw-bold" onclick="addVariantToCart()">
                        Tambahkan ke Keranjang
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cart Detail Modal -->
    <div class="modal fade" id="cartModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-end m-0 w-100 position-fixed bottom-0" style="max-height: 85%;">
            <div class="modal-content bottom-sheet" style="height: 80vh;">
                <div class="sheet-drag-handle"></div>
                <div class="modal-header pt-0 border-bottom">
                    <h5 class="fw-bold mb-0"><i class="bi bi-cart3 text-primary"></i> Detail Pesanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body overflow-y-auto" id="cartModalBody" style="flex: 1;">
                    <!-- Filled by JS -->
                </div>
                <div class="modal-footer border-top p-3 bg-light">
                    <div class="w-100">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Nama Pemesan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" id="customerNameInput" placeholder="Masukkan nama Anda..." required>
                        </div>
                        <div class="d-flex justify-content-between mb-3 fw-bold fs-5">
                            <span>Total Tagihan:</span>
                            <span class="text-primary">Rp <span id="cartGrandTotalText">0</span></span>
                        </div>
                        <button type="button" class="btn btn-primary w-100 rounded-3 py-3 fw-bold fs-6" id="btnSubmitOrder" onclick="submitOrderFinal()">
                            <i class="bi bi-send-fill me-2"></i> Kirim Pesanan Sekarang
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const storeId = "{{ $store->id }}";
        const tableName = "{{ $table }}";
        const signatureHash = "{{ $hash }}";
        
        let cart = [];
        let activeProduct = null;
        let activeVariantId = null;

        // Load cart and name from local storage on start
        document.addEventListener('DOMContentLoaded', () => {
            // Check for active order
            const activeInvoice = localStorage.getItem(`active_order_invoice_${storeId}`);
            if (activeInvoice) {
                const banner = document.getElementById('activeOrderBanner');
                const link = document.getElementById('activeOrderLink');
                if (banner && link) {
                    link.href = `/order/status/${activeInvoice}`;
                    banner.classList.remove('d-none');
                }
            }

            const savedCart = localStorage.getItem(`cart_${storeId}`);
            if (savedCart) {
                cart = JSON.parse(savedCart);
                updateCartUI();
            }
            const savedName = localStorage.getItem(`customer_name_${storeId}`);
            if (savedName) {
                document.getElementById('customerNameInput').value = savedName;
            }
        });

        function saveCart() {
            localStorage.setItem(`cart_${storeId}`, JSON.stringify(cart));
        }

        // Filter menu by search query
        function filterMenu() {
            const query = document.getElementById('searchMenu').value.toLowerCase();
            const items = document.querySelectorAll('.product-item');
            
            items.forEach(item => {
                const name = item.getAttribute('data-name');
                const matchesSearch = name.includes(query);
                
                const activeTab = document.querySelector('.tenant-badge.active');
                const tenantId = activeTab.getAttribute('data-tenant-id');
                const matchesTenant = (tenantId === 'all' || item.getAttribute('data-tenant') === tenantId);
                
                if (matchesSearch && matchesTenant) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Select active category tab
        function selectTenant(tenantId) {
            document.querySelectorAll('.tenant-badge').forEach(badge => {
                badge.classList.remove('active');
            });
            event.target.classList.add('active');
            filterMenu();
        }

        // Trigger add button
        function triggerAddProduct(product) {
            activeProduct = product;
            document.getElementById('variantItemNotes').value = '';
            
            if (product.variants.length === 1) {
                // Auto add single variant
                const variant = product.variants[0];
                addToCart(variant.id, product.nama_produk, parseFloat(variant.harga_jual), '');
                showToast(`Ditambahkan ke keranjang`);
            } else {
                // Show variant sheet
                activeVariantId = null;
                document.getElementById('variantModalTitle').innerText = product.nama_produk;
                
                const list = document.getElementById('variantListContainer');
                list.innerHTML = '';
                
                product.variants.forEach(variant => {
                    const label = variant.variant_label || 'Varian Standar';
                    const isSelected = variant.id === activeVariantId;
                    
                    const card = document.createElement('div');
                    card.className = `variant-option-card ${isSelected ? 'selected' : ''}`;
                    card.setAttribute('data-variant-id', variant.id);
                    card.onclick = () => selectVariantOption(variant.id);
                    
                    card.innerHTML = `
                        <div>
                            <div class="fw-bold">${label}</div>
                            <small class="text-muted">Stok: ${variant.stok > 999 ? 'Tersedia' : variant.stok}</small>
                        </div>
                        <div class="fw-bold text-primary">Rp ${new Intl.NumberFormat('id-ID').format(variant.harga_jual)}</div>
                    `;
                    list.appendChild(card);
                });
                
                const variantModal = new bootstrap.Modal(document.getElementById('variantModal'));
                variantModal.show();
            }
        }

        function selectVariantOption(variantId) {
            activeVariantId = variantId;
            document.querySelectorAll('.variant-option-card').forEach(card => {
                card.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
        }

        function addVariantToCart() {
            if (!activeVariantId) {
                alert('Silakan pilih salah satu varian terlebih dahulu.');
                return;
            }
            
            const variant = activeProduct.variants.find(v => v.id === activeVariantId);
            const notes = document.getElementById('variantItemNotes').value.trim();
            const label = variant.variant_label ? `${activeProduct.nama_produk} (${variant.variant_label})` : activeProduct.nama_produk;

            addToCart(variant.id, label, parseFloat(variant.harga_jual), notes);
            
            bootstrap.Modal.getInstance(document.getElementById('variantModal')).hide();
            showToast(`Ditambahkan ke keranjang`);
        }

        function addToCart(variantId, label, price, notes) {
            // Find existing matching variant AND notes
            const existing = cart.find(item => item.variant_id === variantId && item.notes === notes);
            
            if (existing) {
                existing.qty++;
            } else {
                cart.push({
                    variant_id: variantId,
                    name: label,
                    price: price,
                    qty: 1,
                    notes: notes
                });
            }
            
            saveCart();
            updateCartUI();
        }

        function changeQty(index, amt) {
            cart[index].qty += amt;
            if (cart[index].qty <= 0) {
                cart.splice(index, 1);
            }
            saveCart();
            updateCartUI();
            
            // Re-render cart modal body
            renderCartModalList();
        }

        function updateCartUI() {
            const bar = document.getElementById('cartBar');
            const totalText = document.getElementById('cartTotalText');
            const countBadge = document.getElementById('cartCountBadge');
            
            if (cart.length === 0) {
                bar.style.display = 'none';
                return;
            }
            
            let total = 0;
            let qtySum = 0;
            cart.forEach(item => {
                total += item.price * item.qty;
                qtySum += item.qty;
            });
            
            totalText.innerText = new Intl.NumberFormat('id-ID').format(total);
            countBadge.innerText = qtySum;
            bar.style.display = 'block';
            
            document.getElementById('cartGrandTotalText').innerText = new Intl.NumberFormat('id-ID').format(total);
        }

        function openCartModal() {
            renderCartModalList();
            new bootstrap.Modal(document.getElementById('cartModal')).show();
        }

        function renderCartModalList() {
            const body = document.getElementById('cartModalBody');
            body.innerHTML = '';
            
            if (cart.length === 0) {
                body.innerHTML = '<div class="text-center text-muted py-5">Keranjang belanja kosong</div>';
                bootstrap.Modal.getInstance(document.getElementById('cartModal')).hide();
                return;
            }
            
            cart.forEach((item, index) => {
                const itemEl = document.createElement('div');
                itemEl.className = 'd-flex align-items-start justify-content-between border-bottom py-3';
                
                itemEl.innerHTML = `
                    <div style="flex: 1; padding-right: 12px;">
                        <div class="fw-bold" style="font-size: 14px;">${item.name}</div>
                        ${item.notes ? `<div class="text-danger small" style="font-size: 11px;"><i class="bi bi-pencil-fill"></i> ${item.notes}</div>` : ''}
                        <div class="text-primary fw-semibold small mt-1">Rp ${new Intl.NumberFormat('id-ID').format(item.price)}</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-outline-secondary px-2 rounded-2" onclick="changeQty(${index}, -1)">-</button>
                        <span class="fw-bold" style="min-width: 20px; text-align: center;">${item.qty}</span>
                        <button class="btn btn-sm btn-outline-primary px-2 rounded-2" onclick="changeQty(${index}, 1)">+</button>
                    </div>
                `;
                body.appendChild(itemEl);
            });
        }

        function submitOrderFinal() {
            const customerName = document.getElementById('customerNameInput').value.trim();
            if (customerName === '') {
                alert('Silakan masukkan nama Anda untuk mempermudah pemanggilan pesanan.');
                return;
            }

            // Save name
            localStorage.setItem(`customer_name_${storeId}`, customerName);

            const btn = document.getElementById('btnSubmitOrder');
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Mengirim Pesanan...`;

            const payload = {
                store_id: parseInt(storeId),
                table_number: tableName,
                customer_name: customerName,
                hash: signatureHash,
                items: cart,
                _token: "{{ csrf_token() }}"
            };

            fetch("/order/submit", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify(payload)
            })
            .then(async r => {
                const res = await r.json();
                if (!r.ok) {
                    throw new Error(res.message || 'Gagal mengirim pesanan');
                }
                
                // Clear cart
                cart = [];
                saveCart();
                updateCartUI();
                
                // Close modals
                bootstrap.Modal.getInstance(document.getElementById('cartModal')).hide();
                
                // Redirect to status screen
                window.location.href = `/order/status/${res.invoice}`;
            })
            .catch(err => {
                alert(err.message);
                btn.disabled = false;
                btn.innerHTML = `<i class="bi bi-send-fill me-2"></i> Kirim Pesanan Sekarang`;
            });
        }

        // Custom notification toaster helper
        function showToast(msg) {
            // Check if toaster exists or create
            let toastEl = document.getElementById('customToaster');
            if (!toastEl) {
                toastEl = document.createElement('div');
                toastEl.id = 'customToaster';
                toastEl.style.cssText = `
                    position: fixed;
                    top: 20px;
                    left: 50%;
                    transform: translateX(-50%);
                    background: rgba(15, 23, 42, 0.9);
                    color: white;
                    padding: 10px 24px;
                    border-radius: 30px;
                    font-size: 13px;
                    font-weight: 500;
                    z-index: 2000;
                    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
                    opacity: 0;
                    transition: opacity 0.3s;
                `;
                document.body.appendChild(toastEl);
            }
            
            toastEl.innerText = msg;
            toastEl.style.opacity = '1';
            
            setTimeout(() => {
                toastEl.style.opacity = '0';
            }, 1800);
        }
    </script>
</body>
</html>
