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
    <link rel="stylesheet" href="{{ asset('css/game.css') }}">
    <title>Game Lobby - 1000 A.D.</title>
</head>
<body>
    <div class="auth-container" style="max-width:900px;">
        <div class="auth-title">1000 &nbsp; A. D.</div>
        <div class="auth-subtitle">
            <b>Game Lobby</b> &mdash; Welcome, {{ Auth::user()->login_name }}
            <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                @csrf
                <button type="submit" style="background:none; border:none; color:var(--text-accent); cursor:pointer; text-decoration:underline; font-size:inherit;">Logout</button>
            </form>
            @if(Auth::user()->isAdmin())
                &nbsp;|&nbsp; <a href="{{ route('admin.dashboard') }}" style="color:var(--text-accent);">Admin Panel</a>
            @endif
        </div>

        @if(session('error'))
            <p class="auth-error">{{ session('error') }}</p>
        @endif
        @if(session('success'))
            <p class="auth-success">{{ session('success') }}</p>
        @endif

        @yield('content')

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
