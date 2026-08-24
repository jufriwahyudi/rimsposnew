@extends('layouts.main.main')
@section('title', 'Edit Tiket Servis #' . $serviceOrder->order_number)

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Layanan Servis</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('service-orders.index') }}">Tiket Servis</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('service-orders.show', $serviceOrder->id) }}">#{{ $serviceOrder->order_number }}</a></li>
                    <li class="breadcrumb-item active">Edit</li>
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
                        <h5 class="fw-bold mb-0" style="color:#7c3aed">Edit Tiket Servis #{{ $serviceOrder->order_number }}</h5>
                        <small class="text-muted">Perbarui data unit dan keterangan tiket</small>
                    </div>
                    <a href="{{ route('service-orders.show', $serviceOrder->id) }}" class="btn btn-sm btn-outline-secondary">
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

                <form method="POST" action="{{ route('service-orders.update', $serviceOrder->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card border p-3 h-100">
                                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-person"></i> Data Pelanggan</h6>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nama Pelanggan <span class="text-danger">*</span></label>
                                    <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name', $serviceOrder->customer_name) }}" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">No. Telepon / WhatsApp</label>
                                    <input type="text" name="customer_phone" class="form-control" value="{{ old('customer_phone', $serviceOrder->customer_phone) }}">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="card border p-3 h-100">
                                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-phone"></i> Unit & Objek Servis</h6>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nama Unit / Objek <span class="text-danger">*</span></label>
                                    <input type="text" name="target_name" class="form-control" value="{{ old('target_name', $serviceOrder->target_name) }}" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">No. Identitas (IMEI / Plat Nomor / SN)</label>
                                    <input type="text" name="target_identifier" class="form-control" value="{{ old('target_identifier', $serviceOrder->target_identifier) }}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Teknisi / Staff Penanggung Jawab</label>
                                    <select name="assigned_staff_id" class="form-select">
                                        <option value="">-- Belum Ditugaskan --</option>
                                        @foreach ($staffs as $s)
                                            <option value="{{ $s->id }}" {{ old('assigned_staff_id', $serviceOrder->assigned_staff_id) == $s->id ? 'selected' : '' }}>
                                                {{ $s->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border p-3 mb-3">
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-tools"></i> Keluhan & Status</h6>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Keluhan Pelanggan <span class="text-danger">*</span></label>
                                <textarea name="complaint_notes" class="form-control" rows="3" required>{{ old('complaint_notes', $serviceOrder->complaint_notes) }}</textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Hasil Diagnosa / Keterangan</label>
                                <textarea name="diagnosis_notes" class="form-control" rows="3">{{ old('diagnosis_notes', $serviceOrder->diagnosis_notes) }}</textarea>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Status Tiket</label>
                                <select name="status" class="form-select">
                                    <option value="received" {{ old('status', $serviceOrder->status) == 'received' ? 'selected' : '' }}>Diterima</option>
                                    <option value="diagnosing" {{ old('status', $serviceOrder->status) == 'diagnosing' ? 'selected' : '' }}>Pemeriksaan / Diagnosa</option>
                                    <option value="waiting_parts" {{ old('status', $serviceOrder->status) == 'waiting_parts' ? 'selected' : '' }}>Menunggu Sparepart/Bahan</option>
                                    <option value="in_progress" {{ old('status', $serviceOrder->status) == 'in_progress' ? 'selected' : '' }}>Sedang Dikerjakan</option>
                                    <option value="completed" {{ old('status', $serviceOrder->status) == 'completed' ? 'selected' : '' }}>Selesai</option>
                                    <option value="delivered" {{ old('status', $serviceOrder->status) == 'delivered' ? 'selected' : '' }}>Sudah Diambil / Lunas</option>
                                    <option value="cancelled" {{ old('status', $serviceOrder->status) == 'cancelled' ? 'selected' : '' }}>Batal</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Estimasi Selesai</label>
                                <input type="datetime-local" name="estimated_completed_at" class="form-control" value="{{ old('estimated_completed_at', $serviceOrder->estimated_completed_at ? $serviceOrder->estimated_completed_at->format('Y-m-d\TH:i') : '') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Garansi (Hari)</label>
                                <input type="number" name="warranty_days" class="form-control" value="{{ old('warranty_days', $serviceOrder->warranty_days) }}">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('service-orders.show', $serviceOrder->id) }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-circle"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
