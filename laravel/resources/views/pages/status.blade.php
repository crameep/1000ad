{{-- Status page - ported from status.cfm --}}
@extends('layouts.game')

@section('content')
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
    <td class="header" align="center" width="100%" style="font-size:16px;"><b>Status</b></td>
</tr>
</table>

<br>

<table border="0" cellspacing="0" cellpadding="0" align="left">
<tr>
    <td width="10">&nbsp;</td>
    <td valign="top">
        {{-- Goods table --}}
        <table border="1" cellspacing="1" cellpadding="1" style="border-color:darkslategray;" width="150">
        <tr>
            <td colspan="3" style="background-color:darkslategray;" align="center"><b>Goods</b></td>
        </tr>
        <tr>
            <td><a href="javascript:openHelp('resources#WOOD')">?</a></td>
            <td>Wood</td>
            <td align="right">{{ number_format($player->wood) }}</td>
        </tr>
        <tr>
            <td><a href="javascript:openHelp('resources#FOOD')">?</a></td>
            <td>Food</td>
            <td align="right">{{ number_format($player->food) }}</td>
        </tr>
        <tr>
            <td><a href="javascript:openHelp('resources#WINE')">?</a></td>
            <td>Wine</td>
            <td align="right">{{ number_format($player->wine) }}</td>
        </tr>
        <tr>
            <td><a href="javascript:openHelp('resources#IRON')">?</a></td>
            <td>Iron</td>
            <td align="right">{{ number_format($player->iron) }}</td>
        </tr>
        <tr>
            <td><a href="javascript:openHelp('resources#TOOLS')">?</a></td>
            <td>Tools</td>
            <td align="right">{{ number_format($player->tools) }}</td>
        </tr>
        <tr>
            <td><a href="javascript:openHelp('resources#SWORD')">?</a></td>
            <td>Swords</td>
            <td align="right">{{ number_format($player->swords) }}</td>
        </tr>
        <tr>
            <td><a href="javascript:openHelp('resources#BOW')">?</a></td>
            <td>Bows</td>
            <td align="right">{{ number_format($player->bows) }}</td>
        </tr>
        <tr>
            <td><a href="javascript:openHelp('resources#MACE')">?</a></td>
            <td>Maces</td>
            <td align="right">{{ number_format($player->maces) }}</td>
        </tr>
        <tr>
            <td><a href="javascript:openHelp('resources#HORSE')">?</a></td>
            <td>Horses</td>
            <td align="right">{{ number_format($player->horses) }}</td>
        </tr>
        <tr>
            <td colspan="2" style="background-color:darkslategray;"><b>Total:</b></td>
            <td align="right" style="background-color:darkslategray;"><b>{{ number_format($totalGoods) }}</b></td>
        </tr>
        <tr>
            <td colspan="2"><span style="font-size:10px;">Warehouse<br>space</span></td>
            <td align="right">{{ number_format($warehouseSpace) }}</td>
        </tr>
        <tr>
            <td colspan="2"><span style="font-size:10px;">@if($extraSpace < 0)Needed<br>space:@else Extra<br>Space:@endif</span></td>
            <td align="right">{{ number_format(abs($extraSpace)) }}</td>
        </tr>
        </table>
        <br>
    </td>
    <td width="10">&nbsp;</td>
    <td valign="top">
        {{-- Army table --}}
        <table border="1" cellspacing="1" cellpadding="1" style="border-color:darkslategray;" width="150">
        <tr>
            <td colspan="2" style="background-color:darkslategray;" align="center"><b>Army</b></td>
        </tr>
        <tr>
            <td>Trained Peasants</td>
            <td align="right">{{ number_format($player->trained_peasants) }}</td>
        </tr>
        <tr>
            <td>Macemen</td>
            <td align="right">{{ number_format($player->macemen) }}</td>
        </tr>
        <tr>
            <td>Swordsman</td>
            <td align="right">{{ number_format($player->swordsman) }}</td>
        </tr>
        <tr>
            <td>Archers</td>
            <td align="right">{{ number_format($player->archers) }}</td>
        </tr>
        <tr>
            <td>Horseman</td>
            <td align="right">{{ number_format($player->horseman) }}</td>
        </tr>
        <tr>
            <td>{{ $uunitName }}</td>
            <td align="right">{{ number_format($player->uunit) }}</td>
        </tr>
        <tr>
            <td>Catapults</td>
            <td align="right">{{ number_format($player->catapults) }}</td>
        </tr>
        <tr>
            <td>Thieves</td>
            <td align="right">{{ number_format($player->thieves) }}</td>
        </tr>
        <tr>
            <td style="background-color:darkslategray;"><b>Total:</b></td>
            <td align="right" style="background-color:darkslategray;"><b>{{ number_format($totalArmy) }}</b></td>
        </tr>
        <tr>
            <td><span style="font-size:10px;">Fort Space</span></td>
            <td align="right">{{ number_format($maxArmy) }}</td>
        </tr>
        <tr>
            <td><span style="font-size:10px;">Free Space</span></td>
            <td align="right">{{ number_format($armyFreeSpace) }}</td>
        </tr>
        </table>
    </td>
    <td width="10">&nbsp;</td>
    <td valign="top">
        {{-- People table --}}
        <table border="1" cellspacing="1" cellpadding="1" style="border-color:darkslategray;" width="150">
        <tr>
            <td colspan="2" style="background-color:darkslategray;" align="center"><b>People</b></td>
        </tr>
        <tr>
            <td>Population</td>
            <td align="right">{{ number_format($player->people) }}</td>
        </tr>
        <tr>
            <td nowrap>House Space:</td>
            <td align="right">{{ number_format($houseSpace) }}</td>
        </tr>
        <tr>
            <td>Free Space:</td>
            <td align="right">{{ number_format($peopleFreeSpace) }}</td>
        </tr>
        </table>
    </td>
