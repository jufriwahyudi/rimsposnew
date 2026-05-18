<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Zahraa | Admin Dashboard')</title>
<link rel="icon" href="{{ asset('assets/images/favicon-32x32.png') }}" type="image/png">

<!-- loader -->
<link href="{{ asset('assets/css/pace.min.css') }}" rel="stylesheet">
<script src="{{ asset('assets/js/pace.min.js') }}"></script>

<!-- plugins -->
<link href="{{ asset('assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css') }}" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/metismenu/metisMenu.min.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/metismenu/mm-vertical.css') }}">

<!-- bootstrap -->
<link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Material+Icons+Outlined" rel="stylesheet">

<!-- main css -->
<link href="{{ asset('assets/css/bootstrap-extended.css') }}" rel="stylesheet">
@vite('resources/sass/app.scss')