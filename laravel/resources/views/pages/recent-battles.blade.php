{{-- Recent Battles page - ported from recent_battles.cfm --}}
@extends('layouts.game')

@section('content')
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
    <td class="header" align="center" width="92%" style="font-size:16px;"><b>Recent Battles</b></td>
    <td class="header" align="center" width="8%"><b></b></td>
</tr>
</table>

{{-- View Detail --}}
@if($pageFlag === 'viewDetail' && $battleDetail)
    <br>
    <table border="1" cellspacing="1" cellpadding="1" style="border-color:darkslategray;">
    <tr>
        <td style="background-color:darkslategray;"><span style="color:white;">Date/Time</span></td>
        <td style="background-color:darkslategray;"><span style="color:white;">Attacker</span></td>
        <td style="background-color:darkslategray;"><span style="color:white;">Defender</span></td>
        <td style="background-color:darkslategray;"><span style="color:white;">Result</span></td>
    </tr>
    <tr>
        <td valign="top"><span style="font-size:10px;">
            {{ $battleDetail->created_on->format('m/d/y') }}<br>{{ $battleDetail->created_on->format('h:i A') }}
        </span></td>
        <td valign="top">
            {{ $battleDetail->attacker_name }} ({{ $battleDetail->attack_id }})
            @if($battleDetail->attack_alliance)
                <br><span style="font-size:10px;"><b>{{ $battleDetail->attack_alliance }}</b></span>
            @endif
        </td>
        <td valign="top">
            {{ $battleDetail->defender_name }} ({{ $battleDetail->defense_id }})
            @if($battleDetail->defense_alliance)
                <br><span style="font-size:10px;"><b>{{ $battleDetail->defense_alliance }}</b></span>
            @endif
        </td>
        <td valign="top">
            @if($battleDetail->attacker_wins)
                {{ $battleDetail->message }}
            @else
                Defense Held
            @endif
        </td>
    </tr>
    @if($battleDetail->show_detail)
    <tr><td colspan="10">{!! $battleDetail->battle_details !!}</td></tr>
    @endif
    </table>
    <br>
@endif

{{-- Battle Results --}}
@if($pageFlag === 'view_battles')
    @if($battles->isEmpty())
        <span style="color:red;">No Battles found.</span><br>
    @else
        <table border="1" cellspacing="1" cellpadding="1" style="border-color:darkslategray;">
        <tr>
            <td style="background-color:darkslategray;"><span style="color:white;">&nbsp;</span></td>
            <td style="background-color:darkslategray;"><span style="color:white;">Date/Time</span></td>
            <td style="background-color:darkslategray;"><span style="color:white;">Type</span></td>
            <td style="background-color:darkslategray;"><span style="color:white;">Attacker</span></td>
            <td style="background-color:darkslategray;"><span style="color:white;">Defender</span></td>
            <td style="background-color:darkslategray;"><span style="color:white;">Result</span></td>
        </tr>
        @foreach($battles as $idx => $v)
        @php
            $showDetail = false;
            if ($v->attack_id === $player->id || $v->defense_id === $player->id) {
                $showDetail = true;
            } elseif ($player->alliance_id > 0 && $player->alliance_member_type == 1) {
                if ($v->attack_alliance_id === $player->alliance_id || $v->defense_alliance_id === $player->alliance_id) {
                    $showDetail = true;
                }
            }
        @endphp
        <tr>
            <td valign="top" align="right"><span style="font-size:10px;">{{ $idx + 1 }}.</span></td>
            <td valign="top"><span style="font-size:10px;">
                @if($showDetail)
                    <a href="{{ route('game.recent-battles.detail', $v->id) }}">
                @endif
                {{ $v->created_on->format('m/d/y') }}<br>{{ $v->created_on->format('h:i A') }}
                @if($showDetail)</a>@endif
            </span></td>
            <td valign="top">
                @if($v->attack_type >= 0 && $v->attack_type <= 9)Army
                @elseif($v->attack_type >= 10 && $v->attack_type <= 19)Catapults
                @elseif($v->attack_type >= 20 && $v->attack_type <= 29)Thieves
                @endif
            </td>
            <td valign="top">
                {{ $v->attacker_name }} ({{ $v->attack_id }})
                @if($v->attack_alliance)
                    <br><span style="font-size:10px;"><b>{{ $v->attack_alliance }}</b></span>
                @endif
            </td>
            <td valign="top">
                {{ $v->defender_name }} ({{ $v->defense_id }})
                @if($v->defense_alliance)
                    <br><span style="font-size:10px;"><b>{{ $v->defense_alliance }}</b></span>
                @endif
            </td>
            <td valign="top">
                @if($v->attacker_wins)
                    {{ $v->message }}
                @else
                    Defense Held
                @endif
            </td>
        </tr>
        @if(($idx + 1) % 10 === 0)
        <tr><td colspan="6" style="background-color:darkslategray;" height="5"></td></tr>
        @endif
        @endforeach
        </table>
    @endif
@endif

{{-- Search Form --}}
<br>
<form action="{{ route('game.recent-battles') }}" method="GET">
<input type="hidden" name="pageFlag" value="view_battles">
View battles fought within last
<input type="text" name="numHours" value="24" size="3"> hours<br>
attack type
<select name="attackType">
    <option value="0">Any</option>
    <option value="1">Army</option>
    <option value="2">Catapults</option>
    <option value="3">Thieves</option>
</select><br>
and where
<select name="defenderOrAttacker">
    <option value="0">Defender</option>
    <option value="1">Attacker</option>
    <option value="2" selected>Defender or Attacker</option>
</select><br>
<input type="radio" name="searchType" value="empireNo" checked>was empire # <input type="text" name="viewPlayer" value="{{ $player->id }}" size="4"><br>
<input type="radio" name="searchType" value="alliance">alliance
<select name="allianceName">
    <option value="">--- Select One ---</option>
    <option value="___ANY___">--- All Alliances ---</option>
    @foreach($alliances as $a)
        <option value="{{ $a->tag }}" @selected($a->id === $player->alliance_id)>{{ $a->tag }}</option>
    @endforeach
</select>
<br>
<input type="submit" value="View">
</form>
@endsection
