import Storage from './storage';
import Cart from './cart';
import Api from './api';
// import './printer';

const POS = {
    tabs: [],
    activeTabId: null,
    akun_bank_kop: '-',

    /* =========================
     * INIT
     * ========================= */
    init() {
        this.tabs = Storage.loadTabs();
        this.activeTabId = Storage.loadActive();

        if (!Array.isArray(this.tabs)) this.tabs = [];
        if (this.tabs.length === 0) this.newTab();
        if (!this.activeTabId) this.activeTabId = this.tabs[0].id;

        this.bindEvents();
        this.render();
        this.renderTabs();
    },

    /* =========================
     * TAB MANAGEMENT
     * ========================= */
    newTab() {
        const cart = Cart.create();
        this.tabs.push(cart);
        this.activeTabId = cart.id;
        this.persist();
        this.render();
        this.renderTabs();
    },

    switchTab(id) {
        this.activeTabId = id;
        this.persist();
        this.render();
        this.renderTabs();
    },

    closeTab(id) {
        this.tabs = this.tabs.filter(t => t.id !== id);

        if (this.tabs.length === 0) {
            this.newTab();
            return;
        }

        if (this.activeTabId === id) {
            this.activeTabId = this.tabs[0].id;
        }

        this.persist();
        this.render();
        this.renderTabs();
    },

    get cart() {
        return this.tabs.find(t => t.id === this.activeTabId);
    },

    /* =========================
     * CART ACTION
     * ========================= */
    async addByCode(code) {
        if (!code) return;
        try {
            const res = await Api.findProduct(code);

            if (res.type === 'single') {
                if (res.data.stok <= 0) {
                    Swal.fire('Stok habis', 'Produk tidak tersedia', 'warning');
                    return;
                }
                Cart.addItem(this.cart, res.data);
                this.persist();
                this.render();
                return;
            }

            if (res.type === 'multiple') {
                this.showVariantChooser(res.data);
            }
        } catch (err) {
            Swal.fire('Error', err.message || 'Gagal mencari produk', 'error');
        }
    },

    showVariantChooser(variants) {
        const tbody = document.getElementById('variantList');
        if (!tbody) return;

        tbody.innerHTML = '';

        variants.forEach(v => {
            const tr = document.createElement('tr');
            if (v.stok <= 0) {
                tr.classList.add('table-secondary');
            }
            tr.style.cursor = 'pointer';
            tr.innerHTML = `
                <td>${v.sku}</td>
                <td>${v.name}<br><small>${v.variant}</small></td>
                <td class="text-end">
                    ${v.stok > 0 ? v.stok : '<span class="text-danger">Habis</span>'}
                </td>
                <td class="text-end">${this.numberSeparator(v.price)}</td>
            `;

            if (v.stok > 0) {
                tr.addEventListener('click', () => {
                    Cart.addItem(this.cart, v);
                    this.persist();
                    this.render();

                    bootstrap.Modal
                        .getInstance(document.getElementById('variantModal'))
                        .hide();
                });
            }

            tbody.appendChild(tr);
        });

        new bootstrap.Modal(
            document.getElementById('variantModal')
        ).show();
    },

    updateQty(index, qty) {
        Cart.updateQty(this.cart, index, parseInt(qty));
        this.persist();
        this.render();
    },

    removeItem(index) {
        Cart.removeItem(this.cart, index);
        this.persist();
        this.render();
    },

    /* =========================
     * STORAGE
     * ========================= */
    persist() {
        Storage.saveTabs(this.tabs);
        Storage.saveActive(this.activeTabId);
    },

    /* =========================
     * EVENT BINDING
     * ========================= */
    bindEvents() {
        const skuInput = document.getElementById('skuInput');

        skuInput?.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                this.addByCode(e.target.value.trim());
                e.target.value = '';
            }
        });

        document.getElementById('skuSearchBtn')?.addEventListener('click', () => {
            this.addByCode(skuInput.value.trim());
            skuInput.value = '';
            skuInput.focus();
        });

        document.getElementById('posTabs')?.addEventListener('click', e => {
            const tab = e.target.closest('[data-tab-id]');
            if (!tab) return;

            if (e.target.dataset.action === 'close') {
                this.closeTab(tab.dataset.tabId);
            } else {
                this.switchTab(tab.dataset.tabId);
            }
        });

        document.addEventListener('keydown', e => {
            if (e.target.classList.contains('discount-input') && e.key === 'Enter') {
                e.preventDefault();
                this.applyItemDiscount(e.target);
                e.target.blur(); // opsional: biar konsisten
            }

            if (e.target.id === 'transactionDiscount' && e.key === 'Enter') {
                e.preventDefault();
                this.applyTransactionDiscount(e.target);
                e.target.blur();
            }
        });

        document.addEventListener('blur', e => {
            if (e.target.classList.contains('discount-input')) {
                this.applyItemDiscount(e.target);
            }

            if (e.target.id === 'transactionDiscount') {
                this.applyTransactionDiscount(e.target);
            }
        }, true);
    },


    /* =========================
     * RENDERING
     * ========================= */
    render() {
        // baca tanggal dan customer name dari cart untuk ditampilkan di input
        const transactionDateInput = document.getElementById('transactionDate');
        if (transactionDateInput) {
            transactionDateInput.value = this.cart.transaction_date || new Date().toISOString().split('T')[0];
        }

        const customerNameInput = document.getElementById('customerName');
        if (customerNameInput) {
            customerNameInput.value = this.cart.customer_name || '';
        }

        const tbody = document.getElementById('cartBody');
        if (!tbody || !this.cart) return;

        tbody.innerHTML = '';

        this.cart.items.forEach((item, index) => {
            const tr = document.createElement('tr');
            //tambah class ke tr
            tr.classList.add('align-top');
            let viewdiskon = '';
            if (item.discount_type === 'percent') {
                viewdiskon = `<small class="text-muted d-block mt-1">
                        - Rp ${this.numberSeparator(item.discount_amount ?? 0)}
                    </small>`;
            }
            tr.innerHTML = `
                <td>
                    ${item.name}<br>
                    <small>${item.variant}</small>
                </td>
                <td class="text-end">${this.numberSeparator(item.price)}</td>
                <td class="text-center">
                    <input type="number"
                        class="form-control form-control-sm text-center"
                        min="1"
                        value="${item.qty}"
                        onchange="POS.updateQty(${index}, this.value)">
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="number"
                            class="form-control form-control-sm discount-input"
                            data-product-id="${item.product_id}"
                            placeholder="% / Rp"
                            value="${item.discount_value}">
                        <span class="input-group-text">
                            ${item.discount_type === 'percent' ? '%' : 'Rp'}
                        </span>
                    </div>
                    ${viewdiskon}
                </td>
                <td class="text-end">${this.numberSeparator(item.subtotal)}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-danger"
                        onclick="POS.removeItem(${index})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('subtotal').innerText =
            this.numberSeparator(this.cart.subtotal);

        document.getElementById('total').innerText =
            this.numberSeparator(this.cart.total);

        document.getElementById('transactionDiscountValue').innerText =
            this.numberSeparator(this.cart.transaction_discount || 0);
    },

    renderTabs() {
        const el = document.getElementById('posTabs');
        if (!el) return;

        el.innerHTML = '';

        this.tabs.forEach((tab, i) => {
            const btn = document.createElement('button');
            btn.className = `btn btn-sm me-1 ${tab.id === this.activeTabId
                ? 'btn-primary'
                : 'btn-outline-primary'
                }`;
            btn.dataset.tabId = tab.id;
            btn.innerHTML = `
                ${i + 1}
                <span data-action="close" style="margin-left:6px;cursor:pointer">×</span>
            `;
            el.appendChild(btn);
        });
    },

    checkout() {
        // update transaction date from input
        const transactionDateInput = document.getElementById('transactionDate');
        if (transactionDateInput) {
            this.cart.transaction_date = transactionDateInput.value;
        }
        if (this.cart.items.length === 0) {
            Swal.fire('Keranjang kosong', 'Tambahkan produk terlebih dahulu', 'warning');
            return;
        }

        Swal.fire({
            title: 'Pembayaran',
            width: 470,
            customClass: {
                popup: 'pos-checkout'
            },
            html: `
                <style>
                    .pos-total {
                        font-size: 26px;
                        font-weight: 700;
                        color: #4f46e5;
                    }
                    .pay-method {
                        display: flex;
                        gap: 8px;
                    }
                    .pay-btn {
                        flex: 1;
                        padding: 10px;
                        border-radius: 8px;
                        border: 1px solid #ddd;
                        cursor: pointer;
                        font-weight: 600;
                        background: #f9fafb;
                    }
                    .pay-btn.active {
                        background: #4f46e5;
                        color: #fff;
                        border-color: #4f46e5;
                    }
                    .pos-card {
                        background: #fff;
                        border-radius: 10px;
                        padding: 12px;
                        border: 1px solid #e5e7eb;
                    }
                    .pos-label {
                        font-size: 13px;
                        color: #6b7280;
                        margin-bottom: 4px;
                    }
                </style>

                <div class="text-center mb-3">
                    <div class="text-muted small">Total Bayar</div>
                    <div class="pos-total">Rp ${this.numberSeparator(this.cart.total)}</div>
                </div>

                <div class="mb-3">
                    <div class="pos-label">Metode Pembayaran</div>
                    <div class="pay-method">
                        <div id="btnCash" class="pay-btn active">💵 Cash</div>
                        <div id="btnTransfer" class="pay-btn">🏦 Transfer</div>
                        <div id="btnSplit" class="pay-btn">🔀 Split</div>
                    </div>
                </div>

                <div id="cashSection" class="pos-card">
                    <div class="pos-label">Uang Diterima</div>
                    <input
                        type="number"
                        id="paidAmount"
                        class="form-control form-control-lg"
                        placeholder="Masukkan nominal"
                    >
                    <div class="mt-2">
                        <div class="pos-label">Kembalian</div>
                        <div class="fw-bold text-success fs-5" id="cashChangeDisplay">Rp 0</div>
                    </div>
                </div>

                <div id="transferSection" class="pos-card d-none">
                    <div class="pos-label">Akun Bank Tujuan</div>
                    <select id="akunBank" class="form-select">
                        ${this.buildAkunBankOptions()}
                    </select>

                    <div class="mt-2 small text-muted">
                        Transfer sesuai total pembayaran
                    </div>
                </div>

                <div id="splitSection" class="pos-card d-none">
                    <div class="pos-label">Pembayaran Tunai</div>
                    <input
                        type="number"
                        id="splitCash"
                        class="form-control mb-2"
                        placeholder="Nominal cash"
                    >
                    <div class="pos-label">Pembayaran Transfer</div>
                    <input
                        type="number"
                        id="splitTransfer"
                        class="form-control mb-2"
                        placeholder="Nominal transfer"
                    >

                    <div class="pos-label">Akun Bank</div>
                    <select id="splitBank" class="form-select">
                        ${this.buildAkunBankOptions()}
                    </select>

                    <div class="mt-2 small text-muted">
                        Total harus pas dengan total pembayaran
                    </div>
                </div>

            `,
            showCancelButton: true,
            confirmButtonText: 'Bayar',
            cancelButtonText: 'Batal',
            focusConfirm: false,
            didOpen: () => {
                const btnCash = document.getElementById('btnCash');
                const btnTransfer = document.getElementById('btnTransfer');
                const btnSplit = document.getElementById('btnSplit');

                const cashSection = document.getElementById('cashSection');
                const transferSection = document.getElementById('transferSection');
                const splitSection = document.getElementById('splitSection');

                const reset = () => {
                    cashSection.classList.add('d-none');
                    transferSection.classList.add('d-none');
                    splitSection.classList.add('d-none');

                    btnCash.classList.remove('active');
                    btnTransfer.classList.remove('active');
                    btnSplit.classList.remove('active');
                };

                btnCash.onclick = () => {
                    reset();
                    btnCash.classList.add('active');
                    cashSection.classList.remove('d-none');
                };

                btnTransfer.onclick = () => {
                    reset();
                    btnTransfer.classList.add('active');
                    transferSection.classList.remove('d-none');
                };

                btnSplit.onclick = () => {
                    reset();
                    btnSplit.classList.add('active');
                    splitSection.classList.remove('d-none');
                };

                // Hitung kembalian untuk cash
                const total = POS.cart.total;

                // === CASH ===
                document.getElementById('paidAmount').addEventListener('input', e => {
                    const paid = parseFloat(e.target.value) || 0;
                    const change = paid - total;

                    document.getElementById('cashChangeDisplay').innerText =
                        change > 0 ? "Rp " + POS.numberSeparator(change) : "Rp 0";
                });

                // === SPLIT ===
                const splitCashInput = document.getElementById('splitCash');
                const splitTransferInput = document.getElementById('splitTransfer');

                function updateSplitCash() {
                    const transfer = parseFloat(splitTransferInput.value) || 0;
                    const change = total - transfer;

                    document.getElementById('splitCash').value = change > 0 ? change : 0;
                }
                function updateSplitTrasfer() {
                    const cash = parseFloat(splitCashInput.value) || 0;
                    const change = total - cash;

                    document.getElementById('splitTransfer').value = change > 0 ? change : 0;
                }

                splitCashInput.addEventListener('input', updateSplitTrasfer);
                splitTransferInput.addEventListener('input', updateSplitCash);
            },
            preConfirm: () => {
                const total = this.cart.total;

                const isCash = document.getElementById('btnCash').classList.contains('active');
                const isTransfer = document.getElementById('btnTransfer').classList.contains('active');
                const isSplit = document.getElementById('btnSplit').classList.contains('active');

                /* ================= CASH ================= */
                if (isCash) {
                    const paid = parseFloat(document.getElementById('paidAmount').value);
                    console.log('Paid:', paid, 'Total:', total);

                    if (isNaN(paid) || paid < total) {
                        Swal.showValidationMessage('Uang tunai tidak cukup');
                        return false;
                    }

                    return {
                        payment_method: 'cash',
                        paid_amount: paid,
                        cash_amount: paid,
                        transfer_amount: 0,
                        akun_kasir: window.AKUN_KASIR,
                        akun_bank: null
                    };
                }

                /* ================= TRANSFER ================= */
                if (isTransfer) {
                    const bank = document.getElementById('akunBank').value;

                    if (!bank) {
                        Swal.showValidationMessage('Pilih akun bank tujuan');
                        return false;
                    }

                    return {
                        payment_method: 'transfer',
                        paid_amount: total,
                        cash_amount: 0,
                        transfer_amount: total,
                        akun_kasir: null,
                        akun_bank: bank
                    };
                }

                /* ================= SPLIT ================= */
                if (isSplit) {
                    const cash = parseFloat(document.getElementById('splitCash').value) || 0;
                    const transfer = parseFloat(document.getElementById('splitTransfer').value) || 0;
                    const bank = document.getElementById('splitBank').value;

                    if (!bank) {
                        Swal.showValidationMessage('Pilih akun bank untuk transfer');
                        return false;
                    }

                    if (cash <= 0 && transfer <= 0) {
                        Swal.showValidationMessage('Masukkan nominal split pembayaran');
                        return false;
                    }

                    if (cash + transfer !== total) {
                        Swal.showValidationMessage('Total split tidak sama dengan total pembayaran');
                        return false;
                    }

                    return {
                        payment_method: 'split',
                        paid_amount: total,
                        cash_amount: cash,
                        transfer_amount: transfer,
                        akun_kasir: cash > 0 ? window.AKUN_KASIR : null,
                        akun_bank: bank
                    };
                }

                Swal.showValidationMessage('Pilih metode pembayaran');
                return false;
            }
        }).then(result => {
            if (!result.isConfirmed) return;

            Object.assign(this.cart, result.value);

            Api.checkout(this.cart)
                .then(res => {
                    Swal.fire({
                        title: 'Transaksi Berhasil',
                        html: `
                            <div style="font-size:14px;color:#6b7280">
                                Invoice <b>${res.invoice}</b>
                            </div>
                            <div style="margin-top:8px">
                                Apakah ingin mencetak struk?
                            </div>
                        `,
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: '🖨 Cetak',
                        cancelButtonText: 'Tidak',
                        customClass: {
                            popup: 'pos-checkout'
                        },
                        reverseButtons: true
                    }).then(result => {
                        // reset transaction date & customer name for next transaction
                        this.cart.transaction_date = new Date().toISOString().split('T')[0];
                        $('#transactionDate').val(this.cart.transaction_date);
                        $('#customerName').val("");
                        if (result.isConfirmed) {
                            // Printer.printReceipt(res.invoice);
                            // fetch(`/datasales/receiptdata/${res.sale_id}`)
                            //     .then(res => res.json())
                            //     .then(data => {
                            //         Printer.printReceiptPrinter(data);
                            //     });
                            // PRINT DENGAN NEW WINDOW
                            const printWindow = window.open(`/sales/${res.sale_id}/receipt`, '_blank');
                            printWindow.focus();
                            printWindow.onload = function () {
                                printWindow.print();
                                printWindow.onafterprint = function () {
                                    printWindow.close();
                                };
                            };
                        }

                        this.closeTab(this.cart.id);
                        document.getElementById('skuInput').focus();
                    });
                });

        });
    },

    applyItemDiscount(input) {
        const productId = input.dataset.productId;
        const value = parseFloat(input.value) || 0;

        Cart.setItemDiscount(this.cart, productId, value);
        Cart.recalculate(this.cart);

        this.persist();
        this.render();
    },

    applyTransactionDiscount(input) {
        const val = parseFloat(input.value) || 0;

        this.cart.transaction_discount_type = val <= 100 ? 'percent' : 'nominal';
        this.cart.transaction_discount_value = val;

        Cart.recalculate(this.cart);

        this.persist();
        this.render();
    },

    buildAkunBankOptions() {
        let html = '<option value="">-- Pilih Bank --</option>';

        window.AKUN_BANK.forEach(item => {
            html += `<option value="${item.id}" ${item.id === this.akun_bank_kop ? 'selected' : ''}>
                    ${item.no_rek} - ${item.nama_rek} (${item.bank_rek})
                 </option>`;
        });

        return html;
    },

    numberSeparator(x) {
        return parseFloat(x || 0).toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
    },

    updateTransactionDate() {
        const transactionDateInput = document.getElementById('transactionDate');
        if (transactionDateInput) {
            this.cart.transaction_date = transactionDateInput.value;
            this.persist();
        }
    },

    updateCustomerName() {
        const customerNameInput = document.getElementById('customerName');
        if (customerNameInput) {
            this.cart.customer_name = customerNameInput.value || 'Umum';
            this.persist();
        }
    }
};



window.POS = POS;

document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('#skuInput')) {
        POS.init();
    }
});

export default POS;
