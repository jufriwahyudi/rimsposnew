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
        .onboarding-card {
            border: 1px solid #c4b5fd;
            background-color: #f5f3ff;
            border-radius: 12px;
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
                            <small class="text-muted">Kelola data toko, onboarding cabang baru, dan konfigurasi printer</small>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="openCreate()">
                        <i class="fa fa-plus"></i> Tambah Toko Baru
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
                                            <th>User & Rekening</th>
                                            <th>Tipe Bisnis</th>
                                            <th>Printer</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center" width="15%">Aksi</th>
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
                                                <td>
                                                    <div class="d-flex flex-column gap-1" style="font-size: 11px;">
                                                        <span class="badge bg-light text-dark border text-start">
                                                            <i class="bi bi-people text-primary me-1"></i> {{ $store->users_count }} Pengguna
                                                        </span>
                                                        <span class="badge bg-light text-dark border text-start">
                                                            <i class="bi bi-credit-card text-success me-1"></i> {{ $store->rekenings->count() }} Rek. Kas
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ $store->business_type === 'fnb' ? 'warning text-dark' : 'info' }} d-block mb-1">
                                                        {{ $store->business_type === 'fnb' ? 'F&B' : 'Retail' }}
                                                    </span>
                                                    <div style="font-size: 10px;" class="mt-1 d-flex flex-column gap-1">
                                                        @if($store->enable_cash_register)
                                                            <span class="badge bg-purple text-white" style="background-color: #7c3aed; font-size: 9px;">
                                                                <i class="bi bi-clock-history"></i> Buka/Tutup Kasir: Wajib
                                                            </span>
                                                        @endif
                                                        @if($store->business_type === 'fnb')
                                                            <span class="badge bg-{{ $store->addon_self_service ? 'success' : 'secondary' }}" style="font-size: 9px;">
                                                                Self-Service: {{ $store->addon_self_service ? 'Aktif' : 'Non-aktif' }}
                                                            </span>
                                                            <span class="badge bg-{{ $store->addon_kds ? 'success' : 'secondary' }}" style="font-size: 9px;">
                                                                KDS: {{ $store->addon_kds ? 'Aktif' : 'Non-aktif' }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ $store->printer_type === '58mm' ? 'info' : ($store->printer_type === 'pdf' ? 'warning text-dark' : 'primary') }}">
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
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-info"
                                                            onclick="openStoreSummary({{ $store->id }})" title="Quick Hub / Ringkasan Toko">
                                                            <i class="bi bi-speedometer2"></i> Kelola
                                                        </button>
                                                        <button class="btn btn-outline-primary"
                                                            onclick="openEdit({{ $store->id }})" title="Edit Toko">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger"
                                                            onclick="deleteStore({{ $store->id }}, '{{ addslashes($store->name) }}')" title="Hapus">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="10" class="text-center text-muted py-4">Belum ada data toko.</td>
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

    {{-- ========== MODAL FORM TOKO ========== --}}
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
                                    <option value="pdf">PDF A4</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check form-switch ms-1">
                                    <input class="form-check-input" type="checkbox" id="is_active" checked>
                                    <label class="form-check-label fw-semibold" for="is_active">Toko Aktif</label>
                                </div>
                            </div>
                            <div class="col-12 mt-2">
                                <div class="p-2 border rounded-3 bg-light">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="enable_cash_register">
                                        <label class="form-check-label fw-bold text-dark" for="enable_cash_register">
                                            <i class="bi bi-clock-history text-primary me-1"></i> Wajib Buka/Tutup Kasir (Shift & Rekonsiliasi Kas)
                                        </label>
                                        <div class="text-muted small" style="font-size: 11px;">
                                            Jika diaktifkan, kasir wajib input modal kas awal (Buka Kasir) sebelum transaksi dan wajib rekonsiliasi kas fisik sebelum menutup sesi kasir.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- ========== SECTION ONBOARDING TOKO BARU (Hanya saat Tambah Toko) ========== --}}
                            <div class="col-12" id="onboardingSection">
                                <div class="onboarding-card p-3 mt-2">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-lightning-charge-fill fs-5 text-warning me-2"></i>
                                        <div>
                                            <h6 class="fw-bold mb-0 text-primary">Setup Otomatis & Onboarding Toko Baru</h6>
                                            <small class="text-muted">Mempercepat implementasi cabang baru dalam sekali simpan</small>
                                        </div>
                                    </div>

                                    <div class="row g-2 mt-1">
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="create_rekening" checked>
                                                <label class="form-check-label fw-semibold" for="create_rekening">
                                                    Auto-Create Rekening Kas Utama
                                                </label>
                                                <div class="text-muted" style="font-size: 11px;">Akun kas (1001 - Kas Toko) untuk drawer kasir</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6" id="onboardTenantWrap" style="display:none;">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="create_default_tenant" checked>
                                                <label class="form-check-label fw-semibold" for="create_default_tenant">
                                                    Auto-Create Tenant / Dapur Utama
                                                </label>
                                                <div class="text-muted" style="font-size: 11px;">Khusus FnB (Dapur Utama / Tenant 1)</div>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-3 text-muted opacity-25">

                                    {{-- Toggle Buat User Pertama --}}
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="create_user">
                                        <label class="form-check-label fw-bold text-dark" for="create_user">
                                            <i class="bi bi-person-plus-fill text-primary me-1"></i> Buat Akun Pengguna / Kasir Pertama Sekaligus
                                        </label>
                                    </div>

                                    {{-- Form User Onboarding (Collapsible) --}}
                                    <div id="userOnboardingFields" style="display:none;" class="p-3 border rounded-3 bg-white mt-2">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold small">Nama User <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-sm" id="user_name" placeholder="Nama Kasir / Admin">
                                                <div class="invalid-feedback" id="err-user_name"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold small">Email User <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control form-control-sm" id="user_email" placeholder="email@toko.com">
                                                <div class="invalid-feedback" id="err-user_email"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold small">Password <span class="text-danger">*</span></label>
                                                <input type="password" class="form-control form-control-sm" id="user_password" placeholder="Min. 8 karakter">
                                                <div class="invalid-feedback" id="err-user_password"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold small">Pilihan Role <span class="text-danger">*</span></label>
                                                <select class="form-select form-control-sm" id="user_role_mode" onchange="toggleRoleMode()">
                                                    <option value="existing">Pilih Dari Role yang Sudah Ada</option>
                                                    <option value="new">+ Buat Role Baru untuk Toko Ini</option>
                                                </select>
                                            </div>

                                            {{-- Role Existing --}}
                                            <div class="col-12" id="wrapExistingRole">
                                                <label class="form-label fw-semibold small">Pilih Role</label>
                                                <select class="form-select form-control-sm" id="existing_role_id">
                                                    @foreach($roles as $r)
                                                        <option value="{{ $r->id }}">
                                                            {{ $r->nama }} ({{ $r->role_type }}) {{ $r->store ? '[' . $r->store->name . ']' : '[Global]' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <div class="invalid-feedback" id="err-existing_role_id"></div>
                                            </div>

                                            {{-- Role New --}}
                                            <div class="col-12" id="wrapNewRole" style="display:none;">
                                                <div class="row g-2 p-2 border rounded bg-light">
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold small">Nama Role Baru</label>
                                                        <input type="text" class="form-control form-control-sm" id="new_role_name" placeholder="Contoh: Kasir Toko">
                                                        <div class="invalid-feedback" id="err-new_role_name"></div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold small">Jenis Role</label>
                                                        <select class="form-select form-control-sm" id="new_role_type">
                                                            <option value="STORE">STORE (Kasir / Toko)</option>
                                                            <option value="ADMIN">ADMIN (Admin Toko)</option>
                                                            <option value="WAREHOUSE">WAREHOUSE (Gudang)</option>
                                                            <option value="STELLING">STELLING (Tenant / Dapur)</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold small">Preset Hak Akses Menu</label>
                                                        <select class="form-select form-control-sm" id="menu_preset">
                                                            <option value="cashier">Kasir Standar (POS, Data Penjualan, Shift Kasir)</option>
                                                            <option value="admin_store">Admin Toko Lengkap (POS, Produk, Stok, Laporan)</option>
                                                            <option value="warehouse">Gudang (PO, Stock Opname, Bahan Baku)</option>
                                                            <option value="kitchen">Dapur (Bahan Baku & Resep)</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold small">Atau Salin Dari Role Lain</label>
                                                        <select class="form-select form-control-sm" id="copy_role_from">
                                                            <option value="">-- Gunakan Preset Menu di Samping --</option>
                                                            @foreach($roles as $r)
                                                                <option value="{{ $r->id }}">
                                                                    Salin dari: {{ $r->nama }} {{ $r->store ? '[' . $r->store->name . ']' : '[Global]' }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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

    {{-- ========== MODAL QUICK STORE HUB (SUMMARY & DETAIL) ========== --}}
    <div class="modal fade" id="modalStoreSummary" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content rounded-4">
                <div class="modal-header border-bottom bg-light">
                    <div>
                        <h5 class="modal-title fw-bold" id="summaryStoreTitle">Ringkasan Toko</h5>
                        <div class="text-muted small" id="summaryStoreSubtitle">-</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-3 col-6">
                            <div class="p-3 border rounded-3 bg-light text-center">
                                <div class="text-muted small">Total Pengguna</div>
                                <h3 class="fw-bold mb-0 text-primary" id="sumCountUsers">0</h3>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="p-3 border rounded-3 bg-light text-center">
                                <div class="text-muted small">Role Toko</div>
                                <h3 class="fw-bold mb-0 text-info" id="sumCountRoles">0</h3>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="p-3 border rounded-3 bg-light text-center">
                                <div class="text-muted small">Rekening Kas/Bank</div>
                                <h3 class="fw-bold mb-0 text-success" id="sumCountRekenings">0</h3>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="p-3 border rounded-3 bg-light text-center">
                                <div class="text-muted small">Tenant / Stelling</div>
                                <h3 class="fw-bold mb-0 text-warning" id="sumCountTenants">0</h3>
                            </div>
                        </div>
                    </div>

                    {{-- Nav Tabs Hub --}}
                    <ul class="nav nav-pills mb-3" id="hubTabs">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#hubTabUsers">
                                <i class="bi bi-people me-1"></i> Pengguna (Users)
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#hubTabRoles">
                                <i class="bi bi-shield-lock me-1"></i> Role & Hak Akses
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#hubTabRekenings">
                                <i class="bi bi-credit-card me-1"></i> Rekening Kas
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#hubTabTenants">
                                <i class="bi bi-shop me-1"></i> Tenant FnB
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        {{-- Tab Users --}}
                        <div class="tab-pane fade show active" id="hubTabUsers">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold mb-0">Daftar Pengguna Toko Ini</h6>
                                <button class="btn btn-sm btn-primary" onclick="openQuickAddUser()">
                                    <i class="bi bi-person-plus"></i> Tambah User Cepat
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nama</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Tenant</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableHubUsers"></tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Tab Roles --}}
                        <div class="tab-pane fade" id="hubTabRoles">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold mb-0">Role Khusus & Global</h6>
                                <button class="btn btn-sm btn-info text-white" onclick="openQuickAddRole()">
                                    <i class="bi bi-plus-circle"></i> Buat Role Baru
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nama Role</th>
                                            <th>Tipe Role</th>
                                            <th>Cakupan</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableHubRoles"></tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Tab Rekenings --}}
                        <div class="tab-pane fade" id="hubTabRekenings">
                            <h6 class="fw-bold mb-2">Rekening Kas & Pembayaran Toko</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>No. Rekening / Kode</th>
                                            <th>Nama Akun Rekening</th>
                                            <th>Tipe / Bank</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableHubRekenings"></tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Tab Tenants --}}
                        <div class="tab-pane fade" id="hubTabTenants">
                            <h6 class="fw-bold mb-2">Daftar Tenant / Dapur Toko</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Kode</th>
                                            <th>Nama Tenant</th>
                                            <th>Komisi (%)</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableHubTenants"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== MODAL QUICK ADD USER ========== --}}
    <div class="modal fade" id="modalQuickUser" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Tambah User ke Toko</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="quickUserForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label fw-semibold small">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="qu_name" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold small">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control form-control-sm" id="qu_email" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold small">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control form-control-sm" id="qu_password" required placeholder="Min. 8 karakter">
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold small">Role</label>
                            <select class="form-select form-control-sm" id="qu_role_id">
                                <option value="">-- Pilih Role --</option>
                                @foreach($roles as $r)
                                    <option value="{{ $r->id }}">{{ $r->nama }} ({{ $r->role_type }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2" id="qu_tenant_wrap" style="display:none;">
                            <label class="form-label fw-semibold small">Tenant (Khusus Akun Tenant)</label>
                            <select class="form-select form-control-sm" id="qu_tenant_id">
                                <option value="">-- Bukan Tenant --</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary" id="btnSubmitQuickUser">Simpan User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ========== MODAL QUICK ADD ROLE ========== --}}
    <div class="modal fade" id="modalQuickRole" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Buat Role untuk Toko Ini</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="quickRoleForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label fw-semibold small">Nama Role <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="qr_nama_role" placeholder="Contoh: Kasir Shift Pagi" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold small">Jenis Role <span class="text-danger">*</span></label>
                            <select class="form-select form-control-sm" id="qr_jenis_role" required>
                                <option value="STORE">STORE (Kasir / Toko)</option>
                                <option value="ADMIN">ADMIN (Admin Toko)</option>
                                <option value="WAREHOUSE">WAREHOUSE (Gudang)</option>
                                <option value="STELLING">STELLING (Tenant / Dapur)</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold small">Preset Hak Akses Menu</label>
                            <select class="form-select form-control-sm" id="qr_menu_preset">
                                <option value="cashier">Kasir Standar (POS, Data Penjualan, Shift Kasir)</option>
                                <option value="admin_store">Admin Toko Lengkap (POS, Produk, Stok, Laporan)</option>
                                <option value="warehouse">Gudang (PO, Stock Opname, Bahan Baku)</option>
                                <option value="kitchen">Dapur (Bahan Baku & Resep)</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold small">Atau Salin Dari Role Lain</label>
                            <select class="form-select form-control-sm" id="qr_copy_role_from">
                                <option value="">-- Gunakan Preset Menu di Atas --</option>
                                @foreach($roles as $r)
                                    <option value="{{ $r->id }}">
                                        Salin dari: {{ $r->nama }} {{ $r->store ? '[' . $r->store->name . ']' : '[Global]' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-info text-white" id="btnSubmitQuickRole">Buat Role</button>
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
        let currentActiveStoreId = null;
        let currentActiveStoreData = null;

        const routes = {
            store: "{{ route('stores.store') }}",
            edit: (id) => `/stores/${id}/edit`,
            update: (id) => `/stores/${id}`,
            destroy: (id) => `/stores/${id}`,
            restore: (id) => `/stores/${id}/restore`,
            summary: (id) => `/stores/${id}/summary`,
            quickUser: (id) => `/stores/${id}/quick-user`,
            quickRole: (id) => `/stores/${id}/quick-role`,
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
            document.getElementById('enable_cash_register').checked = false;
            document.getElementById('business_id').selectedIndex = 0;
            document.getElementById('bussiness_type').value = 'retail';
            document.getElementById('addon_self_service').checked = false;
            document.getElementById('addon_kds').checked = false;
            document.getElementById('addonFields').style.display = 'none';

            // Onboarding defaults
            document.getElementById('onboardingSection').style.display = 'block';
            document.getElementById('create_rekening').checked = true;
            document.getElementById('create_default_tenant').checked = true;
            document.getElementById('onboardTenantWrap').style.display = 'none';
            document.getElementById('create_user').checked = false;
            document.getElementById('userOnboardingFields').style.display = 'none';
            document.getElementById('user_role_mode').value = 'existing';
            toggleRoleMode();

            document.getElementById('modalStoreLabel').textContent = 'Tambah Toko Baru (Onboarding)';
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
                    document.getElementById('enable_cash_register').checked = data.enable_cash_register == 1;
                    document.getElementById('logo_data').value    = '';
                    document.getElementById('business_id').value  = data.business_id ?? '';
                    document.getElementById('bussiness_type').value = data.business_type ?? 'retail';
                    document.getElementById('addon_self_service').checked = data.addon_self_service == 1;
                    document.getElementById('addon_kds').checked = data.addon_kds == 1;
                    document.getElementById('addonFields').style.display = data.business_type === 'fnb' ? 'block' : 'none';
                    
                    // Hide onboarding when editing
                    document.getElementById('onboardingSection').style.display = 'none';

                    setLogoPreview(data.logo_url ?? null);
                    document.getElementById('modalStoreLabel').textContent = 'Edit Toko';
                    clearErrors();
                    new bootstrap.Modal(document.getElementById('modalStore')).show();
                });
        }

        // ── toggle role mode ─────────────────────────────────────────────────────
        function toggleRoleMode() {
            const mode = document.getElementById('user_role_mode').value;
            document.getElementById('wrapExistingRole').style.display = mode === 'existing' ? 'block' : 'none';
            document.getElementById('wrapNewRole').style.display = mode === 'new' ? 'block' : 'none';
        }

        document.getElementById('create_user').addEventListener('change', function() {
            document.getElementById('userOnboardingFields').style.display = this.checked ? 'block' : 'none';
        });

        // ── submit form ──────────────────────────────────────────────────────────
        document.getElementById('storeForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const id     = document.getElementById('storeId').value;
            const isEdit = !!id;
            const payload = {
                business_id:          document.getElementById('business_id').value,
                name:                 document.getElementById('name').value,
                code:                 document.getElementById('code').value,
                city:                 document.getElementById('city').value,
                phone:                document.getElementById('phone').value,
                address:              document.getElementById('address').value,
                printer_type:         document.getElementById('printer_type').value,
                is_active:            document.getElementById('is_active').checked ? 1 : 0,
                enable_cash_register: document.getElementById('enable_cash_register').checked ? 1 : 0,
                logo_data:            document.getElementById('logo_data').value || null,
                bussiness_type:       document.getElementById('bussiness_type').value,
                addon_self_service:   document.getElementById('addon_self_service').checked ? 1 : 0,
                addon_kds:             document.getElementById('addon_kds').checked ? 1 : 0,
                _token:               '{{ csrf_token() }}',
            };

            if (!isEdit) {
                payload.create_rekening       = document.getElementById('create_rekening').checked ? 1 : 0;
                payload.create_default_tenant = document.getElementById('create_default_tenant').checked ? 1 : 0;
                payload.create_user           = document.getElementById('create_user').checked ? 1 : 0;

                if (payload.create_user) {
                    payload.user_name        = document.getElementById('user_name').value;
                    payload.user_email       = document.getElementById('user_email').value;
                    payload.user_password    = document.getElementById('user_password').value;
                    payload.user_role_mode   = document.getElementById('user_role_mode').value;
                    payload.existing_role_id = document.getElementById('existing_role_id').value;
                    payload.new_role_name    = document.getElementById('new_role_name').value;
                    payload.new_role_type    = document.getElementById('new_role_type').value;
                    payload.copy_role_from   = document.getElementById('copy_role_from').value;
                    payload.menu_preset      = document.getElementById('menu_preset').value;
                }
            }

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
            document.getElementById('onboardTenantWrap').style.display = isFnB ? 'block' : 'none';
            if (!isFnB) {
                document.getElementById('addon_self_service').checked = false;
                document.getElementById('addon_kds').checked = false;
            }
        });

        // ─── QUICK STORE SUMMARY & HUB ──────────────────────────────────────────
        function openStoreSummary(id) {
            currentActiveStoreId = id;
            fetch(routes.summary(id))
                .then(r => r.json())
                .then(data => {
                    currentActiveStoreData = data;
                    const st = data.store;
                    document.getElementById('summaryStoreTitle').textContent = `Kelola: ${st.name} (${st.code})`;
                    document.getElementById('summaryStoreSubtitle').textContent = `${st.business_type === 'fnb' ? 'F&B Foodcourt / Resto' : 'Retail'} | ${st.city ?? 'Kota -'} | ${st.address ?? 'Alamat -'}`;

                    document.getElementById('sumCountUsers').textContent = data.users.length;
                    document.getElementById('sumCountRoles').textContent = data.roles.length;
                    document.getElementById('sumCountRekenings').textContent = data.rekenings.length;
                    document.getElementById('sumCountTenants').textContent = data.tenants.length;

                    // Populate Users Table
                    const tUsers = document.getElementById('tableHubUsers');
                    tUsers.innerHTML = '';
                    if (data.users.length === 0) {
                        tUsers.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Belum ada pengguna untuk toko ini.</td></tr>';
                    } else {
                        data.users.forEach(u => {
                            const rName = u.roles && u.roles[0] && u.roles[0].roles ? u.roles[0].roles.nama : '-';
                            const tName = u.tenant ? `<span class="badge bg-info text-dark">${u.tenant.nama_tenant}</span>` : '-';
                            tUsers.innerHTML += `
                                <tr>
                                    <td><strong>${u.name}</strong></td>
                                    <td>${u.email}</td>
                                    <td><span class="badge bg-primary">${rName}</span></td>
                                    <td>${tName}</td>
                                </tr>
                            `;
                        });
                    }

                    // Populate Roles Table
                    const tRoles = document.getElementById('tableHubRoles');
                    tRoles.innerHTML = '';
                    if (data.roles.length === 0) {
                        tRoles.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Belum ada role.</td></tr>';
                    } else {
                        data.roles.forEach(r => {
                            const isSpecific = r.store_id == id;
                            tRoles.innerHTML += `
                                <tr>
                                    <td><strong>${r.nama}</strong></td>
                                    <td><span class="badge bg-secondary">${r.role_type}</span></td>
                                    <td>${isSpecific ? '<span class="badge bg-success">Khusus Toko Ini</span>' : '<span class="badge bg-light text-dark border">Global</span>'}</td>
                                </tr>
                            `;
                        });
                    }

                    // Populate Rekenings Table
                    const tReks = document.getElementById('tableHubRekenings');
                    tReks.innerHTML = '';
                    if (data.rekenings.length === 0) {
                        tReks.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Belum ada rekening kas.</td></tr>';
                    } else {
                        data.rekenings.forEach(rk => {
                            tReks.innerHTML += `
                                <tr>
                                    <td><code>${rk.no_rek}</code></td>
                                    <td><strong>${rk.nama_rek}</strong></td>
                                    <td><span class="badge bg-success">${rk.bank_rek ?? 'KAS'}</span></td>
                                </tr>
                            `;
                        });
                    }

                    // Populate Tenants Table
                    const tTenants = document.getElementById('tableHubTenants');
                    tTenants.innerHTML = '';
                    if (data.tenants.length === 0) {
                        tTenants.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Belum ada tenant / stelling.</td></tr>';
                    } else {
                        data.tenants.forEach(tn => {
                            tTenants.innerHTML += `
                                <tr>
                                    <td><code>${tn.kode_tenant}</code></td>
                                    <td><strong>${tn.nama_tenant}</strong></td>
                                    <td>${tn.commission_rate ?? 0}%</td>
                                    <td><span class="badge bg-success">${tn.stts == 'Y' ? 'Aktif' : 'Non-aktif'}</span></td>
                                </tr>
                            `;
                        });
                    }

                    new bootstrap.Modal(document.getElementById('modalStoreSummary')).show();
                });
        }

        // ── Quick Add User Modal ─────────────────────────────────────────────────
        function openQuickAddUser() {
            document.getElementById('quickUserForm').reset();
            const qWrap = document.getElementById('qu_tenant_wrap');
            const qTenant = document.getElementById('qu_tenant_id');
            qTenant.innerHTML = '<option value="">-- Bukan Tenant --</option>';

            if (currentActiveStoreData && currentActiveStoreData.tenants && currentActiveStoreData.tenants.length > 0) {
                qWrap.style.display = 'block';
                currentActiveStoreData.tenants.forEach(tn => {
                    qTenant.innerHTML += `<option value="${tn.id}">${tn.nama_tenant} (${tn.kode_tenant})</option>`;
                });
            } else {
                qWrap.style.display = 'none';
            }

            new bootstrap.Modal(document.getElementById('modalQuickUser')).show();
        }

        document.getElementById('quickUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            if (!currentActiveStoreId) return;

            const btn = document.getElementById('btnSubmitQuickUser');
            btn.disabled = true; btn.textContent = 'Menyimpan...';

            const payload = {
                name: document.getElementById('qu_name').value,
                email: document.getElementById('qu_email').value,
                password: document.getElementById('qu_password').value,
                role_id: document.getElementById('qu_role_id').value || null,
                tenant_id: document.getElementById('qu_tenant_id').value || null,
                _token: '{{ csrf_token() }}',
            };

            fetch(routes.quickUser(currentActiveStoreId), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify(payload),
            })
            .then(async r => {
                const data = await r.json();
                btn.disabled = false; btn.textContent = 'Simpan User';
                if (!r.ok) {
                    showToast(data.message ?? 'Gagal menambahkan user.', false);
                    return;
                }
                bootstrap.Modal.getInstance(document.getElementById('modalQuickUser')).hide();
                showToast(data.message);
                openStoreSummary(currentActiveStoreId); // Refresh hub
            })
            .catch(() => {
                btn.disabled = false; btn.textContent = 'Simpan User';
                showToast('Terjadi kesalahan jaringan.', false);
            });
        });

        // ── Quick Add Role Modal ─────────────────────────────────────────────────
        function openQuickAddRole() {
            document.getElementById('quickRoleForm').reset();
            new bootstrap.Modal(document.getElementById('modalQuickRole')).show();
        }

        document.getElementById('quickRoleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            if (!currentActiveStoreId) return;

            const btn = document.getElementById('btnSubmitQuickRole');
            btn.disabled = true; btn.textContent = 'Membuat Role...';

            const payload = {
                nama_role: document.getElementById('qr_nama_role').value,
                jenis_role: document.getElementById('qr_jenis_role').value,
                menu_preset: document.getElementById('qr_menu_preset').value,
                copy_role_from: document.getElementById('qr_copy_role_from').value || null,
                _token: '{{ csrf_token() }}',
            };

            fetch(routes.quickRole(currentActiveStoreId), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify(payload),
            })
            .then(async r => {
                const data = await r.json();
                btn.disabled = false; btn.textContent = 'Buat Role';
                if (!r.ok) {
                    showToast(data.message ?? 'Gagal membuat role.', false);
                    return;
                }
                bootstrap.Modal.getInstance(document.getElementById('modalQuickRole')).hide();
                showToast(data.message);
                openStoreSummary(currentActiveStoreId); // Refresh hub
            })
            .catch(() => {
                btn.disabled = false; btn.textContent = 'Buat Role';
                showToast('Terjadi kesalahan jaringan.', false);
            });
        });
    </script>
@endpush
