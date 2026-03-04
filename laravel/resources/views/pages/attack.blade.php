{{-- Attack page - ported from attack.cfm --}}
@extends('layouts.game')

@section('content')
<div class="page-title-bar">
    <h2>Attack</h2>
    <a href="javascript:openHelp('attack')" class="help-link">Help</a>
</div>

<x-advisor-panel :tips="$advisorTips" />

@if($underProtection)
    <br>
    <span>
    Cannot attack under protection.
    <br>
    (You are under protection for the first 6 years of game)
    </span>
@else

{{-- Active attacks table --}}
@if($attacks->isEmpty())
    <br>
    <span>Your armies are not attacking anyone.</span><br>
@else
    <br>
    <span>
    The following armies are active:<br>
    </span>
    <div class="table-scroll">
    <table class="game-table">
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
                <br><span class="text-small text-error">Warning!!! attacking<br>empires smaller<br>than 1/2 of your<br>size will result <br>in revolt.</span>
            @endif
        </td>
        <td valign="top">{{ $attack->type_label }}</td>
        <td valign="top"><span class="text-small">
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
        </span></td>
        <td valign="top">{{ $attack->status_label }}</td>
        <td valign="top">
            @if($attack->status == 0 || $attack->status == 1 || $attack->status == 2)
                <form action="{{ route('game.attack.launch') }}" method="POST" class="inline-form">
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
    </div>
@endif

<br>

{{-- Army Attack Form --}}
<div class="form-panel">
    <div class="form-header">Army Attack</div>
    <div class="form-body">
        <form action="{{ route('game.attack.launch') }}" method="POST">
            @csrf
            <input type="hidden" name="eflag" value="attack_empire">
            <select name="attack_type">
                <option value="0">Conquer (take land)</option>
                <option value="1">Raid (destroy)</option>
                <option value="2">Rob (steal resources)</option>
                <option value="3">Slaughter (kill population)</option>
            </select>
            empire # <input type="text" name="attackPlayerID" value="{{ request('menuPlayerID', 0) }}" maxlength="5" size="5"> with
            <div class="unit-row">
                <input type="text" name="send_uunit" value="0" maxlength="10" size="8">
                <x-game-icon :src="soldierIcon($soldiers[9], 9, $player->civ)" :alt="$uniqueUnitName" :size="40" />
                <div class="unit-info">
                    <div class="unit-name">{{ $uniqueUnitName }}</div>
                    <div class="unit-count">You have {{ number_format($player->uunit) }}</div>
                </div>
            </div>
            <div class="unit-row">
                <input type="text" name="send_swordsman" value="0" maxlength="10" size="8">
                <x-game-icon :src="soldierIcon($soldiers[2], 2)" :alt="'Swordsman'" :size="40" />
                <div class="unit-info">
                    <div class="unit-name">Swordsman</div>
                    <div class="unit-count">You have {{ number_format($player->swordsman) }}</div>
                </div>
            </div>
            <div class="unit-row">
                <input type="text" name="send_archers" value="0" maxlength="10" size="8">
                <x-game-icon :src="soldierIcon($soldiers[1], 1)" :alt="'Archers'" :size="40" />
                <div class="unit-info">
                    <div class="unit-name">Archers</div>
                    <div class="unit-count">You have {{ number_format($player->archers) }}</div>
                </div>
            </div>
            <div class="unit-row">
                <input type="text" name="send_horseman" value="0" maxlength="10" size="8">
                <x-game-icon :src="soldierIcon($soldiers[3], 3)" :alt="'Horseman'" :size="40" />
                <div class="unit-info">
                    <div class="unit-name">Horseman</div>
                    <div class="unit-count">You have {{ number_format($player->horseman) }}</div>
                </div>
            </div>
            <div class="unit-row">
                <input type="text" name="send_macemen" value="0" maxlength="10" size="8">
                <x-game-icon :src="soldierIcon($soldiers[6], 6)" :alt="'Macemen'" :size="40" />
                <div class="unit-info">
                    <div class="unit-name">Macemen</div>
                    <div class="unit-count">You have {{ number_format($player->macemen) }}</div>
                </div>
            </div>
            <div class="unit-row">
                <input type="text" name="send_trainedPeasants" value="0" maxlength="10" size="8">
                <x-game-icon :src="soldierIcon($soldiers[7], 7)" :alt="'Trained Peasants'" :size="40" />
                <div class="unit-info">
                    <div class="unit-name">Trained Peasants</div>
                    <div class="unit-count">You have {{ number_format($player->trained_peasants) }}</div>
                </div>
            </div>
            also send:<br>
            <input type="text" name="sendwine" value="0" maxlength="10" size="8"> wine (you have {{ number_format($player->wine) }})
            <br><input type="checkbox" name="sendmaxwine" value="1"> Send max wine?
            <div><input type="checkbox" name="sendAll" value="1"> Send All Army</div>
            <div class="form-footer"><input type="submit" value="Attack"></div>
        </form>
    </div>
</div>

<br>

{{-- Catapult Attack Form --}}
<div class="form-panel">
    <div class="form-header">Catapult Attack</div>
    <div class="form-body">
        <form action="{{ route('game.attack.launch') }}" method="POST">
            @csrf
            <input type="hidden" name="eflag" value="catapult_attack">
            <select name="attack_type">
                <option value="10">Catapult Army and Towers</option>
                <option value="11">Catapult Population</option>
                <option value="12">Catapult Buildings</option>
            </select>
            empire # <input type="text" name="attackPlayerID" value="{{ request('menuPlayerID', 0) }}" maxlength="5" size="5"> with
            <div class="unit-row">
                <input type="text" name="send_catapults" value="0" maxlength="10" size="8">
                <x-game-icon :src="soldierIcon($soldiers[5], 5)" :alt="'Catapults'" :size="40" />
                <div class="unit-info">
                    <div class="unit-name">Catapults</div>
                    <div class="unit-count">You have {{ number_format($player->catapults) }}</div>
                </div>
            </div>
            <div><input type="checkbox" name="sendAll" value="1"> Send All Army</div>
            <div class="form-footer"><input type="submit" value="Attack"></div>
        </form>
    </div>
</div>

<br>

{{-- Thief Attack Form --}}
<div class="form-panel">
    <div class="form-header">Thief Attack</div>
    <div class="form-body">
        <form action="{{ route('game.attack.launch') }}" method="POST">
            @csrf
            <input type="hidden" name="eflag" value="thief_attack">
            <select name="attack_type">
                <option value="20">Steal Army Information</option>
                <option value="24">Steal Building Information</option>
                <option value="25">Steal Research Information</option>
                <option value="21">Steal Goods</option>
                <option value="22">Poison Water</option>
                <option value="23">Set Fire</option>
            </select>
            empire # <input type="text" name="attackPlayerID" value="{{ request('menuPlayerID', 0) }}" maxlength="5" size="5"> with
            <div class="unit-row">
                <input type="text" name="send_thieves" value="0" maxlength="10" size="8">
                <x-game-icon :src="soldierIcon($soldiers[8], 8)" :alt="'Thieves'" :size="40" />
                <div class="unit-info">
                    <div class="unit-name">Thieves</div>
                    <div class="unit-count">You have {{ number_format($player->thieves) }}</div>
                </div>
            </div>
            <div><input type="checkbox" name="sendAll" value="1"> Send All Army</div>
            <div class="form-footer"><input type="submit" value="Attack"></div>
        </form>
    </div>
</div>

@endif {{-- end of under protection --}}
@endsection
