@extends('layouts.main.main')

@section('title', ($newspaper->headline ?: 'Koran Toko') . ' - Koran Toko Digital')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,400&family=Lora:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
<style>
    .newspaper-container {
        font-family: 'Lora', Georgia, serif;
        background-color: #fdfbf7;
        color: #2b2b2b;
        border: 1px solid #e1dbcf;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        border-radius: 4px;
        line-height: 1.7;
    }
    .newspaper-header {
        border-bottom: 4px double #2b2b2b;
        text-align: center;
        padding-bottom: 20px;
    }
    .newspaper-title {
        font-family: 'Playfair Display', 'Times New Roman', serif;
        font-weight: 700;
        font-size: 2.8rem;
        letter-spacing: -1px;
        text-transform: uppercase;
        color: #1a1a1a;
    }
    .newspaper-meta {
        font-family: 'Playfair Display', serif;
        font-size: 0.95rem;
        border-top: 1px solid #2b2b2b;
        border-bottom: 1px solid #2b2b2b;
        padding: 5px 0;
        margin-top: 15px;
        font-style: italic;
    }
    .newspaper-headline {
        font-family: 'Playfair Display', serif;
        font-size: 1.85rem;
        font-weight: 700;
        line-height: 1.3;
        text-align: center;
        margin: 30px 0;
        color: #111;
        padding: 0 10px;
    }
    .newspaper-body {
        font-size: 1.1rem;
        padding: 0 15px;
    }
    /* Style first paragraph to look editorial */
    .newspaper-body > p:first-of-type::first-letter {
        font-family: 'Playfair Display', serif;
        font-size: 3.5rem;
        float: left;
        line-height: 0.8;
        margin-top: 5px;
        margin-right: 8px;
        font-weight: bold;
        color: #1a1a1a;
    }
    .newspaper-body h4 {
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        margin-top: 30px;
        margin-bottom: 15px;
        border-bottom: 1px solid #e1dbcf;
        padding-bottom: 5px;
        color: #1a1a1a;
    }
    .newspaper-body h5 {
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        margin-top: 25px;
        margin-bottom: 10px;
        color: #333;
    }
    .newspaper-body ul, .newspaper-body ol {
        padding-left: 20px;
        margin-bottom: 20px;
    }
    .newspaper-body li {
        margin-bottom: 8px;
    }
    .newspaper-body table {
        width: 100%;
        margin: 20px 0;
        border-collapse: collapse;
    }
    .newspaper-body th, .newspaper-body td {
        border-bottom: 1px solid #e1dbcf;
        padding: 8px 12px;
        text-align: left;
    }
    .newspaper-body th {
        background-color: #f7f4ec;
        font-family: 'Playfair Display', serif;
        font-weight: bold;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12 col-xl-8 mx-auto mb-5">
        
        <div class="mb-3 d-flex justify-content-between align-items-center">
            <a href="{{ route('newspaper.index') }}" class="btn btn-outline-secondary px-3 rounded-pill fw-semibold btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Koran
            </a>
            
            <button onclick="window.print()" class="btn btn-light px-3 rounded-pill fw-semibold btn-sm shadow-sm border">
                <i class="fas fa-print me-1"></i> Cetak Edisi Ini
            </button>
        </div>

        <div class="card newspaper-container p-4 p-md-5">
            <!-- Newspaper Header -->
            <div class="newspaper-header">
                <div class="newspaper-title">KORAN TOKO DIGITAL</div>
                <div class="newspaper-meta d-flex justify-content-between align-items-center px-2 flex-wrap gap-2">
                    <span><strong>Edisi:</strong> {{ $newspaper->store->name ?? 'Toko' }} ({{ strtoupper($newspaper->store->business_type ?? 'RETAIL') }})</span>
                    <span><i class="far fa-calendar-alt me-1"></i> {{ $newspaper->report_date->translatedFormat('l, d F Y') }}</span>
                    <span>
                        @if($newspaper->ai_provider === 'deepseek')
                            <span class="badge bg-primary text-white px-2 py-1"><i class="fas fa-brain me-1"></i> AI DeepSeek</span>
                        @elseif($newspaper->ai_provider === 'gemini')
                            <span class="badge bg-info text-white px-2 py-1"><i class="fas fa-robot me-1"></i> AI Gemini</span>
                        @else
                            <span class="badge bg-secondary text-white px-2 py-1"><i class="fas fa-bolt me-1"></i> Edisi Cepat Otomatis</span>
                        @endif
                    </span>
                </div>
            </div>

            <!-- Main Headline -->
            <h1 class="newspaper-headline">
                "{{ $newspaper->headline ?: 'Rangkuman Harian Kinerja Operasional Toko' }}"
            </h1>

            @php
                $payload = $newspaper->raw_payload ?? [];
                $summary = $payload['summary'] ?? [];
                $netRevenue = $summary['net_revenue'] ?? $summary['total_revenue'] ?? null;
                $txCount = $summary['total_transactions'] ?? null;
                $avgBill = $summary['avg_transaction_value'] ?? null;
                $vsYest = $summary['revenue_vs_yesterday_pct'] ?? null;
            @endphp
            @if($netRevenue !== null)
            <div class="row g-2 mb-4 text-center py-2 border-top border-bottom rounded" style="background-color: #f7f4ec;">
                <div class="col-6 col-md-3 border-end">
                    <small class="text-muted d-block" style="font-size: 0.75rem;">OMSET BERSIH</small>
                    <strong class="fs-6">Rp {{ number_format($netRevenue, 0, ',', '.') }}</strong>
                </div>
                <div class="col-6 col-md-3 border-end">
                    <small class="text-muted d-block" style="font-size: 0.75rem;">TOTAL TRANSAKSI</small>
                    <strong class="fs-6">{{ number_format($txCount, 0, ',', '.') }} Struk</strong>
                </div>
                <div class="col-6 col-md-3 border-end">
                    <small class="text-muted d-block" style="font-size: 0.75rem;">RATA-RATA / STRUK</small>
                    <strong class="fs-6">Rp {{ number_format($avgBill, 0, ',', '.') }}</strong>
                </div>
                <div class="col-6 col-md-3">
                    <small class="text-muted d-block" style="font-size: 0.75rem;">VS KEMARIN</small>
                    <strong class="fs-6 {{ ($vsYest ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ ($vsYest ?? 0) >= 0 ? '+' : '' }}{{ $vsYest ?? 0 }}%
                    </strong>
                </div>
            </div>
            @endif

            <!-- Newspaper Body -->
            <div class="newspaper-body">
                {!! $newspaper->content_html !!}
            </div>

            <div class="text-center mt-5 pt-4 border-top text-muted" style="font-family: 'Playfair Display', serif; font-size: 0.9rem; font-style: italic;">
                -- Akhir dari Koran Toko Digital --
            </div>
        </div>

    </div>
</div>
@endsection
