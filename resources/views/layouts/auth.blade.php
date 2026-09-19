<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Easemed Admin Portal - Secure login for administrators">
    <title>@yield('title', 'Login')</title>

    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/easemed-mark.svg') }}">
    <link rel="shortcut icon" href="{{ asset('images/easemed-mark.svg') }}" />

    <!-- Pollix CSS Files (same as base layout) -->
    <link rel="stylesheet" href="{{ asset('pollix/vendors/typicons/typicons.css') }}">
    <link rel="stylesheet" href="{{ asset('pollix/vendors/css/vendor.bundle.base.css') }}">
    <link rel="stylesheet" href="{{ asset('pollix/vendors/mdi/css/materialdesignicons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('pollix/css/vertical-layout-light/style.css') }}">

    {{-- Allow pages to push additional styles --}}
    @stack('styles')

    <style>
        body {
            background-image: url('{{ asset("images/doctor.jpg") }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
        }

        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        @yield('content')
    </div>

    <!-- Pollix JS Files (same as base layout) -->
    <script src="{{ asset('pollix/vendors/js/vendor.bundle.base.js') }}"></script>
    <script src="{{ asset('pollix/vendors/chart.js/Chart.min.js') }}"></script>
    <script src="{{ asset('pollix/js/off-canvas.js') }}"></script>
    <script src="{{ asset('pollix/js/hoverable-collapse.js') }}"></script>
    <script src="{{ asset('pollix/js/template.js') }}"></script>
    <script src="{{ asset('pollix/js/settings.js') }}"></script>
    <script src="{{ asset('pollix/js/todolist.js') }}"></script>
    <script src="{{ asset('pollix/js/dashboard.js') }}"></script>

    {{-- Allow pages to push additional scripts --}}
    @stack('scripts')
</body>
</html>
