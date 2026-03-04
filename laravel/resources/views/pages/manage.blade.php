{{-- Empire Management page - ported from manage.cfm --}}
@extends('layouts.game')

@section('content')
<div class="page-title-bar">
    <h2>Empire Management</h2>
    <a href="javascript:openHelp('manage')" class="help-link">Help</a>
</div>

<x-advisor-panel :tips="$advisorTips" />

<br>

{{-- Weapon Production --}}
<div class="form-panel">
<form action="{{ route('game.manage.weapons') }}" method="POST">
    @csrf
<div class="form-header">Weapon Production</div>
<div class="form-body">
You have {{ $player->weapon_smith }} weaponsmiths and <br>
<input type="text" name="bowProduction" size="4" maxlength="8" value="{{ $player->bow_weapon_smith }}"> of them are producing bows and<br>
<input type="text" name="swordProduction" size="4" maxlength="8" value="{{ $player->sword_weapon_smith }}"> of them are producing swords<br>
<input type="text" name="maceProduction" size="4" maxlength="8" value="{{ $player->mace_weapon_smith }}"> of them are producing maces<br>
and {{ $freeWeaponsmiths }} are idle.
<hr>
Your weaponsmiths are using {{ number_format($woodUsed) }} wood and {{ number_format($ironUsed) }} iron for production every month.
</div>
<div class="form-footer"><input type="submit" value="Change"></div>
</form>
</div>

<br>

{{-- Food Rationing --}}
<div class="form-panel">
<form action="{{ route('game.manage.food-ratio') }}" method="POST">
    @csrf
<div class="form-header">Food Rationing</div>
<div class="form-body">
<input type="radio" name="foodRatio" value="3" @checked($player->food_ratio == 3)>Very High <span class="text-small">(High population growth)</span><br>
<input type="radio" name="foodRatio" value="2" @checked($player->food_ratio == 2)>High<br>
<input type="radio" name="foodRatio" value="1" @checked($player->food_ratio == 1)>Above Average<br>
<input type="radio" name="foodRatio" value="0" @checked($player->food_ratio == 0)>Average<br>
<input type="radio" name="foodRatio" value="-1" @checked($player->food_ratio == -1)>Below Average<br>
<input type="radio" name="foodRatio" value="-2" @checked($player->food_ratio == -2)>Low<br>
<input type="radio" name="foodRatio" value="-3" @checked($player->food_ratio == -3)>Very Low <span class="text-small">(High Population Decline)</span><br>
</div>
<div class="form-footer"><input type="submit" value="Change Food Rationing"></div>
</form>
</div>

<br>

{{-- Land Conversion --}}
<div class="form-panel">
<form action="{{ route('game.manage.land') }}" method="POST">
    @csrf
<div class="form-header">Land</div>
<div class="form-body">
    Change <input type="text" name="mLandChange" size="5" maxlength="10" value="0"> mountain land to forest <br>(100 gold for each land)<br><br>

    Change <input type="text" name="fLandChange" size="5" maxlength="10" value="0"> forest land to plains <br>(25 gold for each land)<br><br>
</div>
<div class="form-footer"><input type="submit" value="Change"></div>
</form>
</div>
@endsection
