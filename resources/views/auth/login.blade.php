@extends('layouts.auth.app')

@section('title', 'Login - ' . config('app.name'))

@section('content')
    <div class="section-authentication-cover">
        <div class="row g-0 min-vh-100">

            {{-- LEFT : HERO --}}
            <div class="col-xl-7 col-xxl-8 d-none d-xl-flex align-items-top justify-content-center border-end"
                style="background: url('{{ asset('assets/images/bg-themes/bg03.jpeg') }}') no-repeat center; background-size: cover;">
                <div class="text-center px-5 pt-2">
                    <!-- <h1 class="fw-bold text-muted mb-3" style="text-shadow: 0 4px 12px rgba(0,0,0,.45);">
                        {{ config('app.name') }}
                    </h1> -->
                    <p class="text-muted text-center fw-bold fs-4 mb-0 mt-4" style="text-shadow: 0 4px 12px rgba(0,0,0,.45);">
                        Kelola Inventori dan Penjualan<br>Mudah dan Terintegrasi
                    </p>
                </div>
            </div>

            {{-- RIGHT : LOGIN --}}
            <div
                class="col-12 col-xl-5 col-xxl-4 d-flex align-items-center justify-content-center
                   border-top border-4 border-primary border-gradient-1">
                <div class="w-100 px-4 px-sm-5" style="max-width:420px;">

                    {{-- Logo & Title --}}
                    <div class="d-flex align-items-center mb-4">
                        <img src="{{ asset('assets/images/alazca_logo.png') }}" width="64" alt="Logo">
                        <div class="ms-3">
                            <h4 class="mb-0 fw-bold">{{ config('app.name') }}</h4>
                            <small class="text-muted">
                                Al-Azhar Management of Inventory & Retail Application
                            </small>
                        </div>
                    </div>

                    {{-- Description --}}
                    <p class="text-center text-muted mb-4">
                        Satu sistem terintegrasi untuk stok, pembelian, dan penjualan sekolah
                    </p>

                    {{-- Error --}}
                    @if (session('error'))
                        <div class="alert alert-danger small">
                            {{ session('error') }}
                        </div>
                    @endif

                    @auth
                        {{-- Logged User --}}
                        <div class="mb-4">
                            <p class="fw-semibold text-center mb-3">Lanjut sebagai</p>

                            <a href="{{ route('dashboard') }}" class="text-decoration-none">
                                <div class="d-flex align-items-center gap-3 border p-3 rounded-4">
                                    @if (Auth::user()->pegawai && Auth::user()->pegawai->foto && Auth::user()->pegawai->foto !== '-')
                                        <img src="{{ Storage::disk('s3')->temporaryUrl(Auth::user()->pegawai->foto, now()->addMinutes(60)) }}"
                                            width="48" height="48" class="rounded-circle" alt="">
                                    @else
                                        <img src="{{ asset('assets/images/avatars/11.png') }}" width="48" height="48"
                                            class="rounded-circle" alt="">
                                    @endif
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">{{ Auth::user()->name }}</h6>
                                        <small class="text-muted">{{ Auth::user()->nik }}</small>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <div class="text-center text-muted my-3 small">atau</div>
                    @endauth

                    {{-- SSO Button --}}
                    <a href="{{ route('sso.login') }}{{ auth()->check() ? '?prompt=login' : '' }}"
                        class="btn btn-grd-primary text-white fw-bold py-2 w-100 d-flex align-items-center justify-content-center gap-2">
                        <img src="{{ asset('assets/images/apps/alazca_favicon.png') }}" width="20" alt="">
                        Masuk dengan CASANDRA
                    </a>

                    {{-- Footer --}}
                    <div class="text-center text-muted small mt-4">
                        SSO terintegrasi dengan sistem pusat Al-Azhar
                    </div>

                </div>
            </div>

        </div>
    </div>
@endsection
