@extends('layouts.main.main')
@section('title', 'Manage Toko')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Pengaturan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active">Manage Toko</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
    <style>
        .logo-thumb {
            width: 36px;
            height: 36px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }
        .logo-placeholder {
            width: 36px;
            height: 36px;
            border-radius: 6px;
            background: #f1f1f1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #aaa;
            font-size: 18px;
            border: 1px solid #dee2e6;
        }
        #cropContainer {
            max-height: 420px;
            overflow: hidden;
        }
        #cropContainer img {
            max-width: 100%;
            display: block;
        }
        .logo-preview-wrap {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            transition: border-color .2s;
            min-height: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-preview-wrap:hover { border-color: #7c3aed; }
        #logoPreview {
            max-width: 120px;
            max-height: 120px;
            border-radius: 8px;
            object-fit: cover;
        }
    </style>
@endpush

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card rounded-4 p-2">
                <div class="card-header d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-start">
                        <img src="{{ asset('assets/images/alazca_logo.png') }}" alt="Logo"
                            style="width:35px;height:35px;" class="me-2 mt-1">
                        <div>
                            <h5 class="fw-bold mb-0" style="color:#7c3aed">Manage Toko</h5>
                            <small class="text-muted">Kelola data toko dan konfigurasi printer</small>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="openCreate()">
                        <i class="fa fa-plus"></i> Tambah Toko
                    </button>
                </div>

                <div class="card-body">

                    {{-- Tab aktif / terhapus --}}
                    <ul class="nav nav-tabs mb-3" id="storeTabs">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabActive">
                                Aktif <span class="badge bg-primary ms-1">{{ $stores->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabTrashed">
                                Terhapus <span class="badge bg-danger ms-1">{{ $trashed->count() }}</span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">

                        {{-- ===== TAB AKTIF ===== --}}
                        <div class="tab-pane fade show active" id="tabActive">
                            <div class="table-responsive">
                                <table class="table table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th width="4%">No</th>
                                            <th width="50px">Logo</th>
                                            <th>Kode</th>
                                            <th>Nama Toko</th>
                                            <th>Kota</th>
                                            <th>No. Telepon</th>
                                            <th>Tipe Bisnis</th>
                                            <th>Printer</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center" width="12%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($stores as $i => $store)
                                            <tr>
                                                <td>{{ $i + 1 }}</td>
                                                <td>
                                                    @if ($store->logo)
                                                        <img src="{{ Storage::url($store->logo) }}" class="logo-thumb" alt="Logo">
                                                    @else
                                                        <span class="logo-placeholder"><i class="bi bi-shop"></i></span>
                                                    @endif
                                                </td>
                                                <td><span class="badge bg-secondary">{{ $store->code }}</span></td>
                                                <td>
                                                    <div class="fw-semibold">{{ $store->name }}</div>
                                                    @if($store->business)
                                                        <small class="text-muted" style="font-size: 11px;">
                                                            <i class="bi bi-building"></i> {{ $store->business->name }}
                                                        </small>
                                                    @endif
                                                </td>
                                                <td>{{ $store->city ?? '-' }}</td>
                                                <td>{{ $store->phone ?? '-' }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $store->business_type === 'fnb' ? 'warning text-dark' : 'info' }} d-block mb-1">
                                                        {{ $store->business_type === 'fnb' ? 'F&B' : 'Retail' }}
                                                    </span>
                                                    @if($store->business_type === 'fnb')
                                                        <div style="font-size: 10px;" class="mt-1 d-flex flex-column gap-1">
                                                            <span class="badge bg-{{ $store->addon_self_service ? 'success' : 'secondary' }}" style="font-size: 9px;">
                                                                Self-Service: {{ $store->addon_self_service ? 'Aktif' : 'Non-aktif' }}
                                                            </span>
                                                            <span class="badge bg-{{ $store->addon_kds ? 'success' : 'secondary' }}" style="font-size: 9px;">
                                                                KDS: {{ $store->addon_kds ? 'Aktif' : 'Non-aktif' }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ $store->printer_type === '58mm' ? 'info' : 'primary' }}">
                                                        {{ $store->printer_type ?? '80mm' }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    @if ($store->is_active)
                                                        <span class="badge bg-success">Aktif</span>
                                                    @else
                                                        <span class="badge bg-danger">Non-aktif</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-outline-primary"
                                                        onclick="openEdit({{ $store->id }})">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger"
                                                        onclick="deleteStore({{ $store->id }}, '{{ addslashes($store->name) }}')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center text-muted py-4">Belum ada data toko.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- ===== TAB TERHAPUS ===== --}}
                        <div class="tab-pane fade" id="tabTrashed">
                            <div class="table-responsive">
                                <table class="table table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th width="4%">No</th>
                                            <th>Kode</th>
                                            <th>Nama Toko</th>
                                            <th>Dihapus Pada</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($trashed as $i => $store)
                                            <tr>
                                                <td>{{ $i + 1 }}</td>
                                                <td><span class="badge bg-secondary">{{ $store->code }}</span></td>
                                                <td>
                                                    <div class="fw-semibold text-muted">{{ $store->name }}</div>
                                                    @if($store->business)
                                                        <small class="text-muted" style="font-size: 11px;">
                                                            <i class="bi bi-building"></i> {{ $store->business->name }}
                                                        </small>
                                                    @endif
                                                </td>
                                                <td>{{ $store->deleted_at->format('d-m-Y H:i') }}</td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-outline-success"
                                                        onclick="restoreStore({{ $store->id }}, '{{ addslashes($store->name) }}')">
                                                        <i class="bi bi-arrow-counterclockwise"></i> Pulihkan
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">Tidak ada toko terhapus.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== MODAL FORM ========== --}}
    <div class="modal fade" id="modalStore" tabindex="-1" aria-labelledby="modalStoreLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content rounded-4">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="modalStoreLabel">Tambah Toko</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="storeForm" novalidate>
                    @csrf
                    <input type="hidden" id="storeId">
                    <input type="hidden" id="logo_data">
                    <div class="modal-body">
                        <div class="row g-3">

                            {{-- Logo upload --}}
                            <div class="col-12">
                                <label class="form-label fw-semibold">Logo Toko</label>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="logo-preview-wrap" style="width:110px;"
                                        onclick="document.getElementById('logoFile').click()">
                                        <img id="logoPreview" src="" alt="Preview" style="display:none;">
                                        <span id="logoPlaceholder" class="text-muted" style="font-size:12px;">
                                            <i class="bi bi-image fs-4 d-block mb-1"></i>Klik untuk pilih
                                        </span>
                                    </div>
                                    <div class="text-muted small">
                                        Format: JPG / PNG<br>
                                        Gambar akan di-crop menjadi <strong>1:1 (square)</strong>.<br>
                                        <button type="button" class="btn btn-link btn-sm p-0 mt-1 text-danger"
                                            id="btnRemoveLogo" onclick="removeLogo()" style="display:none;">
                                            <i class="bi bi-x-circle"></i> Hapus logo
                                        </button>
                                    </div>
                                </div>
                                <input type="file" id="logoFile" accept="image/*" style="display:none;">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Pilih Bisnis Induk <span class="text-danger">*</span></label>
                                <select class="form-select" id="business_id">
                                    <option value="new">Bisnis Utama (Buat Baru Sesuai Kode & Nama Toko)</option>
                                    @foreach($businesses as $biz)
                                        <option value="{{ $biz->id }}">{{ $biz->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="err-business_id"></div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Tipe Bisnis <span class="text-danger">*</span></label>
                                <select class="form-select" id="bussiness_type">
                                    <option value="retail">Retail</option>
                                    <option value="fnb">F&B</option>
                                </select>
                                <div class="invalid-feedback" id="err-bussiness_type"></div>
                            </div>
                            <div class="col-md-12" id="addonFields" style="display: none;">
                                <label class="form-label fw-semibold text-primary">Fitur Add-on (Khusus F&B)</label>
                                <div class="row g-2 p-2 border rounded-3 bg-light">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="addon_self_service">
                                            <label class="form-check-label fw-bold" for="addon_self_service">Customer Self-Service</label>
                                            <div class="text-muted small" style="font-size: 11px;">Pemesanan QR Meja mandiri pelanggan</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="addon_kds">
                                            <label class="form-check-label fw-bold" for="addon_kds">Kitchen Display System (KDS)</label>
                                            <div class="text-muted small" style="font-size: 11px;">Monitor antrean pesanan di dapur koki</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nama Toko <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" placeholder="Contoh: Toko Pusat">
                                <div class="invalid-feedback" id="err-name"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Kode Toko <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-uppercase" id="code"
                                    placeholder="Contoh: TST" maxlength="50">
                                <div class="invalid-feedback" id="err-code"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Kota</label>
                                <input type="text" class="form-control" id="city" placeholder="Contoh: Banda Aceh">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">No. Telepon</label>
                                <input type="text" class="form-control" id="phone" placeholder="Contoh: 0812-3456-7890">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Alamat</label>
                                <textarea class="form-control" id="address" rows="2"
                                    placeholder="Alamat lengkap toko"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Ukuran Printer <span class="text-danger">*</span></label>
                                <select class="form-select" id="printer_type">
                                    <option value="80mm">80mm</option>
                                    <option value="58mm">58mm</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check form-switch ms-1">
                                    <input class="form-check-input" type="checkbox" id="is_active" checked>
                                    <label class="form-check-label fw-semibold" for="is_active">Toko Aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSubmit">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ========== MODAL CROP ========== --}}
    <div class="modal fade" id="modalCrop" tabindex="-1" aria-labelledby="modalCropLabel"
        aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalCropLabel"><i class="bi bi-crop"></i> Crop Logo</h5>
                </div>
                <div class="modal-body">
                    <div id="cropContainer">
                        <img id="cropImage" src="" alt="Crop">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="btnCancelCrop">Batal</button>
                    <button type="button" class="btn btn-primary" id="btnApplyCrop">
                        <i class="bi bi-check-lg"></i> Gunakan
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div class="toast fade position-fixed top-0 end-0 m-3" id="notifyToast" role="status"
        aria-live="polite" aria-atomic="true" data-bs-autohide="true" data-bs-delay="3000" style="z-index:2000;">
        <div class="toast-header">
            <strong class="me-auto">{{ config('app.name') }}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body"></div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
    <script>
        const routes = {
            store: "{{ route('stores.store') }}",
            edit: (id) => `/stores/${id}/edit`,
            update: (id) => `/stores/${id}`,
            destroy: (id) => `/stores/${id}`,
            restore: (id) => `/stores/${id}/restore`,
        };

        // ── toast ────────────────────────────────────────────────────────────────
        function showToast(msg, success = true) {
            const el = document.getElementById('notifyToast');
            el.querySelector('.toast-body').innerText = msg;
            el.classList.toggle('text-bg-danger', !success);
            el.classList.toggle('text-bg-success', success);
            new bootstrap.Toast(el).show();
        }

        // ── validation ───────────────────────────────────────────────────────────
        function clearErrors() {
            document.querySelectorAll('#storeForm .form-control, #storeForm .form-select')
                .forEach(el => el.classList.remove('is-invalid'));
        }
        function showErrors(errors) {
            clearErrors();
            Object.entries(errors).forEach(([field, msgs]) => {
                const input = document.getElementById(field);
                const err = document.getElementById('err-' + field);
                if (input) input.classList.add('is-invalid');
                if (err) err.textContent = msgs[0];
            });
        }

        // ── logo helpers ─────────────────────────────────────────────────────────
        function setLogoPreview(src) {
            const img = document.getElementById('logoPreview');
            const ph  = document.getElementById('logoPlaceholder');
            const btn = document.getElementById('btnRemoveLogo');
            if (src) {
                img.src = src;
                img.style.display = 'block';
                ph.style.display  = 'none';
                btn.style.display = 'inline-block';
            } else {
                img.src = '';
                img.style.display = 'none';
                ph.style.display  = 'block';
                btn.style.display = 'none';
            }
        }
        function removeLogo() {
            document.getElementById('logo_data').value = '';
            document.getElementById('logoFile').value  = '';
            setLogoPreview(null);
        }

        // ── Cropper.js ───────────────────────────────────────────────────────────
        let cropper = null;

        document.getElementById('logoFile').addEventListener('change', function () {
            if (!this.files || !this.files[0]) return;
            const reader = new FileReader();
            reader.onload = function (e) {
                const cropImage = document.getElementById('cropImage');
                cropImage.src = e.target.result;
                if (cropper) { cropper.destroy(); cropper = null; }
                const cropModal = new bootstrap.Modal(document.getElementById('modalCrop'));
                document.getElementById('modalCrop').addEventListener('shown.bs.modal', function handler() {
                    cropper = new Cropper(cropImage, {
                        aspectRatio: 1, viewMode: 1, autoCropArea: 1, responsive: true,
                    });
                    document.getElementById('modalCrop').removeEventListener('shown.bs.modal', handler);
                });
                cropModal.show();
            };
            reader.readAsDataURL(this.files[0]);
        });

        document.getElementById('btnApplyCrop').addEventListener('click', function () {
            if (!cropper) return;
            const dataUrl = cropper.getCroppedCanvas({ width: 400, height: 400, imageSmoothingQuality: 'high' })
                                   .toDataURL('image/png');
            document.getElementById('logo_data').value = dataUrl;
            setLogoPreview(dataUrl);
            bootstrap.Modal.getInstance(document.getElementById('modalCrop')).hide();
            cropper.destroy(); cropper = null;
            document.getElementById('logoFile').value = '';
        });

        document.getElementById('btnCancelCrop').addEventListener('click', function () {
            bootstrap.Modal.getInstance(document.getElementById('modalCrop')).hide();
            if (cropper) { cropper.destroy(); cropper = null; }
            document.getElementById('logoFile').value = '';
        });

        // ── open create ──────────────────────────────────────────────────────────
        function openCreate() {
            document.getElementById('storeForm').reset();
            document.getElementById('storeId').value   = '';
            document.getElementById('logo_data').value = '';
            document.getElementById('is_active').checked = true;
            document.getElementById('business_id').selectedIndex = 0;
            document.getElementById('bussiness_type').value = 'retail';
            document.getElementById('addon_self_service').checked = false;
            document.getElementById('addon_kds').checked = false;
            document.getElementById('addonFields').style.display = 'none';
            document.getElementById('modalStoreLabel').textContent = 'Tambah Toko';
            setLogoPreview(null);
            clearErrors();
            new bootstrap.Modal(document.getElementById('modalStore')).show();
        }

        // ── open edit ────────────────────────────────────────────────────────────
        function openEdit(id) {
            fetch(routes.edit(id))
                .then(r => r.json())
                .then(data => {
                    document.getElementById('storeId').value      = data.id;
                    document.getElementById('name').value         = data.name;
                    document.getElementById('code').value         = data.code;
                    document.getElementById('city').value         = data.city ?? '';
                    document.getElementById('phone').value        = data.phone ?? '';
                    document.getElementById('address').value      = data.address ?? '';
                    document.getElementById('printer_type').value = data.printer_type ?? '80mm';
                    document.getElementById('is_active').checked  = data.is_active == 1;
                    document.getElementById('logo_data').value    = '';
                    document.getElementById('business_id').value  = data.business_id ?? '';
                    document.getElementById('bussiness_type').value = data.business_type ?? 'retail';
                    document.getElementById('addon_self_service').checked = data.addon_self_service == 1;
                    document.getElementById('addon_kds').checked = data.addon_kds == 1;
                    document.getElementById('addonFields').style.display = data.business_type === 'fnb' ? 'block' : 'none';
                    setLogoPreview(data.logo_url ?? null);
                    document.getElementById('modalStoreLabel').textContent = 'Edit Toko';
                    clearErrors();
                    new bootstrap.Modal(document.getElementById('modalStore')).show();
                });
        }

        // ── submit form ──────────────────────────────────────────────────────────
        document.getElementById('storeForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const id     = document.getElementById('storeId').value;
            const isEdit = !!id;
            const payload = {
                business_id:   document.getElementById('business_id').value,
                name:          document.getElementById('name').value,
                code:          document.getElementById('code').value,
                city:          document.getElementById('city').value,
                phone:         document.getElementById('phone').value,
                address:       document.getElementById('address').value,
                printer_type:  document.getElementById('printer_type').value,
                is_active:     document.getElementById('is_active').checked ? 1 : 0,
                logo_data:     document.getElementById('logo_data').value || null,
                bussiness_type:document.getElementById('bussiness_type').value,
                addon_self_service: document.getElementById('addon_self_service').checked ? 1 : 0,
                addon_kds:          document.getElementById('addon_kds').checked ? 1 : 0,
                _token:        '{{ csrf_token() }}',
            };
            if (isEdit) payload._method = 'PUT';

            const btn = document.getElementById('btnSubmit');
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
                    else showToast(data.message ?? 'Terjadi kesalahan.', false);
                    return;
                }
                bootstrap.Modal.getInstance(document.getElementById('modalStore')).hide();
                showToast(data.message);
                setTimeout(() => location.reload(), 800);
            })
            .catch(() => {
                btn.disabled = false; btn.textContent = 'Simpan';
                showToast('Terjadi kesalahan jaringan.', false);
            });
        });

        // ── delete (soft) ────────────────────────────────────────────────────────
        function deleteStore(id, name) {
            if (!confirm(`Hapus toko "${name}"?`)) return;
            fetch(routes.destroy(id), {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ _token: '{{ csrf_token() }}' }),
            })
            .then(r => r.json())
            .then(data => {
                showToast(data.message, data.success);
                if (data.success) setTimeout(() => location.reload(), 800);
            });
        }

        // ── restore ──────────────────────────────────────────────────────────────
        function restoreStore(id, name) {
            if (!confirm(`Pulihkan toko "${name}"?`)) return;
            fetch(routes.restore(id), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ _token: '{{ csrf_token() }}' }),
            })
            .then(r => r.json())
            .then(data => {
                showToast(data.message, data.success);
                if (data.success) setTimeout(() => location.reload(), 800);
            });
        }

        // ── business type change listener ────────────────────────────────────────
        document.getElementById('bussiness_type').addEventListener('change', function() {
            const isFnB = this.value === 'fnb';
            document.getElementById('addonFields').style.display = isFnB ? 'block' : 'none';
            if (!isFnB) {
                document.getElementById('addon_self_service').checked = false;
                document.getElementById('addon_kds').checked = false;
            }
        });
    </script>
@endpush
