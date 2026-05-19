@extends('layouts.main.main')
@section('title', 'Biaya Operasional')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Keuangan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active">Biaya Operasional</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card rounded-4 p-2">
                <div class="card-header d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-start">
                        <img src="{{ asset('assets/images/alazca_logo.png') }}" alt="Logo"
                            style="width:35px;height:35px;" class="me-2 mt-1">
                        <div>
                            <h5 class="fw-bold mb-0" style="color:#7c3aed">Biaya Operasional</h5>
                            <small class="text-muted">{{ session('store_name') }}</small>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap align-items-center">
                        {{-- Filter Tanggal --}}
                        <input type="date" id="filter_date_from" class="form-control form-control-sm" style="width:auto"
                            value="{{ date('Y-m-01') }}" title="Dari tanggal">
                        <span class="text-muted small">s/d</span>
                        <input type="date" id="filter_date_to" class="form-control form-control-sm" style="width:auto"
                            value="{{ date('Y-m-d') }}" title="Sampai tanggal">
                        {{-- Filter Kategori --}}
                        <select id="filter_category" class="form-select form-select-sm" style="width:auto;min-width:160px">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-outline-secondary btn-sm" id="btnFilter">
                            <i class="material-icons-outlined" style="font-size:15px;vertical-align:middle">search</i>
                            Filter
                        </button>
                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalBiaya">
                            <i class="material-icons-outlined" style="font-size:15px;vertical-align:middle">add</i> Catat
                            Biaya
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tbl-biaya" class="table table-bordered w-100">
                        <thead class="table-light">
                            <tr>
                                <th width="110">Tanggal</th>
                                <th>Kategori</th>
                                <th>Keterangan</th>
                                <th class="text-end" width="140">Jumlah</th>
                                <th class="text-center" width="100">Metode</th>
                                <th>Dicatat Oleh</th>
                                <th class="text-center" width="100">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah/Edit Biaya --}}
    <div class="modal fade" id="modalBiaya" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form id="formBiaya">
                @csrf
                <input type="hidden" id="biaya_id" value="">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalBiayaTitle">Catat Biaya Operasional</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                <select class="form-select" id="biaya_category" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="biaya_date" required
                                    value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jumlah (Rp) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="biaya_amount" min="1" placeholder="0"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
                                <select class="form-select" id="biaya_payment" required>
                                    <option value="cash">Cash</option>
                                    <option value="transfer">Transfer</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Keterangan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="biaya_description"
                                    placeholder="contoh: Gaji karyawan bulan Mei" required maxlength="255">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea class="form-control" id="biaya_notes" rows="2" maxlength="500"
                                    placeholder="Catatan tambahan (opsional)"></textarea>
                            </div>
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
        const csrfToken = '{{ csrf_token() }}';
        let table;

        function buildAjaxData() {
            return {
                date_from: $('#filter_date_from').val(),
                date_to: $('#filter_date_to').val(),
                category_id: $('#filter_category').val(),
            };
        }

        $(function() {
            table = $('#tbl-biaya').DataTable({
                serverSide: true,
                processing: true,
                ajax: {
                    url: '{{ route('expenses.datatables') }}',
                    data: d => Object.assign(d, buildAjaxData()),
                },
                columns: [{
                        data: 'transaction_date',
                        name: 'transaction_date',
                        render: d => d ? new Date(d).toLocaleDateString('id-ID', {
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric'
                        }) : '-'
                    },
                    {
                        data: 'kategori',
                        name: 'category.name',
                        searchable: true
                    },
                    {
                        data: 'description',
                        name: 'description'
                    },
                    {
                        data: 'jumlah',
                        name: 'amount',
                        className: 'text-end',
                        searchable: false
                    },
                    {
                        data: 'metode',
                        name: 'payment_method',
                        className: 'text-center',
                        searchable: false
                    },
                    {
                        data: 'dicatat_oleh',
                        name: 'user.name',
                        searchable: false
                    },
                    {
                        data: 'aksi',
                        name: 'aksi',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                ],
                order: [
                    [0, 'desc']
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
                },
                drawCallback: function() {
                    bindEditButtons();
                    bindDeleteButtons();
                }
            });

            $('#btnFilter').on('click', function() {
                table.ajax.reload();
            });
        });

        // Reset modal untuk tambah
        document.getElementById('modalBiaya').addEventListener('show.bs.modal', function(e) {
            if (!e.relatedTarget) return;
            document.getElementById('modalBiayaTitle').textContent = 'Catat Biaya Operasional';
            document.getElementById('biaya_id').value = '';
            document.getElementById('formBiaya').reset();
            document.getElementById('biaya_date').value = new Date().toISOString().slice(0, 10);
        });

        function bindEditButtons() {
            document.querySelectorAll('.btn-edit').forEach(btn => {
                btn.removeEventListener('click', handleEdit);
                btn.addEventListener('click', handleEdit);
            });
        }

        function handleEdit() {
            document.getElementById('modalBiayaTitle').textContent = 'Edit Biaya Operasional';
            document.getElementById('biaya_id').value = this.dataset.id;
            document.getElementById('biaya_category').value = this.dataset.category;
            document.getElementById('biaya_date').value = this.dataset.date;
            document.getElementById('biaya_amount').value = this.dataset.amount;
            document.getElementById('biaya_description').value = this.dataset.description;
            document.getElementById('biaya_payment').value = this.dataset.payment;
            document.getElementById('biaya_notes').value = this.dataset.notes;
            new bootstrap.Modal(document.getElementById('modalBiaya')).show();
        }

        function bindDeleteButtons() {
            document.querySelectorAll('.btn-delete').forEach(btn => {
                btn.removeEventListener('click', handleDelete);
                btn.addEventListener('click', handleDelete);
            });
        }

        function handleDelete() {
            if (!confirm('Hapus catatan biaya ini?')) return;
            fetch(`/expenses/${this.dataset.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) table.ajax.reload(null, false);
                    else alert(data.message);
                });
        }

        // Submit form biaya
        document.getElementById('formBiaya').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('biaya_id').value;
            const url = id ? `/expenses/${id}` : '/expenses';
            const method = id ? 'PUT' : 'POST';

            fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        expense_category_id: document.getElementById('biaya_category').value,
                        transaction_date: document.getElementById('biaya_date').value,
                        amount: document.getElementById('biaya_amount').value,
                        description: document.getElementById('biaya_description').value,
                        payment_method: document.getElementById('biaya_payment').value,
                        notes: document.getElementById('biaya_notes').value,
                    }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('modalBiaya'))?.hide();
                        table.ajax.reload(null, false);
                    } else {
                        alert(data.message || 'Gagal menyimpan.');
                    }
                });
        });
    </script>
@endpush
