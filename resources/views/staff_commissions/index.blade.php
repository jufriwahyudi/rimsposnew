@extends('layouts.main.main')
@section('title', 'Rekap Komisi & Sharing Fee Staff')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Layanan Servis</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active">Rekap Komisi Staff</li>
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
                        <h5 class="fw-bold mb-0" style="color:#7c3aed">Buku Rekap Komisi & Sharing Fee Staff</h5>
                        <small class="text-muted">Perhitungan dan pencairan komisi hasil pengerjaan jasa servis & penjualan</small>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card bg-warning bg-opacity-10 border-warning p-3 mb-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted fw-bold">TOTAL KOMISI PENDING (BELUM DIBAYAR)</small>
                                    <h3 class="fw-bold text-warning mb-0">Rp {{ number_format($pendingTotal, 0, ',', '.') }}</h3>
                                </div>
                                <i class="bi bi-clock-history fs-1 text-warning"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-success bg-opacity-10 border-success p-3 mb-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted fw-bold">TOTAL KOMISI TELAH DICAIRKAN</small>
                                    <h3 class="fw-bold text-success mb-0">Rp {{ number_format($paidTotal, 0, ',', '.') }}</h3>
                                </div>
                                <i class="bi bi-check-circle fs-1 text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Controls -->
                <div class="row g-2 mb-3">
                    <div class="col-md-3">
                        <select id="filter_staff" class="form-select form-select-sm">
                            <option value="">-- Semua Staff / Teknisi --</option>
                            @foreach ($staffs as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="filter_status" class="form-select form-select-sm">
                            <option value="">-- Semua Status --</option>
                            <option value="pending" selected>Belum Dibayar (Pending)</option>
                            <option value="paid">Sudah Dibayar (Paid)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" id="filter_from" class="form-control form-control-sm" placeholder="Dari Tanggal">
                    </div>
                    <div class="col-md-2">
                        <input type="date" id="filter_to" class="form-control form-control-sm" placeholder="Sampai Tanggal">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="reloadTable()">
                            <i class="bi bi-filter"></i> Filter
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle w-100" id="tbl-commissions">
                        <thead class="table-light">
                            <tr>
                                <th width="40">#</th>
                                <th>Tanggal</th>
                                <th>Nama Staff</th>
                                <th>Referensi</th>
                                <th>Item Layanan / Produk</th>
                                <th>Harga Jual</th>
                                <th>Rate Komisi</th>
                                <th>Nominal Komisi</th>
                                <th>Status</th>
                                <th width="90" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Settle Single Commission -->
    <div class="modal fade" id="modalSettleSingle" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('staff-commissions.settle') }}">
                    @csrf
                    <input type="hidden" name="commission_ids[]" id="single_commission_id">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Konfirmasi Pembayaran Komisi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Anda akan memproses pencairan komisi sebesar <strong id="single_amount_text" class="text-success fs-5">Rp 0</strong>.</p>
                        <p class="text-muted small">Pembayaran ini otomatis akan dicatat sebagai <strong>Pengeluaran Kas Toko (Expense)</strong> dengan kategori <em>Komisi Staff & Teknisi</em>.</p>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tanggal Pembayaran</label>
                            <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Metode Pembayaran</label>
                            <select name="payment_method" class="form-select">
                                <option value="cash" selected>Kas / Tunai</option>
                                <option value="transfer">Transfer Bank</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Catatan Pengeluaran</label>
                            <input type="text" name="notes" class="form-control" placeholder="Contoh: Pencairan komisi servis LCD">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success"><i class="bi bi-cash"></i> Bayar Sekarang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    let table;
    $(document).ready(function() {
        table = $('#tbl-commissions').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('staff-commissions.datatables') }}",
                data: function(d) {
                    d.staff_id = $('#filter_staff').val();
                    d.status = $('#filter_status').val();
                    d.from_date = $('#filter_from').val();
                    d.to_date = $('#filter_to').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'created_at', name: 'created_at' },
                { data: 'staff_name', name: 'staff.name' },
                { data: 'ref_info', name: 'ref_info', searchable: false },
                { data: 'item_name', name: 'item_name' },
                { data: 'item_price', name: 'item_price' },
                { data: 'rate_info', name: 'rate_info', searchable: false },
                { data: 'commission_amount', name: 'commission_amount' },
                { data: 'status', name: 'status', className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ],
            order: [[1, 'desc']]
        });

        $('#filter_staff, #filter_status, #filter_from, #filter_to').on('change', function() {
            table.draw();
        });
    });

    function reloadTable() {
        table.draw();
    }

    function settleSingle(id, amount) {
        $('#single_commission_id').val(id);
        $('#single_amount_text').text('Rp ' + new Intl.NumberFormat('id-ID').format(amount));
        let modal = new bootstrap.Modal(document.getElementById('modalSettleSingle'));
        modal.show();
    }
</script>
@endpush
