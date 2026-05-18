@extends('layouts.main.main')
@section('title', 'POS - Point of Sale')

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card rounded-4 p-2">
                {{-- <div class="card-header d-flex align-items-center mb-3">
                    <img src="{{ asset('assets/images/alazca_logo.png') }}" width="35" class="me-2">
                    <div>
                        <h5 class="fw-bold mb-0" style="color:#7c3aed">Penjualan</h5>
                        <small class="text-muted">Al-Azhar Cairo Banda Aceh</small>
                    </div>
                </div> --}}

                <div class="card-body">
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
                                    <label class="form-label fw-semibold small mb-1">Nama Pelanggan</label>
                                    <div class="input-icon">
                                        <i class="bi bi-person"></i>
                                        <input type="text" id="customerName" class="form-control"
                                            placeholder="Masukkan nama pelanggan" onchange="POS.updateCustomerName()">
                                    </div>
                                </div>
                            </div>

                        </div>
                    @else
                        <input type="hidden" id="transactionDate" value="{{ date('Y-m-d') }}">
                        <input type="hidden" id="customerName" value="Umum">
                    @endif

                    {{-- TAB POS --}}
                    <div class="d-flex align-items-center mb-3">
                        <div id="posTabs" class="me-2"></div>
                        <button type="button" class="btn btn-success btn-sm" onclick="POS.newTab()">
                            + New
                        </button>
                    </div>

                    <div class="row">
                        {{-- LEFT : CART --}}
                        <div class="col-md-8">

                            {{-- INPUT SKU --}}
                            <div class="input-group mb-3">
                                <input type="text" id="skuInput" class="form-control form-control-lg"
                                    placeholder="Scan barcode / input SKU / kode produk" autofocus>
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

                                        {{-- Checkout belum aktif --}}
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

@endsection
@push('styles')
    <style>
        .pos-premium-bar {
            background: linear-gradient(135deg, #ede9fe, #faf5ff);
            border: 1px solid #e5d8ff;
            box-shadow: 0 2px 8px rgba(124, 58, 237, 0.08);
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
    <script src="https://cdn.jsdelivr.net/npm/qz-tray/qz-tray.js"></script>
    <script>
        window.AKUN_BANK = @json($akunkas);
        window.AKUN_KASIR = '{{ $akunkasir }}';
    </script>
@endpush
@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            Printer.initQZ();
        });
    </script>
@endpush
