@extends('layouts.main.main')
@section('title', 'Stok Bahan Baku')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Stok</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Stok Bahan Baku</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-end gap-2">
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalStockIn">
                <i class="material-icons-outlined" style="font-size:16px;vertical-align:middle">add_shopping_cart</i> Stock In (Pembelian)
            </button>
            <a href="{{ route('ingredient-stocks.transfer') }}" class="btn btn-primary btn-sm text-white">
                <i class="material-icons-outlined" style="font-size:16px;vertical-align:middle">local_shipping</i> Transfer Stok
            </a>
            <a href="{{ route('ingredient-stocks.adjust') }}" class="btn btn-warning btn-sm text-white">
                <i class="material-icons-outlined" style="font-size:16px;vertical-align:middle">edit_note</i> Opname / Penyesuaian
            </a>
            <a href="{{ route('ingredient-stocks.report') }}" class="btn btn-info btn-sm text-white">
                <i class="material-icons-outlined" style="font-size:16px;vertical-align:middle">analytics</i> Laporan Aktivitas
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 bg-light-success alert-dismissible fade show py-2">
            <div class="d-flex align-items-center">
                <div class="fs-3 text-success"><span class="material-icons-outlined">check_circle</span></div>
                <div class="ms-3">
                    <div class="text-success">{{ session('success') }}</div>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

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

    <div class="row">
        {{-- Stocks Table --}}
        <div class="col-12 col-lg-8">
            <div class="card rounded-4 p-2">
                <div class="card-header d-flex align-items-start mb-3">
                    <img src="{{ asset('assets/images/alazca_logo.png') }}" alt="Logo" style="width:35px;height:35px;" class="me-2 mt-1">
                    <div>
                        <h5 class="fw-bold mb-0" style="color:#7c3aed">Monitoring Saldo Stok Bahan Baku</h5>
                        <small class="text-muted">Kuantitas stok bahan mentah per lokasi (disimpan dalam Satuan Dasar)</small>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="40">#</th>
                                <th>SKU</th>
                                <th>Nama Bahan Baku</th>
                                <th>Satuan Dasar</th>
                                <th class="text-end">Stok Gudang</th>
                                <th class="text-end">Stok Toko</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ingredients as $i => $item)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td><span class="badge bg-secondary">{{ $item->sku }}</span></td>
                                    <td><strong>{{ $item->name }}</strong></td>
                                    <td><span class="badge bg-info text-dark">{{ $item->baseUnit?->symbol }}</span></td>
                                    <td class="text-end text-primary fw-bold" style="font-size:15px">
                                        {{ number_format($stocks[$item->id]['warehouse'], 2, ',', '.') }}
                                    </td>
                                    <td class="text-end text-success fw-bold" style="font-size:15px">
                                        {{ number_format($stocks[$item->id]['store'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Belum ada data stok bahan baku.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Movement Log --}}
        <div class="col-12 col-lg-4">
            <div class="card rounded-4 p-2">
                <div class="card-header d-flex align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-dark"><span class="material-icons-outlined" style="vertical-align:middle;font-size:18px">history</span> Aktivitas Stok Terakhir</h6>
                </div>
                <div class="card-body p-0" style="max-height: 480px; overflow-y: auto;">
                    <ul class="list-group list-group-flush">
                        @forelse($movements as $mov)
                            @php
                                $badgeColor = 'bg-secondary';
                                if($mov->type === 'PURCHASE' || $mov->type === 'TRANSFER_IN') $badgeColor = 'bg-success';
                                if($mov->type === 'TRANSFER_OUT' || $mov->type === 'SALE' || $mov->type === 'WASTAGE') $badgeColor = 'bg-danger';
                            @endphp
                            <li class="list-group-item px-3 py-2 border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="fw-bold">{{ $mov->ingredient?->name }}</small>
                                    <span class="badge {{ $badgeColor }}">{{ $mov->type }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <small class="text-muted">Loc: {{ $mov->location_type }}</small>
                                    <strong class="{{ $mov->quantity_change > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $mov->quantity_change > 0 ? '+' : '' }}{{ number_format($mov->quantity_change, 2) }} {{ $mov->ingredient?->baseUnit?->symbol }}
                                    </strong>
                                </div>
                                <div class="mt-1" style="font-size:11px">
                                    <span class="text-muted">{{ $mov->notes }}</span>
                                    <span class="d-block text-end text-muted">{{ $mov->created_at }}</span>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted">Belum ada riwayat pergerakan stok.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Stock In (Pembelian) --}}
    <div class="modal fade" id="modalStockIn" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('ingredient-stocks.stock-in') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Catat Stock In (Pembelian Supplier)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Pilih Bahan Baku <span class="text-danger">*</span></label>
                            <select class="form-select" name="ingredient_id" id="stockin_ingredient_id" required onchange="updatePurchaseUnits(this)">
                                <option value="" disabled selected>-- Pilih Bahan Baku --</option>
                                @foreach ($ingredients as $ing)
                                    @php
                                        // Build conversions array for JS
                                        $convs = [];
                                        // Base unit option
                                        $convs[] = [
                                            'value' => 'base', 
                                            'text' => $ing->baseUnit?->symbol . ' (Satuan Dasar)'
                                        ];
                                        foreach($ing->conversions as $c) {
                                            $convs[] = [
                                                'value' => (string) $c->id, 
                                                'text' => $c->purchaseUnit?->symbol . ' (' . $c->code . ') - Isi ' . (float)$c->conversion_factor . ' ' . $ing->baseUnit?->symbol
                                            ];
                                        }
                                    @endphp
                                    <option value="{{ $ing->id }}" data-conversions="{{ json_encode($convs) }}">
                                        {{ $ing->name }} (SKU: {{ $ing->sku }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pilihan Satuan Pembelian <span class="text-danger">*</span></label>
                            <select class="form-select" name="conversion_id" id="stockin_conversion_id" required>
                                <option value="" disabled selected>-- Pilih Bahan Baku Terlebih Dahulu --</option>
                            </select>
                            <small class="text-muted d-block mt-1">Pilih sesuai jenis kemasan supplier yang diterima.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jumlah Pembelian <span class="text-danger">*</span></label>
                            <input type="number" step="0.0001" class="form-control" name="qty_purchased" placeholder="Contoh: 1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Total Biaya Pembelian <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" step="0.01" class="form-control" name="total_cost" placeholder="Total harga nota invoice" required>
                            </div>
                            <small class="text-muted d-block mt-1">Total uang yang dikeluarkan untuk pembelian ini (digunakan untuk menghitung HPP riil batch).</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No. Referensi / PO / Invoice</label>
                            <input type="text" class="form-control" name="reference_id" placeholder="Contoh: INV-9921, PO-221A (Kosongkan untuk nomor otomatis)">
                            <small class="text-muted d-block mt-1">Nomor bukti transaksi supplier.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Pembelian <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="tanggal" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan / Keterangan</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Nomor Invoice PO, Nama Supplier, dll."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan Stok Masuk</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updatePurchaseUnits(select) {
            const selectedOption = select.options[select.selectedIndex];
            const conversions = JSON.parse(selectedOption.getAttribute('data-conversions'));

            const unitSelect = document.getElementById('stockin_conversion_id');
            unitSelect.innerHTML = ""; // Clear

            conversions.forEach(unit => {
                const opt = document.createElement('option');
                opt.value = unit.value;
                opt.text = unit.text;
                unitSelect.appendChild(opt);
            });
        }
    </script>
@endsection
