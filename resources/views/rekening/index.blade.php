@extends('layouts.main.main')
@section('title', 'Rekening Bank')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Keuangan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active">Rekening Bank</li>
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
                            <h5 class="fw-bold mb-0" style="color:#7c3aed">Rekening Bank</h5>
                            <small class="text-muted">{{ session('store_name') }}</small>
                        </div>
                    </div>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalRekening">
                        <i class="material-icons-outlined" style="font-size:16px;vertical-align:middle">add</i> Tambah
                        Rekening
                    </button>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover" id="tbl-rekening">
                        <thead class="table-light">
                            <tr>
                                <th width="40">#</th>
                                <th>Bank</th>
                                <th>Nomor Rekening</th>
                                <th>Atas Nama</th>
                                <th class="text-center" width="120">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rekenings as $i => $rek)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        <span class="badge rounded-pill" style="background:#7c3aed;font-size:12px">
                                            {{ $rek->bank_rek }}
                                        </span>
                                    </td>
                                    <td><code>{{ $rek->no_rek }}</code></td>
                                    <td>{{ $rek->nama_rek }}</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-warning btn-edit-rek" data-id="{{ $rek->id }}"
                                            data-no_rek="{{ $rek->no_rek }}" data-nama_rek="{{ $rek->nama_rek }}"
                                            data-bank_rek="{{ $rek->bank_rek }}">
                                            <i class="material-icons-outlined" style="font-size:15px">edit</i>
                                        </button>
                                        <button class="btn btn-sm btn-danger btn-delete-rek" data-id="{{ $rek->id }}">
                                            <i class="material-icons-outlined" style="font-size:15px">delete</i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Belum ada rekening</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah/Edit Rekening --}}
    <div class="modal fade" id="modalRekening" tabindex="-1">
        <div class="modal-dialog">
            <form id="formRekening">
                @csrf
                <input type="hidden" id="rek_id" value="">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalRekeningTitle">Tambah Rekening</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Bank <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="rek_bank"
                                placeholder="contoh: BRI, BNI, Mandiri" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nomor Rekening <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="rek_no" placeholder="contoh: 1234567890"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Atas Nama <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="rek_nama" placeholder="contoh: Budi Santoso"
                                required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSimpanRek">Simpan</button>
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
                    <h5 class="modal-title">Hapus Rekening</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Yakin ingin menghapus rekening ini?
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
        const baseUrl = '{{ url('settings/rekening') }}';

        // Reset modal saat buka untuk tambah
        document.getElementById('modalRekening').addEventListener('show.bs.modal', function(e) {
            if (!e.relatedTarget) return;
            document.getElementById('modalRekeningTitle').textContent = 'Tambah Rekening';
            document.getElementById('rek_id').value = '';
            document.getElementById('rek_bank').value = '';
            document.getElementById('rek_no').value = '';
            document.getElementById('rek_nama').value = '';
        });

        // Edit rekening
        document.querySelectorAll('.btn-edit-rek').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('modalRekeningTitle').textContent = 'Edit Rekening';
                document.getElementById('rek_id').value = this.dataset.id;
                document.getElementById('rek_bank').value = this.dataset.bank_rek;
                document.getElementById('rek_no').value = this.dataset.no_rek;
                document.getElementById('rek_nama').value = this.dataset.nama_rek;
                new bootstrap.Modal(document.getElementById('modalRekening')).show();
            });
        });

        // Submit tambah / edit
        document.getElementById('formRekening').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('rek_id').value;
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
                        bank_rek: document.getElementById('rek_bank').value,
                        no_rek: document.getElementById('rek_no').value,
                        nama_rek: document.getElementById('rek_nama').value,
                    }),
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        bootstrap.Modal.getInstance(document.getElementById('modalRekening')).hide();
                        location.reload();
                    } else {
                        alert(res.message ?? 'Terjadi kesalahan.');
                    }
                })
                .catch(() => alert('Koneksi gagal, coba lagi.'));
        });

        // Hapus rekening
        let deleteId = null;
        document.querySelectorAll('.btn-delete-rek').forEach(btn => {
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
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
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
