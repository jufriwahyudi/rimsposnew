@extends('layouts.main.main')
@section('title', 'Penyesuaian Stok (Stock Adjustment / Opname)')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Stok</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('ingredient-stocks.index') }}">Stok Bahan Baku</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Penyesuaian Stok</li>
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
                            <h5 class="fw-bold mb-0" style="color:#7c3aed">Penyesuaian & Opname Bahan Baku</h5>
                            <small class="text-muted">Mutakhirkan data stok sistem agar sesuai dengan fisik asli di lapangan</small>
                        </div>
                    </div>
                    <a href="{{ route('ingredient-stocks.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
                </div>
                <div class="card-body">
                    <form action="{{ route('ingredient-stocks.adjust.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Pilih Bahan Baku <span class="text-danger">*</span></label>
                            <select class="form-select" name="ingredient_id" id="ingredient_id" required onchange="updateSystemStock()">
                                <option value="" disabled selected>-- Pilih Bahan Baku --</option>
                                @foreach ($ingredients as $ing)
                                    <option value="{{ $ing->id }}" 
                                        data-warehouse="{{ $stocks[$ing->id]['WAREHOUSE'] }}"
                                        data-store="{{ $stocks[$ing->id]['STORE'] }}"
                                        data-unit="{{ $ing->baseUnit?->symbol }}">
                                        {{ $ing->name }} (SKU: {{ $ing->sku }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Pilih Area Lokasi <span class="text-danger">*</span></label>
                            <select class="form-select" name="location_type" id="location_type" required onchange="updateSystemStock()">
                                <option value="WAREHOUSE" selected>GUDANG (WAREHOUSE)</option>
                                <option value="STORE">TOKO (STORE) - {{ session('store_name') }}</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Stok Tercatat di Sistem</label>
                            <div class="input-group">
                                <input type="text" class="form-control bg-light" id="system_quantity" readonly value="0.0000">
                                <span class="input-group-text system-unit-label">-</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Stok Fisik Asli (Hasil Opname) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.0001" class="form-control" name="actual_quantity" id="actual_quantity" placeholder="0.0000" min="0" required oninput="calculateDifference()">
                                <span class="input-group-text system-unit-label">-</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Selisih Stok Fisik vs Sistem</label>
                            <input type="text" class="form-control bg-light fw-bold" id="stock_difference" readonly value="0.0000">
                            <small class="text-muted d-block mt-1">Nilai positif (+) berarti stok bertambah, nilai negatif (-) berarti stok berkurang (rusak/hilang).</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Alasan Penyesuaian (Reason) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="notes" placeholder="Contoh: Barang gosong dibuang, Selisih opname bulanan, Ayam busuk" required>
                            <small class="text-muted d-block mt-1">Tulis alasan yang jelas. Jika terdapat kata kunci seperti 'rusak', 'busuk', 'gosong', atau 'hilang', sistem otomatis mencatatnya sebagai **WASTAGE**.</small>
                        </div>

                        <div class="border-top pt-3 d-flex justify-content-end">
                            <button type="submit" class="btn btn-warning px-4 text-white">Simpan Penyesuaian</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateSystemStock() {
            const select = document.getElementById('ingredient_id');
            if (select.selectedIndex === 0) return;

            const selectedOption = select.options[select.selectedIndex];
            const locType = document.getElementById('location_type').value;
            const unit = selectedOption.getAttribute('data-unit');

            let qty = 0;
            if (locType === 'WAREHOUSE') {
                qty = parseFloat(selectedOption.getAttribute('data-warehouse'));
            } else {
                qty = parseFloat(selectedOption.getAttribute('data-store'));
            }

            document.getElementById('system_quantity').value = qty.toFixed(4);
            
            const unitLabels = document.querySelectorAll('.system-unit-label');
            unitLabels.forEach(lbl => lbl.innerText = unit);

            calculateDifference();
        }

        function calculateDifference() {
            const systemQty = parseFloat(document.getElementById('system_quantity').value) || 0;
            const actualQty = parseFloat(document.getElementById('actual_quantity').value);

            if (isNaN(actualQty)) {
                document.getElementById('stock_difference').value = "0.0000";
                document.getElementById('stock_difference').className = "form-control bg-light fw-bold";
                return;
            }

            const diff = actualQty - systemQty;
            const diffEl = document.getElementById('stock_difference');
            
            diffEl.value = (diff >= 0 ? '+' : '') + diff.toFixed(4);
            
            if (diff > 0) {
                diffEl.className = "form-control bg-light fw-bold text-success";
            } else if (diff < 0) {
                diffEl.className = "form-control bg-light fw-bold text-danger";
            } else {
                diffEl.className = "form-control bg-light fw-bold text-muted";
            }
        }
    </script>
@endsection
