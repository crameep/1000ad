{{-- Scores page - ported from scores.cfm, scores_show.cfm --}}
@extends('layouts.game')

@section('content')
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
    <td class="header" align="center" width="100%" style="font-size:16px;"><b>Scores</b></td>
</tr>
</table>

<table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr>
<td width="15"></td>
<td valign="top">
    There are <b>{{ $players->count() }}</b> players in the {{ config('game.name') }}.<br>
    {{ $onlineCount }} {{ $onlineCount === 1 ? 'is' : 'are' }} online now.<br>
    <br>
    <span style="font-size:16px;">
    <a href="{{ route('game.recent-battles') }}">Recent Battles</a><br>
    @if(!$deathmatchMode)
    {{-- Alliance scores link could go here --}}
    @endif
    </span><br>
</td>
<td width="25"></td>
<td valign="top" align="right">
    <table border="1" cellspacing="1" cellpadding="1" style="border-color:darkslategray;">
    <tr><td class="header" align="center"><span style="font-size:10px;">Legend:</span></td></tr>
    <tr><td>
    <span style="font-size:10px; color:Aqua;">Your Empire</span><br>
    @if(!$deathmatchMode)
    <span style="font-size:10px; color:Yellow;">Under Protection</span><br>
    <span style="font-size:10px; color:Fuchsia;">Your Alliance Member</span><br>
    <span style="font-size:10px; color:PeachPuff;">Ally</span><br>
    <span style="font-size:10px; color:Crimson;">Enemy</span><br>
    <span style="font-size:10px;">[Alliance] - alliance leader</span><br>
    @endif
    <span style="font-size:10px;">R/L - total research levels</span><br>
    <span style="font-size:10px;">* - is online</span><br>
    </td></tr>
    </table>
</td>
<td width="15"></td>
</tr>
</table>

<br>

<table border="1" cellspacing="0" cellpadding="2" style="border-color:darkslategray;" width="100%">
<tr>
    <td style="background-color:darkslategray;" align="center"><span style="color:white;">&nbsp;</span></td>
    <td style="background-color:darkslategray;" align="center"><span style="color:white;">Player</span></td>
    <td style="background-color:darkslategray;" align="center"><span style="color:white;">Civilization</span></td>
    @if(!$deathmatchMode && $allianceMaxMembers > 0)
    <td style="background-color:darkslategray;" align="center"><span style="color:white;">Alliance</span></td>
    @endif
    <td style="background-color:darkslategray;" align="center"><span style="color:white;">R/L</span></td>
    <td style="background-color:darkslategray;" align="center"><span style="color:white;">Land</span></td>
    <td style="background-color:darkslategray;" align="center"><span style="color:white;">Score</span></td>
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
<tr><td colspan="9" style="background-color:darkslategray;" height="10"></td></tr>

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

{{-- Right-click context menu --}}
<style>
    .menuItem {
        font-family: verdana;
        color: black;
        font-size: 10px;
        cursor: pointer;
    }
    .menuItem:hover {
        background-color: blue;
        color: white;
    }
</style>

<div style="display:none; position:absolute; border:2px outset;" id="pMenu">
<table border="0" cellpadding="1" cellspacing="1" style="background-color:silver;">
<tr><td style="font-family:verdana; font-size:11px; font-weight:bold; color:black;" id="menuName"></td></tr>
<tr><td class="menuItem" onclick="menuEflag('messages')">Send Message</td></tr>
<tr><td class="menuItem" onclick="menuEflag('aid')">Send Aid</td></tr>
<tr><td class="menuItem" onclick="menuEflag('attack', 'attack_type=0')">Conquer Attack</td></tr>
<tr><td class="menuItem" onclick="menuEflag('attack', 'attack_type=10')">Catapult Attack</td></tr>
<tr><td class="menuItem" onclick="menuEflag('attack', 'attack_type=20')">Steal Information</td></tr>
<tr><td class="menuItem" onclick="menuEflag('attack', 'attack_type=23')">Steal Goods</td></tr>
<tr><td class="menuItem" onclick="menuEflag('attack', 'attack_type=24')">Poison Water</td></tr>
<tr><td><hr noshade size="1"></td></tr>
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
