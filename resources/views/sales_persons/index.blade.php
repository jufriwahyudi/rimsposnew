@extends('layouts.main.main')
@section('title', 'Data Pramuniaga / SPG')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Master Data</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active">Pramuniaga (SPG)</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            @if($store && !$store->addon_sales_person)
                <div class="alert alert-warning border-0 bg-warning alert-dismissible fade show py-2">
                    <div class="d-flex align-items-center">
                        <div class="font-35 text-dark"><i class="material-icons-outlined">info</i></div>
                        <div class="ms-3">
                            <h6 class="mb-0 text-dark fw-bold">Fitur Add-on Belum Diaktifkan</h6>
                            <div class="text-dark small">
                                Toko <strong>{{ $store->name }}</strong> belum mengaktifkan Add-on Pramuniaga / SPG.
                                Anda tetap dapat menginput master data di sini, namun pemilihan SPG di aplikasi kasir POS Mobile hanya akan tampil jika Add-on diaktifkan di menu <strong>Manage Toko</strong>.
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card rounded-4 p-2">
                <div class="card-header d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-start">
                        <img src="{{ asset('assets/images/alazca_logo.png') }}" alt="Logo"
                            style="width:35px;height:35px;" class="me-2 mt-1">
                        <div>
                            <h5 class="fw-bold mb-0" style="color:#7c3aed">Data Pramuniaga / SPG</h5>
                            <small class="text-muted">{{ session('store_name', 'Semua Toko') }} &bull; Pencatatan closing sales & komisi</small>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="openCreateModal()">
                        <i class="material-icons-outlined" style="font-size:16px;vertical-align:middle">add</i> Tambah Pramuniaga
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle" id="tbl-sales-person">
                            <thead class="table-light">
                                <tr>
                                    <th width="40" class="text-center">#</th>
                                    <th width="120">Kode SPG</th>
                                    <th>Nama Pramuniaga</th>
                                    <th>No. Telepon</th>
                                    <th class="text-center" width="120">Komisi (%)</th>
                                    <th class="text-center" width="130">Total Penjualan</th>
                                    <th class="text-center" width="100">Status</th>
                                    <th class="text-center" width="120">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($salesPersons as $i => $sp)
                                    <tr>
                                        <td class="text-center">{{ $i + 1 }}</td>
                                        <td>
                                            @if($sp->code)
                                                <span class="badge bg-secondary font-monospace">{{ $sp->code }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-bold">{{ $sp->name }}</div>
                                        </td>
                                        <td>{{ $sp->phone ?? '-' }}</td>
                                        <td class="text-center">
                                            @if($sp->commission_rate > 0)
                                                <span class="badge bg-info text-dark">{{ $sp->commission_rate }}%</span>
                                            @else
                                                <span class="text-muted">0%</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border">
                                                <i class="material-icons-outlined" style="font-size:12px;vertical-align:middle">receipt</i>
                                                {{ $sp->sales_count }} Transaksi
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if ($sp->is_active)
                                                <span class="badge bg-success">Aktif</span>
                                            @else
                                                <span class="badge bg-danger">Non-aktif</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" onclick="openEditModal({{ $sp->id }})" title="Edit">
                                                    <i class="material-icons-outlined" style="font-size:16px;">edit</i>
                                                </button>
                                                <button class="btn btn-outline-danger" onclick="deleteSalesPerson({{ $sp->id }}, '{{ addslashes($sp->name) }}')" title="Hapus">
                                                    <i class="material-icons-outlined" style="font-size:16px;">delete</i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">Belum ada data pramuniaga. Silakan tambah pramuniaga/SPG baru.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL TAMBAH / EDIT --}}
    <div class="modal fade" id="modalSalesPerson" tabindex="-1" aria-labelledby="modalSalesPersonLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="modalSalesPersonLabel">Tambah Pramuniaga</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formSalesPerson" novalidate>
                    @csrf
                    <input type="hidden" id="spId">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Kode SPG / ID</label>
                                <input type="text" class="form-control text-uppercase" id="spCode" placeholder="Contoh: SPG-01" maxlength="30">
                                <div class="invalid-feedback" id="err-code"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Komisi Default (%)</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" id="spCommission" placeholder="0">
                                <div class="invalid-feedback" id="err-commission_rate"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Nama Lengkap Pramuniaga <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="spName" placeholder="Contoh: Rina Anggraini" required>
                                <div class="invalid-feedback" id="err-name"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">No. Telepon / WhatsApp</label>
                                <input type="text" class="form-control" id="spPhone" placeholder="Contoh: 0812-3456-7890">
                                <div class="invalid-feedback" id="err-phone"></div>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="spIsActive" checked>
                                    <label class="form-check-label fw-semibold" for="spIsActive">Status Aktif (Tampil di Kasir)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm" id="btnSubmitSp">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const baseRoute = "{{ route('sales-persons.index') }}";
        const routes = {
            store: "{{ route('sales-persons.store') }}",
            edit: (id) => `${baseRoute}/${id}/edit`,
            update: (id) => `${baseRoute}/${id}`,
            destroy: (id) => `${baseRoute}/${id}`,
        };

        function clearErrors() {
            document.querySelectorAll('#formSalesPerson .form-control').forEach(el => el.classList.remove('is-invalid'));
        }

        function showErrors(errors) {
            clearErrors();
            Object.entries(errors).forEach(([field, msgs]) => {
                const input = document.getElementById(field === 'name' ? 'spName' : (field === 'code' ? 'spCode' : (field === 'phone' ? 'spPhone' : 'spCommission')));
                const err = document.getElementById('err-' + field);
                if (input) input.classList.add('is-invalid');
                if (err) err.textContent = msgs[0];
            });
        }

        function openCreateModal() {
            document.getElementById('formSalesPerson').reset();
            document.getElementById('spId').value = '';
            document.getElementById('spIsActive').checked = true;
            document.getElementById('modalSalesPersonLabel').textContent = 'Tambah Pramuniaga Baru';
            clearErrors();
            new bootstrap.Modal(document.getElementById('modalSalesPerson')).show();
        }

        function openEditModal(id) {
            clearErrors();
            fetch(routes.edit(id))
                .then(r => r.json())
                .then(data => {
                    document.getElementById('spId').value = data.id;
                    document.getElementById('spName').value = data.name;
                    document.getElementById('spCode').value = data.code ?? '';
                    document.getElementById('spPhone').value = data.phone ?? '';
                    document.getElementById('spCommission').value = data.commission_rate ?? 0;
                    document.getElementById('spIsActive').checked = data.is_active == 1;

                    document.getElementById('modalSalesPersonLabel').textContent = 'Edit Data Pramuniaga';
                    new bootstrap.Modal(document.getElementById('modalSalesPerson')).show();
                })
                .catch(() => alert('Gagal mengambil data pramuniaga.'));
        }

        document.getElementById('formSalesPerson').addEventListener('submit', function (e) {
            e.preventDefault();
            const id = document.getElementById('spId').value;
            const isEdit = !!id;
            const payload = {
                name: document.getElementById('spName').value,
                code: document.getElementById('spCode').value,
                phone: document.getElementById('spPhone').value,
                commission_rate: document.getElementById('spCommission').value,
                is_active: document.getElementById('spIsActive').checked ? 1 : 0,
                _token: '{{ csrf_token() }}',
            };
            if (isEdit) payload._method = 'PUT';

            const btn = document.getElementById('btnSubmitSp');
            btn.disabled = true; btn.textContent = 'Menyimpan...';

            fetch(isEdit ? routes.update(id) : routes.store, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify(payload),
            })
            .then(async r => {
                const data = await r.json();
                btn.disabled = false; btn.textContent = 'Simpan';
                if (!r.ok) {
                    if (r.status === 422) showErrors(data.errors);
                    else alert(data.message ?? 'Terjadi kesalahan.');
                    return;
                }
                bootstrap.Modal.getInstance(document.getElementById('modalSalesPerson')).hide();
                location.reload();
            })
            .catch(() => {
                btn.disabled = false; btn.textContent = 'Simpan';
                alert('Terjadi kesalahan jaringan.');
            });
        });

        function deleteSalesPerson(id, name) {
            if (!confirm(`Hapus/Nonaktifkan pramuniaga "${name}"?`)) return;

            fetch(routes.destroy(id), {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ _token: '{{ csrf_token() }}' }),
            })
            .then(r => r.json())
            .then(data => {
                alert(data.message);
                location.reload();
            })
            .catch(() => alert('Gagal menghapus pramuniaga.'));
        }
    </script>
@endpush
