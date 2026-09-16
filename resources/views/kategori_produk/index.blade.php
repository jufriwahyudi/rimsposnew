@extends('layouts.main.main')
@section('title', 'Kategori Produk')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Master Data</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('produk.index') }}">Produk</a></li>
                    <li class="breadcrumb-item active">Kategori Produk</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card rounded-4 p-2">
                <div class="card-header d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-start">
                        <img src="{{ asset('assets/images/alazca_logo.png') }}" alt="Logo"
                            style="width:35px;height:35px;" class="me-2 mt-1">
                        <div>
                            <h5 class="fw-bold mb-0" style="color:#2563eb">Kategori Produk</h5>
                            <small class="text-muted">{{ session('store_name') }} • Kelola kategori menu/produk POS</small>
                        </div>
                    </div>
                    <div>
                        <a href="{{ route('produk.index') }}" class="btn btn-outline-secondary btn-sm me-1">
                            <i class="material-icons-outlined" style="font-size:16px;vertical-align:middle">arrow_back</i> Kembali ke Produk
                        </a>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalKategori">
                            <i class="material-icons-outlined" style="font-size:16px;vertical-align:middle">add</i> Tambah Kategori
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <table class="table table-bordered table-hover align-middle" id="tbl-kategori">
                        <thead class="table-light">
                            <tr>
                                <th width="50" class="text-center">#</th>
                                <th>Nama Kategori</th>
                                <th width="200">Target Printer Produksi</th>
                                <th width="100" class="text-center">Urutan</th>
                                <th width="120" class="text-center">Jumlah Produk</th>
                                <th width="100" class="text-center">Status</th>
                                <th class="text-center" width="120">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $i => $cat)
                                <tr>
                                    <td class="text-center">{{ $i + 1 }}</td>
                                    <td>
                                         <span class="fw-semibold text-dark">{{ $cat->name }}</span>
                                     </td>
                                     <td>
                                         @if($cat->printer)
                                             <span class="badge bg-primary-subtle text-primary border px-2 py-1">
                                                 <i class="material-icons-outlined" style="font-size:13px;vertical-align:middle">print</i> {{ $cat->printer->name }}
                                             </span>
                                         @elseif($cat->station)
                                             <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                                 {{ ucfirst($cat->station) }}
                                             </span>
                                         @else
                                             <span class="text-muted small">-- Tidak dicetak --</span>
                                         @endif
                                     </td>
                                     <td class="text-center">
                                         <span class="badge bg-light text-dark border">{{ $cat->sort_order }}</span>
                                     </td>
                                     <td class="text-center">
                                         <span class="badge bg-info-subtle text-info border px-2 py-1">
                                             {{ $cat->products_count }} Produk
                                         </span>
                                     </td>
                                    <td class="text-center">
                                        @if ($cat->is_active)
                                            <span class="badge bg-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary">Non-aktif</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1">
                                            <button class="btn btn-sm btn-info text-white btn-move-kat" 
                                                data-id="{{ $cat->id }}"
                                                data-name="{{ $cat->name }}"
                                                data-count="{{ $cat->products_count }}"
                                                title="Pindahkan Produk ke Kategori Lain">
                                                <i class="material-icons-outlined" style="font-size:15px">swap_horiz</i>
                                            </button>
                                            <button class="btn btn-sm btn-warning btn-edit-kat" 
                                                data-id="{{ $cat->id }}"
                                                data-name="{{ $cat->name }}" 
                                                data-printer-id="{{ $cat->printer_id ?? ($printers->firstWhere('code', $cat->station)?->id ?? '') }}"
                                                data-sort="{{ $cat->sort_order }}"
                                                data-active="{{ $cat->is_active ? '1' : '0' }}"
                                                title="Edit Kategori">
                                                <i class="material-icons-outlined" style="font-size:15px">edit</i>
                                            </button>
                                            <button class="btn btn-sm btn-danger btn-delete-kat" 
                                                data-id="{{ $cat->id }}"
                                                data-name="{{ $cat->name }}"
                                                title="Hapus Kategori">
                                                <i class="material-icons-outlined" style="font-size:15px">delete</i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Belum ada kategori produk. Klik <strong>Tambah Kategori</strong> untuk membuat kategori baru.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah/Edit Kategori --}}
    <div class="modal fade" id="modalKategori" tabindex="-1">
        <div class="modal-dialog">
            <form id="formKategori">
                @csrf
                <input type="hidden" id="kat_id" value="">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalKategoriTitle">Tambah Kategori Produk</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="kat_name"
                                placeholder="contoh: Makanan Utama, Minuman, Snack, Paket Hemat" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Target Printer Produksi</label>
                            <select class="form-select" id="kat_printer_id">
                                <option value="">-- Tidak Dicetak ke Produksi --</option>
                                @foreach($printers as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }} ({{ strtoupper($p->connection_type) }})</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Item pada kategori ini akan dicetak ke printer stasiun yang dipilih.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Urutan Tampilan</label>
                            <input type="number" class="form-control" id="kat_sort" value="0" min="0" placeholder="0">
                            <small class="text-muted">Angka lebih kecil akan ditampilkan lebih dulu di POS.</small>
                        </div>
                        <div class="mb-3 d-none" id="wrap_is_active">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="kat_is_active" checked>
                                <label class="form-check-label" for="kat_is_active">Kategori aktif</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSimpanKat">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Pindah Produk Antar Kategori --}}
    <div class="modal fade" id="modalPindahProduk" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <form id="formPindahProduk">
                @csrf
                <input type="hidden" id="pindah_from_category_id" value="">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="material-icons-outlined text-info align-middle me-1">swap_horiz</i> Pindahkan Produk ke Kategori Lain</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info border-0 bg-info-subtle p-3 rounded-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="small text-muted">Kategori Asal:</div>
                                    <h6 class="fw-bold mb-0 text-dark" id="pindah_from_category_name">-</h6>
                                </div>
                                <span class="badge bg-primary px-3 py-2" id="pindah_source_count_badge">0 Produk</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Pilih Kategori Tujuan <span class="text-danger">*</span></label>
                            <select class="form-select" id="pindah_target_category_id" required>
                                <option value="">-- Pilih Kategori Tujuan --</option>
                                @foreach($categories as $c)
                                    <option value="{{ $c->id }}" data-name="{{ $c->name }}">{{ $c->name }} ({{ $c->products_count }} produk)</option>
                                @endforeach
                            </select>
                            <div class="form-text">Produk akan dipindahkan ke kategori yang dipilih di atas.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Opsi Pemindahan:</label>
                            <div class="d-flex gap-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="move_option" id="opt_move_all" value="all" checked>
                                    <label class="form-check-label fw-semibold" for="opt_move_all">
                                        Pindahkan Semua Produk dalam Kategori ini
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="move_option" id="opt_move_selected" value="selected">
                                    <label class="form-check-label fw-semibold" for="opt_move_selected">
                                        Pilih Produk Tertentu
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Loading & List of Products -->
                        <div id="pindah-loading" class="text-center py-4 d-none">
                            <div class="spinner-border spinner-border-sm text-primary"></div> Memuat daftar produk...
                        </div>

                        <div id="pindah-empty-products" class="alert alert-warning border-0 p-3 rounded-3 d-none">
                            Kategori ini belum memiliki produk untuk dipindahkan.
                        </div>

                        <div id="pindah-product-list-container" class="d-none">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small fw-semibold text-muted">Daftar Produk yang akan dipindahkan:</span>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" id="btn-select-all-move">Pilih Semua</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" id="btn-deselect-all-move">Batal Semua</button>
                                </div>
                            </div>
                            <div class="table-responsive border rounded-3" style="max-height: 250px; overflow-y: auto;">
                                <table class="table table-sm table-hover align-middle mb-0" id="tbl-pindah-products">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th width="40" class="text-center">
                                                <input type="checkbox" class="form-check-input" id="check-all-products" checked>
                                            </th>
                                            <th width="140">Kode</th>
                                            <th>Nama Produk</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Rendered via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm" id="btnSubmitPindah">
                            <i class="material-icons-outlined align-middle" style="font-size:16px">swap_horiz</i> Pindahkan Produk
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const csrfToken = '{{ csrf_token() }}';

        // Reset modal saat buka untuk tambah
        document.getElementById('modalKategori').addEventListener('show.bs.modal', function(e) {
            if (!e.relatedTarget) return;
            document.getElementById('modalKategoriTitle').textContent = 'Tambah Kategori Produk';
            document.getElementById('kat_id').value = '';
            document.getElementById('kat_name').value = '';
            document.getElementById('kat_printer_id').value = '';
            document.getElementById('kat_sort').value = '0';
            document.getElementById('wrap_is_active').classList.add('d-none');
        });

        // Edit kategori
        document.querySelectorAll('.btn-edit-kat').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('modalKategoriTitle').textContent = 'Edit Kategori Produk';
                document.getElementById('kat_id').value = this.dataset.id;
                document.getElementById('kat_name').value = this.dataset.name;
                document.getElementById('kat_printer_id').value = this.dataset.printerId || '';
                document.getElementById('kat_sort').value = this.dataset.sort || '0';
                document.getElementById('kat_is_active').checked = this.dataset.active === '1';
                document.getElementById('wrap_is_active').classList.remove('d-none');
                new bootstrap.Modal(document.getElementById('modalKategori')).show();
            });
        });

        // Submit form kategori
        document.getElementById('formKategori').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('kat_id').value;
            const url = id ?
                `{{ url('kategori-produk') }}/${id}` :
                `{{ route('kategori-produk.store') }}`;
            const method = id ? 'PUT' : 'POST';

            fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        name: document.getElementById('kat_name').value,
                        printer_id: document.getElementById('kat_printer_id').value || null,
                        sort_order: document.getElementById('kat_sort').value,
                        is_active: document.getElementById('kat_is_active').checked ? 1 : 0,
                    }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('modalKategori'))?.hide();
                        location.reload();
                    } else {
                        alert(data.message || 'Gagal menyimpan kategori.');
                    }
                })
                .catch(err => alert('Terjadi kesalahan: ' + err.message));
        });

        // Hapus kategori
        document.querySelectorAll('.btn-delete-kat').forEach(btn => {
            btn.addEventListener('click', function() {
                const name = this.dataset.name || 'kategori ini';
                if (!confirm(`Hapus kategori "${name}"?`)) return;
                fetch(`{{ url('kategori-produk') }}/${this.dataset.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert(data.message || 'Gagal menghapus kategori.');
                        }
                    })
                    .catch(err => alert('Terjadi kesalahan: ' + err.message));
            });
        });

        // Pindahkan Produk Antar Kategori
        const modalPindah = new bootstrap.Modal(document.getElementById('modalPindahProduk'));
        let currentProductsList = [];

        document.querySelectorAll('.btn-move-kat').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const name = this.dataset.name;
                const count = parseInt(this.dataset.count) || 0;

                document.getElementById('pindah_from_category_id').value = id;
                document.getElementById('pindah_from_category_name').textContent = name;
                document.getElementById('pindah_source_count_badge').textContent = `${count} Produk`;

                // Reset dropdown target
                const selectTarget = document.getElementById('pindah_target_category_id');
                selectTarget.value = '';
                Array.from(selectTarget.options).forEach(opt => {
                    if (opt.value === id) {
                        opt.style.display = 'none';
                        opt.disabled = true;
                    } else {
                        opt.style.display = '';
                        opt.disabled = false;
                    }
                });

                // Reset radio option
                document.getElementById('opt_move_all').checked = true;
                document.getElementById('pindah-product-list-container').classList.add('d-none');
                document.getElementById('pindah-empty-products').classList.add('d-none');
                document.getElementById('pindah-loading').classList.remove('d-none');
                document.getElementById('check-all-products').checked = true;

                modalPindah.show();

                // Fetch produk dalam kategori asal
                fetch(`{{ url('kategori-produk') }}/${id}/products`, {
                    headers: { 'Accept': 'application/json' }
                })
                .then(r => r.json())
                .then(res => {
                    document.getElementById('pindah-loading').classList.add('d-none');
                    currentProductsList = res.products || [];

                    const tbody = document.querySelector('#tbl-pindah-products tbody');
                    tbody.innerHTML = '';

                    if (currentProductsList.length === 0) {
                        document.getElementById('pindah-empty-products').classList.remove('d-none');
                        document.getElementById('btnSubmitPindah').disabled = true;
                    } else {
                        document.getElementById('btnSubmitPindah').disabled = false;
                        currentProductsList.forEach(p => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input product-check-item" value="${p.id}" checked>
                                </td>
                                <td><span class="badge bg-light text-dark border">${p.kode_produk || '-'}</span></td>
                                <td class="fw-semibold">${p.nama_produk}</td>
                            `;
                            tbody.appendChild(tr);
                        });
                    }
                })
                .catch(err => {
                    document.getElementById('pindah-loading').classList.add('d-none');
                    alert('Gagal memuat produk: ' + err.message);
                });
            });
        });

        // Toggle radio opsi pemindahan
        document.querySelectorAll('input[name="move_option"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const container = document.getElementById('pindah-product-list-container');
                if (this.value === 'selected' && currentProductsList.length > 0) {
                    container.classList.remove('d-none');
                } else {
                    container.classList.add('d-none');
                }
            });
        });

        // Check/Uncheck all
        document.getElementById('check-all-products')?.addEventListener('change', function() {
            const isChecked = this.checked;
            document.querySelectorAll('.product-check-item').forEach(c => c.checked = isChecked);
        });

        document.getElementById('btn-select-all-move')?.addEventListener('click', function() {
            document.getElementById('check-all-products').checked = true;
            document.querySelectorAll('.product-check-item').forEach(c => c.checked = true);
        });

        document.getElementById('btn-deselect-all-move')?.addEventListener('click', function() {
            document.getElementById('check-all-products').checked = false;
            document.querySelectorAll('.product-check-item').forEach(c => c.checked = false);
        });

        // Submit form pindah produk
        document.getElementById('formPindahProduk').addEventListener('submit', function(e) {
            e.preventDefault();
            const fromId = document.getElementById('pindah_from_category_id').value;
            const targetId = document.getElementById('pindah_target_category_id').value;
            const moveAll = document.getElementById('opt_move_all').checked;

            if (!targetId) {
                alert('Silakan pilih kategori tujuan terlebih dahulu.');
                return;
            }

            let productIds = [];
            if (!moveAll) {
                document.querySelectorAll('.product-check-item:checked').forEach(c => {
                    productIds.push(parseInt(c.value));
                });
                if (productIds.length === 0) {
                    alert('Silakan pilih minimal satu produk untuk dipindahkan.');
                    return;
                }
            }

            const btnSubmit = document.getElementById('btnSubmitPindah');
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memproses...';

            fetch(`{{ url('kategori-produk') }}/${fromId}/move-products`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    target_category_id: targetId,
                    move_all: moveAll,
                    product_ids: productIds
                })
            })
            .then(r => r.json())
            .then(data => {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="material-icons-outlined align-middle" style="font-size:16px">swap_horiz</i> Pindahkan Produk';

                if (data.success) {
                    modalPindah.hide();
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || 'Gagal memindahkan produk.');
                }
            })
            .catch(err => {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="material-icons-outlined align-middle" style="font-size:16px">swap_horiz</i> Pindahkan Produk';
                alert('Terjadi kesalahan: ' + err.message);
            });
        });
    </script>
@endpush
