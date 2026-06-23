@extends('layouts.main.main')
@section('title', 'Field Kustom Mitra')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Pengaturan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Mitra</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Field Kustom</li>
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
                        <img src="{{ asset('assets/images/alazca_logo.png') }}" alt="Logo"
                            style="width:35px;height:35px;" class="me-2 mt-1">
                        <div>
                            <h5 class="fw-bold mb-0" style="color:#7c3aed">Field Kustom Mitra / Pelanggan</h5>
                            <small class="text-muted">{{ session('store_name') }}</small>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('customers.index') }}" class="btn btn-secondary btn-sm">
                            <i class="bx bx-arrow-back" style="font-size:16px;vertical-align:middle"></i> Kembali
                        </a>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalField">
                            <i class="material-icons-outlined" style="font-size:16px;vertical-align:middle">add</i> Tambah Field
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="40">#</th>
                                <th>Label Field</th>
                                <th>Key Field (Database)</th>
                                <th>Tipe Input</th>
                                <th>Opsi (Khusus Select)</th>
                                <th class="text-center" width="80">Wajib</th>
                                <th class="text-center" width="120">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($fields as $i => $field)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td><strong>{{ $field->label }}</strong></td>
                                    <td><code>{{ $field->name }}</code></td>
                                    <td>
                                        <span class="badge bg-secondary text-uppercase">{{ $field->type }}</span>
                                    </td>
                                    <td>{{ $field->options ?: '-' }}</td>
                                    <td class="text-center">
                                        @if ($field->is_required)
                                            <span class="badge bg-danger">Ya</span>
                                        @else
                                            <span class="badge bg-light text-dark border">Tidak</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-warning btn-edit-field text-white" data-id="{{ $field->id }}"
                                            data-label="{{ htmlspecialchars($field->label) }}"
                                            data-type="{{ htmlspecialchars($field->type) }}"
                                            data-options="{{ htmlspecialchars($field->options) }}"
                                            data-required="{{ $field->is_required ? '1' : '0' }}">
                                            <i class="material-icons-outlined" style="font-size:15px;vertical-align:middle">edit</i>
                                        </button>
                                        <form action="{{ route('customers.custom-fields.destroy', $field->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus field kustom ini? Data mitra yang telah diisi untuk field ini tidak akan terhapus namun tidak akan muncul lagi.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="material-icons-outlined" style="font-size:15px;vertical-align:middle">delete</i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Belum ada field kustom terdaftar untuk store ini</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah/Edit Custom Field --}}
    <div class="modal fade" id="modalField" tabindex="-1">
        <div class="modal-dialog">
            <form id="formField" method="POST" action="{{ route('customers.custom-fields.store') }}">
                @csrf
                <input type="hidden" name="_method" id="field_method" value="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalFieldTitle">Tambah Field Kustom</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Label Field <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="label" id="field_label" placeholder="contoh: Kecamatan" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipe Input <span class="text-danger">*</span></label>
                            <select class="form-select" name="type" id="field_type" required>
                                <option value="text">Text (Teks biasa)</option>
                                <option value="number">Number (Angka)</option>
                                <option value="select">Select (Pilihan Dropdown)</option>
                            </select>
                        </div>
                        <div class="mb-3" id="optionsGroup" style="display:none;">
                            <label class="form-label">Opsi Pilihan <span class="text-muted">(pisahkan dengan koma)</span></label>
                            <input type="text" class="form-control" name="options" id="field_options" placeholder="contoh: Laki-laki, Perempuan">
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_required" id="field_required" value="1">
                            <label class="form-check-label" for="field_required">
                                Wajib diisi (Required)
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const storeAction = "{{ route('customers.custom-fields.store') }}";

        // Tampilkan/sembunyikan opsi pilihan berdasarkan tipe select
        const typeSelect = document.getElementById('field_type');
        const optionsGroup = document.getElementById('optionsGroup');
        const optionsInput = document.getElementById('field_options');

        typeSelect.addEventListener('change', function() {
            if (this.value === 'select') {
                optionsGroup.style.display = 'block';
                optionsInput.setAttribute('required', 'required');
            } else {
                optionsGroup.style.display = 'none';
                optionsInput.removeAttribute('required');
            }
        });

        // Reset modal saat tambah
        document.getElementById('modalField').addEventListener('show.bs.modal', function(e) {
            if (!e.relatedTarget) return;
            document.getElementById('modalFieldTitle').textContent = 'Tambah Field Kustom';
            document.getElementById('field_method').value = 'POST';
            document.getElementById('formField').action = storeAction;
            document.getElementById('field_label').value = '';
            document.getElementById('field_type').value = 'text';
            document.getElementById('field_options').value = '';
            document.getElementById('field_required').checked = false;
            optionsGroup.style.display = 'none';
            optionsInput.removeAttribute('required');
        });

        // Edit field kustom
        document.querySelectorAll('.btn-edit-field').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                document.getElementById('modalFieldTitle').textContent = 'Edit Field Kustom';
                document.getElementById('field_method').value = 'PUT';
                document.getElementById('formField').action = `${storeAction}/${id}`;
                document.getElementById('field_label').value = this.dataset.label;
                document.getElementById('field_type').value = this.dataset.type;
                document.getElementById('field_options').value = this.dataset.options;
                document.getElementById('field_required').checked = this.dataset.required === '1';

                if (this.dataset.type === 'select') {
                    optionsGroup.style.display = 'block';
                    optionsInput.setAttribute('required', 'required');
                } else {
                    optionsGroup.style.display = 'none';
                    optionsInput.removeAttribute('required');
                }

                new bootstrap.Modal(document.getElementById('modalField')).show();
            });
        });
    </script>
@endpush
