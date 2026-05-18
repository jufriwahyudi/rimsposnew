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
