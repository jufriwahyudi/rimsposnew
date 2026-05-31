@extends('layouts.main.main')
@section('title', 'Tenant (Penyewa/Kantin)')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Pengaturan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active">Tenant</li>
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
                            <h5 class="fw-bold mb-0" style="color:#7c3aed">Daftar Tenant</h5>
                            <small class="text-muted">{{ session('store_name') }}</small>
                        </div>
                    </div>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalTenant">
                        <i class="material-icons-outlined" style="font-size:16px;vertical-align:middle">add</i> Tambah Tenant
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="tbl-tenant">
                            <thead class="table-light">
                                <tr>
                                    <th width="40">#</th>
                                    <th>Kode Tenant</th>
                                    <th>Nama Tenant</th>
                                    <th>Komisi (%)</th>
                                    <th>Telepon</th>
                                    <th>Alamat</th>
                                    <th>Status</th>
                                    <th class="text-center" width="120">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tenants as $i => $tenant)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>
                                            <span class="badge rounded-pill" style="background:#7c3aed;font-size:12px">
                                                {{ $tenant->kode_tenant }}
                                            </span>
                                        </td>
                                        <td><strong>{{ $tenant->nama_tenant }}</strong></td>
                                        <td>{{ number_format($tenant->commission_rate, 2) }}%</td>
                                        <td>{{ $tenant->telepon ?? '-' }}</td>
                                        <td>{{ $tenant->alamat ?? '-' }}</td>
                                        <td>
                                            @if ($tenant->stts === 'Y')
                                                <span class="badge bg-success">Aktif</span>
                                            @else
                                                <span class="badge bg-danger">Non-aktif</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-warning btn-edit-tenant" 
                                                data-id="{{ $tenant->id }}"
                                                data-kode_tenant="{{ $tenant->kode_tenant }}" 
                                                data-nama_tenant="{{ $tenant->nama_tenant }}"
                                                data-telepon="{{ $tenant->telepon }}" 
                                                data-alamat="{{ $tenant->alamat }}"
                                                data-commission_rate="{{ $tenant->commission_rate }}"
                                                data-stts="{{ $tenant->stts }}">
                                                <i class="material-icons-outlined" style="font-size:15px">edit</i>
                                            </button>
                                            <button class="btn btn-sm btn-danger btn-delete-tenant" data-id="{{ $tenant->id }}">
                                                <i class="material-icons-outlined" style="font-size:15px">delete</i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Belum ada tenant / kantin</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah/Edit Tenant --}}
    <div class="modal fade" id="modalTenant" tabindex="-1">
        <div class="modal-dialog">
            <form id="formTenant">
                @csrf
                <input type="hidden" id="tenant_id" value="">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTenantTitle">Tambah Tenant</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Kode Tenant <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="tenant_kode"
                                placeholder="contoh: TNT001, KTN002" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Tenant <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="tenant_nama" placeholder="contoh: Stand Bakso"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tingkat Komisi (%) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="tenant_commission" step="0.01" min="0" max="100" placeholder="contoh: 10.00"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nomor Telepon</label>
                            <input type="text" class="form-control" id="tenant_telepon" placeholder="contoh: 081234567890">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" id="tenant_alamat" rows="3" placeholder="contoh: Stand No. 3, Kantin Utama"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="tenant_stts" required>
                                <option value="Y">Aktif</option>
                                <option value="N">Non-aktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSimpanTenant">Simpan</button>
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
                    <h5 class="modal-title">Hapus Tenant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Yakin ingin menghapus tenant ini?
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
        const baseUrl = '{{ url('settings/tenants') }}';

        // Reset modal saat buka untuk tambah
        document.getElementById('modalTenant').addEventListener('show.bs.modal', function(e) {
            if (!e.relatedTarget) return; // Prevent triggering on JS manual show
            document.getElementById('modalTenantTitle').textContent = 'Tambah Tenant';
            document.getElementById('tenant_id').value = '';
            document.getElementById('tenant_kode').value = '';
            document.getElementById('tenant_nama').value = '';
            document.getElementById('tenant_commission').value = '0';
            document.getElementById('tenant_telepon').value = '';
            document.getElementById('tenant_alamat').value = '';
            document.getElementById('tenant_stts').value = 'Y';
        });

        // Edit tenant
        document.querySelectorAll('.btn-edit-tenant').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('modalTenantTitle').textContent = 'Edit Tenant';
                document.getElementById('tenant_id').value = this.dataset.id;
                document.getElementById('tenant_kode').value = this.dataset.kode_tenant;
                document.getElementById('tenant_nama').value = this.dataset.nama_tenant;
                document.getElementById('tenant_commission').value = this.dataset.commission_rate;
                document.getElementById('tenant_telepon').value = this.dataset.telepon !== 'null' && this.dataset.telepon !== 'undefined' ? this.dataset.telepon : '';
                document.getElementById('tenant_alamat').value = this.dataset.alamat !== 'null' && this.dataset.alamat !== 'undefined' ? this.dataset.alamat : '';
                document.getElementById('tenant_stts').value = this.dataset.stts;
                new bootstrap.Modal(document.getElementById('modalTenant')).show();
            });
        });

        // Submit tambah / edit
        document.getElementById('formTenant').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('tenant_id').value;
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
                        kode_tenant: document.getElementById('tenant_kode').value,
                        nama_tenant: document.getElementById('tenant_nama').value,
                        commission_rate: document.getElementById('tenant_commission').value,
                        telepon: document.getElementById('tenant_telepon').value,
                        alamat: document.getElementById('tenant_alamat').value,
                        stts: document.getElementById('tenant_stts').value,
                    }),
                })
                .then(async r => {
                    const res = await r.json();
                    if (r.ok && res.success) {
                        bootstrap.Modal.getInstance(document.getElementById('modalTenant')).hide();
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

        // Hapus tenant
        let deleteId = null;
        document.querySelectorAll('.btn-delete-tenant').forEach(btn => {
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
