@extends('layouts.main.main')
@section('title', 'Pengaturan Produk')

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
                            <h5 class="fw-bold mb-0" style="color: #7c3aed">Pengaturan Produk</h5>
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

                    <form method="POST" action="{{ route('produk.update', $product->id) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="mb-2">
                                    <label>Kode Produk</label>
                                    <input name="kode" class="form-control"
                                        value="{{ old('kode', $product->kode_produk) }}" readonly>
                                </div>

                                <div class="mb-2">
                                    <label>Nama Produk</label>
                                    <input name="nama" class="form-control"
                                        value="{{ old('nama', $product->nama_produk) }}" required>
                                </div>

                                <div class="mb-2">
                                    <label>Deskripsi Produk</label>
                                    <textarea name="deskripsi" class="form-control" rows="3">{{ old('deskripsi', $product->deskripsi) }}</textarea>
                                </div>

                                @if ($isFnB)
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label fw-semibold">Tenant / Stelling Provider</label>
                                            <select name="tenant_id" class="form-select">
                                                <option value="">-- Tanpa Tenant (Umum) --</option>
                                                @foreach ($tenants as $t)
                                                    <option value="{{ $t->id }}" {{ old('tenant_id', $product->tenant_id) == $t->id ? 'selected' : '' }}>
                                                        {{ $t->nama_tenant }} ({{ $t->kode_tenant }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label fw-semibold">Foto Produk</label>
                                            <input type="file" name="image" class="form-control" accept="image/*">
                                            <small class="text-muted">Maksimal 2MB, JPG atau PNG</small>
                                            @if ($product->image)
                                                <div class="mt-2">
                                                    <img src="{{ asset('storage/' . $product->image) }}" alt="Foto Produk" style="max-height: 80px; border-radius: 8px;" class="border">
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="card-footer py-3 text-end">
                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            </div>
                        </div>
                    </form>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-secondary mb-0"><i class="bi bi-grid-3x3-gap-fill text-primary me-1"></i> Daftar Varian Produk</h6>
                        <button type="button" class="btn btn-primary btn-sm rounded-3 px-3" data-bs-toggle="modal"
                            data-bs-target="#modalAddVariant">
                            <i class="bi bi-plus-circle-fill me-1"></i> Tambah Varian
                        </button>
                    </div>

                    <div id="variantListContainer" class="row g-3 mb-4">
                        @foreach ($variantsByGroup as $groupName => $variants)
                            @if ($hasDivisi)
                                <div class="col-12 mt-4 mb-2">
                                    <div class="px-3 py-2 bg-light rounded-3 fw-bold text-primary" style="font-size: 0.9rem; border-left: 4px solid #7c3aed;">
                                        DIVISI: {{ strtoupper($groupName) }}
                                    </div>
                                </div>
                            @endif

                            @foreach ($variants as $variant)
                                <div class="col-md-6 col-lg-4 variant-card-container">
                                    <div class="card h-100 border-0 rounded-4 shadow-sm" style="border: 1px solid #e2e8f0 !important; background-color: #ffffff;">
                                        <!-- Card Header: Title and Status -->
                                        <div class="card-header bg-white border-bottom-0 pt-3 pb-0 d-flex justify-content-between align-items-start gap-2">
                                            <div class="text-truncate">
                                                <h6 class="fw-bold text-dark mb-0 text-truncate" title="{{ $variant->variant_label ?: '-' }}">
                                                    {{ $variant->variant_label ?: 'Varian Utama' }}
                                                </h6>
                                                <small class="text-muted font-monospace" style="font-size: 0.75rem;">SKU: {{ $variant->sku }}</small>
                                            </div>
                                            <span class="badge {{ $variant->is_active === 'Y' ? 'bg-success' : 'bg-secondary' }} rounded-pill" style="font-size: 0.7rem; padding: 4px 10px;">
                                                {{ $variant->is_active === 'Y' ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </div>

                                        <!-- Card Body: Details Grid -->
                                        <div class="card-body py-2">
                                            <div class="row g-2">
                                                <!-- Pricing and Reward -->
                                                <div class="col-6">
                                                    <small class="text-muted d-block" style="font-size: 0.7rem;">Harga Jual</small>
                                                    <span class="fw-bold text-primary" style="font-size: 0.9rem;">Rp {{ number_format($variant->harga_jual, 0, ',', '.') }}</span>
                                                </div>
                                                @if ($showRewardPoints)
                                                    <div class="col-6">
                                                        <small class="text-muted d-block" style="font-size: 0.7rem;">Reward Poin</small>
                                                        <span class="fw-bold text-warning" style="font-size: 0.9rem;"><i class="bi bi-star-fill me-1"></i>{{ number_format($variant->reward_points) }}</span>
                                                    </div>
                                                @endif
                                                
                                                <!-- Inventory -->
                                                <div class="col-6 border-top pt-2">
                                                    <small class="text-muted d-block" style="font-size: 0.7rem;">Stok Toko</small>
                                                    <span class="fw-semibold text-dark" style="font-size: 0.85rem;">
                                                        @if (!$variant->track_stock && $isFnB)
                                                            <span class="badge bg-light text-muted">Unlimited</span>
                                                        @else
                                                            {{ number_format($variant->stok_store ?? 0) }}
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="col-6 border-top pt-2">
                                                    <small class="text-muted d-block" style="font-size: 0.7rem;">Stok Gudang</small>
                                                    <span class="fw-semibold text-dark" style="font-size: 0.85rem;">{{ number_format($variant->stok_warehouse ?? 0) }}</span>
                                                </div>

                                                <!-- FnB Specific Details -->
                                                @if ($isFnB)
                                                    <div class="col-12 border-top pt-2 mt-2">
                                                        <div class="p-2 bg-light rounded-3" style="font-size: 0.75rem;">
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <span class="text-muted">Track Stock:</span>
                                                                <span class="fw-bold {{ $variant->track_stock ? 'text-success' : 'text-warning' }}">
                                                                    {{ $variant->track_stock ? 'Ya' : 'Tidak' }}
                                                                </span>
                                                            </div>
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <span class="text-muted">HPP Manual:</span>
                                                                <span class="fw-bold text-dark">Rp {{ number_format($variant->cost_price_manual, 0, ',', '.') }}</span>
                                                            </div>
                                                            <div class="d-flex justify-content-between">
                                                                <span class="text-muted">Komisi:</span>
                                                                <span class="fw-bold text-info">
                                                                    @if ($variant->commission_type === 'global')
                                                                        Global Tenant
                                                                    @elseif ($variant->commission_type === 'percentage')
                                                                        {{ number_format($variant->commission_rate) }}%
                                                                    @else
                                                                        Rp {{ number_format($variant->commission_rate, 0, ',', '.') }}
                                                                    @endif
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Card Footer: Actions -->
                                        <div class="card-footer bg-white border-top-0 pt-0 pb-3 d-flex justify-content-end gap-2">
                                            <button type="button" class="btn btn-outline-warning btn-sm rounded-3 px-3 btn-edit-variant"
                                                data-id="{{ $variant->id }}"
                                                data-name="{{ $variant->variant_name ?: $variant->variant_label }}"
                                                data-harga="{{ (int)$variant->harga_jual }}"
                                                data-reward-points="{{ (int)$variant->reward_points }}"
                                                @if ($isFnB)
                                                    data-track-stock="{{ $variant->track_stock ? 1 : 0 }}"
                                                    data-cost-price-manual="{{ (int)$variant->cost_price_manual }}"
                                                    data-commission-type="{{ $variant->commission_type }}"
                                                    data-commission-rate="{{ (int)$variant->commission_rate }}"
                                                @endif>
                                                <i class="bi bi-pencil me-1"></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm rounded-3 px-3"
                                                onclick="removeVariantRow(this)" data-id="{{ $variant->id }}">
                                                <i class="bi bi-trash me-1"></i> Hapus
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    </div>

                    <div class="text-end">
                        <a href="{{ route('produk.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i>
                            Kembali</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah Varian --}}
    <div class="modal fade" id="modalAddVariant" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Tambah Varian Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <form id="formAddVariant">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">

                        <div id="newVariantContainer" class="d-flex flex-column gap-3 mb-3" style="max-height: 60vh; overflow-y: auto;"></div>

                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addNewVariantRow()">
                            <i class="bi bi-plus-circle"></i> Tambah Varian Baru
                        </button>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" form="formAddVariant" class="btn btn-primary">
                        Simpan Varian
                    </button>
                </div>

            </div>
        </div>
     </div>

     {{-- Modal Edit Varian --}}
     <div class="modal fade" id="modalEditVariant" tabindex="-1">
         <div class="modal-dialog">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title">Edit Varian</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                 </div>
                 <form id="formEditVariant">
                     @csrf
                     @method('PUT')
                     <input type="hidden" name="variant_id" id="edit_variant_id">
                     <div class="modal-body">
                         <div class="mb-3">
                             <label class="form-label">Nama Varian</label>
                             <input type="text" name="variant_name" id="edit_variant_name" class="form-control" required>
                         </div>
                         <div class="mb-3">
                             <label class="form-label">Harga Jual</label>
                             <input type="number" name="harga_jual" id="edit_variant_harga" class="form-control" min="0" required>
                         </div>
                         @if ($showRewardPoints)
                             <div class="mb-3">
                                 <label class="form-label">Reward Poin</label>
                                 <input type="number" name="reward_points" id="edit_variant_reward_points" class="form-control" min="0" required>
                             </div>
                         @endif

                         @if ($isFnB)
                             <hr class="my-3">
                             <h6 class="fw-bold text-success mb-3" style="font-size: 0.85rem;"><i class="bi bi-gear-fill me-1"></i> Pengaturan FnB (Stok & Komisi)</h6>
                             <div class="row g-3">
                                 <div class="col-md-6 mb-3">
                                     <label class="form-label fw-semibold" style="font-size: 0.8rem;">Track Stock</label>
                                     <select name="track_stock" id="edit_variant_track_stock" class="form-select">
                                         <option value="1">Ya</option>
                                         <option value="0">Tidak</option>
                                     </select>
                                 </div>
                                 <div class="col-md-6 mb-3">
                                     <label class="form-label fw-semibold" style="font-size: 0.8rem;">HPP Manual (Cost)</label>
                                     <div class="input-group">
                                         <span class="input-group-text">Rp</span>
                                         <input type="number" name="cost_price_manual" id="edit_variant_cost_price_manual" class="form-control" min="0">
                                     </div>
                                 </div>
                                 <div class="col-md-6 mb-3">
                                     <label class="form-label fw-semibold" style="font-size: 0.8rem;">Tipe Komisi</label>
                                     <select name="commission_type" id="edit_variant_commission_type" class="form-select">
                                         <option value="global">Global Tenant</option>
                                         <option value="percentage">Persentase (%)</option>
                                         <option value="nominal">Nominal Flat (Rp)</option>
                                     </select>
                                 </div>
                                 <div class="col-md-6 mb-3">
                                     <label class="form-label fw-semibold" style="font-size: 0.8rem;">Rate Komisi</label>
                                     <input type="number" name="commission_rate" id="edit_variant_commission_rate" class="form-control" min="0">
                                 </div>
                             </div>
                         @endif
                     </div>
                     <div class="modal-footer">
                         <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                         <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                     </div>
                 </form>
             </div>
         </div>
     </div>

@endsection
@push('scripts')
    <script>
        function removeVariantRow(button) {
            // ambil data id dari tombol yang diklik dan pakai ajax untuk menghapus varian dari database
            var variantId = $(button).data('id');
            if (variantId) {
                Swal.fire({
                    title: 'Yakin menghapus varian ini?',
                    text: "Data varian akan dihapus dari database!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, hapus!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route('produk.variants.destroy', ['variant' => ':id']) }}'.replace(
                                ':id', variantId),
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            dataType: 'json',
                            beforeSend: function() {
                                Swal.fire({
                                    title: 'Menghapus...',
                                    text: 'Mohon tunggu',
                                    allowOutsideClick: false,
                                    didOpen: () => {
                                        Swal.showLoading()
                                    }
                                });
                            },
                            success: function(response) {
                                Swal.fire(
                                    'Dihapus!',
                                    response.message,
                                    response.icon
                                ).then(() => {
                                    if (response.success) {
                                        const card = $(button).closest('.variant-card-container');
                                        if (card.length) {
                                            card.remove();
                                        } else {
                                            $(button).closest('tr').remove();
                                        }
                                    }
                                });
                            },
                            error: function(xhr) {
                                Swal.fire(
                                    'Gagal!',
                                    xhr.responseJSON?.message || 'Terjadi kesalahan',
                                    'error'
                                );
                            }
                        });
                    }
                });
            } else {
                // Jika varian belum disimpan di database, cukup hapus barisnya
                const card = $(button).closest('.variant-card-container');
                if (card.length) {
                    card.remove();
                } else {
                    $(button).closest('tr').remove();
                }
            }
        }
    </script>
    <script>
        let newVariantRowIndex = 0;

        const isFnB = {{ $isFnB ? 'true' : 'false' }};
        const showRewardPoints = {{ $showRewardPoints ? 'true' : 'false' }};

        function addNewVariantRow() {
            const container = document.getElementById('newVariantContainer');
            const i = newVariantRowIndex++;
            
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
                    <button type="button" class="btn-close text-danger" onclick="this.closest('.variant-card').remove()" style="font-size: 0.75rem;" title="Hapus Varian"></button>
                </div>
                <div class="card-body pt-2 pb-3">
                    <div class="row g-3">
                        <!-- Left Panel: General Info -->
                        <div class="col-md-${isFnB ? '6' : '12'} ${isFnB ? 'border-end pe-3' : ''}">
                            <h6 class="fw-semibold text-primary mb-3" style="font-size: 0.85rem;"><i class="bi bi-info-circle me-1"></i> Informasi Dasar & Harga</h6>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 0.8rem;">Nama Varian</label>
                                    <input name="variants[${i}][nama]" class="form-control form-control-sm" placeholder="Nama varian" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 0.8rem;">Harga Jual</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Rp</span>
                                        <input name="variants[${i}][harga_jual]" class="form-control" type="number" min="0" placeholder="0" required>
                                    </div>
                                </div>
                                ${rewardPointsField}
                            </div>
                        </div>
                        
                        <!-- Right Panel: FnB Settings -->
                        ${isFnB ? `
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

        // Saat modal dibuka, reset dan tambah 1 baris kosong
        $(document).ready(function() {
            const modalAdd = document.getElementById('modalAddVariant');
            if (modalAdd) {
                modalAdd.addEventListener('show.bs.modal', function() {
                    newVariantRowIndex = 0;
                    document.getElementById('newVariantContainer').innerHTML = '';
                    addNewVariantRow();
                });
            }
        });
    </script>
    <script>
        $('#formAddVariant').on('submit', function(e) {
            e.preventDefault();

            $.ajax({
                url: "{{ route('produk.variants.store', $product->id) }}",
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                beforeSend: function() {
                    Swal.fire({
                        title: 'Menyimpan...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                },
                success: function(res) {
                    Swal.fire('Response', res.message, res.icon)
                        .then(() => {
                            if (res.success)
                                location.reload();
                        });
                },
                error: function(xhr) {
                    Swal.fire(
                        'Gagal',
                        xhr.responseJSON?.message || 'Terjadi kesalahan',
                        'error'
                    );
                }
            });
        });
    </script>
    <script>
        $(document).on('click', '.btn-edit-variant', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            var harga = $(this).data('harga');
            var rewardPoints = $(this).data('reward-points');

            $('#edit_variant_id').val(id);
            $('#edit_variant_name').val(name);
            $('#edit_variant_harga').val(harga);
            
            if (showRewardPoints) {
                $('#edit_variant_reward_points').val(rewardPoints);
            }

            if (isFnB) {
                var trackStock = $(this).data('track-stock');
                var costPrice = $(this).data('cost-price-manual');
                var commissionType = $(this).data('commission-type');
                var commissionRate = $(this).data('commission-rate');

                $('#edit_variant_track_stock').val(trackStock);
                $('#edit_variant_cost_price_manual').val(costPrice);
                $('#edit_variant_commission_type').val(commissionType);
                $('#edit_variant_commission_rate').val(commissionRate);
            }

            $('#modalEditVariant').modal('show');
        });

        $('#formEditVariant').on('submit', function(e) {
            e.preventDefault();

            $.ajax({
                url: "{{ route('produk.variants.update') }}",
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                beforeSend: function() {
                    Swal.fire({
                        title: 'Menyimpan...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                },
                success: function(res) {
                    Swal.fire('Response', res.message, 'success')
                        .then(() => {
                            if (res.success)
                                location.reload();
                        });
                },
                error: function(xhr) {
                    Swal.fire(
                        'Gagal',
                        xhr.responseJSON?.message || 'Terjadi kesalahan',
                        'error'
                    );
                }
            });
        });
    </script>
@endpush
