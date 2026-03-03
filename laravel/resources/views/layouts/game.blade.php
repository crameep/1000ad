<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#2a2218">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="1000 AD">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/images/icons/icon-192.png">
    <link rel="stylesheet" href="{{ asset('css/game.css') }}">
    <title>1000 A.D.</title>
    <script>
        function openHelp(h) {
            if (window.innerWidth <= 600) {
                window.location.href = '/game/docs/' + h;
            } else {
                window.open('/game/docs/' + h, '_blank', 'width=800,height=500,scrollbars=yes');
            }
        }
    </script>
</head>
<body>

<div class="game-container">
    {{-- Header --}}
    <div class="game-title">1000 &nbsp; A. D.</div>
    <div class="empire-info">
        {{ $player->name }} #{{ $player->id }} - {{ $empireName }}
    </div>
    <div class="game-date">{{ $gameDate }}</div>

    {{-- Flash messages --}}
    @if(session('game_message'))
        <div class="eflag-message">{!! session('game_message') !!}</div>
    @endif

    {{-- Dead notice --}}
    @if($player->killed_by > 0)
        <div class="dead-notice">
            You have been killed by {{ $player->killed_by_name }} (#{{ $player->killed_by }})
            @if($player->killed_by === 1)
                <br><span class="text-small">You might have been killed because of cheating (using multiple accounts).</span>
            @endif
        </div>
    @endif

    {{-- Turn info --}}
    <div class="turn-info">
        ({{ $playerTurns }} months remaining,
        @if($playerTurns >= $maxTurnsStored)
            maximum turns stored)
        @else
            next free month in {{ intdiv($nextTurnSeconds, 60) }} minutes and {{ $nextTurnSeconds % 60 }} seconds)
        @endif
    </div>

    {{-- Mobile menu toggle --}}
    <button class="menu-toggle" onclick="document.querySelector('.left-panel').classList.toggle('is-open')">
        &#9776; Menu
    </button>

    {{-- Main layout --}}
    <div class="main-layout">
        {{-- Left sidebar --}}
        <div class="left-panel">
            @include('partials.menu')
            @include('partials.news')
        </div>

        {{-- Right content --}}
        <div class="right-panel">
            {{-- Resource bar --}}
            @include('partials.resource-bar')

            {{-- Page content --}}
            <div class="panel">
                <div class="panel-body">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="game-footer">
        Game Time: {{ now()->format('M j, Y h:i A') }}<br>
        &copy; Copyright Ader Software 2000, 2001
    </div>
</div>

<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js')
        .then(function(reg) { console.log('SW registered:', reg.scope); })
        .catch(function(err) { console.log('SW registration failed:', err); });
}

// PWA install prompt
let deferredPrompt;
window.addEventListener('beforeinstallprompt', function(e) {
    e.preventDefault();
    deferredPrompt = e;
    var banner = document.createElement('div');
    banner.id = 'pwa-install';
    banner.style.cssText = 'position:fixed;bottom:0;left:0;right:0;background:var(--bg-header);color:var(--text-primary);padding:14px;text-align:center;z-index:9999;font-family:var(--font-body);border-top:2px solid var(--border-accent);';
    banner.innerHTML = '<span style="font-family:var(--font-title);color:var(--border-accent);">Install 1000 A.D. as an app!</span> ' +
        '<button onclick="installPWA()" style="margin-left:12px;">Install</button> ' +
        '<button onclick="this.parentElement.remove()" style="margin-left:8px;">Not now</button>';
    document.body.appendChild(banner);
});
function installPWA() {
    if (deferredPrompt) {
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then(function() {
            deferredPrompt = null;
            var el = document.getElementById('pwa-install');
            if (el) el.remove();
        });
    }
}
</script>

</body>
</html>
