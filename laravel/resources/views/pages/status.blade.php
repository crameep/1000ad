{{-- Status page - modernized card-based UI --}}
@extends('layouts.game')

@section('content')
<div class="page-title-bar">
    <h2>Status</h2>
</div>

<div class="status-grid">
    {{-- Goods Panel --}}
    <div class="panel status-panel">
        <div class="panel-header">Goods</div>
        <div class="panel-body">
            <div class="status-capacity">
                <div class="progress-ind" title="{{ number_format($totalGoods) }} / {{ number_format($warehouseSpace) }} stored">
                    <span class="progress-ind-label">Storage</span>
                    <div class="progress-ind-track">
                        <div class="progress-ind-fill progress-ind-fill--warehouse" style="width: {{ min(100, round($totalGoods / max(1, $warehouseSpace) * 100)) }}%"></div>
                    </div>
                    <span class="progress-ind-pct">{{ min(100, round($totalGoods / max(1, $warehouseSpace) * 100)) }}%</span>
                </div>
            </div>

            @php
                $goods = [
                    ['key' => 'wood',   'name' => 'Wood',   'qty' => $player->wood],
                    ['key' => 'food',   'name' => 'Food',   'qty' => $player->food],
                    ['key' => 'wine',   'name' => 'Wine',   'qty' => $player->wine],
                    ['key' => 'iron',   'name' => 'Iron',   'qty' => $player->iron],
                    ['key' => 'tools',  'name' => 'Tools',  'qty' => $player->tools],
                    ['key' => 'swords', 'name' => 'Swords', 'qty' => $player->swords],
                    ['key' => 'bows',   'name' => 'Bows',   'qty' => $player->bows],
                    ['key' => 'maces',  'name' => 'Maces',  'qty' => $player->maces],
                    ['key' => 'horses', 'name' => 'Horses', 'qty' => $player->horses],
                ];
            @endphp
            @foreach($goods as $g)
            <div class="status-row">
                <img class="status-row-icon" src="{{ resourceIcon($g['key']) }}" alt="{{ $g['name'] }}">
                <span class="status-row-name">{{ $g['name'] }}</span>
                <span class="status-row-value">{{ number_format($g['qty']) }}</span>
            </div>
            @endforeach

            <div class="status-row status-row--total">
                <span class="status-row-name">Total</span>
                <span class="status-row-value">{{ number_format($totalGoods) }}</span>
            </div>
            <div class="status-row status-row--footer">
                <span class="status-row-name">{{ $extraSpace < 0 ? 'Over capacity' : 'Free space' }}</span>
                <span class="status-row-value {{ $extraSpace < 0 ? 'text-error' : 'text-success' }}">{{ number_format(abs($extraSpace)) }}</span>
            </div>
        </div>
    </div>

    {{-- Army Panel --}}
    <div class="panel status-panel">
        <div class="panel-header">Army</div>
        <div class="panel-body">
            <div class="status-capacity">
                <div class="progress-ind" title="{{ number_format($totalArmy) }} / {{ number_format($maxArmy) }} units">
                    <span class="progress-ind-label">Fort</span>
                    <div class="progress-ind-track">
                        <div class="progress-ind-fill progress-ind-fill--army" style="width: {{ min(100, round($totalArmy / max(1, $maxArmy) * 100)) }}%"></div>
                    </div>
                    <span class="progress-ind-pct">{{ min(100, round($totalArmy / max(1, $maxArmy) * 100)) }}%</span>
                </div>
            </div>

            @php
                $armyRows = [
                    ['idx' => 7, 'name' => 'Trained Peasants', 'qty' => $player->trained_peasants],
                    ['idx' => 6, 'name' => 'Macemen',          'qty' => $player->macemen],
                    ['idx' => 2, 'name' => 'Swordsmen',        'qty' => $player->swordsman],
                    ['idx' => 1, 'name' => 'Archers',          'qty' => $player->archers],
                    ['idx' => 3, 'name' => 'Horsemen',         'qty' => $player->horseman],
                    ['idx' => 9, 'name' => $uunitName,         'qty' => $player->uunit],
                    ['idx' => 5, 'name' => 'Catapults',        'qty' => $player->catapults],
                    ['idx' => 8, 'name' => 'Thieves',          'qty' => $player->thieves],
                ];
            @endphp
            @foreach($armyRows as $a)
            <div class="status-row">
                <img class="status-row-icon" src="{{ soldierIcon($soldiers[$a['idx']], $a['idx'], $player->civ) }}" alt="{{ $a['name'] }}" onerror="this.style.display='none'">
                <span class="status-row-name">{{ $a['name'] }}</span>
                <span class="status-row-value">{{ number_format($a['qty']) }}</span>
            </div>
            @endforeach

            <div class="status-row status-row--total">
                <span class="status-row-name">Total</span>
                <span class="status-row-value">{{ number_format($totalArmy) }}</span>
            </div>
            <div class="status-row status-row--footer">
                <span class="status-row-name">Free space</span>
                <span class="status-row-value {{ $armyFreeSpace < 0 ? 'text-error' : 'text-success' }}">{{ number_format($armyFreeSpace) }}</span>
            </div>
        </div>
    </div>

    {{-- People Panel --}}
    <div class="panel status-panel">
        <div class="panel-header">People</div>
        <div class="panel-body">
            <div class="status-capacity">
                <div class="progress-ind" title="{{ number_format($player->people) }} / {{ number_format($houseSpace) }} housed">
                    <span class="progress-ind-label">Housing</span>
                    <div class="progress-ind-track">
                        <div class="progress-ind-fill progress-ind-fill--housing" style="width: {{ min(100, round($player->people / max(1, $houseSpace) * 100)) }}%"></div>
                    </div>
                    <span class="progress-ind-pct">{{ min(100, round($player->people / max(1, $houseSpace) * 100)) }}%</span>
                </div>
            </div>

            <div class="status-row">
                <span class="status-row-icon-placeholder">&#x1F464;</span>
                <span class="status-row-name">Population</span>
                <span class="status-row-value">{{ number_format($player->people) }}</span>
            </div>
            <div class="status-row">
                <span class="status-row-icon-placeholder">&#x1F3E0;</span>
                <span class="status-row-name">House Space</span>
                <span class="status-row-value">{{ number_format($houseSpace) }}</span>
            </div>
            <div class="status-row status-row--footer">
                <span class="status-row-name">Free space</span>
                <span class="status-row-value {{ $peopleFreeSpace < 0 ? 'text-error' : 'text-success' }}">{{ number_format($peopleFreeSpace) }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Monthly Summary --}}
