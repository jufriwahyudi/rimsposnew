<!doctype html>
<html lang="en" data-bs-theme="light-theme">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    <link rel="icon" href="{{ asset('assets/images/favicon-32x32.png') }}" type="image/png">

    <!-- loader -->
    <link href="{{ asset('assets/css/pace.min.css') }}" rel="stylesheet">
    <script src="{{ asset('assets/js/pace.min.js') }}"></script>

    <!-- plugins -->
    <link href="{{ asset('assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css') }}" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/metismenu/metisMenu.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/metismenu/mm-vertical.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/simplebar/css/simplebar.css') }}">
    <!-- bootstrap -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Material+Icons+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">



    <!-- main css -->
    <link href="{{ asset('assets/css/bootstrap-extended.css') }}" rel="stylesheet">

    <!-- CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />





    {{-- Vite --}}
    @vite(['resources/sass/app.scss'])
    @stack('styles')
</head>

<body>
    <div class="d-flex flex-column min-vh-100">
        {{-- Header --}}
        @include('layouts.main.partials.header')

        {{-- Sidebar --}}
        @include('layouts.main.partials.sidebar')
        x
        {{-- Main Content --}}
        <main class="main-wrapper">
            <div class="main-content">
                {{-- Breadcrumb (opsional) --}}
                @yield('breadcrumb')

                {{-- Page Content --}}
                @yield('content')
            </div>
        </main>

        {{-- Footer --}}
        @include('layouts.main.partials.footer')

        {{-- Overlay, Offcanvas, Customizer --}}
        @include('layouts.main.partials.overlay')
        @include('layouts.main.partials.cart')
    </div>

    {{-- Scripts --}}
    @include('layouts.main.partials.scripts')

    {{-- Script tambahan dari halaman --}}
    @stack('scripts')
</body>

</html>
