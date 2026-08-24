@extends('layouts.main.main')
@section('title', 'Buat Tiket Servis Baru')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Layanan Servis</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('service-orders.index') }}">Tiket Servis</a></li>
                    <li class="breadcrumb-item active">Buat Tiket Baru</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card rounded-4 p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-0" style="color:#7c3aed">Form Penerimaan Servis / Work Order</h5>
                        <small class="text-muted">Input penerimaan unit/barang dari pelanggan</small>
                    </div>
                    <a href="{{ route('service-orders.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('service-orders.store') }}">
                    @csrf

                    <div class="row">
                        <!-- Data Pelanggan -->
                        <div class="col-md-6 mb-3">
                            <div class="card border p-3 h-100">
                                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-person"></i> Data Pelanggan</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Pilih Mitra/Pelanggan (Opsional)</label>
                                    <select name="customer_id" id="customer_id" class="form-select" onchange="selectCustomer(this)">
                                        <option value="">-- Pelanggan Baru / Walk-in --</option>
                                        @foreach ($customers as $c)
                                            <option value="{{ $c->id }}" data-name="{{ $c->name }}" data-phone="{{ $c->phone }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>
                                                {{ $c->name }} ({{ $c->phone ?? 'Tanpa No. HP' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nama Pelanggan <span class="text-danger">*</span></label>
                                    <input type="text" name="customer_name" id="customer_name" class="form-control" value="{{ old('customer_name') }}" required placeholder="Contoh: Budi Santoso">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">No. Telepon / WhatsApp</label>
                                    <input type="text" name="customer_phone" id="customer_phone" class="form-control" value="{{ old('customer_phone') }}" placeholder="Contoh: 08123456789">
                                </div>
                            </div>
                        </div>

                        <!-- Data Unit / Objek Servis -->
                        <div class="col-md-6 mb-3">
                            <div class="card border p-3 h-100">
                                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-phone"></i> Unit & Objek Servis</h6>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nama Unit / Objek <span class="text-danger">*</span></label>
                                    <input type="text" name="target_name" class="form-control" value="{{ old('target_name') }}" required placeholder="Contoh: iPhone 13 128GB Midnight / Honda Vario 160">
                                    <small class="text-muted">Merk, Tipe, Seri Unit yang diservis</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">No. Identitas Unik (IMEI / Plat Nomor / SN)</label>
                                    <input type="text" name="target_identifier" class="form-control" value="{{ old('target_identifier') }}" placeholder="Contoh: 352938102938472 / B 1234 ABC">
                                    <small class="text-muted">Nomor IMEI, Serial Number, atau Plat Polisi</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Teknisi / Staff Penanggung Jawab</label>
                                    <select name="assigned_staff_id" class="form-select">
                                        <option value="">-- Pilih Teknisi (Opsional) --</option>
                                        @foreach ($staffs as $s)
                                            <option value="{{ $s->id }}" {{ old('assigned_staff_id') == $s->id ? 'selected' : '' }}>
                                                {{ $s->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Keluhan & Diagnosa Awal -->
                    <div class="card border p-3 mb-3">
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-tools"></i> Keluhan & Kerusakan</h6>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Keluhan Pelanggan <span class="text-danger">*</span></label>
                                <textarea name="complaint_notes" class="form-control" rows="3" required placeholder="Contoh: Layar blank hitam setelah jatuh, tombol power keras">{{ old('complaint_notes') }}</textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Diagnosa Awal / Kelengkapan Unit</label>
                                <textarea name="diagnosis_notes" class="form-control" rows="3" placeholder="Contoh: LCD pecah dalam, unit diterima tanpa charger dan SIM card">{{ old('diagnosis_notes') }}</textarea>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Estimasi Selesai</label>
                                <input type="datetime-local" name="estimated_completed_at" class="form-control" value="{{ old('estimated_completed_at') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Garansi Layanan (Hari)</label>
                                <input type="number" name="warranty_days" class="form-control" value="{{ old('warranty_days', 30) }}" placeholder="30">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Uang Muka / DP (Rp)</label>
                                <input type="number" step="any" name="down_payment" class="form-control" value="{{ old('down_payment', 0) }}" placeholder="0">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('service-orders.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-circle"></i> Terbitkan Tiket Servis
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function selectCustomer(select) {
        let opt = select.options[select.selectedIndex];
        if (opt.value) {
            $('#customer_name').val(opt.getAttribute('data-name'));
            $('#customer_phone').val(opt.getAttribute('data-phone'));
        }
    }
</script>
@endpush
