@extends('layouts.main.main')
@section('title', 'Purchase Orders')

@push('styles')
    {{-- DateRangePicker CSS --}}
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <style>
        /* ===== PO Page Custom Styles ===== */
        .po-header-card {
            background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%);
            border-radius: 16px;
            padding: 24px 28px;
            color: #fff;
            margin-bottom: 20px;
            box-shadow: 0 8px 30px rgba(124, 58, 237, 0.25);
        }
        .po-header-card h4 { font-weight: 700; margin: 0; font-size: 1.4rem; }
        .po-header-card small { opacity: 0.8; font-size: 0.85rem; }

        /* Filter Card */
        .filter-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.07);
            margin-bottom: 20px;
        }
        .filter-card .card-body { padding: 20px 24px; }
        .filter-card label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 5px;
        }
        .filter-card .form-control,
        .filter-card .form-select {
            border-radius: 8px;
            font-size: 0.88rem;
            border: 1.5px solid #e5e7eb;
            transition: border-color .2s;
        }
        .filter-card .form-control:focus,
        .filter-card .form-select:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124,58,237,.12);
        }
        .btn-filter {
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 8px 20px;
            transition: opacity .2s;
        }
        .btn-filter:hover { opacity: .88; color:#fff; }
        .btn-reset {
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 8px 18px;
            border: 1.5px solid #e5e7eb;
            color: #6b7280;
            background: #fff;
            transition: all .2s;
        }
        .btn-reset:hover { border-color: #7c3aed; color: #7c3aed; }

        /* Table Card */
        .table-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.07);
            overflow: hidden;
        }
        .table-card .card-header {
            background: #fff;
            border-bottom: 1.5px solid #f3f4f6;
            padding: 16px 24px;
        }
        .table-card .card-header h6 { font-weight: 700; color: #1f2937; margin: 0; }
        .po-table thead th {
            background: #f9fafb;
            color: #6b7280;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            border-bottom: 1.5px solid #f3f4f6;
            padding: 12px 16px;
            white-space: nowrap;
        }
        .po-table tbody td {
            padding: 13px 16px;
            vertical-align: middle;
            font-size: 0.875rem;
            border-bottom: 1px solid #f9fafb;
            color: #374151;
        }
        .po-table tbody tr:last-child td { border-bottom: none; }
        .po-table tbody tr:hover { background: #faf5ff; transition: background .15s; }

        .po-number-badge { font-weight: 700; color: #4f46e5; font-size: 0.85rem; }

        /* Status Badges */
        .badge-status {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 30px;
            letter-spacing: .03em;
            text-transform: uppercase;
            display: inline-block;
        }
        .badge-draft     { background: #f3f4f6; color: #6b7280; }
        .badge-submitted { background: #eff6ff; color: #2563eb; }
        .badge-approved  { background: #f0fdf4; color: #16a34a; }
        .badge-rejected  { background: #fef2f2; color: #dc2626; }
        .badge-partial   { background: #fff7ed; color: #ea580c; }
        .badge-received  { background: #ecfdf5; color: #059669; }

        /* Action Buttons */
        .btn-action {
            font-size: 0.78rem;
            font-weight: 600;
            padding: 5px 13px;
            border-radius: 7px;
            border: none;
            transition: all .2s;
            cursor: pointer;
        }
        .btn-submit-po  { background:#fef9c3; color:#a16207; }
        .btn-submit-po:hover  { background:#fde047; color:#713f12; }
        .btn-receive { background:#dcfce7; color:#15803d; text-decoration:none; }
        .btn-receive:hover { background:#bbf7d0; color:#14532d; }
        .btn-del { background:#fee2e2; color:#dc2626; }
        .btn-del:hover { background:#fca5a5; color:#991b1b; }

        /* Pagination */
        .pagination { gap: 4px; margin: 0; }
        .page-link {
            border-radius: 8px !important;
            border: 1.5px solid #e5e7eb;
            color: #6b7280;
            font-size: 0.85rem;
            padding: 6px 13px;
            transition: all .2s;
        }
        .page-link:hover { border-color: #7c3aed; color: #7c3aed; background:#faf5ff; }
        .page-item.active .page-link {
            background: linear-gradient(135deg,#7c3aed,#4f46e5);
            border-color: transparent;
            color: #fff;
        }
        .page-item.disabled .page-link { opacity: .5; }

        .daterangepicker { font-size: 0.85rem; border-radius: 12px; }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Purchase Orders</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Purchase Orders</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')

{{-- ===== HEADER ===== --}}
<div class="po-header-card d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div class="d-flex align-items-center gap-3">
        <div style="background:rgba(255,255,255,.2);border-radius:12px;padding:10px 13px;line-height:1">
            <i class="bx bx-cart-alt fs-3"></i>
        </div>
        <div>
            <h4>Purchase Orders</h4>
            <small><i class="bx bx-store me-1"></i>{{ session('store_name') }}</small>
        </div>
    </div>
    <a href="{{ route('po.create') }}" class="btn btn-light fw-bold px-4 py-2 rounded-3"
       style="color:#7c3aed;font-size:.875rem">
        <i class="bx bx-plus me-1"></i> Tambah PO
    </a>
</div>

{{-- ===== ALERT ===== --}}
@if (session('success'))
    <div class="alert alert-success border-0 rounded-3 d-flex align-items-center gap-2 mb-3 shadow-sm" role="alert">
        <i class="bx bx-check-circle fs-5"></i>
        {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger border-0 rounded-3 d-flex align-items-center gap-2 mb-3 shadow-sm" role="alert">
        <i class="bx bx-error-circle fs-5"></i>
        {{ session('error') }}
    </div>
@endif

{{-- ===== FILTER CARD ===== --}}
<div class="card filter-card">
    <div class="card-body">
        <form method="GET" action="{{ route('po.index') }}" id="filter-form">
            <div class="row g-3 align-items-end">

                {{-- Date Range --}}
                <div class="col-12 col-md-4">
                    <label><i class="bx bx-calendar me-1"></i>Tanggal PO</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"
                              style="border:1.5px solid #e5e7eb;border-right:none;border-radius:8px 0 0 8px">
                            <i class="bx bx-calendar-range text-muted"></i>
                        </span>
                        <input type="text" id="date_range" name="date_range"
                               class="form-control"
                               style="border:1.5px solid #e5e7eb;border-left:none;border-radius:0 8px 8px 0"
                               placeholder="Pilih rentang tanggal..."
                               value="{{ request('date_range') }}"
                               readonly>
                    </div>
                </div>

                {{-- No PO --}}
                <div class="col-12 col-md-3">
                    <label><i class="bx bx-hash me-1"></i>No PO</label>
                    <input type="text" name="po_number" class="form-control"
                           placeholder="Cari No PO..."
                           value="{{ request('po_number') }}">
                </div>

                {{-- Status --}}
                <div class="col-12 col-md-2">
                    <label><i class="bx bx-filter me-1"></i>Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        @foreach(['DRAFT','SUBMITTED','APPROVED','REJECTED','PARTIAL_RECEIVED','RECEIVED'] as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Buttons --}}
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-filter w-100">
                        <i class="bx bx-search me-1"></i>Cari
                    </button>
                    <a href="{{ route('po.index') }}" class="btn btn-reset w-100">
                        <i class="bx bx-reset me-1"></i>Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ===== TABLE CARD ===== --}}
<div class="card table-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6><i class="bx bx-list-ul me-2" style="color:#7c3aed"></i>Daftar Purchase Order</h6>
        <small class="text-muted">
            Menampilkan {{ $pos->firstItem() ?? 0 }}–{{ $pos->lastItem() ?? 0 }}
            dari <strong>{{ $pos->total() }}</strong> PO
        </small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table po-table mb-0">
                <thead>
                    <tr>
                        <th style="width:4%">#</th>
                        <th>Tanggal PO</th>
                        <th>No PO</th>
                        <th>Vendor</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end">Pajak</th>
                        <th class="text-end">Diskon</th>
                        <th class="text-end">Grand Total</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" style="width:170px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pos as $index => $po)
                        <tr>
                            <td class="text-muted">{{ $pos->firstItem() + $index }}</td>
                            <td>
                                <span style="font-size:.85rem;font-weight:600">
                                    {{ $po->request_date->format('d M Y') }}
                                </span>
                            </td>
                            <td><span class="po-number-badge">{{ $po->po_number }}</span></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:30px;height:30px;border-radius:8px;
                                                background:linear-gradient(135deg,#7c3aed,#4f46e5);
                                                display:flex;align-items:center;justify-content:center;
                                                color:#fff;font-size:.7rem;font-weight:700;flex-shrink:0">
                                        {{ strtoupper(substr($po->vendor->nama_vendor ?? '-', 0, 2)) }}
                                    </div>
                                    <span style="font-size:.85rem">{{ $po->vendor->nama_vendor ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="text-end" style="font-size:.85rem">{{ number_format($po->subtotal) }}</td>
                            <td class="text-end" style="font-size:.85rem">{{ number_format($po->tax_total) }}</td>
                            <td class="text-end" style="font-size:.85rem">{{ number_format($po->discount_total) }}</td>
                            <td class="text-end fw-bold" style="font-size:.875rem;color:#1f2937">
                                Rp {{ number_format($po->grand_total) }}
                            </td>
                            <td class="text-center">
                                @php
                                    $badgeClass = match($po->status) {
                                        'DRAFT'            => 'badge-draft',
                                        'SUBMITTED'        => 'badge-submitted',
                                        'APPROVED'         => 'badge-approved',
                                        'REJECTED'         => 'badge-rejected',
                                        'PARTIAL_RECEIVED' => 'badge-partial',
                                        'RECEIVED'         => 'badge-received',
                                        default            => 'badge-draft',
                                    };
                                @endphp
                                <span class="badge-status {{ $badgeClass }}">{{ $po->status }}</span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center flex-wrap">

                                    {{-- DRAFT: Submit + Hapus --}}
                                    @if ($po->status === 'DRAFT')
                                        <form class="form-submit" method="post"
                                              action="{{ route('po.submit', $po) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-action btn-submit-po">
                                                <i class="bx bx-send me-1"></i>Submit
                                            </button>
                                        </form>
                                        <form class="form-delete" method="post"
                                              action="{{ route('po.destroy', $po) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-action btn-del btn-delete"
                                                    data-status="DRAFT">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </form>
                                    @endif

                                    {{-- SUBMITTED: Waiting Badge --}}
                                    @if ($po->status === 'SUBMITTED')
                                        <span class="badge-status badge-submitted">
                                            <i class="bx bx-time-five me-1"></i>Menunggu Approval
                                        </span>
                                    @endif

                                    {{-- APPROVED: Terima Barang + Hapus --}}
                                    @if ($po->status === 'APPROVED')
                                        <a href="{{ route('gr.create', $po) }}"
                                           class="btn btn-action btn-receive">
                                            <i class="bx bx-box me-1"></i>Terima
                                        </a>
                                        <form class="form-delete" method="post"
                                              action="{{ route('po.destroy', $po) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-action btn-del btn-delete"
                                                    data-status="APPROVED">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </form>
                                    @endif

                                    {{-- PARTIAL / RECEIVED: hanya Terima Barang --}}
                                    @if (in_array($po->status, ['PARTIAL_RECEIVED', 'RECEIVED']))
                                        <a href="{{ route('gr.create', $po) }}"
                                           class="btn btn-action btn-receive">
                                            <i class="bx bx-box me-1"></i>Terima Barang
                                        </a>
                                    @endif

                                    {{-- REJECTED: No action --}}
                                    @if ($po->status === 'REJECTED')
                                        <span class="text-muted" style="font-size:.78rem">—</span>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center gap-2" style="color:#9ca3af">
                                    <i class="bx bx-cart-alt" style="font-size:3rem"></i>
                                    <div class="fw-bold">Tidak ada data Purchase Order</div>
                                    <small>Coba ubah filter pencarian atau buat PO baru</small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if ($pos->hasPages())
        <div class="card-footer bg-white border-top border-light d-flex justify-content-between align-items-center px-4 py-3">
            <small class="text-muted">
                Halaman {{ $pos->currentPage() }} dari {{ $pos->lastPage() }}
            </small>
            {{ $pos->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

@endsection

@push('scripts')
    {{-- Moment.js + DateRangePicker --}}
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <script>
        // ===== DateRangePicker Init =====
        $('#date_range').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Hapus',
                applyLabel: 'Terapkan',
                fromLabel: 'Dari',
                toLabel: 'Sampai',
                format: 'DD/MM/YYYY',
                separator: ' - ',
                daysOfWeek: ['Min','Sen','Sel','Rab','Kam','Jum','Sab'],
                monthNames: ['Januari','Februari','Maret','April','Mei','Juni',
                             'Juli','Agustus','September','Oktober','November','Desember'],
                firstDay: 1
            },
            opens: 'left',
        });

        $('#date_range').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(
                picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY')
            );
        });

        $('#date_range').on('cancel.daterangepicker', function() {
            $(this).val('');
        });

        // ===== Konfirmasi Submit PO =====
        document.querySelectorAll('.form-submit').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Kirim PO?',
                    text: 'PO akan dikirim untuk approval. Lanjutkan?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#7c3aed',
                    confirmButtonText: 'Ya, Kirim',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) form.submit();
                });
            });
        });

        // ===== Konfirmasi Hapus PO =====
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function() {
                const form   = this.closest('.form-delete');
                const status = this.dataset.status;
                const extra  = status === 'APPROVED'
                    ? ' PO berstatus APPROVED akan dihapus beserta seluruh item-nya.'
                    : '';

                Swal.fire({
                    title: 'Hapus PO?',
                    text: 'PO yang dihapus tidak dapat dikembalikan!' + extra,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) form.submit();
                });
            });
        });
    </script>
@endpush
