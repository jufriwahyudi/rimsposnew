@extends('layouts.main.main')
@section('title', 'Daftar Cash Register & Shift Kasir')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Kasir & Laporan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active">Daftar Cash Register</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card rounded-4 p-2 shadow-sm border-0">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2 border-bottom pb-3">
                    <div class="d-flex align-items-center">
                        <div class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-calculator-fill fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Daftar Cash Register (Shift Kasir)</h5>
                            <small class="text-muted">Riwayat pembukaan shift, transaksi penjualan, mutasi kas, dan rekonsiliasi kasir</small>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- FILTER BAR --}}
                    <div class="p-3 bg-light rounded-4 mb-4 border">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3 col-6">
                                <label class="form-label small fw-bold text-secondary">
                                    <i class="bi bi-calendar3 me-1"></i> Dari Tanggal
                                </label>
                                <input type="date" id="filter_from" class="form-control form-control-sm rounded-3" value="{{ date('Y-m-01') }}">
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label small fw-bold text-secondary">
                                    <i class="bi bi-calendar-check me-1"></i> Sampai Tanggal
                                </label>
                                <input type="date" id="filter_to" class="form-control form-control-sm rounded-3" value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label small fw-bold text-secondary">
                                    <i class="bi bi-toggle2-on me-1"></i> Status Sesi
                                </label>
                                <select id="filter_status" class="form-select form-select-sm rounded-3">
                                    <option value="">Semua Status Sesi</option>
                                    <option value="open">Buka (Aktif)</option>
                                    <option value="closed">Tutup (Selesai)</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-6 d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-primary w-100 rounded-3 d-flex align-items-center justify-content-center" onclick="reloadTable()">
                                    <i class="bi bi-filter me-1"></i> Terapkan Filter
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-3" onclick="resetFilter()" title="Reset Filter">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- DATATABLE --}}
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle" id="shiftTable" style="width: 100%;">
                            <thead class="table-light text-secondary small">
                                <tr>
                                    <th width="4%">No</th>
                                    <th>Waktu Buka</th>
                                    <th>Waktu Tutup</th>
                                    <th>Kasir</th>
                                    <th class="text-end">Modal Awal</th>
                                    <th class="text-end">Penjualan Tunai</th>
                                    <th class="text-end">Total Penjualan</th>
                                    <th class="text-end">Kas Seharusnya</th>
                                    <th class="text-end">Kas Fisik</th>
                                    <th class="text-center">Selisih</th>
                                    <th class="text-center">Status</th>
                                    <th width="16%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL PREVIEW CETAKAN TUTUP KASIR --}}
    <div class="modal fade" id="modalPreviewReceipt" tabindex="-1" aria-labelledby="modalPreviewReceiptLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 480px;">
            <div class="modal-content rounded-4 shadow-lg border-0">
                <div class="modal-header bg-dark text-white border-0 py-3">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-printer-fill fs-5 me-2 text-warning"></i>
                        <div>
                            <h6 class="modal-title fw-bold mb-0" id="modalPreviewReceiptLabel">Preview Cetakan Tutup Kasir</h6>
                            <small class="text-white-50" style="font-size: 11px;">Format Struk Thermal Kasir</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                {{-- PAPER WIDTH TOGGLE --}}
                <div class="px-3 py-2 bg-light border-bottom d-flex justify-content-between align-items-center">
                    <span class="small text-muted fw-bold">Ukuran Kertas:</span>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary active" id="btnPaper80" onclick="setReceiptWidth('80mm')">80mm</button>
                        <button type="button" class="btn btn-outline-secondary" id="btnPaper58" onclick="setReceiptWidth('58mm')">58mm</button>
                    </div>
                </div>

                <div class="modal-body p-3 bg-slate-100" id="previewModalContentContainer" style="background-color: #e2e8f0; min-height: 250px;">
                    {{-- RECEIPT CONTENT WILL BE LOADED HERE --}}
                    <div class="text-center py-5" id="previewLoadingSpinner">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Memuat...</span>
                        </div>
                        <p class="small text-muted mt-2">Memuat struk cetakan tutup kasir...</p>
                    </div>
                </div>

                <div class="modal-footer bg-light border-top d-flex justify-content-between">
                    <a href="#" target="_blank" id="btnDirectPrintPage" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Buka Tab Baru
                    </a>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="button" class="btn btn-sm btn-primary px-3" onclick="printReceiptFrame()">
                            <i class="bi bi-printer me-1"></i> Cetak Struk
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let table;
        let currentRegisterId = null;

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
                    { data: 'total_sales', name: 'total_sales', className: 'text-end fw-semibold' },
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

        /**
         * Open Thermal Receipt Preview Modal
         */
        function previewShiftReceipt(registerId) {
            currentRegisterId = registerId;
            const modalEl = document.getElementById('modalPreviewReceipt');
            const modal = new bootstrap.Modal(modalEl);
            const container = $('#previewModalContentContainer');
            
            // Set Direct Print Page Link
            const printUrl = "{{ url('/cash-registers/print') }}/" + registerId;
            $('#btnDirectPrintPage').attr('href', printUrl);

            // Show Spinner
            container.html(`
                <div class="text-center py-5" id="previewLoadingSpinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Memuat...</span>
                    </div>
                    <p class="small text-muted mt-2">Memuat struk cetakan tutup kasir...</p>
                </div>
            `);

            modal.show();

            // Load Partial Preview via AJAX
            $.ajax({
                url: "{{ url('/cash-registers/preview') }}/" + registerId,
                type: 'GET',
                success: function (html) {
                    container.html(html);
                },
                error: function (xhr) {
                    container.html(`
                        <div class="alert alert-danger my-3">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            Gagal memuat pratinjau struk: ${xhr.responseJSON?.message || 'Terjadi kesalahan pada server.'}
                        </div>
                    `);
                }
            });
        }

        /**
         * Switch paper width inside modal preview
         */
        function setReceiptWidth(size) {
            const receipt = $('#printableReceiptContent');
            if (size === '58mm') {
                receipt.removeClass('paper-80mm').addClass('paper-58mm');
                $('#btnPaper58').addClass('active');
                $('#btnPaper80').removeClass('active');
            } else {
                receipt.removeClass('paper-58mm').addClass('paper-80mm');
                $('#btnPaper80').addClass('active');
                $('#btnPaper58').removeClass('active');
            }
        }

        /**
         * Print directly from the modal preview
         */
        function printReceiptFrame() {
            if (!currentRegisterId) return;
            const printUrl = "{{ url('/cash-registers/print') }}/" + currentRegisterId;
            
            const printWindow = window.open(printUrl, '_blank', 'width=450,height=600');
            if (printWindow) {
                printWindow.focus();
            }
        }
    </script>
@endpush
