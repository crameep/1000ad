{{-- Attack page - ported from attack.cfm --}}
@extends('layouts.game')

@section('content')
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
    <td class="header" align="center" width="92%" style="font-size:16px;"><b>Attack</b></td>
    <td class="header" align="center" width="8%"><b><a href="javascript:openHelp('attack')">Help</a></b></td>
</tr>
</table>

@if($underProtection)
    <br>
    <font face="verdana" size="2">
    Cannot attack under protection.
    <br>
    (You are under protection for the first 6 years of game)
    </font>
@else

{{-- Active attacks table --}}
@if($attacks->isEmpty())
    <br>
    <font face="verdana" size="2">Your armies are not attacking anyone.</font><br>
@else
    <br>
    <font face="verdana" size="2">
    The following armies are active:<br>
    </font>
    <table border="1" cellspacing="1" cellpadding="1" bordercolor="darkslategray">
    <tr>
        <td nowrap class="header">Empire Attacked</td>
        <td class="header">Attack Type</td>
        <td class="header">Your Army</td>
        <td class="header">Status</td>
        <td class="header">&nbsp;</td>
    </tr>
    @foreach($attacks as $attack)
    <tr>
        <td valign="top">
            {{ $attack->empire_attacked }} (#{{ $attack->attack_player_id }})
            @if($attack->dscore < $player->score / 2 && !$deathmatchMode)
                <br><font face="verdana" size="1" color="red">Warning!!! attacking<br>empires smaller<br>than 1/2 of your<br>size will result <br>in revolt.</font>
            @endif
        </td>
        <td valign="top">{{ $attack->type_label }}</td>
        <td valign="top"><font face="verdana" size="1">
            @if($attack->attack_type >= 0 && $attack->attack_type < 10)
                @if($attack->uunit > 0){{ number_format($attack->uunit) }} {{ $uniqueUnitName }}<br>@endif
                @if($attack->trained_peasants > 0){{ number_format($attack->trained_peasants) }} Trained Peasants<br>@endif
                @if($attack->macemen > 0){{ number_format($attack->macemen) }} Macemen<br>@endif
                @if($attack->swordsman > 0){{ number_format($attack->swordsman) }} Swordsman<br>@endif
                @if($attack->archers > 0){{ number_format($attack->archers) }} Archers<br>@endif
                @if($attack->horseman > 0){{ number_format($attack->horseman) }} Horseman<br>@endif
                @if($attack->cost_wine > 0){{ number_format($attack->cost_wine) }} units of wine<br>@endif
            @elseif($attack->attack_type >= 10 && $attack->attack_type < 20)
                {{ number_format($attack->catapults) }} catapults
            @elseif($attack->attack_type >= 20 && $attack->attack_type < 30)
                {{ number_format($attack->thieves) }} thieves
            @endif

            @if(!$deathmatchMode)
                <br><b>Attack Strength: {{ number_format(round($attack->attack_power)) }}%</b>
            @endif
        </font></td>
        <td valign="top">{{ $attack->status_label }}</td>
        <td valign="top">
            @if($attack->status == 0 || $attack->status == 1 || $attack->status == 2)
                <form action="{{ route('game.attack.launch') }}" method="POST" style="display:inline;">
                    @csrf
                    <input type="hidden" name="eflag" value="cancel_attack">
                    <input type="hidden" name="armyID" value="{{ $attack->id }}">
                    <a href="#" onclick="this.closest('form').submit(); return false;">Cancel</a>
                </form>
            @else
                &nbsp;
            @endif
        </td>
    </tr>
    <tr><td colspan="10" bgcolor="darkslategray" height="5"></td></tr>
    @endforeach
    </table>
@endif

<br>
<br>

{{-- Army Attack Form --}}
<table border="1" cellspacing="1" cellpadding="1" align="center" bordercolor="darkslategray" width="350">
<tr><td class="header">Army Attack</td></tr>
<form action="{{ route('game.attack.launch') }}" method="POST">
    @csrf
    <input type="hidden" name="eflag" value="attack_empire">
<tr><td>
    <select name="attack_type">
        <option value="0">Conquer (take land)</option>
        <option value="1">Raid (destroy)</option>
        <option value="2">Rob (steal resources)</option>
        <option value="3">Slaughter (kill population)</option>
    </select>
    empire # <input type="text" name="attackPlayerID" value="{{ request('menuPlayerID', 0) }}" maxlength="5" size="5"> with<br>
    <input type="text" name="send_uunit" value="0" maxlength="10" size="8"> {{ $uniqueUnitName }} (You have {{ $player->uunit }})<br>
    <input type="text" name="send_swordsman" value="0" maxlength="10" size="8"> Swordsman (You have {{ $player->swordsman }})<br>
    <input type="text" name="send_archers" value="0" maxlength="10" size="8"> Archers (You have {{ $player->archers }})<br>
    <input type="text" name="send_horseman" value="0" maxlength="10" size="8"> Horseman (You have {{ $player->horseman }})<br>
    <input type="text" name="send_macemen" value="0" maxlength="10" size="8"> Macemen (You have {{ $player->macemen }})<br>
    <input type="text" name="send_trainedPeasants" value="0" maxlength="10" size="8"> Trained Peasants (You have {{ $player->trained_peasants }})<br>
    also send:<br>
    <input type="text" name="sendwine" value="0" maxlength="10" size="8"> wine (you have {{ number_format($player->wine) }})
    <br><input type="checkbox" name="sendmaxwine" value="1"> Send max wine?
</td></tr>
<tr><td class="header"><input type="checkbox" name="sendAll" value="1"> Send All Army</td></tr>
<tr><td align="center"><input type="submit" value="     Attack     "></td></tr>
</form>
</table>

<br>
<br>

{{-- Catapult Attack Form --}}
<table border="1" cellspacing="1" cellpadding="1" align="center" bordercolor="darkslategray" width="350">
<tr><td class="header">Catapult Attack</td></tr>
<form action="{{ route('game.attack.launch') }}" method="POST">
    @csrf
    <input type="hidden" name="eflag" value="catapult_attack">
<tr><td>
    <select name="attack_type">
        <option value="10">Catapult Army and Towers</option>
        <option value="11">Catapult Population</option>
        <option value="12">Catapult Buildings</option>
    </select>
    empire # <input type="text" name="attackPlayerID" value="{{ request('menuPlayerID', 0) }}" maxlength="5" size="5"> with<br>
    <input type="text" name="send_catapults" value="0" maxlength="10" size="8"> Catapults (You have {{ $player->catapults }})<br>
</td></tr>
<tr><td class="header"><input type="checkbox" name="sendAll" value="1"> Send All Army</td></tr>
<tr><td align="center"><input type="submit" value="     Attack     "></td></tr>
</form>
</table>

<br>
<br>

{{-- Thief Attack Form --}}
<table border="1" cellspacing="1" cellpadding="1" align="center" bordercolor="darkslategray" width="350">
<tr><td class="header">Thief Attack</td></tr>
<form action="{{ route('game.attack.launch') }}" method="POST">
    @csrf
    <input type="hidden" name="eflag" value="thief_attack">
<tr><td>
    <select name="attack_type">
        <option value="20">Steal Army Information</option>
        <option value="24">Steal Building Information</option>
        <option value="25">Steal Research Information</option>
        <option value="21">Steal Goods</option>
        <option value="22">Poison Water</option>
        <option value="23">Set Fire</option>
    </select>
    empire # <input type="text" name="attackPlayerID" value="{{ request('menuPlayerID', 0) }}" maxlength="5" size="5"> with<br>
    <input type="text" name="send_thieves" value="0" maxlength="10" size="8"> Thieves (You have {{ $player->thieves }})<br>
</td></tr>
<tr><td class="header"><input type="checkbox" name="sendAll" value="1"> Send All Army</td></tr>
<tr><td align="center"><input type="submit" value="     Attack     "></td></tr>
</form>
</table>

@endif {{-- end of under protection --}}
@endsection
