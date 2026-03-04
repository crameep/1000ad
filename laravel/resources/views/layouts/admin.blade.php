<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/game.css') }}?v={{ filemtime(public_path('css/game.css')) }}">
    <title>Admin - 1000 A.D.</title>
</head>
<body>
    <div class="admin-layout">
        <div class="admin-sidebar">
            <div class="admin-sidebar-title">
                <a href="{{ route('admin.dashboard') }}">1000 A.D.</a>
                <span class="text-small text-muted">Admin Panel</span>
            </div>
            <nav class="admin-nav">
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    Dashboard
                </a>
                <a href="{{ route('admin.games.index') }}" class="{{ request()->routeIs('admin.games.*') ? 'active' : '' }}">
                    Games
                </a>
                <a href="{{ route('admin.players.index') }}" class="{{ request()->routeIs('admin.players.*') ? 'active' : '' }}">
                    Players
                </a>
                <a href="{{ route('admin.finance.index') }}" class="{{ request()->routeIs('admin.finance.*') ? 'active' : '' }}">
                    Finance
                </a>
                <hr style="border-color:var(--border-dark); margin:8px 0;">
                <a href="{{ route('lobby') }}">Back to Lobby</a>
                <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                    @csrf
                    <a href="#" onclick="this.closest('form').submit(); return false;">Logout</a>
                </form>
            </nav>
        </div>
        <div class="admin-main">
            @if(session('success'))
                <div class="admin-alert admin-alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="admin-alert admin-alert-error">{{ session('error') }}</div>
            @endif
            @yield('content')
        </div>
    </div>
</body>
</html>
