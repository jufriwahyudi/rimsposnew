@extends('layouts.main.main')
@section('title', 'Tiket Servis / Work Order')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Layanan Servis</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active">Tiket Servis / Work Order</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row mb-3">
        <div class="col-sm-12">
            <div class="card rounded-4 p-3">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h5 class="fw-bold mb-0" style="color:#7c3aed">Tiket Servis & Work Order</h5>
                        <small class="text-muted">Kelola penerimaan, status pengerjaan unit, dan tagihan servis</small>
                    </div>
                    <a href="{{ route('service-orders.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Buat Tiket Servis Baru
                    </a>
                </div>

                <!-- Status Cards Filter -->
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-2">
                        <div class="card bg-light border text-center p-2 mb-0 cursor-pointer" onclick="filterStatus('')">
                            <small class="text-muted">Semua</small>
                            <h5 class="fw-bold mb-0">{{ array_sum($statusCounts) }}</h5>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card bg-secondary text-white text-center p-2 mb-0 cursor-pointer" onclick="filterStatus('received')">
                            <small>Diterima</small>
                            <h5 class="fw-bold mb-0">{{ $statusCounts['received'] ?? 0 }}</h5>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card bg-primary text-white text-center p-2 mb-0 cursor-pointer" onclick="filterStatus('in_progress')">
                            <small>Dikerjakan</small>
                            <h5 class="fw-bold mb-0">{{ $statusCounts['in_progress'] ?? 0 }}</h5>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card bg-warning text-dark text-center p-2 mb-0 cursor-pointer" onclick="filterStatus('waiting_parts')">
                            <small>Menunggu Part</small>
                            <h5 class="fw-bold mb-0">{{ $statusCounts['waiting_parts'] ?? 0 }}</h5>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card bg-success text-white text-center p-2 mb-0 cursor-pointer" onclick="filterStatus('completed')">
                            <small>Selesai</small>
                            <h5 class="fw-bold mb-0">{{ $statusCounts['completed'] ?? 0 }}</h5>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card bg-dark text-white text-center p-2 mb-0 cursor-pointer" onclick="filterStatus('delivered')">
                            <small>Diambil / Lunas</small>
                            <h5 class="fw-bold mb-0">{{ $statusCounts['delivered'] ?? 0 }}</h5>
                        </div>
                    </div>
                </div>

                <!-- Filter Form -->
                <div class="row g-2 mb-3">
                    <div class="col-md-3">
                        <select id="filter_status" class="form-select form-select-sm">
                            <option value="">-- Semua Status --</option>
                            <option value="received">Diterima</option>
                            <option value="diagnosing">Pemeriksaan / Diagnosa</option>
                            <option value="waiting_parts">Menunggu Sparepart/Bahan</option>
                            <option value="in_progress">Sedang Dikerjakan</option>
                            <option value="completed">Selesai (Siap Diambil)</option>
                            <option value="delivered">Sudah Diambil / Lunas</option>
                            <option value="cancelled">Batal</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" id="filter_from" class="form-control form-control-sm" placeholder="Dari Tanggal">
                    </div>
                    <div class="col-md-3">
                        <input type="date" id="filter_to" class="form-control form-control-sm" placeholder="Sampai Tanggal">
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="reloadTable()">
                            <i class="bi bi-filter"></i> Filter
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle w-100" id="tbl-service-orders">
                        <thead class="table-light">
                            <tr>
                                <th width="40">#</th>
                                <th>No. Tiket</th>
                                <th>Tanggal</th>
                                <th>Pelanggan</th>
                                <th>Unit / Objek Servis</th>
                                <th>Teknisi / PIC</th>
                                <th>Total Biaya</th>
                                <th>Status</th>
                                <th width="120" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    let table;
    $(document).ready(function() {
        table = $('#tbl-service-orders').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('service-orders.datatables') }}",
                data: function(d) {
                    d.status = $('#filter_status').val();
                    d.from_date = $('#filter_from').val();
                    d.to_date = $('#filter_to').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'order_number', name: 'order_number' },
                { data: 'created_at', name: 'created_at' },
                { data: 'customer_info', name: 'customer_name' },
                { data: 'target_info', name: 'target_name' },
                { data: 'staff', name: 'assignedStaff.name' },
                { data: 'total_cost', name: 'total_cost', searchable: false },
                { data: 'status', name: 'status', className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ],
            order: [[2, 'desc']]
        });

        $('#filter_status, #filter_from, #filter_to').on('change', function() {
            table.draw();
        });
    });

    function reloadTable() {
        table.draw();
    }

    function filterStatus(status) {
        $('#filter_status').val(status);
        table.draw();
    }
</script>
@endpush
