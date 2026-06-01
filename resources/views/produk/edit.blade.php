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
                                @if ($isFnB)
                                    <th width="10%" class="text-center">Track Stock</th>
                                    <th width="10%" class="text-center">HPP Manual</th>
                                    <th width="15%" class="text-center">Komisi</th>
                                @endif
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
                                        <td colspan="{{ $isFnB ? 10 : 7 }}" class="fw-bold">
                                            === DIVISI {{ strtoupper($groupName) }} ===
                                        </td>
                                    </tr>
                                @endif

                                @foreach ($variants as $variant)
                                    <tr>
                                        <td>
                                            {{ $variant->variant_label ?: '-' }}
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

                                        @if ($isFnB)
                                            <td class="text-center">
                                                <span class="badge {{ $variant->track_stock ? 'bg-success' : 'bg-warning' }}">
                                                    {{ $variant->track_stock ? 'Ya' : 'Tidak' }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                {{ number_format($variant->cost_price_manual) }}
                                            </td>
                                            <td class="text-center">
                                                @if ($variant->commission_type === 'global')
                                                    <span class="badge bg-info">Global Tenant</span>
                                                @elseif ($variant->commission_type === 'percentage')
                                                    <span class="badge bg-primary">{{ number_format($variant->commission_rate) }}%</span>
                                                @else
                                                    <span class="badge bg-success">Rp {{ number_format($variant->commission_rate) }}</span>
                                                @endif
                                            </td>
                                        @endif

                                        <td class="text-center">
                                            <span class="badge {{ $variant->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $variant->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>

                                        <td class="text-center">
                                            <button type="button" class="btn btn-warning btn-sm me-1 btn-edit-variant"
                                                data-id="{{ $variant->id }}"
                                                data-name="{{ $variant->variant_name ?: $variant->variant_label }}"
                                                data-harga="{{ (int)$variant->harga_jual }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
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
                        <input type="hidden" name="product_id" value="{{ $product->id }}">

                        <table class="table table-sm table-bordered" id="newVariantTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Varian</th>
                                    <th width="20%">Harga Jual</th>
                                    @if ($isFnB)
                                        <th width="12%">Track Stock</th>
                                        <th width="15%">Cost (Manual)</th>
                                        <th width="15%">Tipe Komisi</th>
                                        <th width="15%">Rate Komisi</th>
                                    @endif
                                    <th width="8%" class="text-center">Hapus</th>
                                </tr>
                            </thead>
                            <tbody id="newVariantBody"></tbody>
                        </table>

                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addNewVariantRow()">
                            <i class="bi bi-plus-circle"></i> Tambah Baris
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
        let newVariantRowIndex = 0;

        const isFnB = {{ $isFnB ? 'true' : 'false' }};

        function addNewVariantRow() {
            const tbody = document.getElementById('newVariantBody');
            const i = newVariantRowIndex++;
            
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
                        <input name="variants[${i}][nama]" class="form-control form-control-sm"
                            placeholder="Nama varian" required>
                    </td>
                    <td>
                        <input name="variants[${i}][harga_jual]" class="form-control form-control-sm"
                            type="number" min="0" placeholder="0">
                    </td>
                    ${fnbColumns}
                    <td class="text-center align-middle">
                        <button type="button" class="btn btn-danger btn-sm"
                            onclick="this.closest('tr').remove()">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>`;
            tbody.insertAdjacentHTML('beforeend', row);
        }

        // Saat modal dibuka, reset dan tambah 1 baris kosong
        document.getElementById('modalAddVariant').addEventListener('show.bs.modal', function() {
            newVariantRowIndex = 0;
            document.getElementById('newVariantBody').innerHTML = '';
            addNewVariantRow();
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

            $('#edit_variant_id').val(id);
            $('#edit_variant_name').val(name);
            $('#edit_variant_harga').val(harga);

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
