@extends('layouts.auth.app')

@section('title', 'Login - ' . config('app.name'))

@section('content')
<!-- Custom Styles for Futuristic SaaS POS login -->
<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');
    
    body {
        margin: 0;
        padding: 0;
        background-color: #070913 !important;
        font-family: 'Outfit', sans-serif !important;
        overflow-x: hidden;
    }
    
    /* Fullscreen Fixed Canvas Background */
    #abstract-canvas {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        z-index: 0;
        pointer-events: none;
        background: radial-gradient(circle at 80% 20%, #151833 0%, #070913 100%);
    }

    .login-wrapper {
        position: relative;
        z-index: 10;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Left Hero / SaaS Visuals */
    .hero-panel {
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 6rem 4rem 4rem 4rem; /* Extra top padding to prevent top cutoff */
        min-height: 100vh;
        height: auto;
        position: relative;
        box-sizing: border-box;
    }

    .brand-glow-title {
        font-size: 3.5rem;
        font-weight: 700;
        background: linear-gradient(135deg, #ffffff 30%, #a5b4fc 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        text-shadow: 0 0 40px rgba(99, 102, 241, 0.2);
        letter-spacing: -1px;
    }

    .brand-tagline {
        font-size: 1.15rem;
        color: #94a3b8;
        max-width: 480px;
        line-height: 1.6;
        margin-top: 1rem;
    }

    /* Glassmorphic Widget Cards */
    .floating-widget {
        background: rgba(15, 23, 42, 0.45);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 1.25rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        pointer-events: auto;
    }

    .floating-widget:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(99, 102, 241, 0.15);
        border-color: rgba(99, 102, 241, 0.3);
    }

    /* Position custom widgets */
    .widget-container {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
        margin-top: 3rem;
        max-width: 580px;
    }

    .widget-full {
        grid-column: span 2;
    }

    .widget-title {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #64748b;
        font-weight: 600;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .widget-value {
        font-size: 1.6rem;
        font-weight: 700;
        color: #ffffff;
    }

    /* Live pulse dot */
    .pulse-dot {
        width: 8px;
        height: 8px;
        background-color: #10b981;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 0 8px #10b981;
        animation: pulse-glow 1.5s infinite;
    }

    @keyframes pulse-glow {
        0% { transform: scale(0.9); opacity: 0.6; }
        50% { transform: scale(1.2); opacity: 1; box-shadow: 0 0 12px #10b981; }
        100% { transform: scale(0.9); opacity: 0.6; }
    }

    /* Right Glassmorphic Login Card */
    .login-panel {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        padding: 2rem;
    }

    .login-card {
        background: rgba(11, 15, 27, 0.7);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 28px;
        padding: 3rem 2.5rem;
        width: 100%;
        max-width: 440px;
        box-shadow: 0 35px 70px -10px rgba(0, 0, 0, 0.9), 
                    0 0 50px rgba(99, 102, 241, 0.15), 
                    inset 0 1px 2px rgba(255, 255, 255, 0.15);
        position: relative;
        overflow: hidden;
        pointer-events: auto;
    }


    /* Animated gradient card accent border */
    .login-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #6366f1, #a855f7, #ec4899, #6366f1);
        background-size: 300% 100%;
        animation: gradient-shift 6s linear infinite;
    }

    @keyframes gradient-shift {
        0% { background-position: 0% 50%; }
        100% { background-position: 300% 50%; }
    }

    .brand-logo-wrapper {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.5rem;
        padding: 0.6rem;
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.82); /* Frosted glassy background */
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.6);
        box-shadow: 0 10px 25px rgba(99, 102, 241, 0.2), 
                    0 0 15px rgba(6, 182, 212, 0.15);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden; /* Clips the diagonal glass shine sweep */
    }

    /* Diagonal Glossy specual reflection line */
    .brand-logo-wrapper::after {
        content: '';
        position: absolute;
        top: -50%;
        left: -80%;
        width: 30%;
        height: 200%;
        background: linear-gradient(
            to right, 
            rgba(255, 255, 255, 0) 0%, 
            rgba(255, 255, 255, 0.65) 40%, 
            rgba(255, 255, 255, 0.85) 50%, 
            rgba(255, 255, 255, 0.65) 60%, 
            rgba(255, 255, 255, 0) 100%
        );
        transform: rotate(25deg);
        pointer-events: none;
        animation: glass-shine 4.5s infinite ease-in-out;
    }

    @keyframes glass-shine {
        0% { left: -80%; }
        25% { left: 140%; }
        100% { left: 140%; }
    }

    .brand-logo-wrapper:hover {
        transform: translateY(-2px);
        background: rgba(255, 255, 255, 0.9);
        border-color: rgba(255, 255, 255, 0.85);
        box-shadow: 0 15px 35px rgba(99, 102, 241, 0.4), 
                    0 0 25px rgba(6, 182, 212, 0.3);
    }

    .brand-logo-wrapper img {
        width: 140px;
        height: auto;
        filter: drop-shadow(0 4px 8px rgba(99, 102, 241, 0.15));
    }


    .form-title {
        color: #ffffff;
        font-weight: 700;
        font-size: 1.8rem;
        margin-bottom: 0.5rem;
    }

    .form-subtitle {
        color: #94a3b8;
        font-size: 0.95rem;
        margin-bottom: 2rem;
    }

    /* Form input customizations */
    .custom-form-group {
        margin-bottom: 1.5rem;
    }

    .custom-label {
        color: #cbd5e1;
        font-weight: 500;
        font-size: 0.85rem;
        margin-bottom: 0.5rem;
        display: block;
    }

    .custom-input {
        background: rgba(255, 255, 255, 0.03) !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
        border-radius: 12px !important;
        color: #ffffff !important;
        padding: 0.75rem 1rem !important;
        font-size: 0.95rem !important;
        transition: all 0.3s ease !important;
    }

    .custom-input::placeholder {
        color: #64748b !important;
    }

    .custom-input:focus {
        border-color: #6366f1 !important;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.18) !important;
        background: rgba(255, 255, 255, 0.05) !important;
    }

    .password-input-wrapper {
        position: relative;
    }

    .password-toggle-btn {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        padding: 4px;
        z-index: 10;
        transition: color 0.2s;
    }

    .password-toggle-btn:hover {
        color: #ffffff;
    }

    /* Custom submit button */
    .btn-futuristic {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
        background-size: 200% 200%;
        border: none;
        border-radius: 12px;
        color: #ffffff;
        font-weight: 600;
        padding: 0.85rem;
        width: 100%;
        transition: all 0.3s ease;
        box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.4);
        position: relative;
        overflow: hidden;
    }

    .btn-futuristic:hover {
        background-position: right center;
        transform: translateY(-2px);
        box-shadow: 0 15px 30px -5px rgba(99, 102, 241, 0.6);
        color: #ffffff;
    }

    .btn-futuristic:active {
        transform: translateY(0);
    }

    /* Logged in user style */
    .user-profile-badge {
        display: flex;
        align-items: center;
        gap: 1rem;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 0.85rem 1.25rem;
        text-decoration: none !important;
        transition: all 0.3s ease;
        margin-bottom: 1.5rem;
    }

    .user-profile-badge:hover {
        background: rgba(255, 255, 255, 0.07);
        border-color: rgba(99, 102, 241, 0.3);
        transform: translateX(3px);
    }

    .user-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        border: 2px solid #6366f1;
        object-fit: cover;
    }

    .user-info h6 {
        color: #ffffff;
        margin: 0;
        font-weight: 600;
    }

    .user-info small {
        color: #94a3b8;
    }

    .text-separator {
        display: flex;
        align-items: center;
        text-align: center;
        color: #64748b;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin: 1.5rem 0;
    }

    .text-separator::before,
    .text-separator::after {
        content: '';
        flex: 1;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .text-separator:not(:empty)::before {
        margin-right: .75em;
    }

    .text-separator:not(:empty)::after {
        margin-left: .75em;
    }

    /* APK download section */
    .apk-download-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(6, 182, 212, 0.08);
        border: 1px solid rgba(6, 182, 212, 0.2);
        color: #06b6d4;
        text-decoration: none;
        padding: 0.6rem 1.2rem;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 500;
        transition: all 0.3s ease;
        margin-top: 1.5rem;
    }

    .apk-download-badge:hover {
        background: rgba(6, 182, 212, 0.15);
        box-shadow: 0 0 15px rgba(6, 182, 212, 0.3);
        color: #22d3ee;
        transform: translateY(-1px);
    }

    /* Live list for Transactions Widget */
    .tx-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        font-size: 0.85rem;
        animation: fadeInTx 0.5s ease-out;
    }

    .tx-item:last-child {
        border-bottom: none;
    }

    @keyframes fadeInTx {
        from { opacity: 0; transform: translateY(5px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .invalid-feedback {
        color: #ef4444 !important;
        font-weight: 500;
        font-size: 0.8rem;
        margin-top: 0.35rem;
    }

    /* Utility */
    .glow-dot-cyan {
        width: 8px;
        height: 8px;
        background-color: #06b6d4;
        border-radius: 50%;
        box-shadow: 0 0 8px #06b6d4;
        display: inline-block;
    }

    .glow-dot-pink {
        width: 8px;
        height: 8px;
        background-color: #ec4899;
        border-radius: 50%;
        box-shadow: 0 0 8px #ec4899;
        display: inline-block;
    }
</style>

<!-- Canvas for Moving Abstract Lines background -->
<canvas id="abstract-canvas"></canvas>

<div class="container-fluid login-wrapper p-0">
    <div class="row g-0 w-100">
        
        {{-- LEFT PANEL: HERO & SAAS WIDGETS (Only on XL/XXL) --}}
        <div class="col-xl-7 col-xxl-8 d-none d-xl-flex align-items-center justify-content-center">
            <div class="hero-panel w-100">
                
                <div class="mb-4">
                    <span class="badge bg-indigo-glow px-3 py-2 rounded-pill text-white mb-3" 
                          style="background: rgba(99, 102, 241, 0.2); border: 1px solid rgba(99, 102, 241, 0.4); font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.4rem; line-height: 1.2;">
                        <i class="fa fa-sparkles text-warning"></i> Next-Gen POS Ecosystem
                    </span>
                    <h1 class="brand-glow-title">
                        {{ config('app.name') }}
                    </h1>
                    <p class="brand-tagline">
                        Sistem manajemen inventori dan penjualan multi-cabang berbasis SaaS premium. 
                        Didesain untuk kecepatan, akurasi, dan skalabilitas bisnis modern.
                    </p>
                </div>

                {{-- Dashboard Concepts (Floating Widgets) --}}
                <div class="widget-container">
                    
                    {{-- Widget 1: Sales Curve --}}
                    <div class="floating-widget">
                        <div class="widget-title">
                            <span>OMZET HARI INI</span>
                            <span class="badge bg-success-glow text-success" style="background: rgba(16, 185, 129, 0.1); font-size: 0.75rem;">
                                <i class="fa fa-arrow-up me-1"></i> +18.4%
                            </span>
                        </div>
                        <div class="widget-value">Rp 12.850.000</div>
                        
                        {{-- Mini SVG chart line to represent premium SaaS POS --}}
                        <div class="mt-3" style="height: 40px;">
                            <svg class="w-100 h-100" viewBox="0 0 200 40" preserveAspectRatio="none">
                                <defs>
                                    <linearGradient id="chart-glow" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#6366f1" stop-opacity="0.4"/>
                                        <stop offset="100%" stop-color="#6366f1" stop-opacity="0"/>
                                    </linearGradient>
                                </defs>
                                <path d="M0,35 Q30,10 60,25 T120,5 T160,20 T200,8 L200,40 L0,40 Z" fill="url(#chart-glow)"/>
                                <path d="M0,35 Q30,10 60,25 T120,5 T160,20 T200,8" fill="none" stroke="#6366f1" stroke-width="2.5" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>

                    {{-- Widget 2: Registers --}}
                    <div class="floating-widget">
                        <div class="widget-title">
                            <span>STATUS TERMINAL</span>
                            <span class="pulse-dot"></span>
                        </div>
                        <div class="widget-value">5 Aktif</div>
                        <p class="text-muted small mb-0 mt-2" style="font-size: 0.8rem;">
                            Semua register kasir terhubung dan sinkron
                        </p>
                    </div>

                    {{-- Widget 3: Live Transactions (Interactive simulation) --}}
                    <div class="floating-widget widget-full">
                        <div class="widget-title">
                            <span>AKTIVITAS TRANSAKSI TERBARU</span>
                            <span class="text-info small" style="font-size: 0.75rem; font-weight: 500;">
                                <i class="fa fa-sync fa-spin me-1"></i> LIVE
                            </span>
                        </div>
                        <div id="live-tx-container" style="min-height: 85px;">
                            {{-- Will be populated by JS --}}
                        </div>
                    </div>

                </div>

            </div>
        </div>

        {{-- RIGHT PANEL: GLASSMORPHIC LOGIN FORM --}}
        <div class="col-12 col-xl-5 col-xxl-4 login-panel">
            <div class="login-card">
                
                {{-- Logo & Brand --}}
                <div class="text-center">
                    <div class="brand-logo-wrapper">
                        <img src="{{ asset('assets/images/rimspos_logo.png') }}" alt="RimsPOS Logo">
                    </div>
                    <p class="form-subtitle">Premium Cloud POS System</p>
                </div>

                {{-- Error Alerts --}}
                @if (session('error'))
                    <div class="alert alert-danger small border-0 text-white py-2 px-3 mb-4 rounded-3" 
                         style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4) !important;">
                        <i class="fa fa-exclamation-circle me-2"></i> {{ session('error') }}
                    </div>
                @endif

                @auth
                    {{-- Logged User Badge --}}
                    <div>
                        <p class="text-center text-muted small mb-2">Lanjut sebagai sesi aktif:</p>
                        <a href="{{ route('dashboard') }}" class="user-profile-badge">
                            <img src="{{ asset('assets/images/avatars/11.png') }}" class="user-avatar" alt="Avatar">
                            <div class="user-info">
                                <h6>{{ Auth::user()->name }}</h6>
                                <small>{{ Auth::user()->email }}</small>
                            </div>
                            <i class="fa fa-chevron-right ms-auto text-muted" style="font-size: 0.8rem;"></i>
                        </a>
                    </div>
                    <div class="text-separator">atau masuk akun lain</div>
                @endauth

                {{-- Form Login --}}
                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="custom-form-group">
                        <label for="email" class="custom-label">ALAMAT EMAIL</label>
                        <input type="email" id="email" name="email"
                            class="form-control custom-input @error('email') is-invalid @enderror" 
                            value="{{ old('email') }}"
                            placeholder="nama@perusahaan.com" autofocus required>
                        @error('email')
                            <div class="invalid-feedback"><i class="fa fa-exclamation-triangle me-1"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    <div class="custom-form-group mb-4">
                        <label for="password" class="custom-label">KATA SANDI</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="password" name="password"
                                class="form-control custom-input @error('password') is-invalid @enderror"
                                placeholder="••••••••" required>
                            <button type="button" class="password-toggle-btn" onclick="togglePassword()" tabindex="-1">
                                <i id="eye-icon" class="fa fa-eye"></i>
                            </button>
                            @error('password')
                                <div class="invalid-feedback"><i class="fa fa-exclamation-triangle me-1"></i> {{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <button type="submit" class="btn btn-futuristic">
                        Masuk Dashboard <i class="fa fa-arrow-right ms-2" style="font-size: 0.85rem;"></i>
                    </button>
                </form>

                {{-- Download APK link --}}
                @php
                    $latestVersion = \App\Models\AppVersion::first();
                @endphp
                @if ($latestVersion && !empty($latestVersion->download_url))
                    <div class="text-center">
                        <a href="{{ $latestVersion->download_url }}" class="apk-download-badge" target="_blank">
                            <i class="fa fa-download"></i> Download APK Kasir Terbaru (v{{ $latestVersion->version }})
                        </a>
                    </div>
                @endif

            </div>
        </div>

    </div>
</div>

{{-- Inline JS for Canvas animation & Simulation --}}
<script>
    // 1. Password Visibility Toggle
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

    // 2. Abstract Canvas Animation
    (function() {
        const canvas = document.getElementById('abstract-canvas');
        const ctx = canvas.getContext('2d');

        let width = canvas.width = window.innerWidth;
        let height = canvas.height = window.innerHeight;

        window.addEventListener('resize', () => {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
            // update wave base lines
            waves.forEach(w => w.y = height * 0.5);
        });

        // Glowing Line definitions
        const waves = [
            {
                y: height * 0.5,
                length: 0.003,
                amplitude: 120,
                frequency: 0.008,
                speed: 0.004,
                phase: 0,
                color: 'rgba(99, 102, 241, 0.25)', // Indigo
                lineWidth: 3.5
            },
            {
                y: height * 0.5,
                length: 0.004,
                amplitude: 90,
                frequency: 0.012,
                speed: 0.007,
                phase: Math.PI / 4,
                color: 'rgba(6, 182, 212, 0.3)', // Cyan
                lineWidth: 2
            },
            {
                y: height * 0.5,
                length: 0.002,
                amplitude: 150,
                frequency: 0.006,
                speed: 0.003,
                phase: Math.PI / 2,
                color: 'rgba(139, 92, 246, 0.2)', // Violet
                lineWidth: 5
            },
            {
                y: height * 0.5,
                length: 0.005,
                amplitude: 60,
                frequency: 0.018,
                speed: 0.009,
                phase: Math.PI,
                color: 'rgba(236, 72, 153, 0.15)', // Pink
                lineWidth: 1.5
            }
        ];

        // Background Particles
        const particles = [];
        const particleCount = 45;
        for (let i = 0; i < particleCount; i++) {
            particles.push({
                x: Math.random() * width,
                y: Math.random() * height,
                vx: (Math.random() - 0.5) * 0.4,
                vy: (Math.random() - 0.5) * 0.4,
                radius: Math.random() * 2 + 1,
                color: Math.random() > 0.5 ? 'rgba(6, 182, 212, 0.35)' : 'rgba(99, 102, 241, 0.35)'
            });
        }

        // Global mouse position listener (even over pointer-events: none elements)
        let mouse = { x: null, y: null };
        window.addEventListener('mousemove', (e) => {
            mouse.x = e.clientX;
            mouse.y = e.clientY;
        });
        window.addEventListener('mouseleave', () => {
            mouse.x = null;
            mouse.y = null;
        });

        function animate() {
            // Smoothly clear with opacity to produce slight trailing
            ctx.fillStyle = 'rgba(7, 9, 19, 0.15)';
            ctx.fillRect(0, 0, width, height);

            // Draw a subtle digital grid
            ctx.fillStyle = 'rgba(255, 255, 255, 0.01)';
            const grid = 60;
            for (let x = 0; x < width; x += grid) {
                for (let y = 0; y < height; y += grid) {
                    ctx.fillRect(x, y, 1.5, 1.5);
                }
            }

            // Animate Waves
            waves.forEach(wave => {
                ctx.beginPath();
                ctx.strokeStyle = wave.color;
                ctx.lineWidth = wave.lineWidth;
                ctx.lineCap = 'round';
                
                // Add soft neon bloom
                ctx.shadowBlur = 18;
                ctx.shadowColor = wave.color;

                for (let x = 0; x < width; x += 8) {
                    // Sine calculation
                    let y = wave.y + Math.sin(x * wave.length + wave.phase) * wave.amplitude * Math.cos(wave.phase * 0.25);

                    // Pull waves slightly near the cursor
                    if (mouse.x !== null && mouse.y !== null) {
                        const dx = x - mouse.x;
                        const dy = y - mouse.y;
                        const dist = Math.sqrt(dx*dx + dy*dy);
                        if (dist < 200) {
                            const force = (200 - dist) / 200;
                            y += (mouse.y - y) * force * 0.25;
                        }
                    }

                    if (x === 0) {
                        ctx.moveTo(x, y);
                    } else {
                        ctx.lineTo(x, y);
                    }
                }
                ctx.stroke();
                ctx.shadowBlur = 0; // reset shadow for other renders

                wave.phase += wave.speed;
            });

            // Animate Particles & Connections
            particles.forEach(p => {
                p.x += p.vx;
                p.y += p.vy;

                // Wall bounces
                if (p.x < 0 || p.x > width) p.vx *= -1;
                if (p.y < 0 || p.y > height) p.vy *= -1;

                // Particle-to-Particle linkages
                particles.forEach(p2 => {
                    if (p === p2) return;
                    const dx = p.x - p2.x;
                    const dy = p.y - p2.y;
                    const dist = Math.sqrt(dx*dx + dy*dy);
                    if (dist < 100) {
                        ctx.beginPath();
                        ctx.strokeStyle = `rgba(99, 102, 241, ${0.12 * (1 - dist/100)})`;
                        ctx.lineWidth = 0.6;
                        ctx.moveTo(p.x, p.y);
                        ctx.lineTo(p2.x, p2.y);
                        ctx.stroke();
                    }
                });

                // Soft cursor gravity field
                if (mouse.x !== null && mouse.y !== null) {
                    const dx = p.x - mouse.x;
                    const dy = p.y - mouse.y;
                    const dist = Math.sqrt(dx*dx + dy*dy);
                    if (dist < 150) {
                        const force = (150 - dist) / 150;
                        p.x += (mouse.x - p.x) * force * 0.015;
                        p.y += (mouse.y - p.y) * force * 0.015;
                    }
                }

                ctx.beginPath();
                ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
                ctx.fillStyle = p.color;
                ctx.fill();
            });

            requestAnimationFrame(animate);
        }
        animate();
    })();

    // 3. Simulated POS Transactions Loop
    (function() {
        const container = document.getElementById('live-tx-container');
        const transactions = [
            { id: '#1092', category: 'F&B Retail', amount: 'Rp 145.500', time: 'Baru saja', badge: 'glow-dot-cyan' },
            { id: '#1093', category: 'Laundry Express', amount: 'Rp 450.000', time: '1 menit yang lalu', badge: 'glow-dot-pink' },
            { id: '#1094', category: 'Fashion Outlet', amount: 'Rp 1.280.000', time: '3 menit yang lalu', badge: 'pulse-dot' },
            { id: '#1095', category: 'Minimarket', amount: 'Rp 95.000', time: '5 menit yang lalu', badge: 'glow-dot-cyan' },
            { id: '#1096', category: 'F&B Cafe', amount: 'Rp 220.000', time: '8 menit yang lalu', badge: 'glow-dot-pink' }
        ];

        let index = 3;

        function renderTransactions() {
            container.innerHTML = '';
            
            // Show only the 3 latest in our list
            const slice = transactions.slice(index - 3, index);
            slice.forEach(tx => {
                const div = document.createElement('div');
                div.className = 'tx-item';
                div.innerHTML = `
                    <div class="d-flex align-items-center gap-2">
                        <span class="${tx.badge}"></span>
                        <span class="text-white fw-medium">${tx.id}</span>
                        <span class="text-muted" style="font-size: 0.8rem;">(${tx.category})</span>
                    </div>
                    <div class="text-end">
                        <span class="text-success fw-bold">${tx.amount}</span>
                        <div class="text-muted" style="font-size: 0.7rem;">${tx.time}</div>
                    </div>
                `;
                container.appendChild(div);
            });
        }

        // Loop to add/rotate simulated transactions
        setInterval(() => {
            index++;
            if (index > transactions.length) {
                index = 3; // Reset rotation
            }
            
            // Randomize a value slightly to make it look dynamic
            const randomID = '#' + Math.floor(1000 + Math.random() * 9000);
            const categories = ['Cafe & Bistro', 'Electronic POS', 'SaaS Renewal', 'Apparel Store', 'Carwash Service'];
            const randomCat = categories[Math.floor(Math.random() * categories.length)];
            const randomAmt = 'Rp ' + (Math.floor(50 + Math.random() * 950) * 1000).toLocaleString('id-ID');
            const badges = ['glow-dot-cyan', 'glow-dot-pink', 'pulse-dot'];
            const randomBadge = badges[Math.floor(Math.random() * badges.length)];

            transactions.push({
                id: randomID,
                category: randomCat,
                amount: randomAmt,
                time: 'Baru saja',
                badge: randomBadge
            });

            // Adjust older times
            for (let i = 0; i < transactions.length - 1; i++) {
                if (transactions[i].time === 'Baru saja') {
                    transactions[i].time = '1 menit yang lalu';
                } else if (transactions[i].time === '1 menit yang lalu') {
                    transactions[i].time = '3 menit yang lalu';
                } else {
                    const mins = parseInt(transactions[i].time) || 3;
                    transactions[i].time = (mins + 2) + ' menit yang lalu';
                }
            }

            renderTransactions();
        }, 5000);

        // Initial render
        renderTransactions();
    })();
</script>
@endsection
