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

                        {{-- Daftar Varian --}}
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span class="fw-semibold">Daftar Varian</span>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addVariantRow()">
                                    <i class="bi bi-plus-circle"></i> Tambah Varian
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="variantContainer" class="d-flex flex-column gap-3">
                                    {{-- Old Input Variants --}}
                                    @if (old('variants'))
                                        @foreach (old('variants') as $i => $v)
                                            <div class="card border-0 rounded-4 mb-3 variant-card position-relative shadow-sm" style="border: 1px solid #e2e8f0 !important;">
                                                <div class="card-header bg-white border-bottom-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                                                    <span class="fw-bold text-secondary" style="font-size: 0.9rem;">
                                                        <i class="bi bi-tag-fill me-1 text-primary"></i> Varian #{{ $i + 1 }}
                                                    </span>
                                                    <button type="button" class="btn-close text-danger" 
                                                        onclick="removeRow(this)" style="font-size: 0.75rem;" title="Hapus Varian"></button>
                                                </div>
                                                <div class="card-body pt-2 pb-3">
                                                    <div class="row g-3">
                                                        <!-- Left Panel: General Info -->
                                                        <div class="col-md-{{ $isFnB ? '6' : '12' }} {{ $isFnB ? 'border-end pe-3' : '' }}">
                                                            <h6 class="fw-semibold text-primary mb-3" style="font-size: 0.85rem;"><i class="bi bi-info-circle me-1"></i> Informasi Dasar & Harga</h6>
                                                            <div class="row g-2">
                                                                <div class="col-md-6">
                                                                    <label class="form-label fw-semibold" style="font-size: 0.8rem;">Nama Varian</label>
                                                                    <input name="variants[{{ $i }}][nama]" class="form-control form-control-sm" value="{{ $v['nama'] ?? '' }}" placeholder="Nama varian" required>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="form-label fw-semibold" style="font-size: 0.8rem;">Barcode <small class="text-muted fw-normal">(kosongkan = auto)</small></label>
                                                                    <input name="variants[{{ $i }}][barcode]" class="form-control form-control-sm" value="{{ $v['barcode'] ?? '' }}" placeholder="Auto-generate">
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="form-label fw-semibold" style="font-size: 0.8rem;">Harga Jual</label>
                                                                    <div class="input-group input-group-sm">
                                                                        <span class="input-group-text">Rp</span>
                                                                        <input name="variants[{{ $i }}][harga]" class="form-control" type="number" min="0" value="{{ $v['harga'] ?? '' }}" placeholder="0" required>
                                                                    </div>
                                                                </div>
                                                                @if ($showRewardPoints)
                                                                    <div class="col-md-6">
                                                                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Reward Poin</label>
                                                                        <input name="variants[{{ $i }}][reward_points]" class="form-control form-control-sm" type="number" min="0" value="{{ $v['reward_points'] ?? '0' }}">
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <!-- Right Panel: FnB Settings -->
                                                        @if ($isFnB)
                                                            <div class="col-md-6 ps-3">
                                                                <h6 class="fw-semibold text-success mb-3" style="font-size: 0.85rem;"><i class="bi bi-gear-fill me-1"></i> Pengaturan FnB (Stok & Komisi)</h6>
                                                                <div class="row g-2">
                                                                    <div class="col-md-6">
                                                                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Track Stock</label>
                                                                        <select name="variants[{{ $i }}][track_stock]" class="form-select form-select-sm">
                                                                            <option value="1" {{ ($v['track_stock'] ?? '1') == '1' ? 'selected' : '' }}>Ya</option>
                                                                            <option value="0" {{ ($v['track_stock'] ?? '1') == '0' ? 'selected' : '' }}>Tidak</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">HPP Manual (Cost)</label>
                                                                        <div class="input-group input-group-sm">
                                                                            <span class="input-group-text">Rp</span>
                                                                            <input name="variants[{{ $i }}][cost_price_manual]" class="form-control" type="number" min="0" value="{{ $v['cost_price_manual'] ?? '0' }}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Tipe Komisi</label>
                                                                        <select name="variants[{{ $i }}][commission_type]" class="form-select form-select-sm">
                                                                            <option value="global" {{ ($v['commission_type'] ?? 'global') == 'global' ? 'selected' : '' }}>Global Tenant</option>
                                                                            <option value="percentage" {{ ($v['commission_type'] ?? 'global') == 'percentage' ? 'selected' : '' }}>Persentase (%)</option>
                                                                            <option value="nominal" {{ ($v['commission_type'] ?? 'global') == 'nominal' ? 'selected' : '' }}>Nominal Flat (Rp)</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Rate Komisi</label>
                                                                        <input name="variants[{{ $i }}][commission_rate]" class="form-control form-control-sm" type="number" min="0" value="{{ $v['commission_rate'] ?? '0' }}">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
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
        const showRewardPoints = {{ $showRewardPoints ? 'true' : 'false' }};

        function addVariantRow() {
            const container = document.getElementById('variantContainer');
            const i = rowIndex++;
            
            let rewardPointsField = '';
            if (showRewardPoints) {
                rewardPointsField = `
                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size: 0.8rem;">Reward Poin</label>
                    <input name="variants[${i}][reward_points]" class="form-control form-control-sm" type="number" min="0" value="0">
                </div>
                `;
            }

            const card = `
            <div class="card border-0 rounded-4 mb-3 variant-card position-relative shadow-sm" style="border: 1px solid #e2e8f0 !important;">
                <div class="card-header bg-white border-bottom-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-secondary" style="font-size: 0.9rem;">
                        <i class="bi bi-tag-fill me-1 text-primary"></i> Varian Baru
                    </span>
                    <button type="button" class="btn-close text-danger" onclick="removeRow(this)" style="font-size: 0.75rem;" title="Hapus Varian"></button>
                </div>
                <div class="card-body pt-2 pb-3">
                    <div class="row g-3">
                        <!-- Left Panel: General Info -->
                        <div class="col-md-${isFnB ? '6' : '12'} \${isFnB ? 'border-end pe-3' : ''}">
                            <h6 class="fw-semibold text-primary mb-3" style="font-size: 0.85rem;"><i class="bi bi-info-circle me-1"></i> Informasi Dasar & Harga</h6>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 0.8rem;">Nama Varian</label>
                                    <input name="variants[${i}][nama]" class="form-control form-control-sm" placeholder="Nama varian" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 0.8rem;">Barcode <small class="text-muted fw-normal">(kosongkan = auto)</small></label>
                                    <input name="variants[${i}][barcode]" class="form-control form-control-sm barcode-input" placeholder="Auto-generate">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 0.8rem;">Harga Jual</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Rp</span>
                                        <input name="variants[${i}][harga]" class="form-control" type="number" min="0" placeholder="0" required>
                                    </div>
                                </div>
                                \${rewardPointsField}
                            </div>
                        </div>
                        
                        <!-- Right Panel: FnB Settings -->
                        \${isFnB ? `
                        <div class="col-md-6 ps-3">
                            <h6 class="fw-semibold text-success mb-3" style="font-size: 0.85rem;"><i class="bi bi-gear-fill me-1"></i> Pengaturan FnB (Stok & Komisi)</h6>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 0.8rem;">Track Stock</label>
                                    <select name="variants[${i}][track_stock]" class="form-select form-select-sm">
                                        <option value="1">Ya</option>
                                        <option value="0">Tidak</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 0.8rem;">HPP Manual (Cost)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Rp</span>
                                        <input name="variants[${i}][cost_price_manual]" class="form-control" type="number" min="0" value="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 0.8rem;">Tipe Komisi</label>
                                    <select name="variants[${i}][commission_type]" class="form-select form-select-sm">
                                        <option value="global">Global Tenant</option>
                                        <option value="percentage">Persentase (%)</option>
                                        <option value="nominal">Nominal Flat (Rp)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 0.8rem;">Rate Komisi</label>
                                    <input name="variants[${i}][commission_rate]" class="form-control form-control-sm" type="number" min="0" value="0">
                                </div>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            </div>`;
            container.insertAdjacentHTML('beforeend', card);
        }

        function removeRow(btn) {
            const card = btn.closest('.variant-card');
            const container = document.getElementById('variantContainer');
            if (container.querySelectorAll('.variant-card').length <= 1) {
                Swal.fire('Perhatian', 'Minimal harus ada 1 varian.', 'warning');
                return;
            }
            card.remove();
        }

        // Tambah 1 baris kosong jika belum ada old input
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('variantContainer');
            if (container.querySelectorAll('.variant-card').length === 0) {
                addVariantRow();
            }
        });
    </script>
@endpush
