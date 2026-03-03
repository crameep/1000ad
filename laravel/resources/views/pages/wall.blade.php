{{-- Great Wall page - ported from wall.cfm --}}
@extends('layouts.game')

@section('content')
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
    <td class="header" align="center" width="92%" style="font-size:16px;"><b>Great Wall</b></td>
    <td class="header" align="center" width="8%"><b><a href="javascript:openHelp('wall')">Help</a></b></td>
</tr>
</table>

<br>
Wall provides extra protection for your empire. <br>
You currently have {{ number_format($player->wall) }} units of wall which provide you with
<span style="font-size:16px;"><b>{{ $protection }}%</b></span> extra protection.<br>

You need {{ number_format($totalWall) }} units of wall to have 100% extra defense.
<br><br>

<table border="1" cellpadding="1" cellspacing="1" style="border-color:darkslategray;">
<tr><td class="header" colspan="2">
    <b>Percentage of builders you want to dedicate to wall construction:</b>
</td></tr>
<form action="{{ route('game.wall.update') }}" method="POST">
    @csrf
<tr>
    <td>
        <input type="text" name="wallBuildPerTurn" value="{{ number_format($player->wall_build_per_turn) }}" size="6">%
        &nbsp;&nbsp;&nbsp;
        {{ $wallBuilders }} out of {{ $builders }} builders will construct {{ $wallBuild }} units of wall every month.<br>
        Wall construction monthly cost:
        {{ number_format($wallBuild * $wallCosts['gold']) }} gold,
        {{ number_format($wallBuild * $wallCosts['wood']) }} wood,
        {{ number_format($wallBuild * $wallCosts['iron']) }} iron,
        {{ number_format($wallBuild * $wallCosts['wine']) }} wine
        <hr noshade size="1" style="border:none; border-top:1px solid darkslategray;">
        Cost to construct 1 unit of wall is: {{ $wallCosts['gold'] }} gold, {{ $wallCosts['wood'] }} wood, {{ $wallCosts['iron'] }} iron and
        {{ $wallCosts['wine'] }} wine
    </td>
</tr>
<tr>
    <td colspan="2" class="header" align="center"><input type="submit" value="Update"></td>
</tr>
</form>
</table>
@endsection
