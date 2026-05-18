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

                    <form method="POST" action="{{ route('produk.update', $product->id) }}">
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
                            </div>
                            <div class="card-footer py-3 text-end">
                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            </div>
                        </div>
                    </form>

                    <div class="d-flex justify-content-end mb-3">
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                            data-bs-target="#modalAddVariant">
                            <i class="bi bi-plus-circle"></i> Tambah Varian
                        </button>
                    </div>
                    <table class="table" id="variantTable">
                        <thead>
                            <tr>
                                <th>Varian</th>
                                <th>SKU</th>
                                <th width="10%" class="text-center">Harga</th>
                                <th width="10%" class="text-center">Stok Gudang</th>
                                <th width="10%" class="text-center">Stok Toko</th>
                                <th width="10%" class="text-center">Status</th>
                                <th width="8%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        {{-- <tbody>
                            @foreach ($product->variants as $variant)
                                <tr>
                                    <td>
                                        @foreach ($variant->variantAttributes as $va)
                                            {{ $va->attribute->nama }}: {{ $va->value->nama }}
                                            ({{ $va->value->kode }})
                                            <br>
                                        @endforeach
                                    </td>
                                    <td>{{ $variant->sku }}</td>
                                    <td class="text-end">{{ number_format($variant->harga_jual) }}</td>
                                    <td class="text-end">{{ $variant->stok_warehouse }}</td>
                                    <td class="text-end">{{ $variant->stok_store }}</td>
                                    <td class="text-center">
                                        @if ($variant->is_active)
                                            <span class="badge bg-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-warning btn-sm"
                                            onclick="editVariant({{ $variant->id }})"><i
                                                class="bi bi-pencil"></i></button>
                                        <button type="button" class="btn btn-danger btn-sm"
                                            onclick="removeVariantRow(this)" data-id="{{ $variant->id }}"><i
                                                class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody> --}}
                        <tbody>

                            @foreach ($variantsByGroup as $groupName => $variants)
                                @if ($hasDivisi)
                                    <tr class="table-secondary">
                                        <td colspan="7" class="fw-bold">
                                            === DIVISI {{ strtoupper($groupName) }} ===
                                        </td>
                                    </tr>
                                @endif

                                @foreach ($variants as $variant)
                                    <tr>
                                        <td>
                                            @forelse ($variant->variantAttributes->sortBy(fn($va) => $va->attribute->urutan) as $va)
                                                {{ $va->attribute->nama }}:
                                                {{ $va->value->nama }}
                                                ({{ $va->value->kode }})
                                                <br>
                                            @empty
                                                <span class="text-muted">- Tidak ada atribut -</span>
                                            @endforelse
                                        </td>

                                        <td>{{ $variant->sku }}</td>

                                        <td class="text-end">
                                            {{ number_format($variant->harga_jual) }}
                                        </td>

                                        <td class="text-end">
                                            {{ $variant->stok_warehouse ?? 0 }}
                                        </td>

                                        <td class="text-end">
                                            {{ $variant->stok_store ?? 0 }}
                                        </td>

                                        <td class="text-center">
                                            <span class="badge {{ $variant->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $variant->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>

                                        <td class="text-center">
                                            <button type="button" class="btn btn-danger btn-sm"
                                                onclick="removeVariantRow(this)" data-id="{{ $variant->id }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach

                        </tbody>
                    </table>
                    <div class="text-end">
                        <a href="{{ route('produk.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i>
                            Kembali</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
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

                        {{-- PILIH ATTRIBUTE --}}
                        @foreach ($attributes as $attr)
                            <div class="mb-3">
                                <strong>{{ $attr->nama }}</strong><br>
                                @foreach ($attr->values as $val)
                                    <label class="me-3">
                                        <input type="checkbox" class="variant-checkbox"
                                            data-attribute="{{ $attr->id }}" data-kode="{{ $val->kode }}"
                                            data-order="{{ $attr->urutan }}" value="{{ $val->id }}">
                                        {{ $val->nama }} ({{ $val->kode }})
                                    </label>
                                @endforeach
                            </div>
                        @endforeach

                        <hr>

                        {{-- PREVIEW --}}
                        <h6 class="fw-bold mb-2">Preview Varian</h6>

                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Varian</th>
                                    <th>SKU</th>
                                    <th>Barcode</th>
                                    <th>Harga Jual</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="variantPreview">
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        Pilih kombinasi attribute
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
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
                                    if (response.success)
                                        $(button).closest('tr').remove();
                                });
                            },
                            error: function(xhr) {
                                Swal.fire(
                                    'Gagal!',
                                    xhr.responseJSON.message,
                                    xjhr.responseJSON.icon
                                );
                            }
                        });
                    }
                });
            } else {
                // Jika varian belum disimpan di database, cukup hapus barisnya
                $(button).closest('tr').remove();
            }
        }
    </script>
    <script>
        // existing combinations: array of "valueId-valueId"
        const productKode = "{{ $product->kode_produk }}";
        const existingVariants = @json($existingVariants);


        function generateCombinations(grouped) {
            let combinations = [
                []
            ];

            Object.values(grouped).forEach(values => {
                let temp = [];
                combinations.forEach(c => {
                    values.forEach(v => {
                        temp.push([...c, v]);
                    });
                });
                combinations = temp;
            });

            return combinations;
        }

        function refreshPreview() {
            let grouped = {};

            document.querySelectorAll('.variant-checkbox:checked').forEach(el => {
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

            let tbody = document.getElementById('variantPreview');
            tbody.innerHTML = '';

            if (Object.keys(grouped).length === 0) {
                tbody.innerHTML = `
            <tr>
                <td colspan="4" class="text-center text-muted">
                    Pilih kombinasi attribute
                </td>
            </tr>`;
                return;
            }

            let sortedAttributes = Object.values(grouped)
                .sort((a, b) => a.order - b.order);

            let combos = generateCombinations(sortedAttributes.map(a => a.values));

            combos.forEach((combo, i) => {
                // ===== DATA DASAR =====
                let label = combo.map(v => v.kode).join('-');
                let sku = `${productKode}-${label}`;
                let barcode = generateBarcode(productKode, label, i);

                // ===== CEK DUPLIKAT =====
                let valueIds = combo.map(v => parseInt(v.id));


                let existing = existingVariants.find(v =>
                    normalizeIds(v.attribute_value_ids) === normalizeIds(valueIds)
                );

                let harga = '';
                let hargaDisabled = '';
                let statusBadge = '';
                let aksi = `<td class="text-center align-middle">
                            <button type="button" class="btn btn-danger btn-sm"
                                onclick="this.closest('tr').remove()">
                                Hapus
                            </button>
                        </td>`;

                if (existing) {
                    harga = parseInt(existing.harga_jual);
                    sku = existing.sku;
                    barcode = existing.barcode;
                    hargaDisabled = 'disabled';
                    statusBadge = `
                        <span class="badge bg-warning text-dark ms-2">
                            Varian sudah ada
                        </span>`;
                    tbody.insertAdjacentHTML('beforeend', `
                        <tr>
                            <td>
                                ${label}
                                ${statusBadge}
                            </td>
                            <td>
                                ${sku}
                            </td>
                            <td>
                                ${barcode}
                            </td>
                            <td class="text-center">
                                ${harga.toLocaleString()}
                            </td>
                            ${aksi}
                        </tr>
                    `);

                } else {

                    tbody.insertAdjacentHTML('beforeend', `
                        <tr>
                            <td>
                                ${label}
                                ${statusBadge}
                                <input type="hidden" name="variants[${i}][values]"
                                    value="${valueIds.join(',')}">
                            </td>
                            <td>
                                ${sku}
                                <input type="hidden" name="variants[${i}][sku]" value="${sku}">
                            </td>
                            <td>
                                ${barcode}
                                <input type="hidden" name="variants[${i}][barcode]" value="${barcode}">
                            </td>
                            <td>
                                <input type="number"
                                    class="form-control form-control-sm"
                                    name="variants[${i}][harga_jual]"
                                    value="${harga}"
                                    ${hargaDisabled}
                                    required>
                            </td>
                            ${aksi}
                        </tr>
                    `);
                }
            });
        }

        function normalizeIds(ids) {
            return ids
                .map(Number)
                .sort((a, b) => a - b)
                .join('-'); // string stabil
        }

        function generateBarcode(prefix, label, index) {
            // Contoh barcode: PRM-SD-L-001
            return `${prefix}-${label}-${String(index + 1).padStart(3, '0')}`;
        }

        document.querySelectorAll('.variant-checkbox')
            .forEach(el => el.addEventListener('change', refreshPreview));
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
@endpush
