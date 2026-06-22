@extends('layouts.main.main')
@section('title', 'Manajemen Menu')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Mutasi Produk</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Pengaturan</a></li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card rounded-4 p-2">
                <div class="card-header d-flex justify-content-between align-items-center mb-3 flex-wrap">
                    <div class="d-flex align-items-start">
                        <img src={{ asset('assets/images/alazca_logo.png') }} alt="Logo"
                            style="width: 35px; height: 35px;" class="me-2 mt-1">
                        <div>
                            <h5 class="fw-bold mb-0" style="color: #7c3aed">Mutasi Produk: ({{ $product->kode_produk }})
                                {{ $product->nama_produk }}</h5>
                            <small class="text-muted">{{ session('store_name') }}</small>
                        </div>
                    </div>
                    <a href="{{ route('produk.show', $product->id) }}" class="btn btn-primary btn-sm mb-3"><i
                            class="bi bi-arrow-left"></i>
                        Kembali</a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    {{-- Total Stok --}}
                    <div class="alert alert-info">
                        <h6 class="fw-bold mb-2">{{ $variant->variant_label }}</h6>
                        <strong>Stok Gudang:</strong> {{ number_format($variant->stok_warehouse) }} <br>
                        <strong>Stok Toko:</strong> {{ number_format($variant->stok_store) }} <br>
                        <strong>Total:</strong> {{ number_format($variant->stok_total) }}
                    </div>

                    <ul class="nav nav-tabs" id="mutasiTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="gudang-tab" data-bs-toggle="tab" data-bs-target="#gudang"
                                type="button" role="tab" aria-controls="gudang" aria-selected="true"><i
                                    class="bi bi-box"></i> Gudang</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="toko-tab" data-bs-toggle="tab" data-bs-target="#toko"
                                type="button" role="tab" aria-controls="toko" aria-selected="false"><i
                                    class="bi bi-shop"></i> Toko</button>
                        </li>
                        <li class="nav-item" role="">
                            <button class="nav-link" id="barcode-tab" data-bs-toggle="tab" data-bs-target="#barcode"
                                type="button" role="tab" aria-controls="barcode" aria-selected="false"><i
                                    class="fa-solid fa-barcode"></i> Barcode</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="adjustment-tab" data-bs-toggle="tab" data-bs-target="#adjustment"
                                type="button" role="tab" aria-controls="adjustment" aria-selected="false"><i
                                    class="bi bi-arrow-left-right"></i> Penyesuaian Stok</button>
                        </li>
                    </ul>

                    <div class="tab-content mt-3" id="mutasiTabsContent">
                        <div class="tab-pane fade" id="gudang" role="tabpanel" aria-labelledby="gudang-tab">
                            <div class="card border-0 shadow-sm rounded-4">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-2">Gudang</h6>
                                    @include('produk.partials.tabel_mutasi', [
                                        'movements' => $warehouseMovements,
                                    ])
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade show active" id="toko" role="tabpanel" aria-labelledby="toko-tab">
                            <div class="card border-0 shadow-sm rounded-4">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-2">Toko</h6>
                                    @include('produk.partials.tabel_mutasi', [
                                        'movements' => $storeMovements,
                                    ])
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="barcode" role="tabpanel" aria-labelledby="barcode-tab">
                            <div class="card border-0 shadow-sm rounded-4">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-2">Barcode</h6>
                                    <form action="{{ route('produk.barcode.add', $variant->id) }}" method="POST"
                                        id="barcodeForm">
                                        @csrf
                                        <div class="mb-3">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="barcodeInput"
                                                    placeholder="Masukkan barcode..." autofocus name="barcode">
                                                <button class="btn btn-primary" type="submit" id="btnTambahkan">
                                                    <i class="bi bi-plus-circle"></i> Tambahkan
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                    <div id="barcodeResult" class="mt-3">
                                        <table class="table table-bordered table-sm">
                                            <thead class="table-light">
                                                <tr>
                                                    <th width="25%">Barcode</th>
                                                    <th width="25%">Produk</th>
                                                    <th width="25%">Status</th>
                                                    <th width="25%" class="text-center">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody id="barcodeTableBody">
                                                @foreach ($variant->barcodes as $barcode)
                                                    <tr>
                                                        <td>{{ $barcode->barcode }}</td>
                                                        <td>{{ $variant->product->nama_produk }}</td>
                                                        <td>{{ $barcode->is_active === 'Y' ? 'Aktif' : 'Non-Aktif' }}</td>
                                                        <td class="text-center">
                                                            @if ($barcode->is_active === 'N')
                                                                <button class="btn btn-sm btn-outline-success"
                                                                    onclick="toggleBarcodeStatus({{ $barcode->id }})">
                                                                    {{ $barcode->is_active === 'Y' ? 'Nonaktifkan' : 'Aktifkan' }}

                                                                </button>
                                                                <button class="btn btn-sm btn-outline-danger"
                                                                    onclick="deleteBarcode({{ $barcode->id }})">
                                                                    <i class="bi bi-trash"></i> Hapus
                                                                </button>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- Tab Penyesuaian Stok --}}
                        <div class="tab-pane fade" id="adjustment" role="tabpanel" aria-labelledby="adjustment-tab">
                            <div class="card border-0 shadow-sm rounded-4">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3">Penyesuaian Stok</h6>
                                    <form action="{{ route('produk.variants.adjust-stock', ['product' => $product->id, 'variant' => $variant->id]) }}" method="POST">
                                        @csrf
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="posisi" class="form-label">Posisi</label>
                                                <select name="posisi" id="posisi" class="form-select" required>
                                                    <option value="">-- Pilih Posisi --</option>
                                                    <option value="warehouse" {{ old('posisi') == 'warehouse' ? 'selected' : '' }}>Gudang</option>
                                                    <option value="store" {{ old('posisi') == 'store' ? 'selected' : '' }}>Toko</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="adjustment_type" class="form-label">Tipe Penyesuaian</label>
                                                <select name="adjustment_type" id="adjustment_type" class="form-select" required>
                                                    <option value="">-- Pilih Tipe --</option>
                                                    <option value="increase" {{ old('adjustment_type') == 'increase' ? 'selected' : '' }}>Tambah Stok</option>
                                                    <option value="decrease" {{ old('adjustment_type') == 'decrease' ? 'selected' : '' }}>Kurangi Stok</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="qty" class="form-label">Jumlah (Qty)</label>
                                                <input type="number" name="qty" id="qty" class="form-control"
                                                    min="1" value="{{ old('qty') }}" required
                                                    placeholder="Masukkan jumlah">
                                            </div>
                                            <div class="col-md-4" id="costFieldContainer">
                                                <label for="cost" class="form-label">Harga Beli per Unit (Rp) <span id="costRequired" class="text-danger">*</span></label>
                                                <input type="number" name="cost" id="cost" class="form-control"
                                                    min="0" step="0.01" value="{{ old('cost', 0) }}"
                                                    placeholder="Harga beli per unit">
                                                <small id="costHelpText" class="form-text text-muted">Wajib diisi untuk penambahan stok.</small>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="effective_date" class="form-label">Tanggal Efektif</label>
                                                <input type="date" name="effective_date" id="effective_date" class="form-control"
                                                    value="{{ old('effective_date', date('Y-m-d')) }}" required>
                                            </div>
                                            <div class="col-12">
                                                <label for="notes" class="form-label">Catatan</label>
                                                <textarea name="notes" id="notes" class="form-control" rows="2"
                                                    placeholder="Alasan penyesuaian stok...">{{ old('notes') }}</textarea>
                                            </div>
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bi bi-check-circle"></i> Simpan & Posting Penyesuaian
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        function toggleBarcodeStatus(barcodeId) {
            Swal.fire({
                title: 'Konfirmasi',
                text: 'Apakah Anda yakin ingin mengubah status barcode ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, ubah status',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('{{ route('produk.barcode.toggle', ':id') }}'.replace(':id', barcodeId), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Refresh halaman untuk memperbarui tampilan
                                location.reload();
                            } else {
                                Swal.fire('Terjadi kesalahan', data.message || 'Terjadi kesalahan', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire('Terjadi kesalahan saat mengubah status barcode');
                        });
                }
            });

        }

        function deleteBarcode(barcodeId) {
            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Apakah Anda yakin ingin menghapus barcode ini? Data yang dihapus tidak dapat dikembalikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('{{ route('produk.barcode.delete', ':id') }}'.replace(':id', barcodeId), {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            } else {
                                Swal.fire('Terjadi kesalahan', data.message || 'Terjadi kesalahan', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire('Terjadi kesalahan', 'Terjadi kesalahan saat menghapus barcode', 'error');
                        });
                }
            });
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const adjustmentType = document.getElementById('adjustment_type');
            const costInput = document.getElementById('cost');
            const costRequired = document.getElementById('costRequired');
            const costHelpText = document.getElementById('costHelpText');

            function toggleCostField() {
                if (adjustmentType.value === 'decrease') {
                    // Kurang stok — disable cost, pakai harga modal dari batch
                    costInput.value = 0;
                    costInput.setAttribute('disabled', 'disabled');
                    costInput.removeAttribute('required');
                    costRequired.style.display = 'none';
                    costHelpText.textContent = 'Pengurangan stok mengikuti harga modal dari batch (FIFO).';
                } else {
                    // Tambah stok — cost wajib diisi
                    costInput.removeAttribute('disabled');
                    costInput.setAttribute('required', 'required');
                    costRequired.style.display = 'inline';
                    costHelpText.textContent = 'Wajib diisi untuk penambahan stok.';
                }
            }

            adjustmentType.addEventListener('change', toggleCostField);
            // Jalankan saat load untuk handle old() state
            toggleCostField();
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const barcodeForm = document.getElementById('barcodeForm');
            const barcodeInput = document.getElementById('barcodeInput');
            const btnTambahkan = document.getElementById('btnTambahkan');

            let lastBarcode = '';
            let lastSubmitTime = 0;
            let isSubmitting = false;

            if (barcodeForm) {
                barcodeForm.addEventListener('submit', function(e) {
                    const barcodeVal = barcodeInput.value.trim();
                    const now = Date.now();

                    if (isSubmitting) {
                        e.preventDefault();
                        return;
                    }

                    if (barcodeVal === '') {
                        e.preventDefault();
                        return;
                    }

                    // Handle duplicate scans/submits within 1 second
                    if (barcodeVal === lastBarcode && (now - lastSubmitTime) < 1000) {
                        e.preventDefault();
                        console.log('Duplicate scan ignored:', barcodeVal);
                        return;
                    }

                    lastBarcode = barcodeVal;
                    lastSubmitTime = now;
                    isSubmitting = true;

                    // Disable button to prevent double-click
                    if (btnTambahkan) {
                        btnTambahkan.disabled = true;
                        btnTambahkan.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menambahkan...';
                    }
                });
            }
        });
    </script>
@endpush
