@extends('layouts.main.main')

@section('title', 'Activity Logs & Audit')

@section('breadcrumb')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3 text-indigo fw-bold">Superadmin</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i> Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Activity Logs & Audit</li>
                </ol>
            </nav>
        </div>
    </div>
@endsection

@section('content')
    <div class="card rounded-4 border-0 shadow-sm mb-4">
        <div class="card-body">
            <h5 class="fw-bold mb-3 text-indigo"><i class="bx bx-filter-alt me-1"></i> Filter Log Aktivitas</h5>
            <form method="GET" action="{{ route('superadmin.activity-logs') }}" class="row g-3">
                {{-- Filter Store --}}
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Pilih Toko</label>
                    <select name="store_id" class="form-select select2-input">
                        <option value="">Semua Toko</option>
                        @foreach ($stores as $s)
                            <option value="{{ $s->id }}" {{ request('store_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Type --}}
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Jenis Aktivitas</label>
                    <select name="type" class="form-select">
                        <option value="">Semua Kategori</option>
                        <option value="sale" {{ request('type') == 'sale' ? 'selected' : '' }}>Penjualan (POS)</option>
                        <option value="stock" {{ request('type') == 'stock' ? 'selected' : '' }}>Stok (Opname/Adj)</option>
                        <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>Biaya Operasional</option>
                        <option value="audit" {{ request('type') == 'audit' ? 'selected' : '' }}>Sesi Audit</option>
                    </select>
                </div>

                {{-- Date range start --}}
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Tanggal Mulai</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>

                {{-- Date range end --}}
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Tanggal Selesai</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>

                {{-- Text Search --}}
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Cari Deskripsi / No Referensi</label>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Inv / SO / Ket..." value="{{ request('search') }}">
                        <button type="submit" class="btn btn-indigo fw-bold px-3"><i class="bx bx-search"></i></button>
                        <a href="{{ route('superadmin.activity-logs') }}" class="btn btn-outline-secondary"><i class="bx bx-refresh"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Log Timeline / Table --}}
    <div class="card rounded-4 border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-indigo">
                <i class="bx bx-history me-1"></i> Timeline Aktivitas Audit Harian
            </h6>
        </div>
        <div class="card-body">
            @forelse ($paginatedActivities as $act)
                <div class="d-flex align-items-start gap-3 py-3 border-bottom">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width:42px;height:42px;background:var(--bs-{{ $act->color }}-bg-subtle);">
                        <i class="material-icons-outlined text-{{ $act->color }} fs-4">{{ $act->icon }}</i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div>
                                <span class="badge bg-{{ $act->color }}-subtle text-{{ $act->color }} border-0 px-2 py-1 rounded me-2" style="font-size: 11px;">{{ $act->type }}</span>
                                <small class="text-muted"><i class="bx bx-time-five me-1"></i>{{ \Carbon\Carbon::parse($act->timestamp)->format('d M Y - H:i:s') }}</small>
                            </div>
                            <small class="text-indigo-emphasis fw-semibold bg-indigo-subtle px-2 py-0.5 rounded" style="font-size: 11px;">
                                <i class="bx bx-store me-1"></i>Store ID: {{ $act->store_id }}
                            </small>
                        </div>
                        <p class="mb-0 text-slate-800 fs-6">{!! $act->message !!}</p>
                    </div>
                </div>
            @empty
                <div class="text-center py-5 text-muted">
                    <i class="bx bx-info-circle fs-1 mb-2 d-block text-secondary"></i>
                    Tidak ada log aktivitas yang cocok dengan filter pencarian.
                </div>
            @endforelse

            {{-- Pagination Links --}}
            <div class="d-flex justify-content-center mt-4">
                {!! $paginatedActivities->links('pagination::bootstrap-5') !!}
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .text-indigo { color: #4f46e5 !important; }
        .btn-indigo {
            color: #fff;
            background-color: #4f46e5;
            border-color: #4f46e5;
        }
        .btn-indigo:hover {
            color: #fff;
            background-color: #4338ca;
            border-color: #4338ca;
        }
        .bg-indigo-subtle {
            background-color: #e0e7ff !important;
        }
        .text-indigo-emphasis {
            color: #3730a3 !important;
        }
    </style>
@endpush
