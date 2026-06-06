@extends('layouts.main.main')

@section('title', 'Koran Toko Digital - ' . config('app.name'))

@section('content')
<div class="row">
    <div class="col-12 col-lg-8 mx-auto">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="mb-1 fw-bold text-dark">📰 Koran Toko Digital</h4>
                <p class="mb-0 text-muted">Ringkasan performa dan wawasan harian toko Anda disusun otomatis oleh AI.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success border-0 alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger border-0 alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($newspapers->isEmpty())
            <div class="card shadow-sm border-0 rounded-3 p-5 text-center">
                <div class="my-4">
                    <span class="material-icons-outlined text-muted" style="font-size: 64px;">newspaper</span>
                </div>
                <h5 class="fw-semibold text-dark">Belum Ada Edisi Koran</h5>
                <p class="text-muted mx-auto" style="max-width: 400px;">
                    Sistem otomatis menyusun Koran Toko Digital setiap malam pukul 23:59 setelah toko tutup.
                </p>
            </div>
        @else
            <div class="newspaper-timeline">
                @foreach($newspapers as $item)
                    <div class="card shadow-sm border-0 rounded-3 mb-3 hover-shadow-sm transition-all" style="border-left: 4px solid #4f46e5 !important;">
                        <div class="card-body p-4">
                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge bg-light-primary text-primary fw-semibold px-2 py-1">
                                            <i class="far fa-calendar-alt me-1"></i>
                                            {{ $item->report_date->translatedFormat('d F Y') }}
                                        </span>
                                        @if($item->status === 'success')
                                            <span class="badge bg-light-success text-success fw-semibold px-2 py-1">Edisi Siap</span>
                                        @elseif($item->status === 'pending')
                                            <span class="badge bg-light-warning text-warning fw-semibold px-2 py-1">Sedang Disiapkan</span>
                                        @else
                                            <span class="badge bg-light-danger text-danger fw-semibold px-2 py-1">Gagal</span>
                                        @endif
                                    </div>
                                    <h5 class="fw-bold text-dark mb-1">
                                        {{ $item->headline ?: 'Edisi ' . $item->report_date->translatedFormat('d F Y') }}
                                    </h5>
                                    @if($item->status === 'failed')
                                        <small class="text-danger d-block">Error: {{ $item->error_message }}</small>
                                    @endif
                                </div>
                                <div class="w-100 w-sm-auto text-end">
                                    @if($item->status === 'success')
                                        <a href="{{ route('newspaper.show', $item->id) }}" class="btn btn-outline-primary px-4 rounded-pill fw-semibold w-100 w-sm-auto">
                                            Baca Koran <i class="fas fa-arrow-right ms-1"></i>
                                        </a>
                                    @else
                                        <button class="btn btn-light px-4 rounded-pill fw-semibold w-100 w-sm-auto" disabled>
                                            Tidak Tersedia
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $newspapers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
