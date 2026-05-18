@extends('layouts.auth.app')

@section('title', 'Pilih Toko - ' . config('app.name'))

@section('content')
    <div class="section-authentication-cover">
        <div class="row g-0 min-vh-100">

            {{-- LEFT : HERO --}}
            <div class="col-xl-7 col-xxl-8 d-none d-xl-flex align-items-top justify-content-center border-end"
                style="background: url('{{ asset('assets/images/bg-themes/bg03.jpeg') }}') no-repeat center; background-size: cover;">
                <div class="text-center px-5 pt-2">
                    <p class="text-muted text-center fw-bold fs-4 mb-0 mt-4" style="text-shadow: 0 4px 12px rgba(0,0,0,.45);">
                        Kelola Inventori dan Penjualan<br>Mudah dan Terintegrasi
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
                            <small class="text-muted">Al-Azhar Management of Inventory & Retail Application</small>
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
                                <span class="material-icons align-middle me-1" style="font-size:18px;">logout</span>
                                Keluar
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
                                        <span class="material-icons text-muted" style="font-size:20px;">storefront</span>
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
