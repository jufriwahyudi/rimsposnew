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

                        <div class="mb-3">
                            <label class="form-label">Pilih Bahan Baku <span class="text-danger">*</span></label>
                            <select class="form-select" name="ingredient_id" id="ingredient_id" required onchange="updateMaxQuantity(this)">
                                <option value="" disabled selected>-- Pilih Bahan Baku --</option>
                                @foreach ($ingredients as $ing)
                                    <option value="{{ $ing->id }}" 
                                        data-max="{{ $stocks[$ing->id] }}" 
                                        data-unit="{{ $ing->baseUnit?->symbol }}">
                                        {{ $ing->name }} (SKU: {{ $ing->sku }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jumlah Transfer (dalam Satuan Dasar) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.0001" class="form-control" name="qty_to_transfer" id="qty_to_transfer" placeholder="0.0000" min="0.0001" required>
                                <span class="input-group-text" id="unit-label">-</span>
                            </div>
                            <small class="text-muted d-block mt-1" id="max-stock-hint">Pilih bahan baku untuk melihat stok Gudang yang tersedia.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">No. Referensi / Surat Jalan Transfer</label>
                            <input type="text" class="form-control" name="reference_id" placeholder="Contoh: TRF-001A (Kosongkan untuk nomor otomatis)">
                            <small class="text-muted d-block mt-1">Nomor referensi atau surat jalan pemindahan barang.</small>
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
        function updateMaxQuantity(select) {
            const selectedOption = select.options[select.selectedIndex];
            const maxVal = parseFloat(selectedOption.getAttribute('data-max'));
            const unit = selectedOption.getAttribute('data-unit');

            document.getElementById('unit-label').innerText = unit;
            document.getElementById('qty_to_transfer').max = maxVal;
            document.getElementById('max-stock-hint').innerHTML = `Maksimal transfer: <strong class="text-primary">${maxVal.toFixed(4)} ${unit}</strong> (Sesuai sisa stok Gudang)`;
        }
    </script>
@endsection
