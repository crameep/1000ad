<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>1000 A.D.</title>
    <style>
        body {
            background-image: url('/images/bg.gif');
            background-color: #000;
            color: #fff;
            font-family: Verdana, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 10px;
        }
        a { color: aqua; text-decoration: none; }
        a:hover { color: red; text-decoration: underline overline; }

        td { font-family: Verdana, sans-serif; font-size: 12px; }
        td.small { font-size: 10px; }
        td.header {
            background-color: darkslategray;
            color: white;
            font-weight: bold;
            padding: 2px 6px;
        }

        .game-container { max-width: 780px; margin: 0 auto; }
        .game-title { font-size: 48px; font-weight: bold; text-align: center; }
        .empire-info { text-align: center; font-size: 16px; font-weight: bold; }
        .game-date { text-align: center; font-size: 12px; font-weight: bold; }

        .turn-info { font-size: 10px; }

        .main-layout { display: flex; gap: 10px; }
        .left-panel { width: 200px; flex-shrink: 0; }
        .right-panel { flex: 1; }

        .panel { border: 1px solid darkslategray; margin-bottom: 8px; }
        .panel-header {
            background-color: darkslategray;
            text-align: center;
            padding: 3px;
            font-weight: bold;
        }
        .panel-body { padding: 6px; }

        .resource-bar {
            display: flex;
            justify-content: space-between;
            background-color: darkslategray;
            padding: 4px 8px;
            font-weight: bold;
        }

        .land-row { display: flex; gap: 10px; }
        .land-total { background-color: #633; padding: 2px 4px; }
        .land-free { background-color: #363; padding: 2px 4px; }

        .resource-item img { vertical-align: middle; }

        .error-msg { color: red; }
        .info-msg { color: yellow; }

        .menu-list { list-style: disc; padding-left: 20px; }
        .menu-list li { margin: 3px 0; }
        .menu-list a { font-size: 14px; font-weight: bold; }

        input[type="text"], input[type="number"], select {
            font-size: 10px;
            padding: 2px;
        }
        input[type="submit"], input[type="button"] {
            font-size: 10px;
            padding: 2px 8px;
            cursor: pointer;
        }

        .eflag-message {
            background-color: yellow;
            color: red;
            text-align: center;
            font-weight: bold;
            padding: 4px;
            border: 1px solid darkslategray;
        }

        .dead-notice {
            color: red;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
        }
    </style>
    <script>
        function openHelp(h) {
            window.open('/game/docs/' + h, '_blank', 'width=800,height=500,scrollbars=yes');
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
                <br><span style="font-size:12px;">You might have been killed because of cheating (using multiple accounts).</span>
            @endif
        </div>
    @endif

    {{-- Turn info --}}
    <div class="turn-info" style="text-align: center; margin: 4px 0;">
        ({{ $playerTurns }} months remaining,
        @if($playerTurns >= $maxTurnsStored)
            maximum turns stored)
        @else
            next free month in {{ intdiv($nextTurnSeconds, 60) }} minutes and {{ $nextTurnSeconds % 60 }} seconds)
        @endif
    </div>

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
    <hr style="border: none; border-top: 1px solid darkslategray;">
    <div style="text-align: center; font-size: 11px;">
        Game Time: {{ now()->format('M j, Y h:i A') }}<br>
        &copy; Copyright Ader Software 2000, 2001
    </div>
</div>

</body>
</html>
