@extends('layouts.main.main')
@section('title', 'Detail Billing - ' . $store->name)

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Pengaturan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('subscribed-billing.index') }}">SaaS Billing</a></li>
                    <li class="breadcrumb-item active">{{ $store->name }}</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .sub-card {
            border: none;
            border-radius: 16px;
            overflow: hidden;
        }
        .sub-card .card-header {
            border-bottom: 1px solid rgba(0,0,0,.05);
            background: transparent;
            padding: 1rem 1.5rem;
        }
        .sub-card .card-body {
            padding: 1.25rem 1.5rem;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f1f1f1;
        }
        .info-item:last-child { border-bottom: none; }
        .info-label { color: #6c757d; font-size: 0.85rem; }
        .info-value { font-weight: 600; text-align: right; }
        .badge-status {
            font-size: 0.75rem;
            padding: 5px 10px;
            border-radius: 6px;
            font-weight: 600;
        }
        .invoice-status-paid { color: #198754; }
        .invoice-status-unpaid { color: #dc3545; }
    </style>
@endpush

@section('content')
    @php
        $sub = $store->subscription;
        $status = $sub ? $sub->subscription_status : 'active';
        $packageType = $sub ? $sub->package_type : 'lifetime';
    @endphp

    <div class="row g-3 mb-4">
        {{-- === SUBSCRIPTION INFO === --}}
        <div class="col-lg-4">
            <div class="card sub-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bx bx-package text-primary"></i> Paket Langganan</h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="openEditSubscription()">
                        <i class="bx bx-edit-alt"></i> Edit
                    </button>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        @if ($status === 'active')
                            <span class="badge bg-success badge-status fs-6 px-3 py-2">
                                <i class="bx bx-check-circle"></i> Aktif
                            </span>
                        @elseif ($status === 'grace_period')
                            <span class="badge bg-warning text-dark badge-status fs-6 px-3 py-2">
                                <i class="bx bx-time-five"></i> Masa Tenggang ({{ $sub->grace_days_left }} hari)
                            </span>
                        @else
                            <span class="badge bg-danger badge-status fs-6 px-3 py-2">
                                <i class="bx bx-x-circle"></i> Expired
                            </span>
                        @endif
                    </div>
                    <div class="info-item">
                        <span class="info-label">Nama Toko</span>
                        <span class="info-value">{{ $store->name }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Kode</span>
                        <span class="info-value"><span class="badge bg-secondary">{{ $store->code }}</span></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tipe Paket</span>
                        <span class="info-value">
                            @if ($packageType === 'lifetime')
                                <span class="badge bg-success">Lifetime</span>
                            @elseif ($packageType === 'monthly')
                                <span class="badge bg-info">Bulanan</span>
                            @else
                                <span class="badge bg-primary">Tahunan</span>
                            @endif
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Jumlah Tagihan</span>
                        <span class="info-value">
                            @if ($sub && $packageType !== 'lifetime')
                                Rp {{ number_format($sub->billing_amount, 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Mulai</span>
                        <span class="info-value">{{ $sub && $sub->start_date ? $sub->start_date->format('d M Y') : '-' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Berakhir</span>
                        <span class="info-value">{{ $sub && $sub->end_date ? $sub->end_date->format('d M Y') : '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- === INVOICES === --}}
        <div class="col-lg-8">
            <div class="card sub-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bx bx-receipt text-info"></i> Daftar Invoice</h6>
                    <button class="btn btn-sm btn-primary" onclick="openCreateInvoice()">
                        <i class="bx bx-plus"></i> Buat Invoice
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>No. Invoice</th>
                                    <th>Periode</th>
                                    <th>Tagihan</th>
                                    <th>Jatuh Tempo</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($invoices as $inv)
                                    <tr>
                                        <td><strong>{{ $inv->invoice_number }}</strong></td>
                                        <td>
                                            {{ $inv->period_start->format('d M Y') }}
                                            <br><small class="text-muted">s/d {{ $inv->period_end->format('d M Y') }}</small>
                                        </td>
                                        <td>Rp {{ number_format($inv->billing_amount, 0, ',', '.') }}</td>
                                        <td>{{ $inv->due_date->format('d M Y') }}</td>
                                        <td class="text-center">
                                            @if ($inv->status === 'paid')
                                                <span class="badge bg-success badge-status">Lunas</span>
                                            @elseif ($inv->status === 'cancelled')
                                                <span class="badge bg-secondary badge-status">Dibatalkan</span>
                                            @else
                                                <span class="badge bg-danger badge-status">Belum Dibayar</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($inv->status === 'unpaid')
                                                <button class="btn btn-sm btn-outline-success"
                                                    onclick="openPayment({{ $inv->id }}, '{{ $inv->invoice_number }}', {{ $inv->remaining_balance }})">
                                                    <i class="bx bx-money"></i> Bayar
                                                </button>
                                            @else
                                                <button class="btn btn-sm btn-outline-secondary" disabled>
                                                    <i class="bx bx-check"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                    {{-- Payment history rows --}}
                                    @if ($inv->payments->count() > 0)
                                        <tr>
                                            <td colspan="6" class="p-0">
                                                <div class="bg-light p-2 ps-4 border-top">
                                                    <small class="fw-bold text-muted d-block mb-1">
                                                        <i class="bx bx-subdirectory-right"></i> Riwayat Pembayaran
                                                    </small>
                                                    @foreach ($inv->payments as $pay)
                                                        <div class="d-flex justify-content-between align-items-center py-1 {{ !$loop->last ? 'border-bottom' : '' }}">
                                                            <div>
                                                                <small>
                                                                    {{ $pay->payment_date->format('d M Y') }} —
                                                                    <strong>Rp {{ number_format($pay->amount, 0, ',', '.') }}</strong>
                                                                    via {{ $pay->payment_method }}
                                                                    @if ($pay->notes)
                                                                        <em class="text-muted">({{ $pay->notes }})</em>
                                                                    @endif
                                                                </small>
                                                            </div>
                                                            @if ($pay->payment_proof)
                                                                <a href="{{ Storage::url($pay->payment_proof) }}" target="_blank"
                                                                   class="btn btn-sm btn-link p-0">
                                                                    <i class="bx bx-image"></i> Bukti
                                                                </a>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">Belum ada invoice.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== MODAL: Edit Subscription ========== --}}
    <div class="modal fade" id="modalEditSubscription" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold"><i class="bx bx-edit-alt text-primary"></i> Edit Paket Langganan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditSubscription" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tipe Paket <span class="text-danger">*</span></label>
                            <select class="form-select" id="sub_package_type" onchange="toggleDates()">
                                <option value="lifetime" {{ $packageType === 'lifetime' ? 'selected' : '' }}>Lifetime</option>
                                <option value="monthly" {{ $packageType === 'monthly' ? 'selected' : '' }}>Bulanan</option>
                                <option value="yearly" {{ $packageType === 'yearly' ? 'selected' : '' }}>Tahunan</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Jumlah Tagihan <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="sub_billing_amount"
                                   value="{{ $sub ? $sub->billing_amount : 0 }}" min="0" step="1000">
                        </div>
                        <div id="dateFields" style="{{ $packageType === 'lifetime' ? 'display:none;' : '' }}">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Tanggal Mulai</label>
                                <input type="date" class="form-control" id="sub_start_date"
                                       value="{{ $sub && $sub->start_date ? $sub->start_date->format('Y-m-d') : '' }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Tanggal Berakhir</label>
                                <input type="date" class="form-control" id="sub_end_date"
                                       value="{{ $sub && $sub->end_date ? $sub->end_date->format('Y-m-d') : '' }}">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSaveSub">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ========== MODAL: Create Invoice ========== --}}
    <div class="modal fade" id="modalCreateInvoice" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold"><i class="bx bx-receipt text-info"></i> Buat Invoice Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formCreateInvoice" novalidate>
                    @csrf
                    <input type="hidden" id="inv_store_id" value="{{ $store->id }}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Jumlah Tagihan <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="inv_billing_amount"
                                   value="{{ $sub ? $sub->billing_amount : 0 }}" min="1" step="1000">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Periode Mulai <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="inv_period_start">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Periode Akhir <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="inv_period_end">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Jatuh Tempo <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="inv_due_date">
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSaveInvoice">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ========== MODAL: Record Payment ========== --}}
    <div class="modal fade" id="modalPayment" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold"><i class="bx bx-money text-success"></i> Catat Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formPayment" enctype="multipart/form-data" novalidate>
                    @csrf
                    <input type="hidden" id="pay_invoice_id">
                    <div class="modal-body">
                        <div class="alert alert-info py-2 mb-3" id="payInvoiceInfo"></div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Jumlah Bayar <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="pay_amount" min="1" step="1000">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tanggal Bayar <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="pay_date" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Metode Pembayaran <span class="text-danger">*</span></label>
                            <select class="form-select" id="pay_method">
                                <option value="Transfer">Transfer</option>
                                <option value="Cash">Cash</option>
                                <option value="E-Wallet">E-Wallet</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Bukti Bayar (opsional)</label>
                            <input type="file" class="form-control" id="pay_proof" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Catatan (opsional)</label>
                            <textarea class="form-control" id="pay_notes" rows="2" placeholder="Keterangan tambahan..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success" id="btnSavePayment">Simpan Pembayaran</button>
                    </div>
                </form>
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
    <script>
        const csrfToken = '{{ csrf_token() }}';
        const storeId   = {{ $store->id }};

        function showToast(msg, success = true) {
            const el = document.getElementById('notifyToast');
            el.querySelector('.toast-body').innerText = msg;
            el.classList.toggle('text-bg-danger', !success);
            el.classList.toggle('text-bg-success', success);
            new bootstrap.Toast(el).show();
        }

        // ── Toggle date fields ──────────────────────────────────────────────
        function toggleDates() {
            const pkg = document.getElementById('sub_package_type').value;
            document.getElementById('dateFields').style.display = pkg === 'lifetime' ? 'none' : '';
        }

        // ── Open Edit Subscription ──────────────────────────────────────────
        function openEditSubscription() {
            new bootstrap.Modal(document.getElementById('modalEditSubscription')).show();
        }

        // ── Submit Edit Subscription ────────────────────────────────────────
        document.getElementById('formEditSubscription').addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('btnSaveSub');
            btn.disabled = true; btn.textContent = 'Menyimpan...';

            const payload = {
                package_type:   document.getElementById('sub_package_type').value,
                billing_amount: document.getElementById('sub_billing_amount').value,
                start_date:     document.getElementById('sub_start_date').value || null,
                end_date:       document.getElementById('sub_end_date').value || null,
                _token: csrfToken,
                _method: 'PUT',
            };

            fetch(`/subscribed-billing/${storeId}/subscription`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(payload),
            })
            .then(async r => {
                const data = await r.json();
                btn.disabled = false; btn.textContent = 'Simpan';
                if (!r.ok) {
                    showToast(data.message ?? 'Terjadi kesalahan.', false);
                    return;
                }
                bootstrap.Modal.getInstance(document.getElementById('modalEditSubscription')).hide();
                showToast(data.message);
                setTimeout(() => location.reload(), 800);
            })
            .catch(() => {
                btn.disabled = false; btn.textContent = 'Simpan';
                showToast('Terjadi kesalahan jaringan.', false);
            });
        });

        // ── Open Create Invoice ─────────────────────────────────────────────
        function openCreateInvoice() {
            document.getElementById('formCreateInvoice').reset();
            document.getElementById('inv_billing_amount').value = {{ $sub ? $sub->billing_amount : 0 }};
            new bootstrap.Modal(document.getElementById('modalCreateInvoice')).show();
        }

        // ── Submit Create Invoice ───────────────────────────────────────────
        document.getElementById('formCreateInvoice').addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('btnSaveInvoice');
            btn.disabled = true; btn.textContent = 'Menyimpan...';

            const payload = {
                store_id:       document.getElementById('inv_store_id').value,
                billing_amount: document.getElementById('inv_billing_amount').value,
                period_start:   document.getElementById('inv_period_start').value,
                period_end:     document.getElementById('inv_period_end').value,
                due_date:       document.getElementById('inv_due_date').value,
                _token: csrfToken,
            };

            fetch('{{ route("subscribed-billing.store-invoice") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(payload),
            })
            .then(async r => {
                const data = await r.json();
                btn.disabled = false; btn.textContent = 'Simpan';
                if (!r.ok) {
                    showToast(data.message ?? 'Terjadi kesalahan.', false);
                    return;
                }
                bootstrap.Modal.getInstance(document.getElementById('modalCreateInvoice')).hide();
                showToast(data.message);
                setTimeout(() => location.reload(), 800);
            })
            .catch(() => {
                btn.disabled = false; btn.textContent = 'Simpan';
                showToast('Terjadi kesalahan jaringan.', false);
            });
        });

        // ── Open Payment ────────────────────────────────────────────────────
        function openPayment(invoiceId, invoiceNumber, remaining) {
            document.getElementById('formPayment').reset();
            document.getElementById('pay_invoice_id').value = invoiceId;
            document.getElementById('pay_amount').value = remaining;
            document.getElementById('pay_amount').max = remaining;
            document.getElementById('pay_date').value = new Date().toISOString().slice(0, 10);
            document.getElementById('payInvoiceInfo').innerHTML =
                `Invoice <strong>${invoiceNumber}</strong> — Sisa tagihan: <strong>Rp ${Number(remaining).toLocaleString('id-ID')}</strong>`;
            new bootstrap.Modal(document.getElementById('modalPayment')).show();
        }

        // ── Submit Payment ──────────────────────────────────────────────────
        document.getElementById('formPayment').addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('btnSavePayment');
            btn.disabled = true; btn.textContent = 'Menyimpan...';

            const formData = new FormData();
            formData.append('subscribed_invoice_id', document.getElementById('pay_invoice_id').value);
            formData.append('payment_date', document.getElementById('pay_date').value);
            formData.append('amount', document.getElementById('pay_amount').value);
            formData.append('payment_method', document.getElementById('pay_method').value);
            formData.append('notes', document.getElementById('pay_notes').value);
            formData.append('_token', csrfToken);

            const proofFile = document.getElementById('pay_proof').files[0];
            if (proofFile) {
                formData.append('payment_proof', proofFile);
            }

            fetch('{{ route("subscribed-billing.store-payment") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                body: formData,
            })
            .then(async r => {
                const data = await r.json();
                btn.disabled = false; btn.textContent = 'Simpan Pembayaran';
                if (!r.ok) {
                    showToast(data.message ?? 'Terjadi kesalahan.', false);
                    return;
                }
                bootstrap.Modal.getInstance(document.getElementById('modalPayment')).hide();
                showToast(data.message);
                setTimeout(() => location.reload(), 800);
            })
            .catch(() => {
                btn.disabled = false; btn.textContent = 'Simpan Pembayaran';
                showToast('Terjadi kesalahan jaringan.', false);
            });
        });
    </script>
@endpush
