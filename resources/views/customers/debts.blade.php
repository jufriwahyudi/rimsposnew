@extends('layouts.main.main')
@section('title', 'Pelunasan Hutang Kolektif')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Penjualan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active">Pelunasan Hutang</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <!-- Alert Success / Error -->
    @if (session('success'))
        <div class="alert alert-success border-0 bg-success-subtle alert-dismissible fade show p-3 mb-4 rounded-3 shadow-sm">
            <div class="d-flex align-items-center gap-2">
                <i class="material-icons-outlined text-success fs-4">check_circle</i>
                <div>
                    {!! session('success') !!}
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger border-0 bg-danger-subtle alert-dismissible fade show p-3 mb-4 rounded-3 shadow-sm">
            <div class="d-flex align-items-center gap-2">
                <i class="material-icons-outlined text-danger fs-4">error_outline</i>
                <div>
                    {{ session('error') }}
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger border-0 bg-danger-subtle p-3 mb-4 rounded-3 shadow-sm">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <!-- Customer Selector Card -->
        <div class="col-md-12 mb-4">
            <div class="card rounded-4 border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3" style="color:#7c3aed">Pilih Mitra / Pelanggan</h5>
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <select id="select-customer" class="form-select select2-bootstrap-5" style="width: 100%;">
                                <option value="">-- Pilih Mitra --</option>
                                @foreach ($customers as $cust)
                                    <option value="{{ $cust->id }}">{{ $cust->name }} {{ $cust->phone ? '(' . $cust->phone . ')' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mt-3 mt-md-0">
                            <div class="p-3 bg-light rounded-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted small d-block">Total Hutang Mitra Terpilih</span>
                                    <h4 class="fw-bold mb-0 text-danger" id="display-total-debt">Rp 0</h4>
                                </div>
                                <i class="material-icons-outlined text-danger fs-1">account_balance_wallet</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-5 d-none" id="section-payment-form">
            <!-- Collective Payment Form -->
            <div class="card rounded-4 border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h5 class="fw-bold mb-0 text-dark">Form Pembayaran Kolektif</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('customers.debts.pay-collective') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="customer_id" id="hidden-customer-id">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Jumlah Pembayaran (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" id="input-amount" class="form-control form-control-lg" placeholder="Masukkan nominal pembayaran" required min="1">
                            <div class="form-text text-muted small mt-1">Pembayaran akan otomatis memotong transaksi hutang paling lama (FIFO).</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Metode Pembayaran <span class="text-danger">*</span></label>
                            <select name="payment_method" id="select-payment-method" class="form-select form-select-lg" required>
                                <option value="cash" selected>Tunai (Cash)</option>
                                <option value="transfer">Transfer Bank</option>
                            </select>
                        </div>

                        <div class="mb-3 d-none" id="group-bank">
                            <label class="form-label fw-semibold">Rekening Bank Tujuan <span class="text-danger">*</span></label>
                            <select name="akun_bank" class="form-select form-select-lg">
                                <option value="">-- Pilih Rekening Tujuan --</option>
                                @foreach ($akunkas as $a)
                                    <option value="{{ $a->id }}">{{ $a->no_rek}} - {{ $a->nama_rek }} ({{ $a->bank_rek }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Upload Bukti Pembayaran <small class="text-muted">(Opsional)</small></label>
                            <input type="file" name="bukti_bayar" class="form-control" accept="image/*">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" style="background:#7c3aed; border-color:#7c3aed">
                                <i class="material-icons-outlined fs-5 align-middle me-1">payment</i> Proses Pelunasan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Debts List Table -->
        <div class="col-md-7 d-none" id="section-debts-list">
            <div class="card rounded-4 border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h5 class="fw-bold mb-0 text-dark">Rincian Hutang Belum Lunas</h5>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle" id="tbl-debts-detail">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice</th>
                                    <th>Tanggal</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Sisa Hutang</th>
                                </tr>
                            </thead>
                            <tbody id="debts-table-body">
                                <!-- Ajax populated -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div class="col-md-12 py-5 text-center text-muted" id="debts-empty-state">
            <i class="material-icons-outlined text-muted" style="font-size: 80px;">account_balance_wallet</i>
            <h5 class="mt-3">Pilih Mitra Terlebih Dahulu</h5>
            <p>Silakan pilih nama mitra/pelanggan di atas untuk menampilkan rincian tagihan hutang.</p>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize Select2 if available
            if ($.fn.select2) {
                $('#select-customer').select2({
                    theme: 'bootstrap-5',
                    placeholder: '-- Pilih Mitra --'
                });
            }

            // Watch customer selection change
            $('#select-customer').on('change', function() {
                const customerId = $(this).val();
                
                if (!customerId) {
                    $('#section-payment-form').addClass('d-none');
                    $('#section-debts-list').addClass('d-none');
                    $('#debts-empty-state').removeClass('d-none');
                    $('#display-total-debt').text('Rp 0');
                    $('#hidden-customer-id').val('');
                    return;
                }

                // Show loading
                $('#debts-empty-state').addClass('d-none');
                $('#display-total-debt').html('<span class="spinner-border spinner-border-sm"></span> Memuat...');

                fetch(`{{ route('customers.debts.index') }}?customer_id=${customerId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    // Update debt amount display
                    $('#display-total-debt').text('Rp ' + parseFloat(data.total_debt).toLocaleString('id-ID'));
                    $('#hidden-customer-id').val(customerId);

                    if (data.debts.length === 0) {
                        $('#debts-table-body').html('<tr><td colspan="4" class="text-center text-success py-3 fw-semibold">Tidak ada hutang belum lunas!</td></tr>');
                        $('#section-payment-form').addClass('d-none');
                        $('#section-debts-list').removeClass('d-none');
                        return;
                    }

                    // Populate table
                    let html = '';
                    data.debts.forEach(debt => {
                        html += `
                            <tr>
                                <td>
                                    <a href="/sales/${debt.id}" class="fw-bold text-primary" target="_blank">
                                        ${debt.invoice_number}
                                    </a>
                                </td>
                                <td>${debt.sale_date}</td>
                                <td class="text-end">Rp ${parseFloat(debt.grand_total).toLocaleString('id-ID')}</td>
                                <td class="text-end fw-bold text-danger">Rp ${parseFloat(debt.remaining).toLocaleString('id-ID')}</td>
                            </tr>
                        `;
                    });
                    
                    $('#debts-table-body').html(html);
                    
                    // Show forms and lists
                    $('#section-payment-form').removeClass('d-none');
                    $('#section-debts-list').removeClass('d-none');
                })
                .catch(err => {
                    console.error('Error fetching debts:', err);
                    alert('Gagal mengambil data hutang.');
                    $('#display-total-debt').text('Rp 0');
                });
            });

            // Toggle bank accounts field based on payment method
            $('#select-payment-method').on('change', function() {
                const method = $(this).val();
                if (method === 'transfer') {
                    $('#group-bank').removeClass('d-none');
                    $('#group-bank select').attr('required', true);
                } else {
                    $('#group-bank').addClass('d-none');
                    $('#group-bank select').removeAttr('required');
                }
            });
        });
    </script>
@endpush
