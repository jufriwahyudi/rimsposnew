@extends('layouts.main.main')
@section('title', 'Laporan Shift Kasir & Rekonsiliasi')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Laporan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active">Laporan Shift Kasir</li>
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
                        <img src="{{ asset('assets/images/alazca_logo.png') }}" alt="Logo" style="width:35px;height:35px;" class="me-2 mt-1">
                        <div>
                            <h5 class="fw-bold mb-0" style="color:#7c3aed">Laporan Shift Kasir & Rekonsiliasi Kas</h5>
                            <small class="text-muted">Riwayat pembukaan, penutupan, dan selisih kas fisik per sesi kasir</small>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- FILTER BAR --}}
                    <div class="row g-3 mb-4 align-items-end">
                        <div class="col-md-3 col-6">
                            <label class="form-label small fw-semibold">Dari Tanggal</label>
                            <input type="date" id="filter_from" class="form-control" value="{{ date('Y-m-01') }}">
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label small fw-semibold">Sampai Tanggal</label>
                            <input type="date" id="filter_to" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label small fw-semibold">Status Sesi</label>
                            <select id="filter_status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="open">Buka (Aktif)</option>
                                <option value="closed">Tutup (Selesai)</option>
                            </select>
                        </div>
                        <div class="col-md-3 col-6 d-flex gap-2">
                            <button type="button" class="btn btn-primary w-100" onclick="reloadTable()">
                                <i class="bi bi-filter"></i> Filter
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetFilter()">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                        </div>
                    </div>

                    {{-- DATATABLE --}}
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle" id="shiftTable" style="width: 100%;">
                            <thead class="table-light">
                                <tr>
                                    <th width="4%">No</th>
                                    <th>Waktu Buka</th>
                                    <th>Waktu Tutup</th>
                                    <th>Kasir</th>
                                    <th class="text-end">Modal Awal</th>
                                    <th class="text-end">Penjualan Tunai</th>
                                    <th class="text-end">Kas Seharusnya</th>
                                    <th class="text-end">Kas Fisik</th>
                                    <th class="text-center">Selisih</th>
                                    <th class="text-center">Status</th>
                                    <th width="10%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let table;

        $(document).ready(function () {
            table = $('#shiftTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('cash-registers.datatables') }}",
                    data: function (d) {
                        d.from_date = $('#filter_from').val();
                        d.to_date = $('#filter_to').val();
                        d.status = $('#filter_status').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'opened_at', name: 'opened_at' },
                    { data: 'closed_at', name: 'closed_at' },
                    { data: 'cashier_name', name: 'cashier.name' },
                    { data: 'opening_cash', name: 'opening_cash', className: 'text-end' },
                    { data: 'total_cash_sales', name: 'total_cash_sales', className: 'text-end' },
                    { data: 'expected_cash', name: 'expected_cash', className: 'text-end' },
                    { data: 'actual_cash', name: 'actual_cash', className: 'text-end' },
                    { data: 'cash_difference', name: 'cash_difference', className: 'text-center' },
                    { data: 'status', name: 'status', className: 'text-center' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                ],
                order: [[1, 'desc']],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
                }
            });
        });

        function reloadTable() {
            table.ajax.reload();
        }

        function resetFilter() {
            $('#filter_from').val("{{ date('Y-m-01') }}");
            $('#filter_to').val("{{ date('Y-m-d') }}");
            $('#filter_status').val('');
            reloadTable();
        }
    </script>
@endpush
