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
            <div class="card rounded-4 p-2" style="min-height: 450px;">
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
                        <select id="filter_category" class="form-select form-select-sm" style="width:auto;min-width:150px">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        {{-- Filter Status --}}
                        <select id="filter_status" class="form-select form-select-sm" style="width:auto;min-width:140px">
                            <option value="">Semua Status</option>
                            <option value="lunas">Lunas</option>
                            <option value="sebagian">Sebagian</option>
                            <option value="belum_dibayar">Belum Dibayar</option>
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
                    <div class="table-responsive" style="min-height: 350px;">
                        <table id="tbl-biaya" class="table table-bordered align-middle w-100">
                            <thead class="table-light">
                                <tr>
                                    <th width="100">Tanggal</th>
                                    <th>Kategori</th>
                                    <th>Keterangan</th>
                                    <th class="text-end" width="120">Total Tagihan</th>
                                    <th class="text-end" width="120">Terbayar</th>
                                    <th class="text-end" width="120">Sisa Hutang</th>
                                    <th class="text-center" width="110">Status</th>
                                    <th class="text-center" width="90">Metode</th>
                                    <th>Dicatat Oleh</th>
                                    <th class="text-center" width="140">#</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
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
                                <label class="form-label">Tanggal Transaksi <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="biaya_date" required
                                    value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Total Tagihan / Nominal Biaya (Rp) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="biaya_amount" min="1" placeholder="0" required>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label mb-0">Dibayar Saat Ini (Rp) <span class="text-danger">*</span></label>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="btnFullPayment">Bayar Lunas</button>
                                </div>
                                <input type="number" class="form-control" id="biaya_paid_amount" min="0" placeholder="0" required>
                                <small class="text-muted d-block mt-1" id="previewSisaHutang">Sisa Hutang: Rp 0</small>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
                                <select class="form-select" id="biaya_payment" required>
                                    <option value="cash">Cash / Tunai</option>
                                    <option value="transfer">Transfer Bank</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Keterangan / Deskripsi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="biaya_description"
                                    placeholder="contoh: Tagihan Listrik & Air bulan Mei" required maxlength="255">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan Tambahan</label>
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

    {{-- Modal Bayar Cicilan / Pelunasan --}}
    <div class="modal fade" id="modalPay" tabindex="-1">
        <div class="modal-dialog">
            <form id="formPay">
                @csrf
                <input type="hidden" id="pay_expense_id" value="">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Bayar / Cicil Hutang Biaya</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-light border mb-3">
                            <h6 class="fw-bold mb-1" id="pay_info_description">-</h6>
                            <div class="d-flex justify-content-between small text-muted">
                                <span>Total Tagihan: <strong id="pay_info_amount">Rp 0</strong></span>
                                <span>Sisa Hutang: <strong id="pay_info_remaining" class="text-danger">Rp 0</strong></span>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Tanggal Pembayaran <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="pay_date" required value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Nominal Pembayaran (Rp) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="pay_amount" min="1" required placeholder="0">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
                                <select class="form-select" id="pay_method" required>
                                    <option value="cash">Cash / Tunai</option>
                                    <option value="transfer">Transfer Bank</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan Pembayaran</label>
                                <input type="text" class="form-control" id="pay_notes" placeholder="contoh: Cicilan ke-2" maxlength="500">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success"><i class="material-icons-outlined" style="font-size:16px;vertical-align:middle">check</i> Simpan Pembayaran</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Detail & Riwayat Pembayaran --}}
    <div class="modal fade" id="modalDetail" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" style="color: #7c3aed"><i class="material-icons-outlined" style="vertical-align:middle">info</i> Detail Biaya & Riwayat Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="card bg-light border-0 mb-3">
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-md-6"><strong>Kategori:</strong> <span id="detail_category">-</span></div>
                                <div class="col-md-6"><strong>Tanggal Transaksi:</strong> <span id="detail_date">-</span></div>
                                <div class="col-md-12"><strong>Keterangan:</strong> <span id="detail_description">-</span></div>
                                <div class="col-md-4"><strong>Total Tagihan:</strong> <h6 class="fw-bold text-dark mt-1" id="detail_amount">Rp 0</h6></div>
                                <div class="col-md-4"><strong>Terbayar:</strong> <h6 class="fw-bold text-success mt-1" id="detail_paid">Rp 0</h6></div>
                                <div class="col-md-4"><strong>Sisa Hutang:</strong> <h6 class="fw-bold text-danger mt-1" id="detail_remaining">Rp 0</h6></div>
                                <div class="col-md-4"><strong>Status:</strong> <span id="detail_status">-</span></div>
                                <div class="col-md-4"><strong>Metode Utama:</strong> <span id="detail_method">-</span></div>
                                <div class="col-md-4"><strong>Dicatat Oleh:</strong> <span id="detail_user">-</span></div>
                                <div class="col-md-12"><strong>Catatan:</strong> <span id="detail_notes">-</span></div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-2" style="color:#7c3aed"><i class="material-icons-outlined" style="font-size:18px;vertical-align:middle">history</i> Riwayat Pembayaran (Cicilan / Pelunasan)</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="40" class="text-center">No</th>
                                    <th width="110">Tanggal</th>
                                    <th class="text-end" width="130">Nominal</th>
                                    <th width="100" class="text-center">Metode</th>
                                    <th>Catatan</th>
                                    <th>Petugas</th>
                                </tr>
                            </thead>
                            <tbody id="detail_payments_body">
                                <tr><td colspan="6" class="text-center py-3 text-muted">Belum ada riwayat pembayaran</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
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
                payment_status: $('#filter_status').val(),
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
                        data: 'terbayar',
                        name: 'paid_amount',
                        className: 'text-end',
                        searchable: false
                    },
                    {
                        data: 'sisa_hutang',
                        name: 'sisa_hutang',
                        className: 'text-end text-danger fw-bold',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'status_badge',
                        name: 'payment_status',
                        className: 'text-center',
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
                    bindPayButtons();
                    bindDetailButtons();
                }
            });

            $('#btnFilter').on('click', function() {
                table.ajax.reload();
            });

            // Live calculate sisa hutang pada modal tambah/edit
            function updateSisaHutangPreview() {
                const amount = parseFloat($('#biaya_amount').val()) || 0;
                const paid = parseFloat($('#biaya_paid_amount').val()) || 0;
                const remaining = Math.max(0, amount - paid);
                $('#previewSisaHutang').text('Sisa Hutang: Rp ' + remaining.toLocaleString('id-ID'));
                if (remaining > 0) {
                    $('#previewSisaHutang').removeClass('text-muted text-success').addClass('text-danger fw-bold');
                } else {
                    $('#previewSisaHutang').removeClass('text-muted text-danger fw-bold').addClass('text-success');
                }
            }

            $('#biaya_amount, #biaya_paid_amount').on('input', updateSisaHutangPreview);

            $('#btnFullPayment').on('click', function() {
                $('#biaya_paid_amount').val($('#biaya_amount').val());
                updateSisaHutangPreview();
            });
        });

        // Reset modal untuk tambah
        document.getElementById('modalBiaya').addEventListener('show.bs.modal', function(e) {
            if (!e.relatedTarget) return;
            document.getElementById('modalBiayaTitle').textContent = 'Catat Biaya Operasional';
            document.getElementById('biaya_id').value = '';
            document.getElementById('formBiaya').reset();
            document.getElementById('biaya_date').value = new Date().toISOString().slice(0, 10);
            $('#previewSisaHutang').text('Sisa Hutang: Rp 0').removeClass('text-danger text-success fw-bold').addClass('text-muted');
        });

        function bindDetailButtons() {
            document.querySelectorAll('.btn-detail').forEach(btn => {
                btn.removeEventListener('click', handleDetail);
                btn.addEventListener('click', handleDetail);
            });
        }

        function handleDetail() {
            const id = this.dataset.id;
            $('#detail_payments_body').html('<tr><td colspan="6" class="text-center py-3"><i class="fa fa-spinner fa-spin"></i> Memuat data...</td></tr>');
            const modal = new bootstrap.Modal(document.getElementById('modalDetail'));
            modal.show();

            fetch(`/expenses/${id}`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                $('#detail_category').text(data.category_name);
                $('#detail_date').text(data.transaction_date);
                $('#detail_description').text(data.description);
                $('#detail_amount').text(data.amount_formatted);
                $('#detail_paid').text(data.paid_formatted);
                $('#detail_remaining').text(data.remaining_formatted);

                let statusBadge = '';
                if (data.payment_status === 'lunas') {
                    statusBadge = '<span class="badge bg-success">Lunas</span>';
                } else if (data.payment_status === 'sebagian') {
                    statusBadge = '<span class="badge bg-warning text-dark">Sebagian</span>';
                } else {
                    statusBadge = '<span class="badge bg-danger">Belum Dibayar</span>';
                }
                $('#detail_status').html(statusBadge);
                $('#detail_method').text(data.payment_method);
                $('#detail_user').text(data.user_name);
                $('#detail_notes').text(data.notes);

                let rowsHtml = '';
                if (data.payments && data.payments.length > 0) {
                    data.payments.forEach((p, index) => {
                        rowsHtml += `<tr>
                            <td class="text-center">${index + 1}</td>
                            <td>${p.payment_date}</td>
                            <td class="text-end fw-bold text-success">${p.amount_formatted}</td>
                            <td class="text-center"><span class="badge bg-light text-dark border">${p.payment_method}</span></td>
                            <td>${p.notes}</td>
                            <td>${p.user}</td>
                        </tr>`;
                    });
                } else {
                    rowsHtml = '<tr><td colspan="6" class="text-center py-3 text-muted">Belum ada riwayat pembayaran</td></tr>';
                }
                $('#detail_payments_body').html(rowsHtml);
            });
        }

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
            document.getElementById('biaya_paid_amount').value = this.dataset.paid;
            document.getElementById('biaya_description').value = this.dataset.description;
            document.getElementById('biaya_payment').value = this.dataset.payment;
            document.getElementById('biaya_notes').value = this.dataset.notes;

            const amount = parseFloat(this.dataset.amount) || 0;
            const paid = parseFloat(this.dataset.paid) || 0;
            const remaining = Math.max(0, amount - paid);
            $('#previewSisaHutang').text('Sisa Hutang: Rp ' + remaining.toLocaleString('id-ID'));

            new bootstrap.Modal(document.getElementById('modalBiaya')).show();
        }

        function bindPayButtons() {
            document.querySelectorAll('.btn-pay').forEach(btn => {
                btn.removeEventListener('click', handlePay);
                btn.addEventListener('click', handlePay);
            });
        }

        function handlePay() {
            const expenseId = this.dataset.id;
            const description = this.dataset.description;
            const amount = parseFloat(this.dataset.amount) || 0;
            const remaining = parseFloat(this.dataset.remaining) || 0;

            document.getElementById('pay_expense_id').value = expenseId;
            document.getElementById('pay_info_description').textContent = description;
            document.getElementById('pay_info_amount').textContent = 'Rp ' + amount.toLocaleString('id-ID');
            document.getElementById('pay_info_remaining').textContent = 'Rp ' + remaining.toLocaleString('id-ID');
            document.getElementById('pay_amount').value = remaining;
            document.getElementById('pay_date').value = new Date().toISOString().slice(0, 10);
            document.getElementById('pay_notes').value = '';

            new bootstrap.Modal(document.getElementById('modalPay')).show();
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
                        paid_amount: document.getElementById('biaya_paid_amount').value,
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

        // Submit form pay
        document.getElementById('formPay').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('pay_expense_id').value;

            fetch(`/expenses/${id}/pay`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        payment_date: document.getElementById('pay_date').value,
                        amount: document.getElementById('pay_amount').value,
                        payment_method: document.getElementById('pay_method').value,
                        notes: document.getElementById('pay_notes').value,
                    }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('modalPay'))?.hide();
                        table.ajax.reload(null, false);
                    } else {
                        alert(data.message || 'Gagal mencatat pembayaran.');
                    }
                });
        });
    </script>
@endpush
