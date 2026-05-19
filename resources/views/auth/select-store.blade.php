@extends('layouts.auth.app')

@section('title', 'Pilih Toko - ' . config('app.name'))

@section('content')
    <div class="section-authentication-cover">
        <div class="row g-0 min-vh-100">

            {{-- LEFT : HERO --}}
            <div class="col-xl-7 col-xxl-8 d-none d-xl-flex align-items-center justify-content-center position-relative overflow-hidden"
                style="min-height: 100vh;
                       background: url('{{ asset('assets/images/bg-themes/bg_chart.jpg') }}') no-repeat center center / cover;">

                {{-- Gradient overlay --}}
                <div class="position-absolute top-0 start-0 w-100 h-100"
                    style="background: linear-gradient(135deg, rgba(124,58,237,0.78) 0%, rgba(79,70,229,0.68) 100%); z-index:1;">
                </div>

                {{-- Content --}}
                <div class="text-center px-5 position-relative" style="z-index:2;">
                    <img src="{{ asset('assets/images/bg-themes/marketing.png') }}" class="img-fluid mb-4"
                        style="max-width: 380px; filter: drop-shadow(0 24px 48px rgba(0,0,0,0.40));"
                        alt="Marketing Illustration">
                    <h2 class="fw-bold text-white mb-2" style="text-shadow: 0 2px 10px rgba(0,0,0,0.35);">
                        {{ config('app.name') }}
                    </h2>
                    <p class="text-white-50 mb-0"
                        style="font-size: 1rem; max-width: 340px; margin: 0 auto; line-height: 1.6;">
                        Kelola inventori dan penjualan dengan mudah, cepat, dan terintegrasi.
                    </p>
                </div>
            </div>


            {{-- RIGHT : PILIH TOKO --}}
            <div
                class="col-12 col-xl-5 col-xxl-4 d-flex align-items-center justify-content-center
                border-top border-4 border-primary border-gradient-1">
                <div class="w-100 px-4 px-sm-5" style="max-width:440px;">

                    {{-- Logo & Title --}}
                    <div class="d-flex align-items-center mb-4">
                        <img src="{{ asset('assets/images/alazca_logo.png') }}" width="64" alt="Logo">
                        <div class="ms-3">
                            <h4 class="mb-0 fw-bold">{{ config('app.name') }}</h4>
                            <small class="text-muted">{{ config('app.tagline') }}</small>
                        </div>
                    </div>

                    <h5 class="fw-bold mb-1">Pilih Toko</h5>
                    <p class="text-muted small mb-4">
                        Halo, <strong>{{ Auth::user()->name }}</strong>. Pilih toko yang ingin Anda kelola sesi ini.
                    </p>

                    @if ($errors->has('store_id'))
                        <div class="alert alert-danger small">{{ $errors->first('store_id') }}</div>
                    @endif

                    @if ($stores->isEmpty())
                        <div class="alert alert-warning small">
                            Akun Anda belum memiliki akses ke toko manapun.<br>
                            Hubungi administrator untuk mendapatkan akses.
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="mt-3">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary w-100">
                                <i class="fa fa-sign-out-alt me-2"></i> Keluar
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('select-store.choose') }}">
                            @csrf

                            <div class="d-flex flex-column gap-3 mb-4">
                                @foreach ($stores as $store)
                                    <label
                                        class="store-card d-flex align-items-center gap-3 border rounded-4 p-3 cursor-pointer"
                                        for="store_{{ $store->id }}"
                                        style="cursor:pointer; transition: border-color .2s, background .2s;">
                                        <input type="radio" name="store_id" id="store_{{ $store->id }}"
                                            value="{{ $store->id }}" class="form-check-input mt-0 flex-shrink-0"
                                            {{ $loop->first ? 'checked' : '' }}>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold">{{ $store->name }}</div>
                                            <small class="text-muted">
                                                {{ $store->code }}
                                                @if ($store->address)
                                                    &mdash; {{ $store->address }}
                                                @endif
                                            </small>
                                        </div>
                                        <i class="fa fa-store fa-lg text-muted"></i>
                                    </label>
                                @endforeach
                            </div>

                            <button type="submit" class="btn btn-grd-primary text-white fw-bold py-2 w-100">
                                Masuk ke Toko
                            </button>
                        </form>

                        <form method="POST" action="{{ route('logout') }}" class="mt-3 text-center">
                            @csrf
                            <button type="submit" class="btn btn-link btn-sm text-muted text-decoration-none">
                                Ganti akun / Keluar
                            </button>
                        </form>
                    @endif

                </div>
            </div>

        </div>
    </div>

    <style>
        .store-card:has(input:checked) {
            border-color: #7c3aed !important;
            background: #f5f0ff;
        }
    </style>
@endsection
