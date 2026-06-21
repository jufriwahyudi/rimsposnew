@extends('layouts.auth.app')

@section('title', 'Login - ' . config('app.name'))

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
                                {{ config('app.tagline') }}
                            </small>
                        </div>
                    </div>

                    {{-- Description --}}
                    <p class="text-center text-muted mb-4">
                        Masuk untuk mengelola inventori dan penjualan Anda dengan mudah dan terintegrasi.
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
                                    <img src="{{ asset('assets/images/avatars/11.png') }}" width="48" height="48"
                                        class="rounded-circle" alt="">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">{{ Auth::user()->name }}</h6>
                                        <small class="text-muted">{{ Auth::user()->email }}</small>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <div class="text-center text-muted my-3 small">atau</div>
                    @endauth

                    {{-- Login Form --}}
                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email</label>
                            <input type="email" id="email" name="email"
                                class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}"
                                placeholder="Masukkan email" autofocus>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <input type="password" id="password" name="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    placeholder="Masukkan password">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()"
                                    tabindex="-1">
                                    <i id="eye-icon" class="fa fa-eye"></i>
                                </button>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <button type="submit" class="btn btn-grd-primary text-white fw-bold py-2 w-100">
                            Masuk
                        </button>
                    </form>

                    @php
                        $latestVersion = \App\Models\AppVersion::first();
                    @endphp
                    @if ($latestVersion && !empty($latestVersion->download_url))
                        <div class="text-center mt-4">
                            <a href="{{ $latestVersion->download_url }}" class="text-decoration-none text-primary fw-semibold small" target="_blank">
                                <i class="fa fa-download me-1"></i> Download APK Kasir Terbaru (v{{ $latestVersion->version }})
                            </a>
                        </div>
                    @endif

                    <script>
                        function togglePassword() {
                            const input = document.getElementById('password');
                            const icon = document.getElementById('eye-icon');
                            if (input.type === 'password') {
                                input.type = 'text';
                                icon.classList.replace('fa-eye', 'fa-eye-slash');
                            } else {
                                input.type = 'password';
                                icon.classList.replace('fa-eye-slash', 'fa-eye');
                            }
                        }
                    </script>

                </div>
            </div>

        </div>
    </div>
@endsection
