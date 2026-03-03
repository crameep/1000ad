{{-- Scores page - ported from scores.cfm, scores_show.cfm --}}
@extends('layouts.game')

@section('content')
<div class="page-title-bar">
    <h2>Scores</h2>
</div>

<div class="scores-info">
    <div class="scores-text">
        There are <b>{{ $players->count() }}</b> players in the {{ config('game.name') }}.<br>
        {{ $onlineCount }} {{ $onlineCount === 1 ? 'is' : 'are' }} online now.<br>
        <br>
        <span class="text-lg">
        <a href="{{ route('game.recent-battles') }}">Recent Battles</a><br>
        @if(!$deathmatchMode)
        {{-- Alliance scores link could go here --}}
        @endif
        </span><br>
    </div>
    <div class="scores-legend">
        <table class="game-table">
        <tr><td class="bg-header" align="center"><span class="text-sm">Legend:</span></td></tr>
        <tr><td>
        <span class="text-sm" style="color:Aqua;">Your Empire</span><br>
        @if(!$deathmatchMode)
        <span class="text-sm" style="color:Yellow;">Under Protection</span><br>
        <span class="text-sm" style="color:Fuchsia;">Your Alliance Member</span><br>
        <span class="text-sm" style="color:PeachPuff;">Ally</span><br>
        <span class="text-sm" style="color:Crimson;">Enemy</span><br>
        <span class="text-sm">[Alliance] - alliance leader</span><br>
        @endif
        <span class="text-sm">R/L - total research levels</span><br>
        <span class="text-sm">* - is online</span><br>
        </td></tr>
        </table>
    </div>
</div>

<br>

<div class="table-scroll">
<table class="game-table w-full">
<tr>
    <td class="bg-header" align="center">&nbsp;</td>
    <td class="bg-header" align="center">Player</td>
    <td class="bg-header" align="center">Civilization</td>
    @if(!$deathmatchMode && $allianceMaxMembers > 0)
    <td class="bg-header" align="center">Alliance</td>
    @endif
    <td class="bg-header" align="center">R/L</td>
    <td class="bg-header" align="center">Land</td>
    <td class="bg-header" align="center">Score</td>
</tr>

@php
    // Top 10 or all (for admin)
    $startMax = $isAdmin ? $players->count() : 10;
@endphp

{{-- Top 10 rows --}}
@foreach($players->take($startMax) as $idx => $p)
    @include('partials.score-row', ['p' => $p, 'rowNum' => $idx + 1])
@endforeach

@if(!$isAdmin)
<tr><td colspan="9" height="20">&nbsp;</td></tr>
<tr><td colspan="9" class="bg-header" height="10"></td></tr>

{{-- Show 20 players around current player's rank --}}
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
@endif

</table>
</div>

{{-- Right-click context menu --}}
<div style="display:none; position:absolute; border:2px outset;" id="pMenu">
<table class="context-menu">
<tr><td class="context-menu-title" id="menuName"></td></tr>
<tr><td class="menuItem" onclick="menuEflag('messages')">Send Message</td></tr>
<tr><td class="menuItem" onclick="menuEflag('aid')">Send Aid</td></tr>
<tr><td class="menuItem" onclick="menuEflag('attack', 'attack_type=0')">Conquer Attack</td></tr>
<tr><td class="menuItem" onclick="menuEflag('attack', 'attack_type=10')">Catapult Attack</td></tr>
<tr><td class="menuItem" onclick="menuEflag('attack', 'attack_type=20')">Steal Information</td></tr>
<tr><td class="menuItem" onclick="menuEflag('attack', 'attack_type=23')">Steal Goods</td></tr>
<tr><td class="menuItem" onclick="menuEflag('attack', 'attack_type=24')">Poison Water</td></tr>
<tr><td><hr></td></tr>
<tr><td class="menuItem" onclick="menuClose()">Close Menu</td></tr>
</table>
</div>

<script>
var curPID = 0;

function showMenu(pid, pname, event) {
    var menu = document.getElementById('pMenu');
    if (menu.style.display === '') menuClose();

    curPID = pid;
    document.getElementById('menuName').innerText = 'Action for ' + pname + ' (' + pid + ')';
    menu.style.left = (event.pageX - 5) + 'px';
    menu.style.top = (event.pageY + 15) + 'px';
    menu.style.display = '';
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
