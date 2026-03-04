<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2a2218">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="1000 AD">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/images/icons/icon-192.png">
    <link rel="stylesheet" href="{{ asset('css/game.css') }}?v={{ filemtime(public_path('css/game.css')) }}">
    <title>{{ $title ?? '1000 A.D.' }}</title>
</head>
<body>
    <div class="auth-container">
        <div class="auth-title">1000 &nbsp; A. D.</div>
        <div class="auth-subtitle">
            <b>1000 A.D. is a free turn based strategy game.<br>
            All you need to play is a web browser.</b>
        </div>

        <div class="auth-flex">
            <div class="auth-sidebar">
                @yield('sidebar')
            </div>
            <div class="auth-main">
                @if(session('error'))
                    <p class="auth-error">{{ session('error') }}</p>
                @endif
                @if(session('success'))
                    <p class="auth-success">{{ session('success') }}</p>
                @endif
                @yield('content')
            </div>
        </div>

        <div class="game-footer">
            &copy; Copyright Ader Software 2000, 2001
        </div>
    </div>

<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(function(){});
}
</script>
</body>
</html>
