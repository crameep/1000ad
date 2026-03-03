{{-- Great Wall page - ported from wall.cfm --}}
@extends('layouts.game')

@section('content')
<div class="page-title-bar">
    <h2>Great Wall</h2>
    <a href="javascript:openHelp('wall')" class="help-link">Help</a>
</div>

<br>
Wall provides extra protection for your empire. <br>
You currently have {{ number_format($player->wall) }} units of wall which provide you with
<span style="font-size:16px;"><b>{{ $protection }}%</b></span> extra protection.<br>

You need {{ number_format($totalWall) }} units of wall to have 100% extra defense.
<br><br>

<div class="form-panel">
<div class="form-header">Percentage of builders you want to dedicate to wall construction:</div>
<div class="form-body">
<form action="{{ route('game.wall.update') }}" method="POST">
    @csrf
    <input type="text" name="wallBuildPerTurn" value="{{ number_format($player->wall_build_per_turn) }}" size="6">%
    &nbsp;&nbsp;&nbsp;
    {{ $wallBuilders }} out of {{ $builders }} builders will construct {{ $wallBuild }} units of wall every month.<br>
    Wall construction monthly cost:
    {{ number_format($wallBuild * $wallCosts['gold']) }} gold,
    {{ number_format($wallBuild * $wallCosts['wood']) }} wood,
    {{ number_format($wallBuild * $wallCosts['iron']) }} iron,
    {{ number_format($wallBuild * $wallCosts['wine']) }} wine
    <hr>
    Cost to construct 1 unit of wall is: {{ $wallCosts['gold'] }} gold, {{ $wallCosts['wood'] }} wood, {{ $wallCosts['iron'] }} iron and
    {{ $wallCosts['wine'] }} wine
</div>
<div class="form-footer">
    <input type="submit" value="Update">
</div>
</form>
</div>
@endsection
