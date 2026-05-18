@extends('layouts.main.main')
@section('title', 'Manajemen Pengguna')

@section('breadcrumb')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Manajemen Pengguna</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Pengaturan Role</a></li>
            </ol>
        </nav>
    </div>
</div>
@endsection

@section('content')

<div class="row">
    <div class="col-sm-12">
        <div class="card rounded-4 p-2">
            <div class="card-header d-flex justify-content-between">
                <h5>Role: {{ $role->nama }}</h5>
                <a href="{{ route('role.index')}}" class="btn btn-primary">Kembali</a>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item"><a class="nav-link active" id="users-tab" data-bs-toggle="tab" href="#users" role="tab" aria-controls="users" aria-selected="true">User</a></li>
                    <li class="nav-item"><a class="nav-link" id="menus-tabs" data-bs-toggle="tab" href="#menus" role="tab" aria-controls="menus" aria-selected="false">Menu</a></li>
                </ul>
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active" id="users" role="tabpanel" aria-labelledby="users-tab">
                        <h2 class="mt-3">Manajemen Role</h2>
                        <div class="row mt-3">
                            <div class="col-6">
                                <form id="addUserForm">
                                    @csrf
                                    <div class="mb-1">
                                        <label for="user" class="form-label">Pilih User</label>
                                        <select class="form-control" id="user" name="user_id"></select>
                                    </div>
                                    <div class="text-end">
                                        <input type="hidden" name="role_id" value="{{ $role->id }}">
                                        <button type="button" class="btn btn-primary btn-sm" id="saveUserBtn"><i class="fa fa-plus"></i> Tambah User</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="table-responsive mt-3">
                            <table class="table table-striped datatables" id="pegawai-table">
                                <thead>
                                    <tr>
                                        <th width="7%"></th>
                                        <th>NIK</th>
                                        <th>Name</th>
                                        <th>Divisi</th>
                                        <th>Jabatan</th>
                                        <th>Status</th>
                                        <th width="5%" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($role->users as $index => $row)
                                    <tr>
                                        <td>
                                            @php
                                            $foto = optional($row->pengguna->pegawai)->foto;
                                            @endphp

                                            @if ($foto == '-' || $foto == '' || $foto == null)
                                            <img class="b-r-10" width="35" height="35" src="{{ asset('assets/images/avatars/11.png') }}" alt="">
                                            @else
                                            <img class="b-r-10" width="35" height="35" alt="" src="{{ Storage::disk('s3')->url($foto) }}" onerror="this.onerror=null;this.src='{{ asset('assets/images/avatars/11.png') }}';">
                                            @endif
                                        </td>
                                        <td class="align-middle">{{ optional($row->pengguna)->nik }}</td>
                                        <td class="align-middle">{{ optional($row->pengguna)->name }}</td>
                                        <td class="align-middle">{{ optional($row->pengguna->divisi)->nama }}</td>
                                        <td class="align-middle">{{ optional($row->pengguna->pegawai)->jabatan->nama ?? 'Belum ditetapkan' }}</td>
                                        <td class="align-middle">@php echo (optional($row->pengguna->pegawai)->status_kerja == 1 ? '<span class="badge rounded-pill bg-grd-success">Aktif</span>' : '<span class="badge rounded-pill bg-grd-danger">Non Aktif</span>') @endphp</td>
                                        <td class="text-center align-middle">
                                            <a href="javascript:void(0);" class="delete-btn" data-id="{{ Crypt::encryptString($row->id) }}" title="Melihat detail dari role"><i class="fa fa-trash text-danger"></i></a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7">Data tidak ditemukan</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="menus" role="tabpanel" aria-labelledby="menus-tab">
                        <h2 class="mt-3">Manajemen Menu</h2>

                        @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th width="5%"></th>
                                    <th>Nama Menu</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($menus as $menu)
                                <tr>
                                    <td class="text-center">
                                        <div class="parent-icon"><i class="material-icons-outlined">{{ $menu->icon }}</i></div>
                                    </td>
                                    <td>{{ $menu->nama }}</td>
                                    <td class="text-center" width="10%">{{ $menu->stts == 'Y' ? 'Aktif' : 'Non Aktif' }}</td>
                                    <td class="text-center" width="10%">
                                        <div class="form-check checkbox mb-0 d-flex justify-content-center">
                                            <input class="form-check-input" id="checkbox{{$menu->id}}" name="has_access" type="checkbox" value="{{$menu->id}}" {{ ($menu->has_access == 1 ? 'checked=""' : '')}}>
                                            <label class="form-check-label" for="checkbox{{$menu->id}}"></label>
                                        </div>
                                    </td>
                                </tr>
                                @if($menu->children->isNotEmpty())
                                @foreach ($menu->children as $child)
                                <tr>
                                    <td class="text-center">
                                        <svg class="fill-icon" style="width: 20px; height: 20px;">
                                            <use href="{{ url('assets/svg/icon-sprite.svg#fill-'.$child->icon) }}"></use>
                                        </svg>
                                    </td>
                                    <td>
                                        &nbsp;&nbsp;&nbsp;<i class="fa fa-angle-right"></i> {{ $child->nama }}
                                    </td>
                                    <td class="text-center" width="10%">{{ $child->stts == 'Y' ? 'Aktif' : 'Non Aktif' }}</td>
                                    <td class="text-center" width="10%">
                                        <div class="form-check checkbox mb-0 d-flex justify-content-center">
                                            <input class="form-check-input" id="checkbox{{$child->id}}" name="has_access" type="checkbox" value="{{$child->id}}" {{ ($child->has_access == 1 ? 'checked=""' : '')}}>
                                            <label class="form-check-label" for="checkbox{{$child->id}}"></label>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                        <div class="text-end mt-3">
                            <button class="btn btn-primary" onclick="saveMenu()">Simpan</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function () {
        // Inisialisasi Select2 dengan AJAX
        $('#user').select2({
            placeholder: 'Pilih User',
            width: '100%',
            ajax: {
                url: '{{ route("pegawai.search") }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search: params.term // Pencarian berdasarkan input user
                    };
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: true
            }
        });

        // Fungsi untuk menyimpan user yang dipilih ke RoleUser
        $('#saveUserBtn').click(function () {
            var formData = $('#addUserForm').serialize();

            // SweetAlert2 Loading
            $.ajax({
                type: 'POST',
                url: '{{ route("roleuser.store") }}', // Route untuk menyimpan RoleUser
                data: formData,
                beforeSend: function () {
                    Swal.fire({
                        title: 'Menambahkan User...',
                        text: 'Mohon tunggu sebentar',
                        icon: 'info',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                },
                success: function (response) {
                    Swal.fire({
                        title: 'Sukses!',
                        text: 'User berhasil ditambahkan ke role',
                        icon: 'success',
                    }).then(() => {
                        location.reload(); // Refresh halaman setelah berhasil
                    });
                },
                error: function (xhr) {
                    Swal.fire({
                        title: 'Gagal!',
                        text: 'Terjadi kesalahan saat menambahkan user',
                        icon: 'error',
                    });
                }
            });
        });
    });

