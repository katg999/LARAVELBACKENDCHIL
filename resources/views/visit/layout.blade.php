<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title', 'Your visit') - {{ config('app.name', 'Easemed') }}</title>
    <style>
        body { margin:0; background:#f3f4f8; font-family: 'Helvetica Neue', Arial, sans-serif; color:#1b1b2f; }
        header { background:#c800c4; padding:14px 20px; }
        header img { height:34px; display:block; }
        main { max-width:520px; margin:24px auto; padding:0 16px; }
        .card { background:#fff; border-radius:14px; padding:22px; box-shadow:0 1px 4px rgba(0,0,0,.08); margin-bottom:14px; }
        h1 { font-size:22px; margin:0 0 6px; } h2 { font-size:17px; margin:0 0 4px; }
        .muted { color:#5b5b70; font-size:15px; } .row { margin:8px 0; font-size:16px; }
        .btn { display:block; text-align:center; background:#c800c4; color:#fff; text-decoration:none; font-weight:700; padding:15px; border-radius:10px; font-size:18px; margin-top:16px; }
        .pill { display:inline-block; padding:4px 12px; border-radius:999px; font-size:14px; font-weight:700; }
        .ok { background:#d9f5e3; color:#0d5c2b; } .wait { background:#fff0d6; color:#7a4b00; } .no { background:#fde0e0; color:#8a1c1c; }
    </style>
</head>
<body>
    <header><img src="{{ asset('images/easemed-logo-white.svg') }}" alt="{{ config('app.name', 'Easemed') }}"></header>
    <main>@yield('content')</main>
</body>
</html>
