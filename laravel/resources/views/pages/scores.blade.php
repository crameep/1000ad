{{-- Scores page - modernized card-based UI --}}
@extends('layouts.game')

@section('content')
<div class="page-title-bar">
    <h2>Scores</h2>
</div>

{{-- Summary Bar --}}
<div class="scores-summary">
    <div class="scores-summary-left">
        <span><b>{{ $players->count() }}</b> players</span>
        <span class="scores-summary-sep">&middot;</span>
        <span><b class="text-success">{{ $onlineCount }}</b> online</span>
    </div>
    <div class="scores-summary-right">
        <a href="{{ route('game.recent-battles') }}">Recent Battles</a>
    </div>
</div>

{{-- Legend --}}
<div class="scores-legend">
    <span class="legend-item"><span class="legend-dot" style="background: var(--color-score-self)"></span>Your Empire</span>
    @if(!$deathmatchMode)
    <span class="legend-item"><span class="legend-dot" style="background: var(--color-score-protected)"></span>Protected</span>
    <span class="legend-item"><span class="legend-dot" style="background: var(--color-score-alliance)"></span>Alliance</span>
    <span class="legend-item"><span class="legend-dot" style="background: var(--color-score-ally)"></span>Ally</span>
    <span class="legend-item"><span class="legend-dot" style="background: var(--color-score-enemy)"></span>Enemy</span>
    <span class="legend-item">[Tag] = leader</span>
    @endif
    <span class="legend-item">R/L = research</span>
    <span class="legend-item"><span class="online-dot"></span>= online</span>
</div>

{{-- Rankings Panel --}}
<div class="panel">
    <div class="panel-header">Rankings</div>
    <div class="panel-body" style="padding: 0;">
        <div class="table-scroll">
        <table class="scores-table">
            <thead>
                <tr>
                    <th class="text-center">#</th>
                    <th>Player</th>
                    <th class="hide-mobile">Civilization</th>
                    @if(!$deathmatchMode && $allianceMaxMembers > 0)
                    <th class="text-center hide-mobile">Alliance</th>
                    @endif
                    <th class="text-right hide-mobile">R/L</th>
                    <th class="text-right">Land</th>
                    <th class="text-right">Score</th>
                </tr>
            </thead>
            <tbody>
            @php
                $startMax = $isAdmin ? $players->count() : 10;
            @endphp

            @foreach($players->take($startMax) as $idx => $p)
                @include('partials.score-row', ['p' => $p, 'rowNum' => $idx + 1])
            @endforeach
            </tbody>

            @if(!$isAdmin)
            <tbody>
                <tr class="scores-divider"><td colspan="7"></td></tr>

                @php
                    if ($rank <= 10) {
                        $start = 10;
                        $max = $rank + 20 - 10;
                    } elseif ($rank <= 20) {
                        $start = 10;
                        $max = $rank - 10 + 20;
                    } else {
                        $start = $rank - 21;
                        $max = 40;
                        if ($start < 10) { $start = 10; }
                    }
                @endphp

                @foreach($players->slice($start)->take($max) as $idx => $p)
                    @include('partials.score-row', ['p' => $p, 'rowNum' => $start + $idx + 1])
                @endforeach
            </tbody>
            @endif
        </table>
        </div>
    </div>
</div>

{{-- Context Menu --}}
<div id="pMenu">
    <div class="context-menu-title" id="menuName"></div>
    <div class="menuItem" onclick="menuEflag('messages')">Send Message</div>
    <div class="menuItem" onclick="menuEflag('aid')">Send Aid</div>
    <div class="menuItem" onclick="menuEflag('attack', 'attack_type=0')">Conquer Attack</div>
    <div class="menuItem" onclick="menuEflag('attack', 'attack_type=10')">Catapult Attack</div>
    <div class="menuItem" onclick="menuEflag('attack', 'attack_type=20')">Steal Information</div>
    <div class="menuItem" onclick="menuEflag('attack', 'attack_type=23')">Steal Goods</div>
    <div class="menuItem" onclick="menuEflag('attack', 'attack_type=24')">Poison Water</div>
    <hr class="context-menu-hr">
    <div class="menuItem" onclick="menuClose()">Close Menu</div>
</div>

<script>
var curPID = 0;

function showMenu(pid, pname, event) {
    var menu = document.getElementById('pMenu');
    if (menu.style.display === 'block') menuClose();

    curPID = pid;
    document.getElementById('menuName').innerText = 'Action for ' + pname + ' (' + pid + ')';
    menu.style.left = (event.pageX - 5) + 'px';
    menu.style.top = (event.pageY + 15) + 'px';
    menu.style.display = 'block';
    event.stopPropagation();
}

function menuClose() {
    curPID = 0;
    document.getElementById('pMenu').style.display = 'none';
}

function menuEflag(page, extra) {
    var u = '{{ url("/game") }}/' + page + '?menuPlayerID=' + curPID;
    if (extra) u += '&' + extra;
    window.location.href = u;
}

document.addEventListener('click', function() { menuClose(); });
</script>
@endsection
