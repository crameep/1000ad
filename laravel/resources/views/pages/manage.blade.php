{{-- Empire Management page - ported from manage.cfm --}}
@extends('layouts.game')

@section('content')
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
    <td class="header" align="center" width="92%" style="font-size:16px;"><b>Empire Management</b></td>
    <td class="header" align="center" width="8%"><b><a href="javascript:openHelp('manage')">Help</a></b></td>
</tr>
</table>
<br>

{{-- Weapon Production --}}
<table border="1" cellspacing="1" cellpadding="1" style="border-color:darkslategray;" width="300">
<form action="{{ route('game.manage.weapons') }}" method="POST">
    @csrf
<tr><td align="center" style="background-color:darkslategray;"><span style="color:white;">Weapon Production</span></td></tr>
<tr><td>
You have {{ $player->weapon_smith }} weaponsmiths and <br>
<input type="text" name="bowProduction" size="4" maxlength="8" value="{{ $player->bow_weapon_smith }}"> of them are producing bows and<br>
<input type="text" name="swordProduction" size="4" maxlength="8" value="{{ $player->sword_weapon_smith }}"> of them are producing swords<br>
<input type="text" name="maceProduction" size="4" maxlength="8" value="{{ $player->mace_weapon_smith }}"> of them are producing maces<br>
and {{ $freeWeaponsmiths }} are idle.
<hr noshade size="1" style="border:none; border-top:1px solid darkslategray;">
Your weaponsmiths are using {{ number_format($woodUsed) }} wood and {{ number_format($ironUsed) }} iron for production every month.
</td></tr>
<tr><td style="background-color:darkslategray;" align="center"><input type="submit" value="Change" style="font-size:10px; width:80px;"></td></tr>
</form>
</table>

<br>

{{-- Food Rationing --}}
<table border="1" cellspacing="1" cellpadding="1" style="border-color:darkslategray;" width="300">
<form action="{{ route('game.manage.food-ratio') }}" method="POST">
    @csrf
<tr><td align="center" style="background-color:darkslategray;"><span style="color:white;">Food Rationing</span></td></tr>
<tr><td>
<input type="radio" name="foodRatio" value="3" @checked($player->food_ratio == 3)>Very High <span style="font-size:10px;">(High population growth)</span><br>
<input type="radio" name="foodRatio" value="2" @checked($player->food_ratio == 2)>High<br>
<input type="radio" name="foodRatio" value="1" @checked($player->food_ratio == 1)>Above Average<br>
<input type="radio" name="foodRatio" value="0" @checked($player->food_ratio == 0)>Average<br>
<input type="radio" name="foodRatio" value="-1" @checked($player->food_ratio == -1)>Below Average<br>
<input type="radio" name="foodRatio" value="-2" @checked($player->food_ratio == -2)>Low<br>
<input type="radio" name="foodRatio" value="-3" @checked($player->food_ratio == -3)>Very Low <span style="font-size:10px;">(High Population Decline)</span><br>
</td></tr>
<tr><td align="center" style="background-color:darkslategray;"><input type="submit" value="Change Food Rationing" style="font-size:10px;"></td></tr>
</form>
</table>

<br>

{{-- Land Conversion --}}
<table border="1" cellspacing="1" cellpadding="1" style="border-color:darkslategray;" width="300">
<form action="{{ route('game.manage.land') }}" method="POST">
    @csrf
<tr><td align="center" style="background-color:darkslategray;"><span style="color:white;">Land</span></td></tr>
<tr><td>
    Change <input type="text" name="mLandChange" size="5" maxlength="10" value="0"> mountain land to forest <br>(100 gold for each land)<br><br>

    Change <input type="text" name="fLandChange" size="5" maxlength="10" value="0"> forest land to plains <br>(25 gold for each land)<br><br>
</td></tr>
<tr><td style="background-color:darkslategray;" align="center"><input type="submit" value="Change" style="font-size:10px; width:80px;"></td></tr>
</form>
</table>
@endsection
