@extends('layouts.main.main')
@section('title', 'Tambah Produk')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Pengaturan Produk</div>
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
                            <h5 class="fw-bold mb-0" style="color: #7c3aed">Tambah Produk</h5>
                            <small class="text-muted">{{ session('store_name') }}</small>
                        </div>
                    </div>
                    <a href="{{ route('produk.index') }}" class="btn btn-success btn-sm mb-3"><i
                            class="bi bi-arrow-left"></i> Kembali</a>
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

                    <form method="POST" action="{{ route('produk.store') }}" enctype="multipart/form-data">
                        @csrf

                        {{-- Info Produk --}}
                        <div class="card mb-3">
                            <div class="card-header fw-semibold">Informasi Produk</div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <label>Kode Produk</label>
                                    <input name="kode" class="form-control" value="{{ old('kode') }}" required
                                        placeholder="Contoh: PRD-001">
                                </div>
                                <div class="mb-2">
                                    <label>Nama Produk</label>
                                    <input name="nama" class="form-control" value="{{ old('nama') }}" required>
                                </div>
                                <div class="mb-2">
                                    <label>Deskripsi Produk</label>
                                    <textarea name="deskripsi" class="form-control" rows="3">{{ old('deskripsi') }}</textarea>
                                </div>
                                @if ($isFnB)
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label>Tenant / Stelling Provider</label>
                                            <select name="tenant_id" class="form-select">
                                                <option value="">-- Tanpa Tenant (Umum) --</option>
                                                @foreach ($tenants as $t)
                                                    <option value="{{ $t->id }}" {{ old('tenant_id') == $t->id ? 'selected' : '' }}>
                                                        {{ $t->nama_tenant }} ({{ $t->kode_tenant }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label>Foto Produk</label>
                                            <input type="file" name="image" class="form-control" accept="image/*">
                                            <small class="text-muted">Maksimal 2MB, JPG atau PNG</small>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Tabel Varian --}}
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span class="fw-semibold">Daftar Varian</span>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addVariantRow()">
                                    <i class="bi bi-plus-circle"></i> Tambah Varian
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-bordered mb-0" id="variantTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nama Varian <small class="text-muted fw-normal">(contoh: Merah L, XL Hitam)</small></th>
                                                <th width="15%">Barcode <small class="text-muted fw-normal">(kosongkan = auto)</small></th>
                                                <th width="12%">Harga Jual</th>
                                                @if ($isFnB)
                                                    <th width="11%">Track Stock</th>
                                                    <th width="13%">Cost (Manual)</th>
                                                    <th width="13%">Tipe Komisi</th>
                                                    <th width="13%">Rate Komisi</th>
                                                @endif
                                                <th width="7%" class="text-center">Hapus</th>
                                            </tr>
                                        </thead>
                                        <tbody id="variantBody">
                                            {{-- baris lama (old input) --}}
                                            @if (old('variants'))
                                                @foreach (old('variants') as $i => $v)
                                                    <tr>
                                                        <td>
                                                            <input name="variants[{{ $i }}][nama]"
                                                                class="form-control" value="{{ $v['nama'] ?? '' }}"
                                                                placeholder="Nama varian">
                                                        </td>
                                                        <td>
                                                            <input name="variants[{{ $i }}][barcode]"
                                                                class="form-control barcode-input"
                                                                value="{{ $v['barcode'] ?? '' }}" placeholder="Auto-generate">
                                                        </td>
                                                        <td>
                                                            <input name="variants[{{ $i }}][harga]"
                                                                class="form-control" type="number" min="0"
                                                                value="{{ $v['harga'] ?? '' }}" placeholder="0">
                                                        </td>
                                                        @if ($isFnB)
                                                            <td>
                                                                <select name="variants[{{ $i }}][track_stock]" class="form-select form-select-sm">
                                                                    <option value="1" {{ ($v['track_stock'] ?? '1') == '1' ? 'selected' : '' }}>Ya</option>
                                                                    <option value="0" {{ ($v['track_stock'] ?? '1') == '0' ? 'selected' : '' }}>Tidak</option>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <input name="variants[{{ $i }}][cost_price_manual]" class="form-control form-control-sm" type="number" min="0" value="{{ $v['cost_price_manual'] ?? '0' }}">
                                                            </td>
                                                            <td>
                                                                <select name="variants[{{ $i }}][commission_type]" class="form-select form-select-sm">
                                                                    <option value="global" {{ ($v['commission_type'] ?? 'global') == 'global' ? 'selected' : '' }}>Global Tenant</option>
                                                                    <option value="percentage" {{ ($v['commission_type'] ?? 'global') == 'percentage' ? 'selected' : '' }}>Persentase</option>
                                                                    <option value="nominal" {{ ($v['commission_type'] ?? 'global') == 'nominal' ? 'selected' : '' }}>Nominal Flat</option>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <input name="variants[{{ $i }}][commission_rate]" class="form-control form-control-sm" type="number" min="0" value="{{ $v['commission_rate'] ?? '0' }}">
                                                            </td>
                                                        @endif
                                                        <td class="text-center align-middle">
                                                            <button type="button" class="btn btn-danger btn-sm"
                                                                onclick="removeRow(this)">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('produk.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                        <button class="btn btn-primary">
                            <i class="bi bi-save"></i> Simpan Produk
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let rowIndex = {{ old('variants') ? count(old('variants')) : 0 }};
        const isFnB = {{ $isFnB ? 'true' : 'false' }};

        function addVariantRow() {
            const tbody = document.getElementById('variantBody');
            const i = rowIndex++;
            
            let fnbColumns = '';
            if (isFnB) {
                fnbColumns = `
                <td>
                    <select name="variants[${i}][track_stock]" class="form-select form-select-sm">
                        <option value="1">Ya</option>
                        <option value="0">Tidak</option>
                    </select>
                </td>
                <td>
                    <input name="variants[${i}][cost_price_manual]" class="form-control form-control-sm" type="number" min="0" value="0">
                </td>
                <td>
                    <select name="variants[${i}][commission_type]" class="form-select form-select-sm">
                        <option value="global">Global Tenant</option>
                        <option value="percentage">Persentase</option>
                        <option value="nominal">Nominal Flat</option>
                    </select>
                </td>
                <td>
                    <input name="variants[${i}][commission_rate]" class="form-control form-control-sm" type="number" min="0" value="0">
                </td>
                `;
            }

            const row = `
            <tr>
                <td>
                    <input name="variants[${i}][nama]" class="form-control"
                        placeholder="Nama varian" required>
                </td>
                <td>
                    <input name="variants[${i}][barcode]" class="form-control barcode-input"
                        placeholder="Auto-generate">
                </td>
                <td>
                    <input name="variants[${i}][harga]" class="form-control"
                        type="number" min="0" placeholder="0">
                </td>
                ${fnbColumns}
                <td class="text-center align-middle">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>`;
            tbody.insertAdjacentHTML('beforeend', row);
        }

        function removeRow(btn) {
            const row = btn.closest('tr');
            // Minimal 1 baris
            const tbody = document.getElementById('variantBody');
            if (tbody.querySelectorAll('tr').length <= 1) {
                Swal.fire('Perhatian', 'Minimal harus ada 1 varian.', 'warning');
                return;
            }
            row.remove();
        }

        // Tambah 1 baris kosong jika belum ada old input
        document.addEventListener('DOMContentLoaded', function() {
            const tbody = document.getElementById('variantBody');
            if (tbody.querySelectorAll('tr').length === 0) {
                addVariantRow();
            }
        });
    </script>
@endpush
