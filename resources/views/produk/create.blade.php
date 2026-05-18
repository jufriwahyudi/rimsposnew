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

                    <form method="POST" action="{{ route('produk.store') }}">
                        @csrf

                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="mb-2">
                                    <label>Kode Produk</label>
                                    <input name="kode" class="form-control" value="{{ old('kode') }}" required>
                                </div>

                                <div class="mb-2">
                                    <label>Nama Produk</label>
                                    <input name="nama" class="form-control" value="{{ old('nama') }}" required>
                                </div>

                                <div class="mb-2">
                                    <label>Deskripsi Produk</label>
                                    <textarea name="deskripsi" class="form-control" rows="3">{{ old('deskripsi') }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-3">
                            <div class="card-header">Pilih Varian</div>
                            <div class="card-body">

                                @foreach ($attributes as $attr)
                                    <div class="mb-3">
                                        <strong>{{ $attr->nama }}</strong><br>

                                        @foreach ($attr->values as $val)
                                            <label class="me-3">
                                                <input type="checkbox" class="variant-checkbox"
                                                    data-attribute="{{ $attr->id }}" data-order="{{ $attr->urutan }}"
                                                    data-kode="{{ $val->kode }}" value="{{ $val->id }}">
                                                {{ $val->nama }}
                                            </label>
                                        @endforeach
                                    </div>
                                @endforeach

                            </div>
                            <div class="card-footer">
                                <button type="button" class="btn btn-primary" onclick="generateVariants()">
                                    <i class="bi bi-arrow-clockwise"></i> Generate Varian
                                </button>
                            </div>
                        </div>
                        <table class="table" id="variantTable">
                            <thead>
                                <tr>
                                    <th>Varian</th>
                                    <th>Barcode</th>
                                    <th>Harga</th>
                                    <th width="8%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <a href="{{ route('produk.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i>
                            Kembali</a>
                        <button class="btn btn-primary"><i class="bi bi-save"></i> Simpan Produk</button>
                    </form>

                </div>
            </div>
        </div>
    </div>

@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            let oldVariants = @json(old('variants'));

            if (!oldVariants) return;

            // Ambil semua value id dari old variants
            let selectedValues = [];

            Object.values(oldVariants).forEach(v => {
                if (v.values) {
                    v.values.split(',').forEach(id => {
                        selectedValues.push(id);
                    });
                }
            });

            // Centang checkbox sesuai old values
            document.querySelectorAll('.variant-checkbox').forEach(cb => {
                if (selectedValues.includes(cb.value)) {
                    cb.checked = true;
                }
            });

            // Generate ulang tabel
            generateVariants();

            // Restore barcode & harga
            oldVariants.forEach((v, i) => {
                if (document.querySelector(`[name="variants[${i}][barcode]"]`)) {
                    document.querySelector(`[name="variants[${i}][barcode]"]`).value = v.barcode ?? '';
                }
                if (document.querySelector(`[name="variants[${i}][harga]"]`)) {
                    document.querySelector(`[name="variants[${i}][harga]"]`).value = v.harga ?? '';
                }
            });
        });

        function generateBarcode(prefix, label, index) {
            // Contoh barcode: PRM-SD-L-001
            return `${prefix}-${label}-${String(index + 1).padStart(3, '0')}`;
        }

        function generateVariants() {
            let grouped = {};

            document.querySelectorAll('.variant-checkbox:checked')
                .forEach(el => {
                    let attr = el.dataset.attribute;
                    let order = parseInt(el.dataset.order);

                    if (!grouped[attr]) {
                        grouped[attr] = {
                            order: order,
                            values: []
                        };
                    }

                    grouped[attr].values.push({
                        id: el.value,
                        kode: el.dataset.kode
                    });
                });
            if (Object.keys(grouped).length === 0) {
                alert('Pilih minimal 1 varian');
                return;
            }
            let sortedAttributes = Object.values(grouped)
                .sort((a, b) => a.order - b.order);
            let combinations = [
                []
            ];

            sortedAttributes.forEach(attr => {
                let temp = [];

                combinations.forEach(c => {
                    attr.values.forEach(v => {
                        temp.push([...c, v]);
                    });
                });

                combinations = temp;
            });

            let tbody = document.querySelector('#variantTable tbody');
            tbody.innerHTML = '';

            let productKode = document.querySelector('[name="kode"]').value || 'PRD';

            combinations.forEach((combo, i) => {
                let label = combo.map(v => v.kode).join('-');
                let ids = combo.map(v => v.id).join(',');
                let barcode = generateBarcode(productKode, label, i);

                tbody.innerHTML += `
                    <tr>
                        <td class="align-middle">
                            ${label}
                            <input type="hidden" name="variants[${i}][values]" value="${ids}">
                        </td>
                        <td>
                            <input name="variants[${i}][barcode]" class="form-control" value="${barcode}">
                        </td>
                        <td>
                            <input name="variants[${i}][harga]" class="form-control">
                        </td>
                        <td class="text-center align-middle">
                            <button type="button" class="btn btn-danger btn-sm"
                                onclick="this.closest('tr').remove()">
                                Hapus
                            </button>
                        </td>
                    </tr>`;
            });
        }
    </script>
@endpush
