@extends('layouts.auth.app')
@section('title', 'Login Peserta Ujian')

@section('content')

<!-- ✅ Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    * {
        box-sizing: border-box;
    }

    /* 🌊 Background baru: gradasi biru elegan */
    body {
        margin: 0;
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #b3e5fc 0%, #2196f3 50%, #0d47a1 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        transition: all 0.5s ease-in-out;
    }

    /* Container utama */
    .login-container {
        display: flex;
        width: 90%;
        max-width: 960px;
        height: 600px;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        background: #fff;
        position: relative;
    }

    /* 💠 Kiri: branding */
    .login-left {
        flex: 1;
        background: linear-gradient(160deg, #1565c0, #1e3a8a, #0d47a1);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        color: #fff;
        padding: 2rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .login-left::before {
        content: "";
        position: absolute;
        width: 600px;
        height: 600px;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, transparent 70%);
        top: -100px;
        left: -150px;
        filter: blur(80px);
    }

    .login-left img {
        width: 90px;
        height: 90px;
        margin-bottom: 1rem;
        z-index: 1;
    }

    .login-left h2 {
        font-weight: 700;
        z-index: 1;
        color: #fff;
    }

    .login-left p {
        font-size: 14px;
        opacity: 0.9;
        margin-top: 10px;
        z-index: 1;
    }

    /* ✨ Kanan: form login */
    .login-right {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(20px);
        animation: fadeIn 1s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .login-card {
        width: 80%;
        max-width: 360px;
        background: rgba(255, 255, 255, 0.9);
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 8px 24px rgba(31, 38, 135, 0.15);
        animation: slideUp 0.9s ease;
    }

    @keyframes slideUp {
        0% {
            transform: translateY(40px);
            opacity: 0;
        }

        100% {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .login-card h5 {
        color: #1e3a8a;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .form-label {
        font-weight: 500;
    }

    .form-control {
        border-radius: 10px;
        border: 1px solid #ccc;
        padding: 10px 14px;
        transition: all 0.25s ease;
    }

    .form-control:focus {
        border-color: #1565c0;
        box-shadow: 0 0 0 0.2rem rgba(21, 101, 192, 0.25);
    }

    .btn-login {
        background: linear-gradient(135deg, #1565c0, #1e40af);
        color: #fff;
        font-weight: 600;
        border: none;
        border-radius: 10px;
        transition: all 0.3s ease;
        margin-top: 0.5rem;
        box-shadow: 0 4px 14px rgba(21, 101, 192, 0.25);
    }

    .btn-login:hover {
        transform: translateY(-2px);
        background: linear-gradient(135deg, #0d47a1, #1e3a8a);
        box-shadow: 0 8px 24px rgba(21, 101, 192, 0.4);
    }

    /* 🌐 Responsif */
    @media (max-width: 768px) {
        .login-container {
            flex-direction: column;
            height: auto;
            width: 90%;
        }

        .login-left {
            height: 200px;
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }

        .login-card {
            margin: 2rem 0;
        }
    }
</style>

<div class="login-container">
    <div class="login-left">
        <img src="{{ asset('assets/images/alazca_logo.png') }}" alt="Logo">
        <h2>Al-Azhar Cairo Banda Aceh</h2>
        <p>Selamat datang di portal ujian resmi<br>Silakan masuk untuk melanjutkan</p>
    </div>

    <div class="login-right">
        <div class="login-card text-start">
            <h5 class="text-center">Login Peserta Ujian</h5>
            <p class="text-center text-muted mb-4">Gunakan akun yang telah diberikan</p>

            @if(session('error'))
            <div class="alert alert-danger small mb-3">{{ session('error') }}</div>
            @endif
            @if(session('success'))
            <div class="alert alert-success small mb-3">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('peserta.login.submit') }}">
                @csrf
                <div class="mb-3">
                    <label for="registrasi" class="form-label">Nomor Registrasi</label>
                    <input id="registrasi" type="text" name="registrasi" value="{{ old('registrasi') }}" class="form-control @error('registrasi') is-invalid @enderror" placeholder="Masukkan nomor registrasi anda" required autofocus>
                    @error('registrasi')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Tanggal Lahir</label>
                    <input id="password" type="date" name="password" class="form-control @error('password') is-invalid @enderror" required>
                    @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label small" for="remember">Ingat saya</label>
                </div>

                <button type="submit" class="btn btn-login w-100 py-2">Masuk</button>
            </form>
        </div>
    </div>
</div>

@endsection