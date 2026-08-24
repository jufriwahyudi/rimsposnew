@extends('layouts.main.main')
@section('title', 'Transfer Stok Bahan Baku')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Stok</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('ingredient-stocks.index') }}">Stok Bahan Baku</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Transfer Stok</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-8 offset-md-2">
            @if (session('error'))
                <div class="alert alert-danger border-0 bg-light-danger alert-dismissible fade show py-2">
                    <div class="d-flex align-items-center">
                        <div class="fs-3 text-danger"><span class="material-icons-outlined">error</span></div>
                        <div class="ms-3">
                            <div class="text-danger">{{ session('error') }}</div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card rounded-4 p-2">
                <div class="card-header d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-start">
                        <img src="{{ asset('assets/images/alazca_logo.png') }}" alt="Logo" style="width:35px;height:35px;" class="me-2 mt-1">
                        <div>
                            <h5 class="fw-bold mb-0" style="color:#7c3aed">Transfer Stok Bahan Baku</h5>
                            <small class="text-muted">Pindahkan bahan mentah dari Gudang (Warehouse) ke Toko (Store) untuk operasional</small>
                        </div>
                    </div>
                    <a href="{{ route('ingredient-stocks.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
                </div>
                <div class="card-body">
                    <form action="{{ route('ingredient-stocks.transfer.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Asal Lokasi</label>
                            <input type="text" class="form-control bg-light" value="GUDANG (WAREHOUSE)" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tujuan Lokasi</label>
                            <input type="text" class="form-control bg-light" value="TOKO (STORE) - {{ session('store_name') }}" readonly>
                        </div>

                        <!-- OPSI PERLAKUAN AKUNTANSI / STOK -->
                        <div class="mb-4 p-3 rounded-3 border bg-light">
                            <label class="form-label fw-bold d-block mb-2 text-dark">
                                <i class="material-icons-outlined align-middle fs-6 me-1 text-primary">account_balance_wallet</i>
                                Perlakuan Transfer Bahan Baku <span class="text-danger">*</span>
                            </label>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="treatment_type" id="treatment_store_stock" value="store_stock" checked onchange="toggleTreatment(this.value)">
                                <label class="form-check-label fw-semibold" for="treatment_store_stock">
                                    Masuk ke Persediaan Toko (Default)
                                </label>
                                <div class="text-muted small ms-1">Bahan baku dicatat sebagai sisa stok di toko dan baru dihitung beban saat menu resep terjual di kasir.</div>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="treatment_type" id="treatment_direct_expense" value="direct_expense" onchange="toggleTreatment(this.value)">
                                <label class="form-check-label fw-semibold text-danger" for="treatment_direct_expense">
                                    Langsung Diakui sebagai Biaya Operasional Toko
                                </label>
                                <div class="text-muted small ms-1">Stok gudang berkurang, tidak dilacak lagi sebagai saldo stok toko, dan nominal nilai modal langsung dibukukan ke <strong>Biaya Operasional (Expenses)</strong> toko.</div>
                            </div>

                            <!-- OPSI TAMBAHAN JIKA LANGSUNG BIAYA OPERASIONAL -->
                            <div id="expense-options-box" class="mt-3 pt-3 border-top d-none">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Pilih Kategori Biaya Operasional <span class="text-danger">*</span></label>
                                    <select class="form-select" name="expense_category_id" id="expense_category_id">
                                        @foreach ($expenseCategories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-1">Biaya ini akan dicatat ke modul Pengeluaran & Laporan Laba Rugi.</small>
                                </div>

                                <div class="alert alert-info py-2 px-3 mb-0 d-flex align-items-center">
                                    <span class="material-icons-outlined me-2 fs-5 text-info">info</span>
                                    <div class="small">
                                        Estimasi Beban Biaya: <strong id="est-cost-display" class="text-dark">Rp 0</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Pilih Bahan Baku <span class="text-danger">*</span></label>
                            <select class="form-select" name="ingredient_id" id="ingredient_id" required onchange="updateMaxQuantity(this)">
                                <option value="" disabled selected>-- Pilih Bahan Baku --</option>
                                @foreach ($ingredients as $ing)
                                    <option value="{{ $ing->id }}" 
                                        data-max="{{ $stocks[$ing->id] }}" 
                                        data-cost="{{ $costs[$ing->id] ?? 0 }}"
                                        data-unit="{{ $ing->baseUnit?->symbol }}">
                                        {{ $ing->name }} (SKU: {{ $ing->sku }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jumlah Transfer (dalam Satuan Dasar) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.0001" class="form-control" name="qty_to_transfer" id="qty_to_transfer" placeholder="0.0000" min="0.0001" required oninput="calcEstCost()">
                                <span class="input-group-text" id="unit-label">-</span>
                            </div>
                            <small class="text-muted d-block mt-1" id="max-stock-hint">Pilih bahan baku untuk melihat stok Gudang yang tersedia.</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Transaksi</label>
                                <input type="date" class="form-control" name="tanggal" value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">No. Referensi / Surat Jalan Transfer</label>
                                <input type="text" class="form-control" name="reference_id" placeholder="Contoh: TRF-001A (Kosongkan untuk otomatis)">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Catatan / Keterangan</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Tulis catatan jika diperlukan..."></textarea>
                        </div>

                        <div class="border-top pt-3 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-4 text-white">Proses Transfer Stok</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleTreatment(val) {
            const box = document.getElementById('expense-options-box');
            if (val === 'direct_expense') {
                box.classList.remove('d-none');
                calcEstCost();
            } else {
                box.classList.add('d-none');
            }
        }

        function updateMaxQuantity(select) {
            const selectedOption = select.options[select.selectedIndex];
            const maxVal = parseFloat(selectedOption.getAttribute('data-max')) || 0;
            const unit = selectedOption.getAttribute('data-unit') || '-';

            document.getElementById('unit-label').innerText = unit;
            document.getElementById('qty_to_transfer').max = maxVal;
            document.getElementById('max-stock-hint').innerHTML = `Maksimal transfer: <strong class="text-primary">${maxVal.toFixed(4)} ${unit}</strong> (Sesuai sisa stok Gudang)`;
            calcEstCost();
        }

        function calcEstCost() {
            const select = document.getElementById('ingredient_id');
            if (!select || select.selectedIndex < 0) return;
            const selectedOption = select.options[select.selectedIndex];
            const cost = parseFloat(selectedOption.getAttribute('data-cost')) || 0;
            const qty = parseFloat(document.getElementById('qty_to_transfer').value) || 0;
            const total = qty * cost;

            const formatted = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(total);
            const costDisplay = document.getElementById('est-cost-display');
            if (costDisplay) {
                costDisplay.innerText = formatted;
            }
        }
    </script>
@endsection
