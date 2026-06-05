@extends('layouts.main.main')
@section('title', 'Daftar Member')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Pelanggan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active">Daftar Member</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card rounded-4 p-2 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-start">
                        <div class="avatar avatar-md bg-light-primary text-primary rounded-3 me-2 p-1">
                            <i class="material-icons-outlined" style="font-size:28px">people_alt</i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-primary">Keanggotaan (Member Loyalty)</h5>
                            <small class="text-muted">{{ session('store_name') }}</small>
                        </div>
                    </div>
                    <button class="btn btn-primary rounded-pill btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalMember">
                        <i class="material-icons-outlined" style="font-size:16px;vertical-align:middle">add</i> Tambah Member
                    </button>
                </div>
                <div class="card-body">
                    <table id="tbl-members" class="table table-bordered w-100 table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Member</th>
                                <th>No. Handphone</th>
                                <th>Email</th>
                                <th>Tanggal Lahir</th>
                                <th class="text-end">Saldo Poin</th>
                                <th class="text-center" width="140">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah/Edit Member --}}
    <div class="modal fade" id="modalMember" tabindex="-1">
        <div class="modal-dialog">
            <form id="formMember">
                @csrf
                <input type="hidden" id="member_id" value="">
                <div class="modal-content rounded-4 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="modalMemberTitle">Daftarkan Member Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="member_name" placeholder="Contoh: Jufri Wahyudi" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">No. Handphone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="member_phone" placeholder="Contoh: 08123456789" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" class="form-control" id="member_email" placeholder="Contoh: member@rimspos.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tanggal Lahir</label>
                            <input type="date" class="form-control" id="member_birth_date">
                            <small class="text-muted">Digunakan untuk perolehan promo/multiplier ulang tahun.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalMember" style="display:none">Batal</button>
                        <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-3">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const csrfToken = '{{ csrf_token() }}';
        let table;

        $(function() {
            table = $('#tbl-members').DataTable({
                serverSide: true,
                processing: true,
                ajax: {
                    url: '{{ route('members.index') }}',
                },
                columns: [
                    { data: 'name', name: 'name' },
                    { data: 'phone', name: 'phone' },
                    { data: 'email', name: 'email' },
                    { data: 'birth_date', name: 'birth_date' },
                    { data: 'total_points', name: 'total_points', className: 'text-end font-monospace fw-bold' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' },
                ],
                order: [[0, 'asc']],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
                },
                drawCallback: function() {
                    bindEditButtons();
                    bindDeleteButtons();
                }
            });
        });

        // Reset modal untuk tambah
        document.getElementById('modalMember').addEventListener('show.bs.modal', function(e) {
            if (!e.relatedTarget) return;
            document.getElementById('modalMemberTitle').textContent = 'Daftarkan Member Baru';
            document.getElementById('member_id').value = '';
            document.getElementById('formMember').reset();
        });

        function bindEditButtons() {
            document.querySelectorAll('.btn-edit-member').forEach(btn => {
                btn.removeEventListener('click', handleEdit);
                btn.addEventListener('click', handleEdit);
            });
        }

        function handleEdit() {
            document.getElementById('modalMemberTitle').textContent = 'Edit Data Member';
            document.getElementById('member_id').value = this.dataset.id;
            document.getElementById('member_name').value = this.dataset.name;
            document.getElementById('member_phone').value = this.dataset.phone;
            document.getElementById('member_email').value = this.dataset.email === 'null' ? '' : this.dataset.email;
            document.getElementById('member_birth_date').value = this.dataset.birth_date;
            new bootstrap.Modal(document.getElementById('modalMember')).show();
        }

        function bindDeleteButtons() {
            document.querySelectorAll('.btn-delete-member').forEach(btn => {
                btn.removeEventListener('click', handleDelete);
                btn.addEventListener('click', handleDelete);
            });
        }

        function handleDelete() {
            if (!confirm('Yakin ingin menghapus member ini? Seluruh saldo poin dan riwayat mutasi akan terhapus permanen.')) return;
            
            fetch(`/members/${this.dataset.id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    table.ajax.reload(null, false);
                } else {
                    alert(data.message);
                }
            });
        }

        // Submit form member
        document.getElementById('formMember').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('member_id').value;
            const url = id ? `/members/${id}` : '/members';
            const method = id ? 'PUT' : 'POST';

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    name: document.getElementById('member_name').value,
                    phone: document.getElementById('member_phone').value,
                    email: document.getElementById('member_email').value,
                    birth_date: document.getElementById('member_birth_date').value,
                }),
            })
            .then(r => {
                if (!r.ok) {
                    return r.json().then(err => { throw err; });
                }
                return r.json();
            })
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('modalMember'))?.hide();
                    table.ajax.reload(null, false);
                } else {
                    alert(data.message || 'Gagal menyimpan.');
                }
            })
            .catch(err => {
                alert(err.message || 'Terjadi kesalahan sistem.');
            });
        });
    </script>
@endpush
