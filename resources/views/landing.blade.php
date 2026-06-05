<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="RIMSPOS - Solusi POS Multi-Bisnis Terintegrasi untuk Retail, FnB, Barbershop, dan Carwash. Kelola transaksi, stok, meja, antrean, dan komisi staff dengan mudah.">
    <title>RIMSPOS - Aplikasi POS Kasir Multi-Bisnis Modern</title>

    <link rel="icon" href="{{ asset('assets/images/favicon-32x32.png') }}" type="image/png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        outfit: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            950: '#030712',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Custom Styles / Animations -->
    <style>
        body {
            background-color: #030712;
            color: #f3f4f6;
            overflow-x: hidden;
        }
        .glow-effect {
            position: relative;
        }
        .glow-effect::before {
            content: '';
            position: absolute;
            top: -20%;
            left: -20%;
            width: 140%;
            height: 140%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, rgba(0,0,0,0) 70%);
            z-index: -1;
            pointer-events: none;
        }
        .glow-purple::before {
            background: radial-gradient(circle, rgba(139, 92, 246, 0.15) 0%, rgba(0,0,0,0) 70%);
        }
        .glass-card {
            background: rgba(17, 24, 39, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .hover-scale {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .hover-scale:hover {
            transform: translateY(-6px);
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 10px 30px -10px rgba(99, 102, 241, 0.3);
        }
    </style>
</head>
<body class="font-sans antialiased bg-brand-950 text-slate-100 relative">
    <!-- Animated Abstract Lines Canvas Background -->
    <canvas id="bg-canvas" class="fixed top-0 left-0 w-full h-full -z-10 pointer-events-none opacity-60"></canvas>

    <!-- Header / Navbar -->
    <header class="fixed top-0 left-0 w-full z-50 glass-card border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
            <a href="#" class="flex items-center gap-3 group">
                <img src="{{ asset('assets/images/logo.png') }}" alt="RIMSPOS Logo" class="h-10 w-auto object-contain transition-transform group-hover:scale-105" onerror="this.src='{{ asset('assets/images/logo1.png') }}'">
                <span class="font-outfit font-extrabold text-2xl tracking-wider bg-gradient-to-r from-indigo-400 via-purple-400 to-indigo-400 bg-clip-text text-transparent group-hover:opacity-90">
                    RIMSPOS
                </span>
            </a>

            <!-- Navigation Links -->
            <nav class="hidden md:flex items-center gap-8">
                <a href="#fitur" class="text-sm font-medium text-slate-300 hover:text-indigo-400 transition-colors">Fitur</a>
                <a href="#bisnis" class="text-sm font-medium text-slate-300 hover:text-indigo-400 transition-colors">Modul Bisnis</a>
                <a href="#pricing" class="text-sm font-medium text-slate-300 hover:text-indigo-400 transition-colors">Harga</a>
                <a href="#kontak" class="text-sm font-medium text-slate-300 hover:text-indigo-400 transition-colors">Kontak</a>
            </nav>

            <!-- Login / CTA -->
            <div class="flex items-center gap-4">
                @auth
                    <a href="{{ url('/home') }}" class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 transition-colors duration-200">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-300 hover:text-white transition-colors py-2 px-4">
                        Masuk
                    </a>
                    <a href="#pricing" class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 transition-colors duration-200 shadow-lg shadow-indigo-600/20">
                        Coba Sekarang
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative pt-36 pb-20 md:pt-48 md:pb-32 glow-effect">
        <div class="max-w-7xl mx-auto px-6 grid md:grid-cols-12 gap-12 items-center">
            <div class="md:col-span-7 flex flex-col gap-6 text-left">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold self-start tracking-wide uppercase">
                    🚀 New Era of POS SaaS
                </div>
                <h1 class="font-outfit font-black text-4xl sm:text-5xl lg:text-6xl tracking-tight leading-tight text-white">
                    Satu Sistem POS untuk <span class="bg-gradient-to-r from-indigo-400 to-purple-400 bg-clip-text text-transparent">Multi-Bisnis Anda</span>
                </h1>
                <p class="text-slate-400 text-lg md:text-xl leading-relaxed max-w-xl">
                    RIMSPOS hadir memudahkan operasional Retail, FnB, Barbershop, hingga Carwash. Aplikasi kasir canggih terintegrasi penuh dengan laporan keuangan, stok, antrean, dan komisi staff.
                </p>
                <div class="flex flex-wrap gap-4 mt-2">
                    <a href="#pricing" class="px-8 py-4 rounded-xl text-base font-bold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 transition-all duration-200 shadow-xl shadow-indigo-600/30 transform hover:-translate-y-0.5">
                        Lihat Paket Berlangganan
                    </a>
                    <a href="https://wa.me/62811677585?text=Halo%20RIMSPOS,%20saya%20ingin%20mencoba%20demo%20aplikasi%20kasir." target="_blank" class="px-8 py-4 rounded-xl text-base font-bold text-slate-300 hover:text-white glass-card hover:bg-slate-800/80 transition-all duration-200 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-400 fill-current" viewBox="0 0 24 24">
                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.455L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451 5.436 0 9.86-4.37 9.864-9.799.002-2.63-1.023-5.101-2.885-6.965C16.49 1.977 14.021.953 11.4.953c-5.437 0-9.862 4.371-9.866 9.8.001 1.748.47 3.454 1.36 4.968l-.938 3.428 3.528-.925c1.472.8 3.012 1.205 4.573 1.205zm10.45-6.666c-.287-.143-1.695-.838-1.955-.933-.261-.096-.45-.143-.64.143-.19.285-.735.933-.9 1.121-.166.189-.332.213-.618.071-.287-.143-1.21-.446-2.305-1.424-.853-.76-1.428-1.698-1.595-1.984-.167-.286-.018-.44.125-.581.13-.127.287-.333.43-.5.143-.166.19-.285.286-.475.095-.19.047-.356-.024-.5-.071-.143-.64-1.543-.88-2.11-.233-.564-.469-.488-.64-.497-.166-.008-.356-.01-.546-.01-.19 0-.5.071-.76.356-.26.286-1 .976-1 2.381 0 1.405 1.02 2.761 1.163 2.952.143.19 2.01 3.069 4.869 4.3.68.293 1.21.468 1.62.597.683.217 1.305.187 1.796.114.547-.08 1.695-.69 1.934-1.357.24-.666.24-1.238.166-1.357-.074-.118-.261-.19-.548-.333z"/>
                        </svg>
                        Hubungi WhatsApp
                    </a>
                </div>
            </div>
            <div class="md:col-span-5 relative mt-8 md:mt-0">
                <div class="absolute inset-0 bg-gradient-to-tr from-indigo-500/20 to-purple-500/20 blur-3xl -z-10 rounded-full"></div>
                <!-- Glassmorphism Preview Dashboard -->
                <div class="glass-card rounded-2xl p-6 shadow-2xl relative border border-slate-700/50">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-4 mb-6">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-red-500"></span>
                            <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                            <span class="w-3 h-3 rounded-full bg-green-500"></span>
                        </div>
                        <span class="text-xs font-semibold text-slate-500">RIMSPOS Web Kasir</span>
                    </div>

                    <!-- Mock Stats -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-slate-900/80 border border-slate-800/80 rounded-xl p-4">
                            <span class="text-xs text-slate-500 block mb-1">Transaksi Harian</span>
                            <span class="font-outfit font-bold text-xl text-white">Rp 2.450.000</span>
                        </div>
                        <div class="bg-slate-900/80 border border-slate-800/80 rounded-xl p-4">
                            <span class="text-xs text-slate-500 block mb-1">Total Toko Aktif</span>
                            <span class="font-outfit font-bold text-xl text-indigo-400">4 Outlet</span>
                        </div>
                    </div>

                    <!-- Mock List -->
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between p-3 rounded-lg bg-slate-900/50 border border-slate-800/50">
                            <div class="flex items-center gap-3">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                                <span class="text-sm font-medium text-slate-300">Penjualan Retail</span>
                            </div>
                            <span class="text-xs font-semibold text-slate-500">Stok FIFO</span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-lg bg-slate-900/50 border border-slate-800/50">
                            <div class="flex items-center gap-3">
                                <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                                <span class="text-sm font-medium text-slate-300">Antrean Dapur (FnB)</span>
                            </div>
                            <span class="text-xs font-semibold text-purple-400">KDS Active</span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-lg bg-slate-900/50 border border-slate-800/50">
                            <div class="flex items-center gap-3">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                <span class="text-sm font-medium text-slate-300">Barberman Commission</span>
                            </div>
                            <span class="text-xs font-semibold text-emerald-400">30% Share</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Features Section -->
    <section id="fitur" class="py-20 bg-slate-900/40 border-y border-slate-900">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="text-indigo-400 font-semibold text-sm tracking-wider uppercase">Keunggulan Utama</span>
                <h2 class="font-outfit font-extrabold text-3xl md:text-4xl text-white mt-3">Satu Dashboard, Banyak Kelebihan</h2>
                <p class="text-slate-400 mt-4 leading-relaxed">
                    Kami mendesain RIMSPOS agar fleksibel dan tangguh untuk menunjang berbagai jenis operasional bisnis harian Anda.
                </p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Feature 1 -->
                <div class="glass-card rounded-2xl p-6 hover-scale">
                    <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 mb-6">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="font-outfit font-bold text-lg text-white mb-2">POS Kasir Responsif</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Tampilan kasir modern di web dan mobile Android. Transaksi kilat dengan pencarian produk cerdas, scan barcode, dan cetak struk instant.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="glass-card rounded-2xl p-6 hover-scale">
                    <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 mb-6">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <h3 class="font-outfit font-bold text-lg text-white mb-2">Stok FIFO & Batch</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Manajemen stok ketat dengan metode FIFO, melacak kode batch barang masuk, masa kadaluwarsa, stock opname, dan perpindahan stok antar cabang.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="glass-card rounded-2xl p-6 hover-scale">
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 mb-6">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="font-outfit font-bold text-lg text-white mb-2">Laporan Keuangan</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Dapatkan laporan laba-rugi otomatis secara konsolidasi, neraca lajur, arus kas masuk harian, dan grafik statistik performa penjualan secara real-time.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="glass-card rounded-2xl p-6 hover-scale">
                    <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-6">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="font-outfit font-bold text-lg text-white mb-2">Multi-Outlet & Multi-User</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Kelola banyak cabang toko (multi-tenant) di bawah satu akun superadmin. Tetapkan role user kasir, staf gudang, supervisor, secara dinamis.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Business Modules Section -->
    <section id="bisnis" class="py-20 glow-effect glow-purple">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="text-purple-400 font-semibold text-sm tracking-wider uppercase">Spesialisasi Operasional</span>
                <h2 class="font-outfit font-extrabold text-3xl md:text-4xl text-white mt-3">4 Pilihan Modul Bisnis Terbaik</h2>
                <p class="text-slate-400 mt-4 leading-relaxed">
                    RIMSPOS dapat berganti mode antarmuka dan validasi bisnis secara dinamis sesuai dengan jenis usaha outlet Anda.
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-8">
                <!-- Retail Modul -->
                <div class="glass-card rounded-2xl p-8 hover-scale flex gap-6">
                    <div class="shrink-0 w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-outfit font-bold text-xl text-white mb-2">Toko Retail / Minimarket</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            Alur penjualan kilat dengan barcode scanner, manajemen stok barang fisik dengan kontrol batch & FIFO, diskon bertingkat, voucher belanja, dan laporan laba per transaksi penjualan.
                        </p>
                    </div>
                </div>

                <!-- FnB Modul -->
                <div class="glass-card rounded-2xl p-8 hover-scale flex gap-6">
                    <div class="shrink-0 w-12 h-12 rounded-xl bg-purple-500/10 border border-blue-500/20 flex items-center justify-center text-purple-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-outfit font-bold text-xl text-white mb-2">Resto / Café (FnB)</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            Mendukung Kitchen Display System (KDS) terintegrasi dapur, cetak struk antrean menu, manajemen layout nomor meja aktif, serta fitur pemindahan meja (*move table*) dan penyatuan bill tagihan (*merge bill*).
                        </p>
                    </div>
                </div>

                <!-- Barber Modul -->
                <div class="glass-card rounded-2xl p-8 hover-scale flex gap-6">
                    <div class="shrink-0 w-12 h-12 rounded-xl bg-emerald-500/10 border border-blue-500/20 flex items-center justify-center text-emerald-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-4.879-4.879L21 21M8.879 8.879L3 3m5.879 5.879L1 1m0 0a3 3 0 104.243 4.243m-4.243-4.243a3 3 0 114.243 4.243m0 0L12 12m0 0l3.757-3.757M12 12l-3.757 3.757M12 12l3.757 3.757m0 0A3 3 0 1019.757 12m-3.757 3.757a3 3 0 113.757-3.757"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-outfit font-bold text-xl text-white mb-2">Barbershop / Salon</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            Penetapan antrean kasir per stylist/barberman, pembagian komisi staff otomatis per transaksi pelayanan jasa, monitoring kinerja pelayanan harian, dan penjualan produk styling retail pendukung.
                        </p>
                    </div>
                </div>

                <!-- Carwash Modul -->
                <div class="glass-card rounded-2xl p-8 hover-scale flex gap-6">
                    <div class="shrink-0 w-12 h-12 rounded-xl bg-amber-500/10 border border-blue-500/20 flex items-center justify-center text-amber-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-outfit font-bold text-xl text-white mb-2">Carwash / Detailing</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            Pencatatan nomor plat kendaraan aktif, pemetaan harga berdasarkan kategori ukuran kendaraan (Motor/Sedan/SUV), sistem pembagian tugas washer cuci mobil, serta monitoring antrean pencucian.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-20 bg-slate-900/20 border-t border-slate-900">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="text-indigo-400 font-semibold text-sm tracking-wider uppercase">Skema Berlangganan</span>
                <h2 class="font-outfit font-extrabold text-3xl md:text-4xl text-white mt-3">Investasi Terbaik untuk Bisnis Anda</h2>
                <p class="text-slate-400 mt-4 leading-relaxed">
                    Pilih paket berlangganan SaaS RIMSPOS yang paling pas untuk skala operasional toko Anda. Hubungi kami via WhatsApp untuk aktivasi instan.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 items-stretch">
                <!-- Plan 1: Bulanan -->
                <div class="glass-card rounded-3xl p-8 hover-scale flex flex-col justify-between relative">
                    <div>
                        <span class="text-slate-400 text-sm font-semibold tracking-wider uppercase block mb-2">Paket Bulanan</span>
                        <div class="flex items-baseline mb-6">
                            <span class="text-3xl font-extrabold text-white">Rp 150K</span>
                            <span class="text-slate-400 text-sm ml-1">/ bulan</span>
                        </div>
                        <p class="text-slate-400 text-sm leading-relaxed mb-6">
                            Sangat cocok untuk pemula yang ingin mencoba sistem POS dan bisnis yang baru merintis.
                        </p>
                        <hr class="border-slate-800 mb-6">
                        <ul class="flex flex-col gap-3 text-sm text-slate-300">
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                1 Outlet / Toko Aktif
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Penjualan POS Unlimited
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Fitur Stok FIFO & Batch
                            </li>
                            <li class="flex items-center gap-2 text-slate-500 line-through">
                                Multi-Outlet Terpusat
                            </li>
                            <li class="flex items-center gap-2 text-slate-500 line-through">
                                Pendampingan Setup Awal
                            </li>
                        </ul>
                    </div>
                    <div class="mt-8">
                        <a href="https://wa.me/62811677585?text=Halo%20RIMSPOS,%20saya%20tertarik%20untuk%20berlangganan%20Paket%20Bulanan%20(Rp%20150.000/bulan)." target="_blank" class="block w-full text-center py-3 rounded-xl font-bold text-sm text-indigo-400 border border-indigo-500/30 hover:bg-indigo-500/10 transition-all duration-200">
                            Pilih Paket Bulanan
                        </a>
                    </div>
                </div>

                <!-- Plan 2: Tahunan (Best Deal) -->
                <div class="glass-card rounded-3xl p-8 hover-scale flex flex-col justify-between border-2 border-indigo-500 shadow-xl shadow-indigo-600/10 relative">
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-indigo-500 text-white text-xs font-bold uppercase tracking-wider">
                        ✨ Best Value
                    </div>
                    <div>
                        <span class="text-slate-400 text-sm font-semibold tracking-wider uppercase block mb-2">Paket Tahunan</span>
                        <div class="flex items-baseline mb-6">
                            <span class="text-3xl font-extrabold text-white">Rp 1.5M</span>
                            <span class="text-slate-400 text-sm ml-1">/ tahun</span>
                        </div>
                        <p class="text-slate-400 text-sm leading-relaxed mb-6">
                            Pilihan paling populer. Hemat biaya bulanan dan nikmati fitur lengkap SaaS kasir.
                        </p>
                        <hr class="border-slate-800 mb-6">
                        <ul class="flex flex-col gap-3 text-sm text-slate-300">
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                3 Outlet / Cabang Toko
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Penjualan POS Unlimited
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Fitur Stok FIFO & Batch
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Laporan Konsolidasi Admin
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Prioritas Dukungan 24/7
                            </li>
                        </ul>
                    </div>
                    <div class="mt-8">
                        <a href="https://wa.me/62811677585?text=Halo%20RIMSPOS,%20saya%20tertarik%20untuk%20berlangganan%20Paket%20Tahunan%20(Rp%201.500.000/tahun)." target="_blank" class="block w-full text-center py-3 rounded-xl font-bold text-sm text-white bg-indigo-600 hover:bg-indigo-500 transition-all duration-200 shadow-lg shadow-indigo-600/30">
                            Pilih Paket Tahunan
                        </a>
                    </div>
                </div>

                <!-- Plan 3: Lifetime -->
                <div class="glass-card rounded-3xl p-8 hover-scale flex flex-col justify-between relative">
                    <div>
                        <span class="text-slate-400 text-sm font-semibold tracking-wider uppercase block mb-2">Paket Lifetime</span>
                        <div class="flex items-baseline mb-6">
                            <span class="text-3xl font-extrabold text-white">Hubungi Sales</span>
                        </div>
                        <p class="text-slate-400 text-sm leading-relaxed mb-6">
                            Investasi jangka panjang. Tanpa biaya perpanjangan berkala, hak milik penuh sistem.
                        </p>
                        <hr class="border-slate-800 mb-6">
                        <ul class="flex flex-col gap-3 text-sm text-slate-300">
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Unlimited Outlet / Cabang
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Penjualan POS Unlimited
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Fitur Stok FIFO & Batch
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Laporan Konsolidasi & KDS
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Pendampingan & Setup Awal
                            </li>
                        </ul>
                    </div>
                    <div class="mt-8">
                        <a href="https://wa.me/62811677585?text=Halo%20RIMSPOS,%20saya%20tertarik%20untuk%20menanyakan%20informasi%20mengenai%20Paket%20Lifetime%20SaaS." target="_blank" class="block w-full text-center py-3 rounded-xl font-bold text-sm text-indigo-400 border border-indigo-500/30 hover:bg-indigo-500/10 transition-all duration-200">
                            Hubungi Kami
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Banner -->
    <section id="kontak" class="py-20 relative glow-effect">
        <div class="max-w-5xl mx-auto px-6 text-center">
            <div class="glass-card rounded-3xl p-12 md:p-16 border border-slate-700/60 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-indigo-600/10 to-purple-600/10 -z-10"></div>
                <h2 class="font-outfit font-black text-3xl md:text-5xl text-white mb-6 leading-tight">
                    Siap Mengembangkan <br class="hidden md:inline"> Bisnis Anda Bersama RIMSPOS?
                </h2>
                <p class="text-slate-400 text-base md:text-lg mb-8 max-w-xl mx-auto">
                    Hubungi tim kami hari ini untuk konsultasi, demo operasional, atau langsung melakukan registrasi cabang toko baru Anda.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="https://wa.me/62811677585?text=Halo%20RIMSPOS,%20saya%20ingin%20konsultasi%20mengenai%20layanan%20kasir." target="_blank" class="px-8 py-4 rounded-xl text-base font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition-colors shadow-lg shadow-indigo-600/20 w-full sm:w-auto">
                        Hubungi WhatsApp Sales
                    </a>
                    <a href="{{ route('login') }}" class="px-8 py-4 rounded-xl text-base font-bold text-slate-300 hover:text-white glass-card hover:bg-slate-800 transition-colors w-full sm:w-auto">
                        Masuk Ke Aplikasi
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-12 border-t border-slate-900 bg-brand-950/80">
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-3">
                <img src="{{ asset('assets/images/logo.png') }}" alt="RIMSPOS Logo" class="h-8 w-auto object-contain" onerror="this.src='{{ asset('assets/images/logo1.png') }}'">
                <span class="font-outfit font-extrabold text-xl tracking-wider text-white">RIMSPOS</span>
            </div>
            <p class="text-slate-500 text-sm">
                &copy; {{ date('Y') }} RIMSPOS. Hak Cipta Dilindungi Undang-Undang.
            </p>
            <div class="flex items-center gap-6 text-sm text-slate-400">
                <a href="#fitur" class="hover:text-indigo-400 transition-colors">Fitur</a>
                <a href="#pricing" class="hover:text-indigo-400 transition-colors">Harga</a>
                <a href="https://wa.me/62811677585" target="_blank" class="hover:text-indigo-400 transition-colors">Support</a>
            </div>
        </div>
    </footer>

    <!-- Canvas Background Animation Script -->
    <script>
        const canvas = document.getElementById('bg-canvas');
        const ctx = canvas.getContext('2d');

        let width = canvas.width = window.innerWidth;
        let height = canvas.height = window.innerHeight;

        window.addEventListener('resize', () => {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
        });

        let progress = 0;

        function animate() {
            ctx.clearRect(0, 0, width, height);

            const phase = progress * 2 * Math.PI;

            // Draw 7 wavy lines (from Flutter's _AbstractLinesPainter)
            for (let i = 0; i < 7; i++) {
                const isEven = i % 2 === 0;
                // Colors matched to Flutter: _kPrimary (37, 99, 235) vs _kAccent (56, 189, 248)
                const color = isEven ? 'rgba(37, 99, 235,' : 'rgba(56, 189, 248,';
                const alpha = 0.08 + (i % 3) * 0.02;
                ctx.strokeStyle = `${color}${alpha})`;
                ctx.lineWidth = 1.0 + (i % 3) * 0.4;

                ctx.beginPath();
                const yBase = height * (0.10 + i * 0.13);
                const amplitude = 30.0 + i * 8.0;
                const freq = 1.5 + i * 0.3;
                const phaseOffset = i * 0.7;

                ctx.moveTo(-20, yBase);
                for (let x = -20; x <= width + 20; x += 4) {
                    const normalizedX = x / width;
                    const y = yBase +
                        Math.sin(normalizedX * freq * Math.PI + phase + phaseOffset) * amplitude +
                        Math.cos(normalizedX * (freq * 0.5) * Math.PI + phase * 0.7 + phaseOffset) * (amplitude * 0.4);
                    if (x === -20) {
                        ctx.moveTo(x, y);
                    } else {
                        ctx.lineTo(x, y);
                    }
                }
                ctx.stroke();
            }

            // Draw 4 subtle diagonal accent lines
            for (let i = 0; i < 4; i++) {
                ctx.strokeStyle = 'rgba(56, 189, 248, 0.04)';
                ctx.lineWidth = 0.5;

                ctx.beginPath();
                const startX = width * (0.2 + i * 0.25) + Math.sin(phase + i) * 20;
                ctx.moveTo(startX, 0);
                ctx.lineTo(startX - width * 0.3, height);
                ctx.stroke();
            }

            progress += 0.0004; // Gentle animation speed
            if (progress > 1.0) progress = 0;

            requestAnimationFrame(animate);
        }

        animate();
    </script>
</body>
</html>
