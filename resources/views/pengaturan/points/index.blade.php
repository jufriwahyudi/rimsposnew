@extends('layouts.main.main')
@section('title', 'Loyalty Points Settings')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Pengaturan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active">Loyalty Points Settings</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-9 col-sm-12 mx-auto">
            <div class="card rounded-4 p-2 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center mb-3 bg-transparent border-0">
                    <div class="d-flex align-items-start">
                        <div class="avatar avatar-md bg-light-primary text-primary rounded-3 me-2 p-1">
                            <i class="material-icons-outlined" style="font-size:28px">card_membership</i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-primary">Loyalty Points Configuration</h5>
                            <small class="text-muted">Kelola program poin loyalitas member</small>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form id="formPointSettings">
                        @csrf
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <div class="form-check form-switch p-3 bg-light rounded-3 d-flex align-items-center justify-content-between">
                                    <div>
                                        <label class="form-check-label fw-bold text-dark mb-1" for="is_active">Aktifkan Loyalty Points</label>
                                        <br><small class="text-muted">Aktifkan modul loyalitas keanggotaan</small>
                                    </div>
                                    <input class="form-check-input ms-0" type="checkbox" id="is_active" name="is_active" value="1" {{ $settings->is_active ? 'checked' : '' }} style="width: 2.5em; height: 1.3em;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch p-3 bg-light rounded-3 d-flex align-items-center justify-content-between">
                                    <div>
                                        <label class="form-check-label fw-bold text-dark mb-1" for="is_override">Kustomisasi Cabang Ini</label>
                                        <br><small class="text-muted">Gunakan pengaturan khusus untuk cabang ini saja</small>
                                    </div>
                                    <input class="form-check-input ms-0" type="checkbox" id="is_override" name="is_override" value="1" {{ $isOverride ? 'checked' : '' }} style="width: 2.5em; height: 1.3em;">
                                </div>
                            </div>
                        </div>

                        <!-- Bootstrap Tabs -->
                        <ul class="nav nav-tabs nav-primary" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active d-flex align-items-center" data-bs-toggle="tab" data-bs-target="#earning-rules" type="button" role="tab">
                                    <i class="material-icons-outlined me-1 fs-5">monetization_on</i> Perolehan Poin
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link d-flex align-items-center" data-bs-toggle="tab" data-bs-target="#redemption-rules" type="button" role="tab">
                                    <i class="material-icons-outlined me-1 fs-5">shopping_cart</i> Penukaran Poin
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link d-flex align-items-center" data-bs-toggle="tab" data-bs-target="#expiration-bonus" type="button" role="tab">
                                    <i class="material-icons-outlined me-1 fs-5">date_range</i> Masa Berlaku & Bonus
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content py-4">
                            <!-- Earning Rules Tab -->
                            <div class="tab-pane fade show active" id="earning-rules" role="tabpanel">
                                <h6 class="fw-bold mb-3"><i class="bx bx-cog text-primary me-1"></i> Metode Kalkulasi Perolehan</h6>
                                <div class="mb-4 col-md-8">
                                    <label class="form-label fw-semibold">Metode Perolehan Poin</label>
                                    <select class="form-select" name="earning_method" id="earning_method">
                                        <option value="transaction" {{ $settings->earning_method === 'transaction' ? 'selected' : '' }}>Nominal Transaksi (Transaction-based)</option>
                                        <option value="product" {{ $settings->earning_method === 'product' ? 'selected' : '' }}>Berdasarkan Produk (Product-Specific)</option>
                                        <option value="hybrid" {{ $settings->earning_method === 'hybrid' ? 'selected' : '' }}>Hybrid (Gabungan Nominal & Produk)</option>
                                    </select>
                                    <small class="text-muted d-block mt-1">
                                        Pilih bagaimana member mendapatkan poin saat berbelanja.
                                    </small>
                                </div>

                                <div class="row g-3 mb-4" id="ratio-fields">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Kelipatan Belanja (Rupiah)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" class="form-control" name="earning_threshold" value="{{ (int)$settings->earning_threshold }}" min="1">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Poin yang Didapat</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="earning_points" value="{{ $settings->earning_points }}" min="1">
                                            <span class="input-group-text">Poin</span>
                                        </div>
                                    </div>
                                </div>

                                <h6 class="fw-bold mb-3"><i class="bx bx-block text-danger me-1"></i> Pengecualian Perolehan Poin (Exclusions)</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-check form-check-danger p-2 border rounded-3 bg-light-subtle d-flex align-items-center">
                                            <input class="form-check-input ms-2 me-3" type="checkbox" id="exclude_tax" name="exclude_tax" value="1" {{ $settings->exclude_tax ? 'checked' : '' }}>
                                            <label class="form-check-label text-dark fw-semibold" for="exclude_tax">
                                                Kecualikan Pajak (PPN/PB1)
                                                <br><small class="text-muted fw-normal">Poin tidak dihitung dari nilai pajak</small>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-check-danger p-2 border rounded-3 bg-light-subtle d-flex align-items-center">
                                            <input class="form-check-input ms-2 me-3" type="checkbox" id="exclude_service_charge" name="exclude_service_charge" value="1" {{ $settings->exclude_service_charge ? 'checked' : '' }}>
                                            <label class="form-check-label text-dark fw-semibold" for="exclude_service_charge">
                                                Kecualikan Service Charge
                                                <br><small class="text-muted fw-normal">Khusus FnB, poin tidak dihitung dari biaya layanan</small>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-check-danger p-2 border rounded-3 bg-light-subtle d-flex align-items-center">
                                            <input class="form-check-input ms-2 me-3" type="checkbox" id="exclude_delivery_fee" name="exclude_delivery_fee" value="1" {{ $settings->exclude_delivery_fee ? 'checked' : '' }}>
                                            <label class="form-check-label text-dark fw-semibold" for="exclude_delivery_fee">
                                                Kecualikan Biaya Kirim
                                                <br><small class="text-muted fw-normal">Ongkos kirim pesanan tidak mendapatkan poin</small>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-check-danger p-2 border rounded-3 bg-light-subtle d-flex align-items-center">
                                            <input class="form-check-input ms-2 me-3" type="checkbox" id="exclude_promo_items" name="exclude_promo_items" value="1" {{ $settings->exclude_promo_items ? 'checked' : '' }}>
                                            <label class="form-check-label text-dark fw-semibold" for="exclude_promo_items">
                                                Kecualikan Produk Diskon/Promo
                                                <br><small class="text-muted fw-normal">Barang diskon coret tidak menghasilkan poin</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Redemption Rules Tab -->
                            <div class="tab-pane fade" id="redemption-rules" role="tabpanel">
                                <h6 class="fw-bold mb-3"><i class="bx bx-transfer text-primary me-1"></i> Nilai & Batas Penukaran</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Nilai Konversi (1 Poin = ... Rupiah)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" class="form-control" name="point_value" value="{{ (int)$settings->point_value }}" min="1">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Minimal Saldo Poin untuk Redeem</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="min_points_to_redeem" value="{{ $settings->min_points_to_redeem }}" min="0">
                                            <span class="input-group-text">Poin</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Maksimal Persentase Pembayaran (%)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="max_redeem_percentage" value="{{ (int)$settings->max_redeem_percentage }}" min="1" max="100">
                                            <span class="input-group-text">% dari Total Bill</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Maksimal Nominal Potongan Poin</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" class="form-control" name="max_redeem_amount" value="{{ (int)$settings->max_redeem_amount }}" min="0">
                                        </div>
                                        <small class="text-muted">Masukkan 0 untuk tanpa batasan nominal rupiah</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Expiration & Bonus Tab -->
                            <div class="tab-pane fade" id="expiration-bonus" role="tabpanel">
                                <h6 class="fw-bold mb-3"><i class="bx bx-time-five text-primary me-1"></i> Masa Berlaku Poin (Expiration)</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Tipe Masa Berlaku</label>
                                        <select class="form-select" name="expiration_type" id="expiration_type">
                                            <option value="never" {{ $settings->expiration_type === 'never' ? 'selected' : '' }}>Poin Tidak Pernah Kedaluwarsa</option>
                                            <option value="duration" {{ $settings->expiration_type === 'duration' ? 'selected' : '' }}>Durasi Relatif (Bulan)</option>
                                            <option value="fixed_date" {{ $settings->expiration_type === 'fixed_date' ? 'selected' : '' }}>Tanggal Tetap Setiap Tahun</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6" id="exp-duration-field" style="display: {{ $settings->expiration_type === 'duration' ? 'block' : 'none' }}">
                                        <label class="form-label fw-semibold">Hangus Setelah (Bulan)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="expiration_duration_months" value="{{ $settings->expiration_duration_months }}" min="1">
                                            <span class="input-group-text">Bulan sejak diperoleh</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6" id="exp-fixed-field" style="display: {{ $settings->expiration_type === 'fixed_date' ? 'block' : 'none' }}">
                                        <label class="form-label fw-semibold">Tanggal Hangus Tahunan (MM-DD)</label>
                                        <input type="text" class="form-control" name="expiration_fixed_date" value="{{ $settings->expiration_fixed_date }}" placeholder="Contoh: 12-31 (31 Desember)">
                                    </div>
                                </div>

                                <h6 class="fw-bold mb-3"><i class="bx bx-gift text-primary me-1"></i> Bonus & Promosi Keanggotaan</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Poin Pendaftaran Baru (Welcome Points)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="welcome_points" value="{{ $settings->welcome_points }}" min="0">
                                            <span class="input-group-text">Poin</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Birthday Point Multiplier</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bx bx-cake"></i></span>
                                            <input type="number" class="form-control" step="0.1" name="birthday_multiplier" value="{{ $settings->birthday_multiplier }}" min="1">
                                            <span class="input-group-text">x Lipat Poin</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border-top pt-3 text-end">
                            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm" id="btnSaveSettings">
                                <i class="material-icons-outlined align-middle me-1" style="font-size: 18px">save</i> Simpan Pengaturan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Toggle input fields based on earning method selection
        function toggleEarningFields() {
            const method = document.getElementById('earning_method').value;
            const ratioFields = document.getElementById('ratio-fields');
            if (method === 'product') {
                ratioFields.style.opacity = '0.5';
                ratioFields.querySelectorAll('input').forEach(el => el.disabled = true);
            } else {
                ratioFields.style.opacity = '1';
                ratioFields.querySelectorAll('input').forEach(el => el.disabled = false);
            }
        }

        // Toggle input fields based on expiration type selection
        function toggleExpirationFields() {
            const type = document.getElementById('expiration_type').value;
            document.getElementById('exp-duration-field').style.display = type === 'duration' ? 'block' : 'none';
            document.getElementById('exp-fixed-field').style.display = type === 'fixed_date' ? 'block' : 'none';
        }

        document.getElementById('earning_method').addEventListener('change', toggleEarningFields);
        document.getElementById('expiration_type').addEventListener('change', toggleExpirationFields);

        // Run on load
        toggleEarningFields();
        toggleExpirationFields();

        // AJAX Form Submit
        document.getElementById('formPointSettings').addEventListener('submit', function(e) {
            e.preventDefault();

            const btn = document.getElementById('btnSaveSettings');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Menyimpan...';

            const formData = new FormData(this);
            // Handle checkboxes explicitly since unchecked checkboxes don't send anything in FormData
            const checkboxes = ['is_active', 'is_override', 'exclude_tax', 'exclude_service_charge', 'exclude_delivery_fee', 'exclude_promo_items'];
            checkboxes.forEach(cb => {
                if (!formData.has(cb)) {
                    formData.append(cb, '0');
                }
            });

            // Convert to JSON
            const data = {};
            formData.forEach((value, key) => data[key] = value);

            fetch('{{ route('settings.points.update') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(res => {
                btn.disabled = false;
                btn.innerHTML = '<i class="material-icons-outlined align-middle me-1" style="font-size:18px">save</i> Simpan Pengaturan';

                if (res.success) {
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message || 'Gagal menyimpan pengaturan.');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="material-icons-outlined align-middle me-1" style="font-size:18px">save</i> Simpan Pengaturan';
                alert('Terjadi kesalahan koneksi.');
            });
        });
    </script>
@endpush
