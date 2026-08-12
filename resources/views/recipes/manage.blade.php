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
        <div class="col-sm-12 col-md-10 offset-md-1">
            <div class="card rounded-4 p-2">
                <div class="card-header d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-start">
                        <img src="{{ asset('assets/images/alazca_logo.png') }}" alt="Logo" style="width:35px;height:35px;" class="me-2 mt-1">
                        <div>
                            <h5 class="fw-bold mb-0" style="color:#7c3aed">Konfigurasi Resep: {{ $product->nama_produk }}</h5>
                            <small class="text-muted">Kode Produk: {{ $product->kode_produk }} | Tipe Sekarang: 
                                <span class="badge bg-light text-dark border">{{ $product->product_type }}</span>
                            </small>
                        </div>
                    </div>
                    <a href="{{ route('recipes.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
                </div>
                <div class="card-body">
                    <form action="{{ route('recipes.save', $product->id) }}" method="POST">
                        @csrf
                        <div class="alert alert-info border-0 bg-light-info py-2 rounded-3 mb-4">
                            <span class="material-icons-outlined" style="vertical-align:middle;font-size:18px">info</span>
                            <small>Tentukan bahan baku mentah dan takaran/jumlah (dalam Satuan Dasar) yang dikonsumsi per 1 porsi menu jualan ini.</small>
                        </div>

                        <table class="table table-bordered align-middle" id="recipes-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Pilih Bahan Baku <span class="text-danger">*</span></th>
                                    <th width="200">Jumlah Terpakai (Base Unit) <span class="text-danger">*</span></th>
                                    <th width="150">Satuan</th>
                                    <th width="80" class="text-center">Hapus</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($currentRecipes as $index => $row)
                                    <tr class="recipe-row">
                                        <td>
                                            <select class="form-select select-ingredient" name="recipes[{{ $index }}][ingredient_id]" required onchange="updateUnitLabel(this)">
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
                                            <input type="number" step="0.0001" class="form-control" name="recipes[{{ $index }}][quantity_required]" value="{{ (float)$row->quantity_required }}" required>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border unit-label">{{ $row->ingredient?->baseUnit?->symbol }}</span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-danger btn-remove-row" onclick="removeRow(this)">
                                                <i class="material-icons-outlined" style="font-size:16px">delete</i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="empty-row-placeholder">
                                        <td colspan="4" class="text-center text-muted py-3">Resep belum dikonfigurasi. Klik tombol di bawah untuk menambah bahan baku.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <div class="mb-4">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btn-add-row">
                                <i class="material-icons-outlined" style="font-size:16px;vertical-align:middle">add</i> Tambah Baris Bahan
                            </button>
                        </div>

                        <div class="border-top pt-3 d-flex justify-content-end">
                            <button type="submit" class="btn btn-success px-4">Simpan Konfigurasi Resep</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Template Row for Javascript --}}
    <table class="d-none">
        <tr id="recipe-row-template">
            <td>
                <select class="form-select select-ingredient" name="recipes[__INDEX__][ingredient_id]" required onchange="updateUnitLabel(this)">
                    <option value="" disabled selected>-- Pilih Bahan Baku --</option>
                    @foreach ($ingredients as $ing)
                        <option value="{{ $ing->id }}" data-unit="{{ $ing->baseUnit?->symbol }}">
                            {{ $ing->name }} (SKU: {{ $ing->sku }})
                        </option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="number" step="0.0001" class="form-control" name="recipes[__INDEX__][quantity_required]" placeholder="0.0000" required>
            </td>
            <td>
                <span class="badge bg-light text-dark border unit-label">-</span>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger btn-remove-row" onclick="removeRow(this)">
                    <i class="material-icons-outlined" style="font-size:16px">delete</i>
                </button>
            </td>
        </tr>
    </table>

    <script>
        let rowIndex = {{ count($currentRecipes) }};

        document.getElementById('btn-add-row').addEventListener('click', function() {
            // Remove empty placeholder if it exists
            const placeholder = document.getElementById('empty-row-placeholder');
            if (placeholder) {
                placeholder.remove();
            }

            const template = document.getElementById('recipe-row-template').cloneNode(true);
            template.removeAttribute('id');
            template.classList.add('recipe-row');

            // Replace placeholder index
            let html = template.innerHTML;
            html = html.replace(/__INDEX__/g, rowIndex);
            template.innerHTML = html;

            document.querySelector('#recipes-table tbody').appendChild(template);
            rowIndex++;
        });

        function removeRow(button) {
            button.closest('.recipe-row').remove();
            
            // Show placeholder if empty
            const rows = document.querySelectorAll('#recipes-table tbody .recipe-row');
            if (rows.length === 0) {
                const tbody = document.querySelector('#recipes-table tbody');
                tbody.innerHTML = `
                    <tr id="empty-row-placeholder">
                        <td colspan="4" class="text-center text-muted py-3">Resep belum dikonfigurasi. Klik tombol di bawah untuk menambah bahan baku.</td>
                    </tr>
                `;
            }
        }

        function updateUnitLabel(select) {
            const selectedOption = select.options[select.selectedIndex];
            const unit = selectedOption.getAttribute('data-unit');
            const row = select.closest('.recipe-row');
            row.querySelector('.unit-label').innerText = unit || '-';
        }
    </script>
@endsection
