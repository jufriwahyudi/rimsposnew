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
                                <th width="120" class="text-center">Urutan</th>
                                <th width="150" class="text-center">Jumlah Produk</th>
                                <th width="120" class="text-center">Status</th>
                                <th class="text-center" width="130">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $i => $cat)
                                <tr>
                                    <td class="text-center">{{ $i + 1 }}</td>
                                    <td>
                                        <span class="fw-semibold text-dark">{{ $cat->name }}</span>
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
                                        <button class="btn btn-sm btn-warning btn-edit-kat" 
                                            data-id="{{ $cat->id }}"
                                            data-name="{{ $cat->name }}" 
                                            data-sort="{{ $cat->sort_order }}"
                                            data-active="{{ $cat->is_active ? '1' : '0' }}">
                                            <i class="material-icons-outlined" style="font-size:15px">edit</i>
                                        </button>
                                        <button class="btn btn-sm btn-danger btn-delete-kat" 
                                            data-id="{{ $cat->id }}"
                                            data-name="{{ $cat->name }}">
                                            <i class="material-icons-outlined" style="font-size:15px">delete</i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Belum ada kategori produk. Klik <strong>Tambah Kategori</strong> untuk membuat kategori baru.</td>
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
            document.getElementById('kat_sort').value = '0';
            document.getElementById('wrap_is_active').classList.add('d-none');
        });

        // Edit kategori
        document.querySelectorAll('.btn-edit-kat').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('modalKategoriTitle').textContent = 'Edit Kategori Produk';
                document.getElementById('kat_id').value = this.dataset.id;
                document.getElementById('kat_name').value = this.dataset.name;
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
    </script>
@endpush
