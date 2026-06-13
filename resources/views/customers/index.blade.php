@extends('layouts.main.main')
@section('title', 'Mitra (Pelanggan)')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Pengaturan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active">Mitra (Pelanggan)</li>
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
                            <h5 class="fw-bold mb-0" style="color:#7c3aed">Mitra / Pelanggan</h5>
                            <small class="text-muted">{{ session('store_name') }}</small>
                        </div>
                    </div>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalCustomer">
                        <i class="material-icons-outlined" style="font-size:16px;vertical-align:middle">add</i> Tambah
                        Mitra
                    </button>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover" id="tbl-customer">
                        <thead class="table-light">
                            <tr>
                                <th width="40">#</th>
                                <th>Nama Mitra</th>
                                <th>Telepon</th>
                                <th>Alamat</th>
                                <th class="text-center" width="160">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($customers as $i => $cust)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td><strong>{{ $cust->name }}</strong></td>
                                    <td>{{ $cust->phone ?? '-' }}</td>
                                    <td>{{ $cust->alamat ?? '-' }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('customers.show', $cust->id) }}" class="btn btn-sm btn-info text-white" title="Detail Riwayat">
                                            <i class="material-icons-outlined" style="font-size:15px;vertical-align:middle">visibility</i>
                                        </a>
                                        <button class="btn btn-sm btn-warning btn-edit-customer" data-id="{{ $cust->id }}"
                                            data-name="{{ htmlspecialchars($cust->name) }}"
                                            data-phone="{{ htmlspecialchars($cust->phone) }}"
                                            data-alamat="{{ htmlspecialchars($cust->alamat) }}">
                                            <i class="material-icons-outlined" style="font-size:15px;vertical-align:middle">edit</i>
                                        </button>
                                        <button class="btn btn-sm btn-danger btn-delete-customer" data-id="{{ $cust->id }}">
                                            <i class="material-icons-outlined" style="font-size:15px;vertical-align:middle">delete</i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Belum ada data mitra / pelanggan</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah/Edit Customer --}}
    <div class="modal fade" id="modalCustomer" tabindex="-1">
        <div class="modal-dialog">
            <form id="formCustomer">
                @csrf
                <input type="hidden" id="customer_id" value="">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCustomerTitle">Tambah Mitra</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Mitra / Pelanggan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="customer_name" placeholder="contoh: Mitra A"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nomor Telepon</label>
                            <input type="text" class="form-control" id="customer_phone" placeholder="contoh: 081234567890">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" id="customer_alamat" rows="3" placeholder="contoh: Jl. Merdeka No. 10"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSimpanCustomer">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Confirm Delete --}}
    <div class="modal fade" id="modalHapus" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Hapus Mitra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Yakin ingin menghapus data mitra ini?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger btn-sm" id="btnKonfirmasiHapus">Hapus</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const csrfToken = '{{ csrf_token() }}';
        const baseUrl = '{{ url('settings/customers') }}';

        // Reset modal saat buka untuk tambah
        document.getElementById('modalCustomer').addEventListener('show.bs.modal', function(e) {
            if (!e.relatedTarget) return; // Prevent triggering on JS manual show
            document.getElementById('modalCustomerTitle').textContent = 'Tambah Mitra';
            document.getElementById('customer_id').value = '';
            document.getElementById('customer_name').value = '';
            document.getElementById('customer_phone').value = '';
            document.getElementById('customer_alamat').value = '';
        });

        // Edit customer
        document.querySelectorAll('.btn-edit-customer').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('modalCustomerTitle').textContent = 'Edit Mitra';
                document.getElementById('customer_id').value = this.dataset.id;
                document.getElementById('customer_name').value = this.dataset.name;
                document.getElementById('customer_phone').value = this.dataset.phone !== 'null' ? this.dataset.phone : '';
                document.getElementById('customer_alamat').value = this.dataset.alamat !== 'null' ? this.dataset.alamat : '';
                new bootstrap.Modal(document.getElementById('modalCustomer')).show();
            });
        });

        // Submit tambah / edit
        document.getElementById('formCustomer').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('customer_id').value;
            const url = id ? `${baseUrl}/${id}` : baseUrl;
            const method = id ? 'PUT' : 'POST';

            fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        name: document.getElementById('customer_name').value,
                        phone: document.getElementById('customer_phone').value,
                        alamat: document.getElementById('customer_alamat').value,
                    }),
                })
                .then(async r => {
                    const res = await r.json();
                    if (r.ok && res.success) {
                        bootstrap.Modal.getInstance(document.getElementById('modalCustomer')).hide();
                        location.reload();
                    } else {
                        let errorMsg = res.message ?? 'Terjadi kesalahan.';
                        if (res.errors) {
                            errorMsg = Object.values(res.errors).flat().join('\n');
                        }
                        alert(errorMsg);
                    }
                })
                .catch(() => alert('Koneksi gagal, coba lagi.'));
        });

        // Hapus customer
        let deleteId = null;
        document.querySelectorAll('.btn-delete-customer').forEach(btn => {
            btn.addEventListener('click', function() {
                deleteId = this.dataset.id;
                new bootstrap.Modal(document.getElementById('modalHapus')).show();
            });
        });

        document.getElementById('btnKonfirmasiHapus').addEventListener('click', function() {
            if (!deleteId) return;
            fetch(`${baseUrl}/${deleteId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                })
                .then(async r => {
                    const res = await r.json();
                    if (r.ok && res.success) {
                        bootstrap.Modal.getInstance(document.getElementById('modalHapus')).hide();
                        location.reload();
                    } else {
                        alert(res.message ?? 'Gagal menghapus.');
                    }
                })
                .catch(() => alert('Koneksi gagal, coba lagi.'));
        });
    </script>
@endpush
