@extends('layouts.auth.app')

@section('title', 'Pilih Toko - ' . config('app.name'))

@section('content')
<!-- Custom Styles for Futuristic SaaS POS select-store -->
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

    .select-store-wrapper {
        position: relative;
        z-index: 10;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }

    /* Centered Glassmorphic Card */
    .login-card {
        background: rgba(11, 15, 27, 0.7);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 28px;
        padding: 3rem 2.5rem;
        width: 100%;
        max-width: 460px;
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

    /* Brand Logo Box with glass reflection */
    .brand-logo-wrapper {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.5rem;
        padding: 0.6rem;
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.82);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.6);
        box-shadow: 0 10px 25px rgba(99, 102, 241, 0.2), 
                    0 0 15px rgba(6, 182, 212, 0.15);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

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
        font-size: 1.5rem;
        margin-bottom: 0.35rem;
    }

    .form-subtitle {
        color: #94a3b8;
        font-size: 0.9rem;
        margin-bottom: 2rem;
    }

    /* Store Option Cards styling */
    .store-option-card {
        display: flex;
        align-items: center;
        gap: 1rem;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 1.1rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .store-option-card:hover {
        background: rgba(255, 255, 255, 0.06);
        border-color: rgba(255, 255, 255, 0.15);
    }

    .store-option-card:has(input:checked) {
        background: rgba(99, 102, 241, 0.08);
        border-color: #6366f1;
        box-shadow: 0 0 20px rgba(99, 102, 241, 0.25);
    }

    .store-option-card input[type="radio"] {
        accent-color: #6366f1;
        width: 1.1rem;
        height: 1.1rem;
    }

    .store-name {
        color: #ffffff;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .store-desc {
        color: #94a3b8;
        font-size: 0.8rem;
        margin-top: 0.15rem;
        line-height: 1.4;
    }

    .store-icon {
        color: #64748b;
        transition: color 0.3s, filter 0.3s;
    }

    .store-option-card:has(input:checked) .store-icon {
        color: #6366f1;
        filter: drop-shadow(0 0 8px rgba(99, 102, 241, 0.5));
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
</style>

<!-- Canvas for Moving Abstract Lines background -->
<canvas id="abstract-canvas"></canvas>

<div class="container-fluid select-store-wrapper p-0">
    <div class="login-card">
        
        {{-- Logo & Brand --}}
        <div class="text-center">
            <div class="brand-logo-wrapper">
                <img src="{{ asset('assets/images/rimspos_logo.png') }}" alt="RimsPOS Logo">
            </div>
            <h5 class="form-title">Pilih Toko</h5>
            <p class="form-subtitle">
                Halo, <strong>{{ Auth::user()->name }}</strong>. Silakan pilih toko yang ingin dikelola.
            </p>
        </div>

        {{-- Error Alerts --}}
        @if ($errors->has('store_id'))
            <div class="alert alert-danger small border-0 text-white py-2 px-3 mb-4 rounded-3" 
                 style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4) !important;">
                <i class="fa fa-exclamation-circle me-2"></i> {{ $errors->first('store_id') }}
            </div>
        @endif

        @if ($stores->isEmpty())
            <div class="alert alert-warning small border-0 text-white py-3 px-3 mb-4 rounded-3"
                 style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.3) !important;">
                <i class="fa fa-info-circle me-2"></i> Akun Anda belum memiliki akses ke toko manapun. Hubungi administrator.
            </div>
            
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-futuristic" style="background: linear-gradient(135deg, #475569, #64748b);">
                    <i class="fa fa-sign-out-alt me-2"></i> Keluar / Log Out
                </button>
            </form>
        @else
            <form method="POST" action="{{ route('select-store.choose') }}">
                @csrf

                <div class="d-flex flex-column gap-3 mb-4">
                    @foreach ($stores as $store)
                        <label class="store-option-card" for="store_{{ $store->id }}">
                            <input type="radio" name="store_id" id="store_{{ $store->id }}"
                                value="{{ $store->id }}" class="form-check-input mt-0 flex-shrink-0"
                                {{ $loop->first ? 'checked' : '' }}>
                            
                            <div class="flex-grow-1">
                                <div class="store-name">{{ $store->name }}</div>
                                <div class="store-desc">
                                    Code: {{ $store->code }}
                                    @if ($store->address)
                                        <br>{{ $store->address }}
                                    @endif
                                </div>
                            </div>
                            
                            <i class="fa fa-store fa-lg store-icon"></i>
                        </label>
                    @endforeach
                </div>

                <button type="submit" class="btn btn-futuristic mb-3">
                    Masuk ke Toko <i class="fa fa-arrow-right ms-2" style="font-size: 0.85rem;"></i>
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="text-center">
                @csrf
                <button type="submit" class="btn btn-link btn-sm text-muted text-decoration-none">
                    Ganti akun / Keluar
                </button>
            </form>
        @endif

    </div>
</div>

{{-- Inline JS for Canvas animation --}}
<script>
    (function() {
        const canvas = document.getElementById('abstract-canvas');
        const ctx = canvas.getContext('2d');

        let width = canvas.width = window.innerWidth;
        let height = canvas.height = window.innerHeight;

        window.addEventListener('resize', () => {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
            waves.forEach(w => w.y = height * 0.5);
        });

        // Glowing Line definitions (Identik dengan halaman Login)
        const waves = [
            {
                y: height * 0.5,
                length: 0.003,
                amplitude: 120,
                frequency: 0.008,
                speed: 0.004,
                phase: 0,
                color: 'rgba(99, 102, 241, 0.25)',
                lineWidth: 3.5
            },
            {
                y: height * 0.5,
                length: 0.004,
                amplitude: 90,
                frequency: 0.012,
                speed: 0.007,
                phase: Math.PI / 4,
                color: 'rgba(6, 182, 212, 0.3)',
                lineWidth: 2
            },
            {
                y: height * 0.5,
                length: 0.002,
                amplitude: 150,
                frequency: 0.006,
                speed: 0.003,
                phase: Math.PI / 2,
                color: 'rgba(139, 92, 246, 0.2)',
                lineWidth: 5
            },
            {
                y: height * 0.5,
                length: 0.005,
                amplitude: 60,
                frequency: 0.018,
                speed: 0.009,
                phase: Math.PI,
                color: 'rgba(236, 72, 153, 0.15)',
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
            ctx.fillStyle = 'rgba(7, 9, 19, 0.15)';
            ctx.fillRect(0, 0, width, height);

            ctx.fillStyle = 'rgba(255, 255, 255, 0.01)';
            const grid = 60;
            for (let x = 0; x < width; x += grid) {
                for (let y = 0; y < height; y += grid) {
                    ctx.fillRect(x, y, 1.5, 1.5);
                }
            }

            waves.forEach(wave => {
                ctx.beginPath();
                ctx.strokeStyle = wave.color;
                ctx.lineWidth = wave.lineWidth;
                ctx.lineCap = 'round';
                ctx.shadowBlur = 18;
                ctx.shadowColor = wave.color;

                for (let x = 0; x < width; x += 8) {
                    let y = wave.y + Math.sin(x * wave.length + wave.phase) * wave.amplitude * Math.cos(wave.phase * 0.25);

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
                ctx.shadowBlur = 0;

                wave.phase += wave.speed;
            });

            particles.forEach(p => {
                p.x += p.vx;
                p.y += p.vy;

                if (p.x < 0 || p.x > width) p.vx *= -1;
                if (p.y < 0 || p.y > height) p.vy *= -1;

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
</script>
@endsection
