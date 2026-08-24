@extends('layouts.main.main')
@section('title', 'Atur Resep - ' . $product->nama_produk)

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Stok</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('recipes.index') }}">Resep Menu</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Atur Resep</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-11 mx-auto">
            <div class="card rounded-4 p-2 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-start">
                        <img src="{{ asset('assets/images/alazca_logo.png') }}" alt="Logo" style="width:35px;height:35px;" class="me-2 mt-1">
                        <div>
                            <h5 class="fw-bold mb-0" style="color:#7c3aed">Konfigurasi Resep: {{ $product->nama_produk }}</h5>
                            <small class="text-muted">Kode Produk: {{ $product->kode_produk }} | Total Varian: {{ $variants->count() }} Varian</small>
                        </div>
                    </div>
                    <a href="{{ route('recipes.index') }}" class="btn btn-secondary btn-sm rounded-3"><i class="bi bi-arrow-left"></i> Kembali</a>
                </div>

                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger rounded-3">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('recipes.save', $product->id) }}" method="POST" id="form-recipes">
                        @csrf

                        <!-- Navigation Tabs -->
                        <ul class="nav nav-pills mb-4 bg-light p-2 rounded-4 gap-1" id="recipeTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active rounded-3 px-3 py-2 fw-semibold" id="tab-default" data-bs-toggle="pill" data-bs-target="#content-default" type="button" role="tab">
                                    <i class="bi bi-box-seam me-1"></i> Resep Default (Produk)
                                    <span class="badge bg-primary ms-1" id="badge-count-default">{{ count($defaultRecipes) }}</span>
                                </button>
                            </li>
                            @foreach ($variants as $variant)
                                @php
                                    $hasCustom = $variant->recipes->isNotEmpty();
                                @endphp
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link rounded-3 px-3 py-2 fw-semibold" id="tab-var-{{ $variant->id }}" data-bs-toggle="pill" data-bs-target="#content-var-{{ $variant->id }}" type="button" role="tab">
                                        <i class="bi bi-diagram-2 me-1"></i> {{ $variant->variant_label ?: 'Varian #' . $variant->id }}
                                        <span class="badge {{ $hasCustom ? 'bg-success' : 'bg-secondary' }} ms-1" id="badge-count-var-{{ $variant->id }}">
                                            {{ $hasCustom ? count($variant->recipes) . ' Khusus' : 'Default' }}
                                        </span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>

                        <!-- Tab Contents -->
                        <div class="tab-content" id="recipeTabsContent">

                            <!-- 1. TAB RESEP DEFAULT (PRODUK) -->
                            <div class="tab-pane fade show active" id="content-default" role="tabpanel">
                                <div class="alert alert-info border-0 bg-light-info py-2 rounded-3 mb-3 d-flex align-items-center">
                                    <i class="bi bi-info-circle-fill fs-5 text-info me-2"></i>
                                    <div>
                                        <strong>Resep Default Level Produk:</strong> Komposisi bahan baku di bawah ini otomatis digunakan untuk semua varian, kecuali jika varian tersebut diatur dengan resep khusus.
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle" id="table-default">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Pilih Bahan Baku <span class="text-danger">*</span></th>
                                                <th width="220">Takaran Terpakai (Base Unit) <span class="text-danger">*</span></th>
                                                <th width="150">Satuan</th>
                                                <th width="70" class="text-center">Hapus</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbody-default">
                                            @forelse ($defaultRecipes as $index => $row)
                                                <tr class="recipe-row">
                                                    <td>
                                                        <select class="form-select select-ingredient" name="default_recipes[{{ $index }}][ingredient_id]" required onchange="updateUnitLabel(this)">
                                                            <option value="" disabled>-- Pilih Bahan Baku --</option>
                                                            @foreach ($ingredients as $ing)
                                                                <option value="{{ $ing->id }}" 
                                                                    data-unit="{{ $ing->baseUnit?->symbol }}"
                                                                    {{ $row->ingredient_id === $ing->id ? 'selected' : '' }}>
                                                                    {{ $ing->name }} (SKU: {{ $ing->sku }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.0001" class="form-control" name="default_recipes[{{ $index }}][quantity_required]" value="{{ (float)$row->quantity_required }}" required>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border unit-label">{{ $row->ingredient?->baseUnit?->symbol }}</span>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRecipeRow(this, 'default')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr class="empty-placeholder">
                                                    <td colspan="4" class="text-center text-muted py-3">Resep default belum dikonfigurasi. Klik tombol tambah bahan di bawah.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mb-3">
                                    <button type="button" class="btn btn-outline-primary btn-sm rounded-3" onclick="addRecipeRow('default')">
                                        <i class="bi bi-plus-circle me-1"></i> Tambah Baris Bahan Default
                                    </button>
                                </div>
                            </div>

                            <!-- 2. TAB PER VARIAN -->
                            @foreach ($variants as $variant)
                                @php
                                    $hasCustom = $variant->recipes->isNotEmpty();
                                    $varRecipes = $variant->recipes;
                                @endphp
                                <div class="tab-pane fade" id="content-var-{{ $variant->id }}" role="tabpanel">
                                    <div class="card border rounded-4 mb-3 p-3 bg-light">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                            <div class="form-check form-switch fs-6 mb-0">
                                                <input class="form-check-input switch-custom-recipe" type="checkbox" role="switch" 
                                                    id="switch-var-{{ $variant->id }}" 
                                                    name="variants[{{ $variant->id }}][has_custom_recipe]" 
                                                    value="1" 
                                                    {{ $hasCustom ? 'checked' : '' }}
                                                    onchange="toggleVariantCustomRecipe({{ $variant->id }})">
                                                <label class="form-check-label fw-bold text-dark" for="switch-var-{{ $variant->id }}">
                                                    Gunakan Resep Khusus untuk Varian: {{ $variant->variant_label ?: 'Varian #' . $variant->id }}
                                                </label>
                                            </div>
                                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-3" onclick="copyDefaultToVariant({{ $variant->id }})">
                                                <i class="bi bi-copy me-1"></i> Salin Resep dari Default
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Default Fallback Banner -->
                                    <div id="fallback-banner-{{ $variant->id }}" class="alert alert-secondary border-0 bg-light-secondary rounded-3 py-3 mb-3 {{ $hasCustom ? 'd-none' : '' }}">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-arrow-return-right fs-4 text-secondary me-3"></i>
                                            <div>
                                                <strong>Menggunakan Resep Default Produk:</strong> Varian ini tidak memiliki resep khusus dan secara otomatis akan memotong bahan baku sesuai resep default produk.
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Variant Custom Table Container -->
                                    <div id="custom-table-container-{{ $variant->id }}" class="{{ $hasCustom ? '' : 'd-none' }}">
                                        <div class="table-responsive">
                                            <table class="table table-bordered align-middle" id="table-var-{{ $variant->id }}">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Pilih Bahan Baku <span class="text-danger">*</span></th>
                                                        <th width="220">Takaran Terpakai (Base Unit) <span class="text-danger">*</span></th>
                                                        <th width="150">Satuan</th>
                                                        <th width="70" class="text-center">Hapus</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbody-var-{{ $variant->id }}">
                                                    @forelse ($varRecipes as $index => $row)
                                                        <tr class="recipe-row">
                                                            <td>
                                                                <select class="form-select select-ingredient" name="variants[{{ $variant->id }}][recipes][{{ $index }}][ingredient_id]" required onchange="updateUnitLabel(this)">
                                                                    <option value="" disabled>-- Pilih Bahan Baku --</option>
                                                                    @foreach ($ingredients as $ing)
                                                                        <option value="{{ $ing->id }}" 
                                                                            data-unit="{{ $ing->baseUnit?->symbol }}"
                                                                            {{ $row->ingredient_id === $ing->id ? 'selected' : '' }}>
                                                                            {{ $ing->name }} (SKU: {{ $ing->sku }})
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <input type="number" step="0.0001" class="form-control" name="variants[{{ $variant->id }}][recipes][{{ $index }}][quantity_required]" value="{{ (float)$row->quantity_required }}" required>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-light text-dark border unit-label">{{ $row->ingredient?->baseUnit?->symbol }}</span>
                                                            </td>
                                                            <td class="text-center">
                                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRecipeRow(this, 'var-{{ $variant->id }}')">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr class="empty-placeholder">
                                                            <td colspan="4" class="text-center text-muted py-3">Resep khusus varian belum diisi. Klik tambah bahan atau salin dari default.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="mb-3">
                                            <button type="button" class="btn btn-outline-success btn-sm rounded-3" onclick="addRecipeRow('var-{{ $variant->id }}')">
                                                <i class="bi bi-plus-circle me-1"></i> Tambah Baris Bahan Varian
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                        </div>

                        <div class="border-top pt-3 mt-4 d-flex justify-content-end gap-2">
                            <a href="{{ route('recipes.index') }}" class="btn btn-light rounded-3 px-4">Batal</a>
                            <button type="submit" class="btn btn-primary rounded-3 px-4">
                                <i class="bi bi-save me-1"></i> Simpan Semua Konfigurasi Resep
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Template for JavaScript Row Creation -->
    <table class="d-none">
        <tr id="row-template">
            <td>
                <select class="form-select select-ingredient" name="__NAME_PREFIX__[ingredient_id]" required onchange="updateUnitLabel(this)">
                    <option value="" disabled selected>-- Pilih Bahan Baku --</option>
                    @foreach ($ingredients as $ing)
                        <option value="{{ $ing->id }}" data-unit="{{ $ing->baseUnit?->symbol }}">
                            {{ $ing->name }} (SKU: {{ $ing->sku }})
                        </option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="number" step="0.0001" class="form-control" name="__NAME_PREFIX__[quantity_required]" placeholder="0.0000" required>
            </td>
            <td>
                <span class="badge bg-light text-dark border unit-label">-</span>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRecipeRow(this, '__SECTION__')">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    </table>
@endsection

@push('scripts')
<script>
    let sectionCounters = {
        'default': {{ count($defaultRecipes) }},
        @foreach ($variants as $variant)
            'var-{{ $variant->id }}': {{ count($variant->recipes) }},
        @endforeach
    };

    function addRecipeRow(sectionKey) {
        const tbody = document.getElementById(`tbody-${sectionKey}`);
        const placeholder = tbody.querySelector('.empty-placeholder');
        if (placeholder) {
            placeholder.remove();
        }

        const idx = sectionCounters[sectionKey] || 0;
        let namePrefix = '';
        if (sectionKey === 'default') {
            namePrefix = `default_recipes[${idx}]`;
        } else {
            const variantId = sectionKey.replace('var-', '');
            namePrefix = `variants[${variantId}][recipes][${idx}]`;
        }

        const template = document.getElementById('row-template').cloneNode(true);
        template.removeAttribute('id');
        template.classList.add('recipe-row');

        let html = template.innerHTML;
        html = html.replace(/__NAME_PREFIX__/g, namePrefix);
        html = html.replace(/__SECTION__/g, sectionKey);
        template.innerHTML = html;

        tbody.appendChild(template);
        sectionCounters[sectionKey] = idx + 1;
        updateBadgeCount(sectionKey);
    }

    function removeRecipeRow(btn, sectionKey) {
        const row = btn.closest('.recipe-row');
        const tbody = row.closest('tbody');
        row.remove();

        const remainingRows = tbody.querySelectorAll('.recipe-row');
        if (remainingRows.length === 0) {
            tbody.innerHTML = `
                <tr class="empty-placeholder">
                    <td colspan="4" class="text-center text-muted py-3">Resep belum dikonfigurasi. Klik tombol tambah bahan di bawah.</td>
                </tr>
            `;
        }
        updateBadgeCount(sectionKey);
    }

    function updateUnitLabel(select) {
        const selectedOption = select.options[select.selectedIndex];
        const unit = selectedOption ? selectedOption.getAttribute('data-unit') : '-';
        const row = select.closest('.recipe-row');
        row.querySelector('.unit-label').innerText = unit || '-';
    }

    function toggleVariantCustomRecipe(variantId) {
        const checkbox = document.getElementById(`switch-var-${variantId}`);
        const isCustom = checkbox.checked;
        const banner = document.getElementById(`fallback-banner-${variantId}`);
        const container = document.getElementById(`custom-table-container-${variantId}`);

        if (isCustom) {
            banner.classList.add('d-none');
            container.classList.remove('d-none');

            // If empty, auto-add 1 row or copy default
            const tbody = document.getElementById(`tbody-var-${variantId}`);
            if (!tbody.querySelector('.recipe-row')) {
                addRecipeRow(`var-${variantId}`);
            }
        } else {
            banner.classList.remove('d-none');
            container.classList.add('d-none');
        }
        updateBadgeCount(`var-${variantId}`);
    }

    function copyDefaultToVariant(variantId) {
        const defaultRows = document.querySelectorAll('#tbody-default .recipe-row');
        if (defaultRows.length === 0) {
            Swal.fire('Perhatian', 'Resep default masih kosong. Silakan isi resep default terlebih dahulu.', 'warning');
            return;
        }

        const checkbox = document.getElementById(`switch-var-${variantId}`);
        checkbox.checked = true;
        toggleVariantCustomRecipe(variantId);

        const tbody = document.getElementById(`tbody-var-${variantId}`);
        tbody.innerHTML = '';
        sectionCounters[`var-${variantId}`] = 0;

        defaultRows.forEach((row) => {
            const ingId = row.querySelector('.select-ingredient').value;
            const qty = row.querySelector('input[type="number"]').value;

            addRecipeRow(`var-${variantId}`);

            const newlyAddedRows = tbody.querySelectorAll('.recipe-row');
            const lastRow = newlyAddedRows[newlyAddedRows.length - 1];

            const select = lastRow.querySelector('.select-ingredient');
            select.value = ingId;
            updateUnitLabel(select);

            lastRow.querySelector('input[type="number"]').value = qty;
        });

        Swal.fire({
            icon: 'success',
            title: 'Berhasil Disalin',
            text: 'Resep default telah disalin ke varian ini. Anda dapat menyesuaikan takaran sesuai kebutuhan.',
            timer: 1500,
            showConfirmButton: false
        });
    }

    function updateBadgeCount(sectionKey) {
        if (sectionKey === 'default') {
            const count = document.querySelectorAll('#tbody-default .recipe-row').length;
            document.getElementById('badge-count-default').innerText = count;
        } else {
            const variantId = sectionKey.replace('var-', '');
            const checkbox = document.getElementById(`switch-var-${variantId}`);
            const badge = document.getElementById(`badge-count-var-${variantId}`);
            if (checkbox && checkbox.checked) {
                const count = document.querySelectorAll(`#tbody-var-${variantId} .recipe-row`).length;
                badge.className = 'badge bg-success ms-1';
                badge.innerText = `${count} Khusus`;
            } else {
                badge.className = 'badge bg-secondary ms-1';
                badge.innerText = 'Default';
            }
        }
    }
</script>
@endpush