</tr>
</table>

<br clear="all">
<br>

{{-- Monthly Summary --}}
Monthly Summary (approximately):
<table border="1" cellspacing="1" cellpadding="1" style="border-color:darkslategray;">
<tr>
    <td class="header">Goods</td>
    <td class="header">Total Gain/Loss</td>
    <td class="header">Production</td>
    <td class="header">Consumption</td>
    <td class="header">Military</td>
    <td class="header">Import/Export</td>
    <td class="header">Other</td>
</tr>

{{-- Gold Row --}}
<tr>
    <td class="small">Gold</td>
    <td align="right" class="small"><b>{{ sprintf('%+d', $totalGold) }}</b></td>
    <td align="right" class="small">{{ sprintf('%+d', $goldProduction) }}</td>
    <td align="right" class="small">{{ sprintf('%+d', $goldConsumption) }}</td>
    <td align="right" class="small">{{ sprintf('%+d', $payGold) }}</td>
    <td align="right" class="small">{{ sprintf('%+d', $buyGold) }}</td>
    <td align="right" class="small">---</td>
</tr>

{{-- Food Row --}}
<tr>
    <td valign="top" class="small">Food <br>(Summer)<br>(Winter)</td>
    <td align="right" class="small"><br><b>{{ sprintf('%+d', $totalFoodSummer) }}</b><br><b>{{ sprintf('%+d', $totalFoodWinter) }}</b></td>
    <td align="right" class="small"><br>{{ sprintf('%+d', $farmerProduction + $hunterProduction) }}<br>{{ sprintf('%+d', $hunterProduction) }}</td>
    <td align="right" class="small"><br>{{ sprintf('%+d', $stableConsumption) }}</td>
    <td align="right" class="small"><br>{{ sprintf('%+d', $eatSoldiersFood) }}</td>
    <td align="right" class="small"><br>{{ sprintf('%+d', $buyFood) }}</td>
    <td align="right" class="small">(people eat)<br>{{ sprintf('%+d', $foodEaten) }}</td>
</tr>

{{-- Wood Row --}}
<tr>
    <td valign="top" class="small">Wood <br>(Summer)<br>(Winter)</td>
    <td align="right" class="small"><br><b>{{ sprintf('%+d', $totalWoodSummer) }}</b><br><b>{{ sprintf('%+d', $totalWoodWinter) }}</b></td>
    <td align="right" class="small"><br>{{ sprintf('%+d', $woodProduction) }}</td>
    <td align="right" class="small"><br>{{ sprintf('%+d', $toolWoodConsumption + $bowWoodConsumption + $maceWoodConsumption) }}</td>
    <td align="right" class="small"><br>{{ sprintf('%+d', $catapultWood) }}</td>
    <td align="right" class="small"><br>{{ sprintf('%+d', $buyWood) }}</td>
    <td align="right" class="small"><br>(heating)<br>{{ sprintf('%+d', $burnWood) }}</td>
</tr>

{{-- Iron Row --}}
<tr>
    <td valign="top" class="small">Iron</td>
    <td align="right" class="small"><b>{{ sprintf('%+d', $totalIron) }}</b></td>
    <td align="right" class="small">{{ sprintf('%+d', $ironProduction) }}</td>
    <td align="right" class="small">{{ sprintf('%+d', $toolIronConsumption + $swordIronConsumption + $maceIronConsumption + $wallIronConsumption) }}</td>
    <td align="right" class="small">{{ sprintf('%+d', $catapultIron) }}</td>
    <td align="right" class="small">{{ sprintf('%+d', $buyIron) }}</td>
    <td align="right" class="small">---</td>
</tr>
</table>
@endsection