<div class="panel" style="margin-top: 4px;">
    <div class="panel-header">Monthly Summary (approx.)</div>
    <div class="panel-body">
        <div class="summary-grid">
            {{-- Gold --}}
            <div class="summary-card">
                <div class="summary-card-header">
                    <span class="res-symbol text-gold">&#x1FA99;</span>
                    <span class="summary-card-title">Gold</span>
                    <span class="summary-card-net {{ $totalGold >= 0 ? 'num-positive' : 'num-negative' }}">{{ sprintf('%+d', $totalGold) }}/mo</span>
                </div>
                <div class="summary-card-body">
                    <div class="summary-line">
                        <span>Production</span>
                        <span class="{{ $goldProduction >= 0 ? 'num-positive' : 'num-negative' }}">{{ sprintf('%+d', $goldProduction) }}</span>
                    </div>
                    <div class="summary-line">
                        <span>Consumption</span>
                        <span class="{{ $goldConsumption >= 0 ? 'num-positive' : 'num-negative' }}">{{ sprintf('%+d', $goldConsumption) }}</span>
                    </div>
                    <div class="summary-line">
                        <span>Military upkeep</span>
                        <span class="{{ $payGold >= 0 ? 'num-positive' : 'num-negative' }}">{{ sprintf('%+d', $payGold) }}</span>
                    </div>
                    <div class="summary-line">
                        <span>Trade</span>
                        <span class="{{ $buyGold >= 0 ? 'num-positive' : 'num-negative' }}">{{ sprintf('%+d', $buyGold) }}</span>
                    </div>
                </div>
            </div>

            {{-- Food --}}
            <div class="summary-card">
                <div class="summary-card-header">
                    <img class="status-row-icon" src="{{ resourceIcon('food') }}" alt="Food">
                    <span class="summary-card-title">Food</span>
                    <span class="summary-card-net">
                        <span class="{{ $totalFoodSummer >= 0 ? 'num-positive' : 'num-negative' }}">{{ sprintf('%+d', $totalFoodSummer) }}</span>
                        <span class="text-muted" style="font-size:9px">(S)</span>
                        <span class="{{ $totalFoodWinter >= 0 ? 'num-positive' : 'num-negative' }}">{{ sprintf('%+d', $totalFoodWinter) }}</span>
                        <span class="text-muted" style="font-size:9px">(W)</span>
                    </span>
                </div>
                <div class="summary-card-body">
                    <div class="summary-line">
                        <span>Hunters</span>
                        <span class="{{ $hunterProduction >= 0 ? 'num-positive' : 'num-negative' }}">{{ sprintf('%+d', $hunterProduction) }}</span>
                    </div>
                    <div class="summary-line">
                        <span>Farmers <span class="text-muted" style="font-size:9px">(summer)</span></span>
                        <span class="{{ $farmerProduction >= 0 ? 'num-positive' : 'num-negative' }}">{{ sprintf('%+d', $farmerProduction) }}</span>
                    </div>
                    <div class="summary-line">
                        <span>Stables</span>
                        <span class="{{ $stableConsumption >= 0 ? 'num-positive' : 'num-negative' }}">{{ sprintf('%+d', $stableConsumption) }}</span>
                    </div>
                    <div class="summary-line">
                        <span>Army eats</span>
                        <span class="{{ $eatSoldiersFood >= 0 ? 'num-positive' : 'num-negative' }}">{{ sprintf('%+d', $eatSoldiersFood) }}</span>
                    </div>
                    <div class="summary-line">
                        <span>People eat</span>
                        <span class="{{ $foodEaten >= 0 ? 'num-positive' : 'num-negative' }}">{{ sprintf('%+d', $foodEaten) }}</span>
                    </div>
                    <div class="summary-line">
                        <span>Trade</span>
                        <span class="{{ $buyFood >= 0 ? 'num-positive' : 'num-negative' }}">{{ sprintf('%+d', $buyFood) }}</span>
                    </div>
                </div>
            </div>

            {{-- Wood --}}
            <div class="summary-card">
                <div class="summary-card-header">
                    <img class="status-row-icon" src="{{ resourceIcon('wood') }}" alt="Wood">
                    <span class="summary-card-title">Wood</span>
                    <span class="summary-card-net">
                        <span class="{{ $totalWoodSummer >= 0 ? 'num-positive' : 'num-negative' }}">{{ sprintf('%+d', $totalWoodSummer) }}</span>
                        <span class="text-muted" style="font-size:9px">(S)</span>
                        <span class="{{ $totalWoodWinter >= 0 ? 'num-positive' : 'num-negative' }}">{{ sprintf('%+d', $totalWoodWinter) }}</span>
                        <span class="text-muted" style="font-size:9px">(W)</span>
                    </span>
                </div>
                <div class="summary-card-body">
                    <div class="summary-line">
                        <span>Production</span>
                        <span class="{{ $woodProduction >= 0 ? 'num-positive' : 'num-negative' }}">{{ sprintf('%+d', $woodProduction) }}</span>
                    </div>
                    <div class="summary-line">
                        <span>Crafting <span class="text-muted" style="font-size:9px">(tools/bows/maces)</span></span>
                        <span class="num-negative">{{ sprintf('%+d', $toolWoodConsumption + $bowWoodConsumption + $maceWoodConsumption) }}</span>
                    </div>
                    <div class="summary-line">
                        <span>Wall</span>
                        <span class="{{ $wallWoodConsumption >= 0 ? 'num-positive' : 'num-negative' }}">{{ sprintf('%+d', $wallWoodConsumption) }}</span>
                    </div>
                    <div class="summary-line">
                        <span>Catapults</span>
                        <span class="{{ $catapultWood >= 0 ? 'num-positive' : 'num-negative' }}">{{ sprintf('%+d', $catapultWood) }}</span>
                    </div>
                    <div class="summary-line">
                        <span>Trade</span>
                        <span class="{{ $buyWood >= 0 ? 'num-positive' : 'num-negative' }}">{{ sprintf('%+d', $buyWood) }}</span>
                    </div>
                    <div class="summary-line">
                        <span>Heating <span class="text-muted" style="font-size:9px">(winter)</span></span>
                        <span class="num-negative">{{ sprintf('%+d', $burnWood) }}</span>
                    </div>
                </div>
            </div>

            {{-- Iron --}}
            <div class="summary-card">
                <div class="summary-card-header">
                    <img class="status-row-icon" src="{{ resourceIcon('iron') }}" alt="Iron">
                    <span class="summary-card-title">Iron</span>
                    <span class="summary-card-net {{ $totalIron >= 0 ? 'num-positive' : 'num-negative' }}">{{ sprintf('%+d', $totalIron) }}/mo</span>
                </div>
                <div class="summary-card-body">
                    <div class="summary-line">
                        <span>Production</span>
                        <span class="{{ $ironProduction >= 0 ? 'num-positive' : 'num-negative' }}">{{ sprintf('%+d', $ironProduction) }}</span>
                    </div>
                    <div class="summary-line">
                        <span>Crafting <span class="text-muted" style="font-size:9px">(tools/swords/maces)</span></span>
                        <span class="num-negative">{{ sprintf('%+d', $toolIronConsumption + $swordIronConsumption + $maceIronConsumption) }}</span>
                    </div>
                    <div class="summary-line">
                        <span>Wall</span>
                        <span class="{{ $wallIronConsumption >= 0 ? 'num-positive' : 'num-negative' }}">{{ sprintf('%+d', $wallIronConsumption) }}</span>
                    </div>
                    <div class="summary-line">
                        <span>Catapults</span>
                        <span class="{{ $catapultIron >= 0 ? 'num-positive' : 'num-negative' }}">{{ sprintf('%+d', $catapultIron) }}</span>
                    </div>
                    <div class="summary-line">
                        <span>Trade</span>
                        <span class="{{ $buyIron >= 0 ? 'num-positive' : 'num-negative' }}">{{ sprintf('%+d', $buyIron) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
