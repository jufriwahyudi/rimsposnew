@extends('layouts.main.main')
@section('title', 'Manajemen Pengguna')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Manajemen Pengguna</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Daftar Role</a></li>
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
                            <h5 class="fw-bold mb-0" style="color: #7c3aed">Pengaturan Menu</h5>
                            <small class="text-muted">Al-Azhar Cairo Banda Aceh</small>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                        data-bs-target="#addRole"><i class="fa fa-plus"></i> Tambah Role</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="role-table">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Nama Role</th>
                                    <th width="12%" class="text-center">Jenis</th>
                                    <th width="10%" class="text-end">User</th>
                                    <th width="10%" class="text-end">Menu</th>
                                    <th width="10%" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roles as $index => $row)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $row->nama }}</td>
                                        <td class="text-center">{{ $row->role_type }}</td>
                                        <td class="text-end">{{ $row->users->count() }}</td>
                                        <td class="text-end">{{ $row->menus->count() }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('role.show', Crypt::encryptString($row->id)) }}"
                                                title="Melihat detail dari role"><i class="fa fa-search"></i></a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">Data tidak ditemukan</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="addRole" tabindex="-1" role="dialog" aria-labelledby="addRoleLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addRoleLabel">Tambah Role</h5>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addRoleForm">
                        @csrf
                        <div class="mb-3">
                            <label for="namaRole" class="form-label">Nama Role</label>
                            <input type="text" class="form-control" id="namaRole" name="nama_role"
                                placeholder="Masukkan nama role" maxlength="190" required>
                        </div>
                        <div class="mb-3">
                            <label for="jenisRole" class="form-label">Jenis Role</label>
                            <select class="form-control" id="jenisRole" name="jenis_role">
                                <option value="ADMIN">Admin</option>
                                <option value="WAREHOUSE">Warehouse</option>
                                <option value="STORE">Store</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="button" data-bs-dismiss="modal">Tutup</button>
                    <button class="btn btn-secondary" type="button" onclick="submitRole()">Simpan</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function submitRole() {
            // Ambil CSRF token dari form
            let csrfToken = document.querySelector('input[name="_token"]').value;

            // Ambil data dari form
            let formData = new FormData(document.getElementById('addRoleForm'));
            let namaRole = formData.get('nama_role');

            // Pastikan form field tidak kosong
            if (!namaRole) {
                Swal.fire({
                    icon: 'error',
                    title: 'Peringatan',
                    text: 'Nama role tidak boleh kosong!',
                });
                return;
            }
            Swal.fire({
                title: 'Loading...',
                text: 'Sedang menyimpan data, mohon tunggu...',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading(); // Tampilkan animasi loading
                }
            });
            // Kirim data menggunakan AJAX
            fetch("{{ route('role.store') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken, // Masukkan CSRF token ke dalam header
                        'Accept': 'application/json',
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    Swal.fire(data.title, data.message, data.icon).then(() => {
                        if (data.success) {
                            location.reload(); // Refresh halaman setelah menyimpan data
                        }
                    });
                })
                .catch(error => {
                    Swal.fire("Error", "Terjadi kesalahan saat proses data", "error");
                });
        }
    </script>
@endpush
