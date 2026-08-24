@extends('layouts.main.main')
@section('title', 'POS - Point of Sale')

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card rounded-4 p-2">
                <div class="card-body">
                    {{-- CASHIER SHIFT BAR (Jika store mewajibkan Kas Register) --}}
                    @if ($store->enable_cash_register)
                        <div class="cashier-shift-bar rounded-4 p-3 mb-3 border d-flex flex-wrap align-items-center justify-content-between gap-2 {{ $activeRegister ? 'bg-light-success border-success' : 'bg-light-warning border-warning' }}">
                            <div class="d-flex align-items-center flex-wrap gap-3">
                                @if ($activeRegister)
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-success me-2 px-2 py-1"><i class="bi bi-circle-fill" style="font-size: 8px;"></i> KASIR BUKA</span>
                                        <span class="fw-semibold text-dark">{{ $activeRegister->cashier?->name ?? auth()->user()->name }}</span>
                                    </div>
                                    <div class="text-muted small border-start ps-3">
                                        <i class="bi bi-clock me-1 text-primary"></i> Buka: <strong class="text-dark">{{ $activeRegister->opened_at->format('d/m/Y H:i') }}</strong>
                                    </div>
                                    <div class="text-muted small border-start ps-3">
                                        <i class="bi bi-cash-stack me-1 text-success"></i> Modal Awal: <strong class="text-dark">Rp {{ number_format($activeRegister->opening_cash, 0, ',', '.') }}</strong>
                                    </div>
                                @else
                                    <div class="d-flex align-items-center text-danger">
                                        <i class="bi bi-exclamation-triangle-fill fs-4 me-2"></i>
                                        <div>
                                            <strong class="d-block text-danger">Kasir Belum Dibuka</strong>
                                            <span class="small text-muted">Toko ini mewajibkan input kas awal sebelum kasir dapat memproses transaksi penjualan.</span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                @if ($activeRegister)
                                    <button type="button" class="btn btn-outline-success btn-sm" onclick="openCashMovementModal('cash_in')">
                                        <i class="bi bi-plus-circle"></i> Kas Masuk
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="openCashMovementModal('cash_out')">
                                        <i class="bi bi-dash-circle"></i> Kas Keluar
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="openCloseCashierModal()">
                                        <i class="bi bi-door-closed"></i> Tutup Kasir & Rekonsiliasi
                                    </button>
                                @else
                                    <button type="button" class="btn btn-primary btn-sm px-3" onclick="openOpenCashierModal()">
                                        <i class="bi bi-door-open"></i> Buka Kasir Sekarang
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- PREMIUM POS INFO BAR --}}
                    @if (isAdmin() || isStore())
                        <div class="pos-premium-bar rounded-4 p-3 mb-4">
                            <div class="row g-3 align-items-end">
                                {{-- Tanggal --}}
                                <div class="col-md-3 col-6">
                                    <label class="form-label fw-semibold small mb-1">Tanggal Transaksi</label>
                                    <div class="input-icon">
                                        <i class="bi bi-calendar-date"></i>
                                        <input type="date" id="transactionDate" class="form-control"
                                            value="{{ date('Y-m-d') }}" onchange="POS.updateTransactionDate()">
                                    </div>
                                </div>

                                {{-- Pelanggan --}}
                                <div class="col-md-4 col-6">
                                    <label class="form-label fw-semibold small mb-1">Nama Pelanggan / Mitra</label>
                                    <div class="input-icon">
                                        <i class="bi bi-person"></i>
                                        <select id="customerId" class="form-select select2-customer" style="width: 100%;">
                                            <option value="">-- Pelanggan Umum --</option>
                                            @foreach ($customers as $cust)
                                                <option value="{{ $cust->id }}" data-phone="{{ $cust->phone }}">{{ $cust->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <input type="hidden" id="transactionDate" value="{{ date('Y-m-d') }}">
                        <input type="hidden" id="customerName" value="Umum">
                        <input type="hidden" id="customerId" value="">
                    @endif

                    {{-- TAB POS --}}
                    <div class="d-flex align-items-center mb-3 flex-wrap gap-2">
                        <div id="posTabs" class="me-2"></div>
                        <button type="button" class="btn btn-success btn-sm" onclick="POS.newTab()">
                            + New
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm ms-auto" onclick="openServiceOrdersModal()">
                            <i class="bi bi-tools"></i> Tarik Tiket Servis
                        </button>
                    </div>

                    <div class="row">
                        {{-- LEFT : CART --}}
                        <div class="col-md-8">
                            {{-- INPUT SKU CARI --}}
                            <div class="input-group mb-3">
                                <input type="text" id="skuInput" class="form-control form-control-lg"
                                    placeholder="Scan barcode / input SKU / kode produk" autofocus>
                                <button class="btn btn-primary btn-lg" id="skuSearchBtn" type="button">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>

                            {{-- CART TABLE --}}
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Produk</th>
                                            <th width="100" class="text-center">Harga</th>
                                            <th width="80" class="text-center">Qty</th>
                                            <th width="140" class="text-center">Diskon</th>
                                            <th width="120" class="text-center">Subtotal</th>
                                            <th width="50"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="cartBody">
                                        {{-- DIISI OLEH JS --}}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- RIGHT : SUMMARY --}}
                        <div class="col-md-4">
                            <div class="card shadow-sm rounded-4">
                                <div class="card-body">
                                    <h5 class="mb-3 fw-bold">Summary</h5>

                                    {{-- SUBTOTAL --}}
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Subtotal</span>
                                        <strong>Rp <span id="subtotal">0</span></strong>
                                    </div>

                                    {{-- DISKON TRANSAKSI --}}
                                    <div class="mb-2">
                                        <label class="form-label mb-1">
                                            Diskon Transaksi
                                            <small class="text-muted">( % atau Rp )</small>
                                        </label>
                                        <input type="number" id="transactionDiscount" class="form-control text-end"
                                            placeholder="contoh: 10 atau 5000" min="0">
                                    </div>

                                    {{-- NILAI DISKON --}}
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Potongan</span>
                                        <strong class="text-danger">
                                            - Rp <span id="transactionDiscountValue">0</span>
                                        </strong>
                                    </div>

                                    <hr>

                                    {{-- TOTAL --}}
                                    <div class="d-flex justify-content-between fs-4 fw-bold mb-3">
                                        <span>Total</span>
                                        <span>Rp <span id="total">0</span></span>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="button" class="btn btn-warning btn-lg" onclick="POS.persist()">
                                            Pending
                                        </button>

                                        <button type="button" class="btn btn-success btn-lg" onclick="POS.checkout()">
                                            Checkout
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL PILIH VARIAN --}}
    <div class="modal fade" id="variantModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pilih Varian Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>SKU</th>
                                <th>Varian</th>
                                <th class="text-end">Stok</th>
                                <th class="text-end">Harga</th>
                            </tr>
                        </thead>
                        <tbody id="variantList">
                            {{-- diisi via JS --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- ======================================================== --}}
    {{-- MODAL PERINGATAN SHIFT MENGGANTUNG / KEMARIN (STALE SHIFT)--}}
    {{-- ======================================================== --}}
    @if ($activeRegister && !empty($registerFreshness) && $registerFreshness['is_stale'])
    <div class="modal fade" id="modalStaleShift" tabindex="-1" data-bs-backdrop="static" aria-labelledby="modalStaleShiftLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 shadow border-warning">
                <div class="modal-header bg-warning text-dark py-3">
                    <h5 class="modal-title fw-bold" id="modalStaleShiftLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Shift Kasir Kemarin Belum Ditutup
                    </h5>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-warning d-flex align-items-center mb-3">
                        <i class="bi bi-clock-history fs-2 me-3 text-warning"></i>
                        <div>
                            <div class="fw-bold">Shift Dibuka Sejak:</div>
                            <div class="fs-6">{{ $registerFreshness['opened_at_formatted'] }} <span class="badge bg-secondary ms-1">± {{ $registerFreshness['duration_hours'] }} jam lalu</span></div>
                        </div>
                    </div>
                    <p class="mb-3 text-secondary">
                        @if ($registerFreshness['must_close'])
                            Shift kasir ini sudah aktif lebih dari <strong>24 jam</strong>. Untuk menjaga ketertiban pencatatan kas dan laporan keuangan, Anda <strong>wajib melakukan Tutup Kasir</strong> terlebih dahulu sebelum membuka sesi hari ini.
                        @else
                            Sistem mendeteksi shift kasir sebelumnya masih aktif. Apakah Anda ingin menutup shift kemarin untuk menghitung uang laci dan membuka shift baru hari ini?
                        @endif
                    </p>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary btn-lg fw-bold" onclick="handleCloseStaleShiftAndOpenNew()">
                            <i class="bi bi-door-closed me-2"></i> Tutup Shift Kemarin & Buka Baru (Disarankan)
                        </button>
                        @if (!$registerFreshness['must_close'])
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-arrow-right-circle me-1"></i> Lanjutkan Shift Ini (Khusus Toko Lembur)
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ======================================================== --}}
    {{-- MODAL TARIK TIKET SERVIS / WORK ORDER                   --}}
    {{-- ======================================================== --}}
    <div class="modal fade" id="modalServiceOrders" tabindex="-1" aria-labelledby="modalServiceOrdersLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="modalServiceOrdersLabel">
                        <i class="bi bi-tools me-2"></i> Tarik Tiket Servis / Work Order Selesai
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchServiceOrdersInput" class="form-control" placeholder="Cari No. Tiket / IMEI / Plat / Nama Pelanggan..." oninput="loadServiceOrders(this.value)">
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>No. Tiket</th>
                                    <th>Pelanggan</th>
                                    <th>Unit Servis</th>
                                    <th class="text-end">Total Biaya</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" width="80">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="serviceOrdersList">
                                {{-- Loaded via JS --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- MODAL BUKA KASIR (OPEN CASHIER)                         --}}
    {{-- ======================================================== --}}
    <div class="modal fade" id="modalOpenCashier" tabindex="-1" data-bs-backdrop="static" aria-labelledby="modalOpenCashierLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="modalOpenCashierLabel">
                        <i class="bi bi-door-open me-2"></i> Buka Kasir (Open Cashier)
                    </h5>
                </div>
                <form id="formOpenCashier" onsubmit="submitOpenCashier(event)">
                    <div class="modal-body p-4">
                        <div class="text-center mb-3">
                            <div class="avatar-lg bg-light-primary text-primary rounded-circle d-inline-flex align-items-center justify-content-center p-3 mb-2" style="font-size: 32px; width: 64px; height: 64px;">
                                <i class="bi bi-cash-coin"></i>
                            </div>
                            <h6 class="fw-bold mb-1">{{ $store->name }}</h6>
                            <p class="text-muted small mb-0">Kasir: <strong>{{ auth()->user()->name }}</strong> | {{ date('d M Y') }}</p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Modal Kas Awal (Opening Cash Float) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text fw-bold">Rp</span>
                                <input type="number" id="opening_cash" class="form-control fw-bold fs-4 text-end" placeholder="0" min="0" step="100" required autofocus>
                            </div>
                            <div class="form-text">Masukkan jumlah uang kas modal di laci kasir saat membuka toko.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Catatan Buka Kasir (Opsional)</label>
                            <textarea id="open_notes" class="form-control" rows="2" placeholder="Catatan shift pagi / nomor laci / dsb"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold" id="btnSubmitOpenCashier">
                            <i class="bi bi-check-circle me-1"></i> Buka Sesi Kasir
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- MODAL KAS MASUK / KAS KELUAR (PETTY CASH MOVEMENT)        --}}
    {{-- ======================================================== --}}
    <div class="modal fade" id="modalCashMovement" tabindex="-1" aria-labelledby="modalCashMovementLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 shadow">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="modalCashMovementLabel">
                        <i class="bi bi-arrow-left-right me-2"></i> Kas Masuk / Kas Keluar
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formCashMovement" onsubmit="submitCashMovement(event)">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Jenis Kas <span class="text-danger">*</span></label>
                            <select id="movement_type" class="form-select form-select-lg fw-bold" onchange="handleMovementTypeChange()" required>
                                <option value="cash_in">🟢 Kas Masuk (Uang Tambahan / Setoran Masuk)</option>
                                <option value="cash_out">🔴 Kas Keluar (Pengeluaran Toko / Biaya / Petty Cash)</option>
                            </select>
                        </div>

                        {{-- Section Kategori Beban Kas Keluar --}}
                        <div class="mb-3 p-3 bg-light rounded-3 border" id="cashOutExpenseGroup" style="display: none;">
                            <label class="form-label fw-semibold mb-2">Tipe Kas Keluar</label>
                            <div class="d-flex flex-column gap-2 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="expense_type_radio" id="radioExpenseOperational" value="expense" checked onchange="toggleExpenseCategoryDropdown()">
                                    <label class="form-check-label fw-semibold text-dark" for="radioExpenseOperational">
                                        🏷️ Beban / Biaya Operasional Toko
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="expense_type_radio" id="radioExpenseNonOperational" value="non_expense" onchange="toggleExpenseCategoryDropdown()">
                                    <label class="form-check-label text-secondary" for="radioExpenseNonOperational">
                                        🏦 Setoran / Mutasi Kas / Non-Beban
                                    </label>
                                </div>
                            </div>

                            <div id="wrapperExpenseCategory" class="mt-2">
                                <label class="form-label small fw-semibold text-dark">Kategori Beban (Masuk Laba Rugi) <span class="text-danger">*</span></label>
                                <select id="movement_expense_category_id" class="form-select">
                                    <option value="">-- Pilih Kategori Beban --</option>
                                    @if(isset($expenseCategories))
                                        @foreach($expenseCategories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                <div class="form-text small text-muted">Pengeluaran ini akan langsung dicatat ke Laporan Biaya & Laba Rugi Toko.</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nominal Kas (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text fw-bold">Rp</span>
                                <input type="number" id="movement_amount" class="form-control fw-bold fs-4 text-end" placeholder="0" min="1" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Keterangan / Keperluan <span class="text-danger">*</span></label>
                            <textarea id="movement_notes" class="form-control" rows="2" placeholder="Contoh: Beli kantong plastik, bayar retribusi sampah, dsb" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSubmitMovement">Simpan Pergerakan Kas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- MODAL TUTUP KASIR & REKONSILIASI KAS (CLOSE CASHIER)     --}}
    {{-- ======================================================== --}}
    <div class="modal fade" id="modalCloseCashier" tabindex="-1" data-bs-backdrop="static" aria-labelledby="modalCloseCashierLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content rounded-4 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold" id="modalCloseCashierLabel">
                        <i class="bi bi-door-closed me-2"></i> Tutup Kasir & Rekonsiliasi Kas (Shift Close)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formCloseCashier" onsubmit="submitCloseCashier(event)">
                    <div class="modal-body p-4">
                        <div id="closeCashierLoading" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="text-muted mt-2">Menghitung rekapitulasi sesi kasir...</p>
                        </div>

                        <div id="closeCashierContent" style="display: none;">
                            {{-- RINGKASAN SISTEM --}}
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-calculator me-1 text-primary"></i> 1. Ringkasan Kas Sistem (Expected Cash)</h6>
                            <div class="table-responsive mb-3">
                                <table class="table table-bordered align-middle">
                                    <tbody class="small">
                                        <tr>
                                            <td class="text-muted">Modal Kas Awal</td>
                                            <td class="text-end fw-semibold">Rp <span id="cc_opening_cash">0</span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Penjualan Tunai (Cash Sales)</td>
                                            <td class="text-end text-success fw-semibold">+ Rp <span id="cc_cash_sales">0</span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Kas Masuk Tambahan (Petty Cash In)</td>
                                            <td class="text-end text-success fw-semibold">+ Rp <span id="cc_cash_in">0</span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Kas Keluar Operasional (Petty Cash Out)</td>
                                            <td class="text-end text-danger fw-semibold">- Rp <span id="cc_cash_out">0</span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Refund Kas Tunai</td>
                                            <td class="text-end text-danger fw-semibold">- Rp <span id="cc_refund_cash">0</span></td>
                                        </tr>
                                        <tr class="table-primary fw-bold fs-6">
                                            <td>Total Kas Seharusnya di Laci (Expected Cash)</td>
                                            <td class="text-end text-primary">Rp <span id="cc_expected_cash">0</span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Penjualan Non-Tunai (Transfer/QRIS/Bank)</td>
                                            <td class="text-end text-info fw-semibold">Rp <span id="cc_non_cash_sales">0</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <hr>

                            {{-- INPUT REKONSILIASI FISIK --}}
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-wallet2 me-1 text-success"></i> 2. Rekonsiliasi Kas Fisik Aktual</h6>

                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-semibold mb-0">Total Uang Kas Fisik di Laci <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleDenominations()">
                                    <i class="bi bi-calculator"></i> Kalkulator Pecahan Uang
                                </button>
                            </div>

                            {{-- Denomination Calculator (Expandable) --}}
                            <div id="denomCalculator" class="p-3 bg-light border rounded-3 mb-3" style="display: none;">
                                <div class="row g-2">
                                    @php
                                        $denoms = [100000, 50000, 20000, 10000, 5000, 2000, 1000, 500];
                                    @endphp
                                    @foreach($denoms as $d)
                                        <div class="col-6 col-md-3">
                                            <label class="small text-muted mb-1">Rp {{ number_format($d, 0, ',', '.') }}</label>
                                            <input type="number" class="form-control form-control-sm denom-input text-end" data-value="{{ $d }}" min="0" placeholder="0" oninput="calcDenominations()">
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="input-group input-group-lg mb-3">
                                <span class="input-group-text fw-bold">Rp</span>
                                <input type="number" id="cc_actual_cash" class="form-control fw-bold fs-3 text-end" placeholder="0" min="0" required oninput="calcDifference()">
                            </div>

                            {{-- INDIKATOR SELISIH --}}
                            <div id="diffBadgeContainer" class="p-3 rounded-3 mb-3 bg-light border text-center">
                                <span class="text-muted small d-block mb-1">Status Rekonsiliasi Kas:</span>
                                <div id="diffBadge" class="fs-5 fw-bold text-muted">Masukkan nominal kas fisik</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Catatan / Keterangan Penutupan Kasir</label>
                                <textarea id="cc_notes" class="form-control" rows="2" placeholder="Tuliskan alasan jika ada selisih kas / catatan penutupan"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger btn-lg fw-bold" id="btnSubmitClose">
                            <i class="bi bi-lock-fill me-1"></i> Tutup Kasir & Selesaikan Rekonsiliasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('styles')
    <style>
        .pos-premium-bar {
            background: linear-gradient(135deg, #ede9fe, #faf5ff);
            border: 1px solid #e5d8ff;
            box-shadow: 0 2px 8px rgba(124, 58, 237, 0.08);
        }

        .cashier-shift-bar {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .bg-light-success {
            background-color: #f0fdf4 !important;
        }

        .bg-light-warning {
            background-color: #fffbeb !important;
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #7c3aed;
            font-size: 1rem;
        }

        .input-icon input {
            padding-left: 34px !important;
        }

        .pos-premium-bar label {
            color: #5b21b6;
        }
    </style>
@endpush

@push('initial-scripts')
    <script>
        window.AKUN_BANK = @json($akunkas);
        window.AKUN_KASIR = '{{ $akunkasir }}';
        window.PRINTER_TYPE = '{{ $store->printer_type ?? '80mm' }}';
        window.ENABLE_CASH_REGISTER = {{ $store->enable_cash_register ? 'true' : 'false' }};
        window.ACTIVE_REGISTER_ID = {{ $activeRegister ? $activeRegister->id : 'null' }};
        window.IS_STALE_SHIFT = {{ (!empty($registerFreshness) && $registerFreshness['is_stale']) ? 'true' : 'false' }};
        window.MUST_CLOSE_SHIFT = {{ (!empty($registerFreshness) && $registerFreshness['must_close']) ? 'true' : 'false' }};
    </script>
@endpush

@push('scripts')
    <script>
        let expectedCashValue = 0;

        // Auto open modal jika kasir belum dibuka / stale & store mewajibkan
        document.addEventListener("DOMContentLoaded", () => {
            if (window.ENABLE_CASH_REGISTER) {
                if (!window.ACTIVE_REGISTER_ID) {
                    openOpenCashierModal();
                } else if (window.IS_STALE_SHIFT) {
                    const staleModalEl = document.getElementById('modalStaleShift');
                    if (staleModalEl) {
                        new bootstrap.Modal(staleModalEl).show();
                    }
                }
            }
        });

        function handleCloseStaleShiftAndOpenNew() {
            const staleModalEl = document.getElementById('modalStaleShift');
            if (staleModalEl) {
                const modalInstance = bootstrap.Modal.getInstance(staleModalEl);
                if (modalInstance) modalInstance.hide();
            }
            openCloseCashierModal();
        }

        // ── Open Cashier ─────────────────────────────────────────────────────────
        function openOpenCashierModal() {
            document.getElementById('formOpenCashier').reset();
            new bootstrap.Modal(document.getElementById('modalOpenCashier')).show();
        }

        function submitOpenCashier(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitOpenCashier');
            const openingCash = document.getElementById('opening_cash').value;
            const notes = document.getElementById('open_notes').value;

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Membuka Kasir...';

            fetch("{{ route('cash-registers.open') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    opening_cash: openingCash,
                    notes: notes
                })
            })
            .then(async r => {
                const res = await r.json();
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Buka Sesi Kasir';
                if (!r.ok) {
                    Swal.fire('Error', res.message || 'Gagal membuka kasir', 'error');
                    return;
                }
                Swal.fire({
                    icon: 'success',
                    title: 'Kasir Berhasil Dibuka',
                    text: res.message,
                    timer: 1200,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Buka Sesi Kasir';
                Swal.fire('Error', 'Terjadi kesalahan jaringan.', 'error');
            });
        }

        // ── Petty Cash Movement ───────────────────────────────────────────────────
        function handleMovementTypeChange() {
            const type = document.getElementById('movement_type').value;
            const expenseGroup = document.getElementById('cashOutExpenseGroup');
            if (type === 'cash_out') {
                expenseGroup.style.display = 'block';
                toggleExpenseCategoryDropdown();
            } else {
                expenseGroup.style.display = 'none';
            }
        }

        function toggleExpenseCategoryDropdown() {
            const isOperational = document.getElementById('radioExpenseOperational').checked;
            const wrapper = document.getElementById('wrapperExpenseCategory');
            const selectEl = document.getElementById('movement_expense_category_id');
            if (isOperational) {
                wrapper.style.display = 'block';
            } else {
                wrapper.style.display = 'none';
                selectEl.value = '';
            }
        }

        function openCashMovementModal(defaultType = 'cash_in') {
            document.getElementById('formCashMovement').reset();
            document.getElementById('movement_type').value = defaultType;
            document.getElementById('radioExpenseOperational').checked = true;
            handleMovementTypeChange();
            new bootstrap.Modal(document.getElementById('modalCashMovement')).show();
        }

        function submitCashMovement(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitMovement');
            const type = document.getElementById('movement_type').value;
            const amount = document.getElementById('movement_amount').value;
            const notes = document.getElementById('movement_notes').value;
            let expenseCategoryId = null;

            if (type === 'cash_out') {
                const isOperational = document.getElementById('radioExpenseOperational').checked;
                if (isOperational) {
                    expenseCategoryId = document.getElementById('movement_expense_category_id').value;
                    if (!expenseCategoryId) {
                        Swal.fire('Perhatian', 'Silakan pilih Kategori Beban Operasional terlebih dahulu.', 'warning');
                        return;
                    }
                }
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

            fetch("{{ route('cash-registers.movement') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    type,
                    amount,
                    notes,
                    expense_category_id: expenseCategoryId
                })
            })
            .then(async r => {
                const res = await r.json();
                btn.disabled = false;
                btn.innerText = 'Simpan Pergerakan Kas';
                if (!r.ok) {
                    Swal.fire('Error', res.message || 'Gagal menyimpan pergerakan kas', 'error');
                    return;
                }
                bootstrap.Modal.getInstance(document.getElementById('modalCashMovement')).hide();
                Swal.fire('Berhasil', res.message, 'success');
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerText = 'Simpan Pergerakan Kas';
                Swal.fire('Error', 'Terjadi kesalahan jaringan.', 'error');
            });
        }

        // ── Close Cashier & Rekonsiliasi ──────────────────────────────────────────
        function openCloseCashierModal() {
            const modal = new bootstrap.Modal(document.getElementById('modalCloseCashier'));
            modal.show();

            document.getElementById('closeCashierLoading').style.display = 'block';
            document.getElementById('closeCashierContent').style.display = 'none';
            document.getElementById('denomCalculator').style.display = 'none';
            document.querySelectorAll('.denom-input').forEach(i => i.value = '');
            document.getElementById('cc_actual_cash').value = '';
            document.getElementById('cc_notes').value = '';

            fetch("{{ route('cash-registers.summary') }}")
                .then(r => r.json())
                .then(res => {
                    document.getElementById('closeCashierLoading').style.display = 'none';
                    if (!res.success) {
                        Swal.fire('Error', res.message || 'Gagal mengambil data kasir', 'error');
                        return;
                    }

                    const sum = res.summary;
                    expectedCashValue = sum.expected_cash;

                    document.getElementById('cc_opening_cash').innerText = formatRupiah(sum.opening_cash);
                    document.getElementById('cc_cash_sales').innerText = formatRupiah(sum.cash_sales);
                    document.getElementById('cc_cash_in').innerText = formatRupiah(sum.cash_in);
                    document.getElementById('cc_cash_out').innerText = formatRupiah(sum.cash_out);
                    document.getElementById('cc_refund_cash').innerText = formatRupiah(sum.refund_cash);
                    document.getElementById('cc_expected_cash').innerText = formatRupiah(sum.expected_cash);
                    document.getElementById('cc_non_cash_sales').innerText = formatRupiah(sum.non_cash_sales);

                    calcDifference();
                    document.getElementById('closeCashierContent').style.display = 'block';
                })
                .catch(() => {
                    document.getElementById('closeCashierLoading').style.display = 'none';
                    Swal.fire('Error', 'Terjadi kesalahan saat memuat data rekonsiliasi.', 'error');
                });
        }

        function toggleDenominations() {
            const el = document.getElementById('denomCalculator');
            el.style.display = el.style.display === 'none' ? 'block' : 'none';
        }

        function calcDenominations() {
            let total = 0;
            document.querySelectorAll('.denom-input').forEach(input => {
                const count = parseInt(input.value) || 0;
                const value = parseInt(input.dataset.value) || 0;
                total += count * value;
            });
            document.getElementById('cc_actual_cash').value = total;
            calcDifference();
        }

        function calcDifference() {
            const actual = parseFloat(document.getElementById('cc_actual_cash').value) || 0;
            const diff = actual - expectedCashValue;
            const badge = document.getElementById('diffBadge');

            if (document.getElementById('cc_actual_cash').value === '') {
                badge.className = 'fs-6 fw-semibold text-muted';
                badge.innerText = 'Masukkan nominal kas fisik untuk melihat selisih';
                return;
            }

            if (diff === 0) {
                badge.className = 'fs-5 fw-bold text-success';
                badge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Kas Sesuai / Pas (Selisih: Rp 0)';
            } else if (diff > 0) {
                badge.className = 'fs-5 fw-bold text-info';
                badge.innerHTML = `<i class="bi bi-arrow-up-circle-fill me-1"></i> Kas Lebih: +Rp ${formatRupiah(diff)}`;
            } else {
                badge.className = 'fs-5 fw-bold text-danger';
                badge.innerHTML = `<i class="bi bi-exclamation-circle-fill me-1"></i> Kas Kurang: -Rp ${formatRupiah(Math.abs(diff))}`;
            }
        }

        function submitCloseCashier(e) {
            e.preventDefault();
            const actualCash = parseFloat(document.getElementById('cc_actual_cash').value);
            if (isNaN(actualCash) || actualCash < 0) {
                Swal.fire('Peringatan', 'Harap masukkan nominal uang fisik yang valid.', 'warning');
                return;
            }

            const diff = actualCash - expectedCashValue;
            const notes = document.getElementById('cc_notes').value;

            if (diff !== 0 && (!notes || notes.trim().length === 0)) {
                Swal.fire('Catatan Diperlukan', 'Terdapat selisih kas. Harap isi catatan/alasan selisih sebelum menutup kasir.', 'warning');
                return;
            }

            // Gather denominations
            const denoms = {};
            document.querySelectorAll('.denom-input').forEach(input => {
                const count = parseInt(input.value) || 0;
                if (count > 0) {
                    denoms[input.dataset.value] = count;
                }
            });

            Swal.fire({
                title: 'Tutup Kasir Sekarang?',
                text: 'Pastikan seluruh transaksi shift ini sudah selesai dan perhitungan kas fisik sudah benar.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Tutup Kasir',
                cancelButtonText: 'Periksa Lagi'
            }).then(result => {
                if (!result.isConfirmed) return;

                const btn = document.getElementById('btnSubmitClose');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan Rekonsiliasi...';

                fetch("{{ route('cash-registers.close') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        actual_cash: actualCash,
                        denominations: denoms,
                        notes: notes
                    })
                })
                .then(async r => {
                    const res = await r.json();
                    btn.disabled = false;
                    btn.innerText = 'Tutup Kasir & Selesaikan Rekonsiliasi';
                    if (!r.ok) {
                        Swal.fire('Error', res.message || 'Gagal menutup kasir', 'error');
                        return;
                    }

                    bootstrap.Modal.getInstance(document.getElementById('modalCloseCashier')).hide();

                    Swal.fire({
                        icon: 'success',
                        title: 'Kasir Berhasil Ditutup',
                        text: res.message,
                        showCancelButton: true,
                        confirmButtonText: '<i class="bi bi-printer"></i> Cetak Struk Penutupan',
                        cancelButtonText: 'Selesai',
                        confirmButtonColor: '#0d6efd'
                    }).then(r => {
                        if (r.isConfirmed && res.data?.id) {
                            window.open(`/cash-registers/print/${res.data.id}`, '_blank');
                        }
                        location.reload();
                    });
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.innerText = 'Tutup Kasir & Selesaikan Rekonsiliasi';
                    Swal.fire('Error', 'Terjadi kesalahan jaringan.', 'error');
                });
            });
        }

        function formatRupiah(num) {
            return new Intl.NumberFormat('id-ID').format(Math.round(num || 0));
        }

        // ── Tarik Tiket Servis ──────────────────────────────────────────────────
        let cachedServiceOrders = [];

        function openServiceOrdersModal() {
            $('#modalServiceOrders').modal('show');
            loadServiceOrders();
        }

        function loadServiceOrders(search = '') {
            $('#serviceOrdersList').html('<tr><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat tiket servis...</td></tr>');
            fetch(`/pos/service-orders?search=${encodeURIComponent(search)}`)
                .then(r => {
                    if (!r.ok) throw new Error('Gagal memuat data (' + r.status + ')');
                    return r.json();
                })
                .then(res => {
                    cachedServiceOrders = res.data || [];
                    if (cachedServiceOrders.length === 0) {
                        $('#serviceOrdersList').html('<tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada tiket servis aktif yang siap ditarik.</td></tr>');
                        return;
                    }
                    let html = '';
                    cachedServiceOrders.forEach((o, index) => {
                        const total = new Intl.NumberFormat('id-ID').format(o.total_cost || 0);
                        const dp = new Intl.NumberFormat('id-ID').format(o.down_payment || 0);
                        const remaining = new Intl.NumberFormat('id-ID').format(o.remaining_payment || o.total_cost || 0);
                        html += `
                            <tr>
                                <td><strong class="text-primary">#${o.order_number}</strong></td>
                                <td>
                                    <strong>${o.customer_name}</strong>
                                    ${o.customer_phone ? `<br><small class="text-muted"><i class="bi bi-whatsapp"></i> ${o.customer_phone}</small>` : ''}
                                </td>
                                <td>
                                    <strong>${o.target_name}</strong>
                                    ${o.target_identifier ? `<br><small class="badge bg-light text-dark border">${o.target_identifier}</small>` : ''}
                                </td>
                                <td class="text-end">
                                    <strong>Rp ${total}</strong>
                                    ${o.down_payment > 0 ? `<br><small class="text-success">DP: Rp ${dp}</small><br><small class="text-danger fw-semibold">Sisa: Rp ${remaining}</small>` : ''}
                                </td>
                                <td class="text-center"><span class="badge bg-success">${(o.status || '').toUpperCase()}</span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-primary" onclick="importServiceOrderByIndex(${index})">
                                        <i class="bi bi-cart-plus"></i> Tarik
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    $('#serviceOrdersList').html(html);
                })
                .catch(err => {
                    console.error('Error loadServiceOrders:', err);
                    $('#serviceOrdersList').html('<tr><td colspan="6" class="text-center py-4 text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Gagal memuat tiket: ' + err.message + '</td></tr>');
                });
        }

        function importServiceOrderByIndex(index) {
            const order = cachedServiceOrders[index];
            if (!order) return;
            importServiceOrder(order);
        }

        function importServiceOrder(order) {
            if (!window.POS || !window.POS.cart) {
                Swal.fire('Error', 'Sistem POS belum siap. Silakan muat ulang halaman.', 'error');
                return;
            }

            const items = order.items || [];
            if (items.length === 0) {
                Swal.fire('Perhatian', 'Tiket ini belum memiliki rincian item jasa atau sparepart.', 'warning');
                return;
            }

            // Set customer name or dropdown
            if (order.customer_id && $('#customerId').length) {
                $('#customerId').val(order.customer_id).trigger('change.select2');
            } else if (order.customer_name) {
                window.POS.cart.customer_name = order.customer_name;
            }

            items.forEach(item => {
                const price = parseFloat(item.price) || 0;
                const qty = parseInt(item.qty) || 1;
                window.POS.cart.items.push({
                    key: (window.crypto && window.crypto.randomUUID) ? window.crypto.randomUUID() : 'item-' + Date.now() + '-' + Math.random(),
                    product_id: item.product_id || null,
                    variant_id: item.product_variant_id || null,
                    sku: item.sku || ('SRV-' + item.id),
                    name: item.name,
                    variant: item.variant ? item.variant.variant_label : '',
                    price: price,
                    qty: qty,
                    stok: 999999,
                    discount_type: null,
                    discount_value: 0,
                    discount_amount: 0,
                    subtotal: price * qty,
                    product_type: item.item_type === 'product' ? 'SINGLE' : 'SERVICE',
                    staff_user_id: item.staff_user_id || null,
                    commission_type: item.commission_type || null,
                    commission_rate: item.commission_rate || 0,
                    notes: `Tiket #${order.order_number} - ${order.target_name}`
                });
            });

            // Recalculate subtotal
            let subtotal = 0;
            window.POS.cart.items.forEach(i => {
                subtotal += (i.price * i.qty);
            });
            window.POS.cart.subtotal = subtotal;

            const dp = parseFloat(order.down_payment) || 0;
            if (dp > 0) {
                window.POS.cart.transaction_discount_type = 'nominal';
                window.POS.cart.transaction_discount_value = dp;
                window.POS.cart.transaction_discount = dp;
                window.POS.cart.discount_total = dp;
            }

            window.POS.cart.total = Math.max(0, subtotal - (window.POS.cart.transaction_discount || 0));

            window.POS.persist();
            window.POS.render();

            const modalEl = document.getElementById('modalServiceOrders');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }

            Swal.fire({
                icon: 'success',
                title: 'Tiket Berhasil Ditarik',
                text: `Tiket #${order.order_number} (${order.target_name}) telah dimuat ke kasir POS.`,
                timer: 2000,
                showConfirmButton: false
            });
        }
    </script>
@endpush
