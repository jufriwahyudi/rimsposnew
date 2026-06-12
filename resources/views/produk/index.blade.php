@extends('layouts.main.main')
@section('title', 'Manajemen Produk')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Pengaturan Produk</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Pengaturan</a></li>
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
                        <img src={{ asset('assets/images/alazca_logo.png') }} alt="Logo"
                            style="width: 35px; height: 35px;" class="me-2 mt-1">
                        <div>
                            <h5 class="fw-bold mb-0" style="color: #7c3aed">Pengaturan Produk</h5>
                            <small class="text-muted">{{ session('store_name') }}</small>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalImport">
                            <i class="bi bi-file-earmark-arrow-up"></i> Import Produk
                        </button>
                        <button type="button" class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#modalImportStok">
                            <i class="bi bi-boxes"></i> Import Stok Awal
                        </button>
                        <a href="{{ route('produk.create') }}" class="btn btn-success btn-sm">
                            <i class="bi bi-plus"></i> Tambah Produk
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <table id="tbl-produk" class="table table-bordered w-100">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Produk</th>
                                <th class="text-center" width="8%">Varian</th>
                                <th class="text-center" width="10%">Stok Gudang</th>
                                <th class="text-center" width="10%">Stok Toko</th>
                                <th class="text-center" width="12%">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Import -->
    <div class="modal fade" id="modalImport" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalImportLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="form-import" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalImportLabel"><i class="bi bi-file-earmark-arrow-up text-primary"></i> Import Produk via Excel/CSV</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info border-0 bg-info-subtle text-dark p-3 rounded-4 mb-4">
                            <h6 class="fw-bold mb-1">Panduan Pengisian Data:</h6>
                            <p class="small mb-2">
                                Unduh berkas template terlebih dahulu, isi data produk & varian, lalu unggah kembali ke formulir di bawah ini.
                            </p>
                            <a href="{{ route('produk.import.template') }}" class="btn btn-primary btn-sm fw-semibold shadow-sm">
                                <i class="bi bi-download"></i> Unduh Template ({{ $isFnB ? 'F&B' : 'Retail' }})
                            </a>
                        </div>
                        
                        <div class="mb-3">
                            <label for="import_file" class="form-label fw-bold">Pilih File Excel/CSV</label>
                            <input type="file" class="form-control" id="import_file" name="file" accept=".xlsx, .xls, .csv" required>
                            <div class="form-text">Mendukung format .xlsx, .xls, .csv dengan batas ukuran 5 MB.</div>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="dry_run" name="dry_run" value="1" checked>
                            <label class="form-check-label fw-bold text-warning" for="dry_run">
                                <i class="bi bi-shield-check"></i> Hanya Dry Run (Simulasi Validasi Data)
                            </label>
                            <div class="form-text">
                                Jika dicentang, sistem hanya akan memvalidasi data dan menampilkan hasilnya tanpa menyimpannya ke database.
                            </div>
                        </div>

                        <!-- Progress / Loading Bar -->
                        <div id="import-loading" class="d-none text-center my-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted fw-bold" id="import-loading-text">Sedang menganalisis & memvalidasi data berkas...</p>
                        </div>

                        <!-- Results Dashboard -->
                        <div id="import-result-container" class="d-none">
                            <hr>
                            <h6 class="fw-bold mb-3"><i class="bi bi-clipboard-data text-primary"></i> Hasil Laporan Validasi</h6>
                            
                            <div class="row g-3 text-center mb-4">
                                <div class="col-md-4">
                                    <div class="card bg-light border-0 p-3 rounded-4">
                                        <h4 class="fw-bold mb-1 text-dark" id="res-total">0</h4>
                                        <small class="text-muted">Total Baris</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-success-subtle border-0 p-3 rounded-4">
                                        <h4 class="fw-bold mb-1 text-success" id="res-valid">0</h4>
                                        <small class="text-success-emphasis">Valid</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-danger-subtle border-0 p-3 rounded-4">
                                        <h4 class="fw-bold mb-1 text-danger" id="res-invalid">0</h4>
                                        <small class="text-danger-emphasis">Error</small>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-success border-0 bg-success-subtle p-3 rounded-3 d-none" id="alert-import-success">
                                <i class="bi bi-check-circle-fill"></i> <span id="text-import-success">Simulasi berhasil! Semua data valid.</span>
                            </div>

                            <div class="alert alert-danger border-0 bg-danger-subtle p-3 rounded-3 d-none" id="alert-import-error">
                                <i class="bi bi-exclamation-triangle-fill"></i> Terdapat data tidak valid. Mohon perbaiki berkas Anda sebelum mengimpor.
                            </div>

                            <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                <table class="table table-sm table-striped table-hover border" id="table-import-details">
                                    <thead class="table-dark sticky-top">
                                        <tr>
                                            <th class="text-center" width="8%">Baris</th>
                                            <th>Kode</th>
                                            <th>Nama Produk</th>
                                            <th>Varian</th>
                                            <th class="text-center" width="12%">Status</th>
                                            <th>Keterangan / Detail Error</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Row Details Go Here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary btn-sm" id="btn-submit-import">Mulai Impor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Import Stok -->
    <div class="modal fade" id="modalImportStok" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalImportStokLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="form-import-stok" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalImportStokLabel"><i class="bi bi-boxes text-info"></i> Import Stok Awal via Excel/CSV</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info border-0 bg-info-subtle text-dark p-3 rounded-4 mb-4">
                            <h6 class="fw-bold mb-1">Panduan Pengisian Stok:</h6>
                            <p class="small mb-2">
                                Berkas template yang diunduh akan otomatis terisi dengan seluruh katalog produk varian Anda yang berstatus <strong>Lacak Stok Aktif (track_stock = true)</strong>.
                                Anda cukup mengisi kolom <strong>Posisi (store/warehouse)</strong>, <strong>Jumlah Stok</strong>, dan <strong>Harga Beli/Modal</strong>.
                            </p>
                            <a href="{{ route('produk.import.template-stok') }}" class="btn btn-info btn-sm text-white fw-semibold shadow-sm">
                                <i class="bi bi-download"></i> Unduh Template Stok Pre-filled
                            </a>
                        </div>

                        <div class="mb-3">
                            <label for="transaction_type" class="form-label fw-bold">Jenis Transaksi Stok</label>
                            <select class="form-select" id="transaction_type" name="transaction_type" required>
                                <option value="stock_adjustment" selected>Penyesuaian Stok (Stock Adjustment)</option>
                                <option value="purchase_order">Pembelian / PO (Purchase Order & Goods Receipt)</option>
                            </select>
                            <div class="form-text">
                                Pilih <strong>Penyesuaian Stok</strong> untuk inisialisasi koreksi saldo persediaan secara langsung, atau <strong>Pembelian / PO</strong> untuk membuat dokumen PO dan Penerimaan Barang otomatis yang terikat ke Vendor.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="import_stok_file" class="form-label fw-bold">Pilih File Excel/CSV Hasil Pengisian</label>
                            <input type="file" class="form-control" id="import_stok_file" name="file" accept=".xlsx, .xls, .csv" required>
                            <div class="form-text">Mendukung format .xlsx, .xls, .csv dengan batas ukuran 5 MB.</div>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="dry_run_stok" name="dry_run" value="1" checked>
                            <label class="form-check-label fw-bold text-warning" for="dry_run_stok">
                                <i class="bi bi-shield-check"></i> Hanya Dry Run (Simulasi Validasi Stok)
                            </label>
                            <div class="form-text">
                                Jika dicentang, sistem hanya akan memvalidasi kecocokan SKU dan nominal tanpa menyimpannya ke kartu stok/database.
                            </div>
                        </div>

                        <!-- Progress / Loading Bar -->
                        <div id="import-stok-loading" class="d-none text-center my-4">
                            <div class="spinner-border text-info" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted fw-bold" id="import-stok-loading-text">Sedang memproses & memvalidasi data stok...</p>
                        </div>

                        <!-- Results Dashboard -->
                        <div id="import-stok-result-container" class="d-none">
                            <hr>
                            <h6 class="fw-bold mb-3"><i class="bi bi-clipboard-data text-info"></i> Hasil Laporan Validasi Stok</h6>
                            
                            <div class="row g-3 text-center mb-4">
                                <div class="col-md-4">
                                    <div class="card bg-light border-0 p-3 rounded-4">
                                        <h4 class="fw-bold mb-1 text-dark" id="res-stok-total">0</h4>
                                        <small class="text-muted">Total Baris</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-success-subtle border-0 p-3 rounded-4">
                                        <h4 class="fw-bold mb-1 text-success" id="res-stok-valid">0</h4>
                                        <small class="text-success-emphasis">Valid</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-danger-subtle border-0 p-3 rounded-4">
                                        <h4 class="fw-bold mb-1 text-danger" id="res-stok-invalid">0</h4>
                                        <small class="text-danger-emphasis">Error</small>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-success border-0 bg-success-subtle p-3 rounded-3 d-none" id="alert-import-stok-success">
                                <i class="bi bi-check-circle-fill"></i> <span id="text-import-stok-success">Simulasi berhasil! Semua data valid.</span>
                            </div>

                            <div class="alert alert-danger border-0 bg-danger-subtle p-3 rounded-3 d-none" id="alert-import-stok-error">
                                <i class="bi bi-exclamation-triangle-fill"></i> Terdapat data tidak valid. Mohon perbaiki berkas Anda sebelum mengimpor.
                            </div>

                            <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                <table class="table table-sm table-striped table-hover border" id="table-import-stok-details">
                                    <thead class="table-dark sticky-top">
                                        <tr>
                                            <th class="text-center" width="8%">Baris</th>
                                            <th>SKU</th>
                                            <th>Nama Produk</th>
                                            <th>Varian</th>
                                            <th class="text-center" width="12%">Status</th>
                                            <th>Hasil / Detail Kesalahan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Row Details Go Here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-info btn-sm text-white" id="btn-submit-import-stok">Mulai Impor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            $('#tbl-produk').DataTable({
                serverSide: true,
                processing: true,
                ajax: '{{ route('produk.datatables') }}',
                columns: [{
                        data: 'kode_produk',
                        name: 'kode_produk'
                    },
                    {
                        data: 'nama_produk',
                        name: 'nama_produk'
                    },
                    {
                        data: 'variants_count',
                        name: 'variants_count',
                        searchable: false,
                        className: 'text-end'
                    },
                    {
                        data: 'stock_warehouse',
                        name: 'stock_warehouse',
                        searchable: false,
                        className: 'text-end',
                        render: d => d ?? 0
                    },
                    {
                        data: 'stock_store',
                        name: 'stock_store',
                        searchable: false,
                        className: 'text-end',
                        render: d => d ?? 0
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
                    [1, 'asc']
                ],
                language: {
                    search: 'Cari:',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    info: 'Menampilkan _START_ – _END_ dari _TOTAL_ produk',
                    infoEmpty: 'Tidak ada data',
                    infoFiltered: '(difilter dari _MAX_ total)',
                    zeroRecords: 'Produk tidak ditemukan',
                    paginate: {
                        previous: '&laquo;',
                        next: '&raquo;'
                    },
                    processing: '<div class="spinner-border spinner-border-sm text-primary"></div> Memuat...',
                },
            });

            // Handle Import Form Submission
            $('#form-import').on('submit', function(e) {
                e.preventDefault();
                
                let formData = new FormData(this);
                let btnSubmit = $('#btn-submit-import');
                let loadingArea = $('#import-loading');
                let resultArea = $('#import-result-container');
                let successAlert = $('#alert-import-success');
                let errorAlert = $('#alert-import-error');
                let detailsTable = $('#table-import-details tbody');

                // Reset UI
                btnSubmit.prop('disabled', true);
                loadingArea.removeClass('d-none');
                resultArea.addClass('d-none');
                successAlert.addClass('d-none');
                errorAlert.addClass('d-none');
                detailsTable.empty();

                let isDryRun = $('#dry_run').is(':checked');
                $('#import-loading-text').text(isDryRun ? 'Sedang mensimulasikan validasi data...' : 'Sedang mengimpor data produk...');

                $.ajax({
                    url: '{{ route('produk.import.proses') }}',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        loadingArea.addClass('d-none');
                        btnSubmit.prop('disabled', false);

                        if (res.success_status) {
                            $('#res-total').text(res.total_rows);
                            $('#res-valid').text(res.valid_rows);
                            $('#res-invalid').text(res.invalid_rows);
                            
                            // Render details table
                            if (res.results && res.results.length > 0) {
                                res.results.forEach(row => {
                                    let badge = row.status === 'valid' 
                                        ? '<span class="badge bg-success text-white">Valid</span>' 
                                        : '<span class="badge bg-danger text-white">Error</span>';
                                    
                                    let errorsMsg = row.errors.length > 0 
                                        ? `<ul class="mb-0 text-danger ps-3"><li>${row.errors.join('</li><li>')}</li></ul>`
                                        : '<span class="text-success fw-bold"><i class="bi bi-check2"></i> Siap</span>';

                                    detailsTable.append(`
                                        <tr>
                                            <td class="text-center fw-bold">${row.row}</td>
                                            <td>${row.kode_produk || '-'}</td>
                                            <td>${row.nama_produk || '-'}</td>
                                            <td>${row.variant_name || '-'}</td>
                                            <td class="text-center">${badge}</td>
                                            <td class="small">${errorsMsg}</td>
                                        </tr>
                                    `);
                                });
                            }

                            resultArea.removeClass('d-none');

                            if (res.success) {
                                // If successfully imported (not dry run, no errors)
                                if (!res.is_dry_run) {
                                    Swal.fire({
                                        title: 'Sukses!',
                                        text: `Berhasil mengimpor ${res.imported_count} produk/varian.`,
                                        icon: 'success'
                                    }).then(() => {
                                        $('#modalImport').modal('hide');
                                        $('#tbl-produk').DataTable().ajax.reload();
                                    });
                                } else {
                                    // Dry run successful with 0 errors
                                    $('#text-import-success').text('Simulasi berhasil! Semua data valid. Hilangkan centang "Dry Run" lalu klik "Mulai Impor" kembali untuk menyimpan ke database.');
                                    successAlert.removeClass('d-none');
                                }
                            } else {
                                // There were errors or dry run failed because of invalid rows
                                errorAlert.removeClass('d-none');
                            }

                        } else {
                            Swal.fire('Gagal', res.message || 'Terjadi kesalahan.', 'error');
                        }
                    },
                    error: function(xhr) {
                        loadingArea.addClass('d-none');
                        btnSubmit.prop('disabled', false);
                        let err = xhr.responseJSON;
                        Swal.fire('Gagal', (err && err.message) ? err.message : 'Terjadi kesalahan sistem.', 'error');
                    }
                });
            });

            // Reset results when modal is closed
            $('#modalImport').on('hidden.bs.modal', function () {
                $('#form-import')[0].reset();
                $('#import-result-container').addClass('d-none');
                $('#alert-import-success').addClass('d-none');
                $('#alert-import-error').addClass('d-none');
                $('#table-import-details tbody').empty();
            });

            // Handle Stock Import Form Submission
            $('#form-import-stok').on('submit', function(e) {
                e.preventDefault();
                
                let formData = new FormData(this);
                let btnSubmit = $('#btn-submit-import-stok');
                let loadingArea = $('#import-stok-loading');
                let resultArea = $('#import-stok-result-container');
                let successAlert = $('#alert-import-stok-success');
                let errorAlert = $('#alert-import-stok-error');
                let detailsTable = $('#table-import-stok-details tbody');

                // Reset UI
                btnSubmit.prop('disabled', true);
                loadingArea.removeClass('d-none');
                resultArea.addClass('d-none');
                successAlert.addClass('d-none');
                errorAlert.addClass('d-none');
                detailsTable.empty();

                let isDryRun = $('#dry_run_stok').is(':checked');
                $('#import-stok-loading-text').text(isDryRun ? 'Sedang mensimulasikan validasi stok...' : 'Sedang mengimpor stok...');

                $.ajax({
                    url: '{{ route('produk.import.proses-stok') }}',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        loadingArea.addClass('d-none');
                        btnSubmit.prop('disabled', false);

                        if (res.success_status) {
                            $('#res-stok-total').text(res.total_rows);
                            $('#res-stok-valid').text(res.valid_rows);
                            $('#res-stok-invalid').text(res.invalid_rows);
                            
                            // Render details table
                            if (res.results && res.results.length > 0) {
                                res.results.forEach(row => {
                                    let badge = row.status === 'valid' 
                                        ? '<span class="badge bg-success text-white">Valid</span>' 
                                        : '<span class="badge bg-danger text-white">Error</span>';
                                    
                                    let errorsMsg = row.errors.length > 0 
                                        ? `<ul class="mb-0 text-danger ps-3"><li>${row.errors.join('</li><li>')}</li></ul>`
                                        : `<span class="text-success fw-bold"><i class="bi bi-check2"></i> Qty: ${row.qty} (${row.posisi})</span>`;

                                    detailsTable.append(`
                                        <tr>
                                            <td class="text-center fw-bold">${row.row}</td>
                                            <td>${row.sku || '-'}</td>
                                            <td>${row.nama_produk || '-'}</td>
                                            <td>${row.nama_varian || '-'}</td>
                                            <td class="text-center">${badge}</td>
                                            <td class="small">${errorsMsg}</td>
                                        </tr>
                                    `);
                                });
                            }

                            resultArea.removeClass('d-none');

                            if (res.success) {
                                // If successfully imported (not dry run, no errors)
                                if (!res.is_dry_run) {
                                    Swal.fire({
                                        title: 'Sukses!',
                                        text: `Berhasil mengimpor ${res.imported_count} record stok.`,
                                        icon: 'success'
                                    }).then(() => {
                                        $('#modalImportStok').modal('hide');
                                        $('#tbl-produk').DataTable().ajax.reload();
                                    });
                                } else {
                                    // Dry run successful with 0 errors
                                    $('#text-import-stok-success').text('Simulasi berhasil! Semua data valid. Hilangkan centang "Dry Run" lalu klik "Mulai Impor" kembali untuk menyimpan.');
                                    successAlert.removeClass('d-none');
                                }
                            } else {
                                // There were errors
                                errorAlert.removeClass('d-none');
                            }

                        } else {
                            Swal.fire('Gagal', res.message || 'Terjadi kesalahan.', 'error');
                        }
                    },
                    error: function(xhr) {
                        loadingArea.addClass('d-none');
                        btnSubmit.prop('disabled', false);
                        let err = xhr.responseJSON;
                        Swal.fire('Gagal', (err && err.message) ? err.message : 'Terjadi kesalahan sistem.', 'error');
                    }
                });
            });

            // Reset results when modal is closed
            $('#modalImportStok').on('hidden.bs.modal', function () {
                $('#form-import-stok')[0].reset();
                $('#import-stok-result-container').addClass('d-none');
                $('#alert-import-stok-success').addClass('d-none');
                $('#alert-import-stok-error').addClass('d-none');
                $('#table-import-stok-details tbody').empty();
            });
        });
    </script>
@endpush
