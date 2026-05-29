@extends('layouts.main.main')
@section('title', 'Vendor (Supplier)')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Pengaturan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active">Vendor (Supplier)</li>
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
                            <h5 class="fw-bold mb-0" style="color:#7c3aed">Vendor / Supplier</h5>
                            <small class="text-muted">{{ session('store_name') }}</small>
                        </div>
                    </div>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalVendor">
                        <i class="material-icons-outlined" style="font-size:16px;vertical-align:middle">add</i> Tambah
                        Vendor
                    </button>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover" id="tbl-vendor">
                        <thead class="table-light">
                            <tr>
                                <th width="40">#</th>
                                <th>Kode Vendor</th>
                                <th>Nama Vendor</th>
                                <th>Telepon</th>
                                <th>Alamat</th>
                                <th class="text-center" width="120">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vendors as $i => $vendor)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        <span class="badge rounded-pill" style="background:#7c3aed;font-size:12px">
                                            {{ $vendor->kode_vendor }}
                                        </span>
                                    </td>
                                    <td><strong>{{ $vendor->nama_vendor }}</strong></td>
                                    <td>{{ $vendor->telepon ?? '-' }}</td>
                                    <td>{{ $vendor->alamat ?? '-' }}</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-warning btn-edit-vendor" data-id="{{ $vendor->id }}"
                                            data-kode_vendor="{{ $vendor->kode_vendor }}" data-nama_vendor="{{ $vendor->nama_vendor }}"
                                            data-telepon="{{ $vendor->telepon }}" data-alamat="{{ $vendor->alamat }}">
                                            <i class="material-icons-outlined" style="font-size:15px">edit</i>
                                        </button>
                                        <button class="btn btn-sm btn-danger btn-delete-vendor" data-id="{{ $vendor->id }}">
                                            <i class="material-icons-outlined" style="font-size:15px">delete</i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Belum ada vendor / supplier</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah/Edit Vendor --}}
    <div class="modal fade" id="modalVendor" tabindex="-1">
        <div class="modal-dialog">
            <form id="formVendor">
                @csrf
                <input type="hidden" id="vendor_id" value="">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalVendorTitle">Tambah Vendor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Kode Vendor <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="vendor_kode"
                                placeholder="contoh: VND001, SPL002" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Vendor / Supplier <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="vendor_nama" placeholder="contoh: PT. Jaya Abadi"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nomor Telepon</label>
                            <input type="text" class="form-control" id="vendor_telepon" placeholder="contoh: 081234567890">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" id="vendor_alamat" rows="3" placeholder="contoh: Jl. Merdeka No. 10"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSimpanVendor">Simpan</button>
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
                    <h5 class="modal-title">Hapus Vendor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Yakin ingin menghapus vendor ini?
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
        const baseUrl = '{{ url('settings/vendors') }}';

        // Reset modal saat buka untuk tambah
        document.getElementById('modalVendor').addEventListener('show.bs.modal', function(e) {
            if (!e.relatedTarget) return; // Prevent triggering on JS manual show
            document.getElementById('modalVendorTitle').textContent = 'Tambah Vendor';
            document.getElementById('vendor_id').value = '';
            document.getElementById('vendor_kode').value = '';
            document.getElementById('vendor_nama').value = '';
            document.getElementById('vendor_telepon').value = '';
            document.getElementById('vendor_alamat').value = '';
        });

        // Edit vendor
        document.querySelectorAll('.btn-edit-vendor').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('modalVendorTitle').textContent = 'Edit Vendor';
                document.getElementById('vendor_id').value = this.dataset.id;
                document.getElementById('vendor_kode').value = this.dataset.kode_vendor;
                document.getElementById('vendor_nama').value = this.dataset.nama_vendor;
                document.getElementById('vendor_telepon').value = this.dataset.telepon !== 'null' ? this.dataset.telepon : '';
                document.getElementById('vendor_alamat').value = this.dataset.alamat !== 'null' ? this.dataset.alamat : '';
                new bootstrap.Modal(document.getElementById('modalVendor')).show();
            });
        });

        // Submit tambah / edit
        document.getElementById('formVendor').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('vendor_id').value;
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
                        kode_vendor: document.getElementById('vendor_kode').value,
                        nama_vendor: document.getElementById('vendor_nama').value,
                        telepon: document.getElementById('vendor_telepon').value,
                        alamat: document.getElementById('vendor_alamat').value,
                    }),
                })
                .then(async r => {
                    const res = await r.json();
                    if (r.ok && res.success) {
                        bootstrap.Modal.getInstance(document.getElementById('modalVendor')).hide();
                        location.reload();
                    } else {
                        // Display error message
                        let errorMsg = res.message ?? 'Terjadi kesalahan.';
                        if (res.errors) {
                            errorMsg = Object.values(res.errors).flat().join('\n');
                        }
                        alert(errorMsg);
                    }
                })
                .catch(() => alert('Koneksi gagal, coba lagi.'));
        });

        // Hapus vendor
        let deleteId = null;
        document.querySelectorAll('.btn-delete-vendor').forEach(btn => {
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
