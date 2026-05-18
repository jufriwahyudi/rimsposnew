@extends('layouts.main.main')
@section('title', 'Manage User')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Pengaturan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active">Manage User</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card rounded-4 p-2">
                <div class="card-header d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-start">
                        <img src="{{ asset('assets/images/alazca_logo.png') }}" alt="Logo"
                            style="width:35px;height:35px;" class="me-2 mt-1">
                        <div>
                            <h5 class="fw-bold mb-0" style="color:#7c3aed">Manage User</h5>
                            <small class="text-muted">Kelola akun pengguna dan akses toko</small>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalUser"
                        onclick="resetForm()">
                        <i class="fa fa-plus"></i> Tambah User
                    </button>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped align-middle" id="user-table">
                            <thead>
                                <tr>
                                    <th width="4%">No</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Toko</th>
                                    <th width="10%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $i => $user)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>
                                            @if ($user->roles->first()?->roles)
                                                <span class="badge bg-primary">
                                                    {{ $user->roles->first()->roles->nama }}
                                                </span>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @forelse ($user->stores as $store)
                                                <span class="badge bg-success me-1">{{ $store->name }}</span>
                                            @empty
                                                <span class="text-muted small">-</span>
                                            @endforelse
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-warning me-1"
                                                onclick="editUser({{ $user->id }})" title="Edit">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger"
                                                onclick="deleteUser({{ $user->id }}, '{{ addslashes($user->name) }}')"
                                                title="Hapus">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Belum ada data user.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah / Edit User --}}
    <div class="modal fade" id="modalUser" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalUserTitle">Tambah User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="userForm">
                    @csrf
                    <input type="hidden" id="userId" name="_user_id">
                    <input type="hidden" id="formMethod" name="_method" value="POST">

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nama <span class="text-danger">*</span></label>
                                <input type="text" id="name" name="name" class="form-control"
                                    placeholder="Nama lengkap" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                                <input type="email" id="email" name="email" class="form-control"
                                    placeholder="email@example.com" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Password
                                    <small class="text-muted" id="passwordHint">(kosongkan jika tidak diubah)</small>
                                </label>
                                <input type="password" id="password" name="password" class="form-control"
                                    placeholder="Min. 8 karakter">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Role</label>
                                <select id="role_id" name="role_id" class="form-select">
                                    <option value="">-- Pilih Role --</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}">{{ $role->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Akses Toko</label>
                                <div class="row g-2" id="storeCheckboxes">
                                    @foreach ($stores as $store)
                                        <div class="col-md-4 col-6">
                                            <div class="form-check">
                                                <input class="form-check-input store-check" type="checkbox"
                                                    name="store_ids[]" value="{{ $store->id }}"
                                                    id="store_{{ $store->id }}">
                                                <label class="form-check-label" for="store_{{ $store->id }}">
                                                    {{ $store->name }}
                                                    <span class="text-muted small">({{ $store->code }})</span>
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @if ($stores->isEmpty())
                                    <small class="text-muted">Belum ada toko aktif. Tambahkan toko terlebih dahulu.</small>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSave">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Konfirmasi Hapus --}}
    <div class="modal fade" id="modalDelete" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Konfirmasi Hapus</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Hapus user <strong id="deleteUserName"></strong>?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger btn-sm" id="btnConfirmDelete">Hapus</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let deleteUserId = null;

        function resetForm() {
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('formMethod').value = 'POST';
            document.getElementById('modalUserTitle').textContent = 'Tambah User';
            document.getElementById('passwordHint').classList.add('d-none');
            document.getElementById('password').setAttribute('required', 'required');
            document.querySelectorAll('.store-check').forEach(c => c.checked = false);
        }

        function editUser(id) {
            fetch(`/manage-users/${id}/edit`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(r => r.json())
                .then(data => {
                    document.getElementById('userId').value = data.id;
                    document.getElementById('formMethod').value = 'PUT';
                    document.getElementById('name').value = data.name;
                    document.getElementById('email').value = data.email;
                    document.getElementById('password').value = '';
                    document.getElementById('password').removeAttribute('required');
                    document.getElementById('role_id').value = data.role_id ?? '';
                    document.getElementById('modalUserTitle').textContent = 'Edit User';
                    document.getElementById('passwordHint').classList.remove('d-none');

                    document.querySelectorAll('.store-check').forEach(c => {
                        c.checked = data.store_ids.includes(parseInt(c.value));
                    });

                    new bootstrap.Modal(document.getElementById('modalUser')).show();
                });
        }

        function deleteUser(id, name) {
            deleteUserId = id;
            document.getElementById('deleteUserName').textContent = name;
            new bootstrap.Modal(document.getElementById('modalDelete')).show();
        }

        document.getElementById('btnConfirmDelete').addEventListener('click', function() {
            fetch(`/manage-users/${deleteUserId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) location.reload();
                });
        });

        document.getElementById('userForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('userId').value;
            const method = document.getElementById('formMethod').value;
            const url = id ? `/manage-users/${id}` : '/manage-users';
            const formData = new FormData(this);
            if (method === 'PUT') formData.append('_method', 'PUT');

            fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('modalUser')).hide();
                        location.reload();
                    } else {
                        alert(data.message ?? 'Terjadi kesalahan.');
                    }
                });
        });
    </script>
@endpush