</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        $("#pegawai-table").DataTable({
            order: [[2, 'asc']],
        });
        // Pilih semua tombol dengan class 'delete-btn'
        document.querySelectorAll('.delete-btn').forEach(function (button) {
            // Tambahkan event listener untuk setiap tombol
            button.addEventListener('click', function () {
                // Ambil ID yang dienkripsi dari atribut data-id
                var id = this.getAttribute('data-id');

                // Tampilkan konfirmasi SweetAlert2
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data ini akan dihapus secara permanen!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Loading...',
                            text: 'Sedang menghapus data, mohon tunggu...',
                            icon: 'info',
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading(); // Tampilkan animasi loading
                            }
                        });
                        // Kirim request delete ke server menggunakan fetch
                        fetch("{{ route('role.delete', ':id') }}".replace(':id', id), {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire(
                                        'Terhapus!',
                                        'Data berhasil dihapus.',
                                        'success'
                                    ).then(() => {
                                        // Refresh halaman atau hapus row dari DOM
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire(
                                        'Gagal!',
                                        'Data tidak dapat dihapus.',
                                        'error'
                                    );
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire(
                                    'Error!',
                                    'Terjadi kesalahan.',
                                    'error'
                                );
                            });
                    }
                });
            });
        });
    });
    function saveMenu() {
        const roleid = "{{$role->id}}";
        let datas = new Array();
        document.querySelectorAll('input[name=has_access]').forEach(function (checkbox) {
            if (checkbox.checked) {
                datas.push({
                    "role_id": roleid,
                    "menu_id": checkbox.value
                })
            }
        });
        if (datas.length > 0) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data akses menu ini akan disimpan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, simpan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
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
                    // Kirim request delete ke server menggunakan fetch
                    fetch('{{route('menuuser.store')}}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ data: datas })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire(
                                    'Berhasil!',
                                    data.message,
                                    'success'
                                ).then(() => {
                                    // Refresh halaman atau hapus row dari DOM
                                    location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Gagal!',
                                    data.message,
                                    'error'
                                );
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire(
                                'Error!',
                                'Terjadi kesalahan.',
                                'error'
                            );
                        });
                }
            });
        }
    }
</script>
@endpush