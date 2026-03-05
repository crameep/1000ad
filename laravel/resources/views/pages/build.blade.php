{{-- Buildings page - ported from build.cfm --}}
@extends('layouts.game')

@section('content')
<div class="page-title-bar">
    <h2>Buildings</h2>
    <a href="javascript:openHelp('buildings')" class="help-link">Help</a>
</div>

<x-advisor-panel :tips="$advisorTips" />

{{-- Build Section — integrated cards with status, production, workers --}}
<div class="build-section">
    <div class="build-card-grid">
        @foreach($displayOrder as $i)
            @php
                $b = $buildings[$i];
                $stats = $buildingStats[$i];
                $color = $colors[$i];
            @endphp
            <div class="build-card"
                 data-building="{{ $i }}"
                 data-bname="{{ $b['name'] }}"
                 data-wood="{{ $b['cost_wood'] }}"
                 data-iron="{{ $b['cost_iron'] }}"
                 data-gold="{{ $b['cost_gold'] }}"
                 data-sq="{{ $b['sq'] }}"
                 data-land="{{ $b['land'] }}"
                 data-have="{{ $stats['have'] }}"
                 data-workers-per="{{ $b['workers'] }}"
                 data-prod="{{ $b['production'] ?? 0 }}"
                 data-prod-name="{{ $b['production_name'] ?? '' }}"
                 data-allow-off="{{ $b['allow_off'] ? 1 : 0 }}">
                <img src="{{ buildingIcon($b) }}" alt="{{ $b['name'] }}" class="build-card-icon"
                     onerror="this.style.display='none'" onload="this.style.display=''">
                <div class="build-card-info">
                    <div class="build-card-top">
                        <span class="build-card-name" style="color: {{ $color }}">{{ $b['name'] }}</span>
                        <span class="build-card-count" style="color: {{ $color }}">{{ number_format($stats['have']) }}</span>
                    </div>
                    <div class="build-card-mid">
                        <span class="build-card-cost">
                            @if($b['cost_wood'] > 0)<span>{{ $b['cost_wood'] }}W</span>@endif
                            @if($b['cost_iron'] > 0)<span>{{ $b['cost_iron'] }}I</span>@endif
                            @if($b['cost_gold'] > 0)<span>{{ $b['cost_gold'] }}G</span>@endif
                            <span>{{ $b['sq'] }}{{ $b['land'] }}</span>
                        </span>
                        @if($b['allow_off'])
                            <span class="build-card-status-wrap" onclick="event.stopPropagation()">
                                <span class="build-card-status-label">Prod:</span>
                                <select class="build-card-status" data-column="{{ $b['db_column'] }}_status" data-server-value="{{ $stats['status'] }}" autocomplete="off" title="Production rate — how much of this building's capacity to use">
                                    @for($s = 0; $s <= 10; $s++)
                                        @php $sIndex = $s * 10; @endphp
                                        <option value="{{ $sIndex }}" @if($sIndex == $stats['status']) selected @endif>{{ $sIndex }}%</option>
                                    @endfor
                                </select>
                            </span>
                        @endif
                    </div>
                    @php
                        $hasStats = !empty($stats['production']) || !empty($stats['consumption']) || $stats['workers'] > 0;
                    @endphp
                    <div class="build-card-stats" {!! $hasStats ? '' : 'style="display:none"' !!}>
                        <span class="build-card-prod">{!! $stats['production'] ?? '' !!}</span>
                        <span class="build-card-cons">{!! $stats['consumption'] ?? '' !!}</span>
                        <span class="build-card-workers">{!! $stats['workers'] > 0 ? number_format($stats['workers']) . ' workers' : '' !!}</span>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Action panel — lives inside grid, moves below selected card via JS --}}
        <div class="build-action-panel" id="buildActionPanel" style="display:none;">
            <div class="build-action-info" id="buildInfo"></div>
            <div class="build-action-suggest" id="buildSuggest" style="display:none;"></div>
            <div class="build-action-row">
                <form action="{{ route('game.build.submit') }}" method="POST" id="buildForm" class="build-action-form">
                    @csrf
                    <input type="hidden" name="building_no" id="buildBuildingNo" value="0">
                    <label for="buildQty" class="build-qty-label">Qty:</label>
                    <div class="build-qty-stepper">
                        <button type="button" class="build-qty-btn" id="qtyMinus">&minus;</button>
                        <input type="number" name="qty" id="buildQty" value="1" min="1" max="10000000" class="build-qty-input">
                        <button type="button" class="build-qty-btn" id="qtyPlus">+</button>
                    </div>
                    <button type="button" class="build-max-btn" id="buildHalfBtn" title="Set to half of max">&frac12;</button>
                    <button type="button" class="build-max-btn" id="buildMaxBtn" title="Set to max you can build">Max</button>
                    <button type="submit" class="build-go-btn">Build</button>
                </form>
                <form action="{{ route('game.build.demolish') }}" method="POST" id="demolishForm" class="build-demolish-form">
                    @csrf
                    <input type="hidden" name="building_no" id="demolishBuildingNo" value="0">
                    <input type="hidden" name="qty" id="demolishQty" value="1">
                    <button type="submit" class="build-demolish-btn">Demolish</button>
                </form>
            </div>
        </div>
    </div>

    <div class="build-status-bar">
        <span class="build-totals">
            {{ number_format($totalBuildings) }} buildings &middot; {{ number_format($totalLand) }} land &middot; {{ number_format($totalWorkers) }} workers
        </span>
    </div>

    <div class="build-legend">W = Wood, I = Iron, G = Gold, P = Plains, F = Forest, M = Mountains</div>
</div>

<script>
(function() {
    var freePLand = {{ $freePlains }};
    var freeMLand = {{ $freeMountain }};
    var freeFLand = {{ $freeForest }};
    var gold = {{ $player->gold }};
    var iron = {{ $player->iron }};
    var wood = {{ $player->wood }};
    var freePeople = {{ $free }};
    var selectedCard = null;

    // Economy data from controller (per-turn net production)
    var economy = @json($economy);
    var popDeficit = {{ $popDeficit }};
    var peopleEatOneFood = {{ $peopleEatOneFood }};

    var panel = document.getElementById('buildActionPanel');
    var infoEl = document.getElementById('buildInfo');
    var suggestEl = document.getElementById('buildSuggest');
    var buildQty = document.getElementById('buildQty');
    var demolishQty = document.getElementById('demolishQty');

    // Sync qty fields so demolish uses the same number
    buildQty.addEventListener('input', function() {
        demolishQty.value = this.value;
    });

    // Qty stepper +/- buttons
    document.getElementById('qtyMinus').addEventListener('click', function() {
        var v = parseInt(buildQty.value, 10) || 1;
        if (v > 1) { buildQty.value = v - 1; demolishQty.value = v - 1; }
    });
    document.getElementById('qtyPlus').addEventListener('click', function() {
        var v = parseInt(buildQty.value, 10) || 0;
        buildQty.value = v + 1;
        demolishQty.value = v + 1;
    });

    // Half button — fill qty with half of max affordable
    document.getElementById('buildHalfBtn').addEventListener('click', function() {
        if (!selectedCard) return;
        var half = Math.floor(calcMaxBuild(selectedCard) / 2);
        if (half > 0) { buildQty.value = half; demolishQty.value = half; }
    });

    // Max button — fill qty with max affordable
    document.getElementById('buildMaxBtn').addEventListener('click', function() {
        if (!selectedCard) return;
        var max = calcMaxBuild(selectedCard);
        if (max > 0) { buildQty.value = max; demolishQty.value = max; }
    });

    // Card selection — ignore clicks on <select> elements
    var cards = document.querySelectorAll('.build-card');
    for (var i = 0; i < cards.length; i++) {
        cards[i].addEventListener('click', function(e) {
            if (e.target.tagName === 'SELECT' || e.target.tagName === 'OPTION') return;

            var card = this;
            var buildingNo = card.dataset.building;

            // Deselect previous
            if (selectedCard) selectedCard.classList.remove('build-card-selected');

            // Select new
            selectedCard = card;
            card.classList.add('build-card-selected');

            // Update hidden inputs on both forms
            document.getElementById('buildBuildingNo').value = buildingNo;
            document.getElementById('demolishBuildingNo').value = buildingNo;

            // Move panel right after selected card inside the grid, then show
            card.insertAdjacentElement('afterend', panel);
            panel.style.display = '';

            // Scroll panel into view smoothly
            setTimeout(function() {
                panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 50);

            updatePanel(card);
        });
    }

    function calcMaxBuild(card) {
        var costGold = parseInt(card.dataset.gold, 10);
        var costIron = parseInt(card.dataset.iron, 10);
        var costWood = parseInt(card.dataset.wood, 10);
        var sq = parseInt(card.dataset.sq, 10);
        var land = card.dataset.land;

        var canBuild = 1000000000;
        if (costGold > 0) canBuild = Math.min(canBuild, Math.floor(gold / costGold));
        if (costIron > 0) canBuild = Math.min(canBuild, Math.floor(iron / costIron));
        if (costWood > 0) canBuild = Math.min(canBuild, Math.floor(wood / costWood));

        var freeLand = land === 'P' ? freePLand : (land === 'M' ? freeMLand : freeFLand);
        if (sq > 0) canBuild = Math.min(canBuild, Math.floor(freeLand / sq));

        return Math.max(canBuild, 0);
    }

    // Calculate how many buildings we can add before any resource goes into deficit.
    // Each building adds workers who eat food. Resource producers add to their resource.
    // Gold consumers (winery, mage tower) drain gold. Tool maker drains wood+iron.
    function safeMax(bid, workersPerB, prodPerB, resKey) {
        var foodPerWorker = peopleEatOneFood > 0 ? 1 / peopleEatOneFood : 0;
        var foodCostPerBuilding = workersPerB * foodPerWorker;
        var caps = [];

        // Food cap: each building's workers eat food (unless it produces food)
        var foodSurplus = economy.food || 0;
        if (resKey === 'food' && prodPerB > 0) {
            // Net food per building = production - worker consumption
            var netFoodPerB = prodPerB - foodCostPerBuilding;
            if (netFoodPerB < 0 && foodSurplus > 0) {
                caps.push(Math.floor(foodSurplus / Math.abs(netFoodPerB)));
            }
            // If net positive, food isn't the constraint
        } else if (foodCostPerBuilding > 0 && foodSurplus > 0) {
            // Non-food building: workers only consume food
            caps.push(Math.floor(foodSurplus / foodCostPerBuilding));
        }

        // Gold cap for gold-consuming buildings (winery=16, mage tower=15)
        if (bid === 15 || bid === 16) {
            var goldCost = buildingExtra[bid] ? buildingExtra[bid].goldNeed || 0 : 0;
            var goldSurplus = economy.gold || 0;
            if (goldCost > 0 && goldSurplus > 0) {
                // Net gold drain per building = goldCost - 0 (these don't produce gold)
                caps.push(Math.floor(goldSurplus / goldCost));
            }
        }

        // Tool maker (bid=7) consumes wood + iron
        if (bid === 7) {
            var tmWood = buildingExtra[7] ? buildingExtra[7].woodNeed || 0 : 0;
            var tmIron = buildingExtra[7] ? buildingExtra[7].ironNeed || 0 : 0;
            if (tmWood > 0 && (economy.wood || 0) > 0) caps.push(Math.floor(economy.wood / tmWood));
            if (tmIron > 0 && (economy.iron || 0) > 0) caps.push(Math.floor(economy.iron / tmIron));
        }

        // Stable (bid=14) consumes food
        if (bid === 14) {
            var stableFood = buildingExtra[14] ? buildingExtra[14].foodNeed || 0 : 0;
            if (stableFood > 0 && foodSurplus > 0) {
                // Total food cost per stable = worker food + stable food consumption
                caps.push(Math.floor(foodSurplus / (foodCostPerBuilding + stableFood)));
            }
        }

        return caps.length > 0 ? Math.min.apply(null, caps) : Infinity;
    }

    function calcSuggestion(card) {
        var bid = parseInt(card.dataset.building, 10);
        var max = calcMaxBuild(card);
        var have = parseInt(card.dataset.have, 10);
        var workersPerB = parseInt(card.dataset.workersPer, 10);
        var prodPerB = parseInt(card.dataset.prod, 10);
        var prodName = (card.dataset.prodName || '').toLowerCase();

        // Scale suggestions with empire size: ~25% of current count, min 5
        var growth = Math.max(5, Math.ceil(have * 0.25));

        // Resource key for this building's production
        var resKey = null;
        if (prodName.indexOf('food') >= 0) resKey = 'food';
        else if (prodName.indexOf('wood') >= 0) resKey = 'wood';
        else if (prodName.indexOf('iron') >= 0) resKey = 'iron';
        else if (prodName.indexOf('gold') >= 0) resKey = 'gold';

        // Cap: max we can build without causing any deficit
        var safe = safeMax(bid, workersPerB, prodPerB, resKey);

        // Housing buildings (house=4, town_center=11) — no workers, no deficit risk
        if (bid === 4 || bid === 11) {
            var hQty = popDeficit <= 0 ? growth : Math.max(growth, Math.ceil(Math.abs(popDeficit) / 100));
            var hReason = popDeficit <= 0 ? 'grow population' : 'housing needed';
            return { qty: max > 0 ? Math.min(hQty, max) : hQty, reason: hReason };
        }

        // Gold-consuming buildings (mage tower=15, winery=16)
        if (bid === 15 || bid === 16) {
            var goldNet = economy.gold || 0;
            var gQty = goldNet > 0 ? Math.min(growth, safe) : Math.max(2, Math.ceil(growth * 0.5));
            if (goldNet <= 0) gQty = Math.min(gQty, 2); // minimal when gold negative
            var gReason = goldNet > 0 ? 'gold surplus (+' + goldNet.toLocaleString() + '/turn)' : 'gold deficit (' + goldNet.toLocaleString() + '/turn)';
            return { qty: max > 0 ? Math.min(gQty, max) : gQty, reason: gReason };
        }

        // Resource-producing buildings
        if (resKey && prodPerB > 0) {
            var net = economy[resKey] || 0;
            if (net < 0) {
                // Deficit — suggest enough to fix it, but don't cause other deficits
                var needed = Math.ceil(Math.abs(net) / prodPerB * 1.5);
                needed = Math.max(needed, growth);
                if (safe < Infinity && safe > 0) needed = Math.min(needed, safe);
                return { qty: max > 0 ? Math.min(needed, max) : needed, reason: resKey + ' deficit (' + net.toLocaleString() + '/turn)' };
            } else {
                // Surplus — grow but respect deficit caps
                var qty = growth;
                if (safe < Infinity) qty = Math.min(qty, Math.max(1, safe));
                var reason = resKey + ' surplus (+' + net.toLocaleString() + '/turn)';
                if (safe < growth && safe < Infinity) reason += ', food-limited';
                return { qty: max > 0 ? Math.min(qty, max) : qty, reason: reason };
            }
        }

        // Worker-based: fill free workforce, but cap by food
        if (freePeople > 0 && workersPerB > 0) {
            var fillWorkers = Math.floor(freePeople / workersPerB);
            if (safe < Infinity) fillWorkers = Math.min(fillWorkers, safe);
            if (fillWorkers > 0) return { qty: max > 0 ? Math.min(fillWorkers, max) : fillWorkers, reason: 'fills free workforce' };
        }

        // Default — scale with current count, respect food cap
        var defQty = growth;
        if (safe < Infinity) defQty = Math.min(defQty, Math.max(1, safe));
        return { qty: max > 0 ? Math.min(defQty, max) : defQty, reason: 'steady growth' };
    }

    function updatePanel(card) {
        var max = calcMaxBuild(card);
        var have = parseInt(card.dataset.have, 10);

        // Info row: "Can build 100 · Have 8"
        infoEl.textContent = 'Can build ' + max.toLocaleString() + ' \u00b7 Have ' + have.toLocaleString();

        // Suggestion chip — always show based on economy analysis
        var s = calcSuggestion(card);
        if (s.qty > 0) {
            suggestEl.style.display = '';
            var chipClass = max > 0 ? 'build-suggest-chip' : 'build-suggest-chip build-suggest-chip-disabled';
            suggestEl.innerHTML = 'Suggested: <span class="' + chipClass + '" title="' + (max > 0 ? 'Click to set qty' : 'Not enough resources') + '">'
                + s.qty.toLocaleString() + '</span> <span class="build-suggest-reason">(' + s.reason + ')</span>';
            if (max > 0) {
                suggestEl.querySelector('.build-suggest-chip').addEventListener('click', function() {
                    buildQty.value = s.qty;
                    demolishQty.value = s.qty;
                });
            }
        } else {
            suggestEl.style.display = 'none';
        }
    }

    // Building-specific data for consumption calculations
    var buildingExtra = {
        7:  { woodNeed: {{ $buildings[7]['wood_need'] ?? 0 }}, ironNeed: {{ $buildings[7]['iron_need'] ?? 0 }} },
        8:  { bowWS: {{ $player->bow_weapon_smith ?? 0 }}, swordWS: {{ $player->sword_weapon_smith ?? 0 }}, maceWS: {{ $player->mace_weapon_smith ?? 0 }},
              woodNeed: {{ $buildings[8]['wood_need'] ?? 0 }}, ironNeed: {{ $buildings[8]['iron_need'] ?? 0 }},
              maceWood: {{ $buildings[8]['mace_wood'] ?? 0 }}, maceIron: {{ $buildings[8]['mace_iron'] ?? 0 }} },
        14: { foodNeed: {{ $buildings[14]['food_need'] ?? 0 }} },
        15: { goldNeed: {{ $buildings[15]['gold_need'] ?? 0 }} },
        16: { goldNeed: {{ $buildings[16]['gold_need'] ?? 0 }} }
    };

    function recalcCardStats(card, statusPct) {
        var bid = parseInt(card.dataset.building, 10);
        var have = parseInt(card.dataset.have, 10);
        var workersPerB = parseInt(card.dataset.workersPer, 10);
        var prodPerB = parseInt(card.dataset.prod, 10);
        var prodName = card.dataset.prodName;
        var allowOff = card.dataset.allowOff === '1';

        var bWorking = have;
        if (allowOff) {
            if (statusPct === 0) { bWorking = 0; }
            else { bWorking = Math.round(have * (statusPct / 100)); }
        }

        var workers = bWorking * workersPerB;
        var production = '';
        var consumption = '';

        // Production (skip if nothing working)
        if (bWorking > 0) {
            if (bid === 8) {
                var ex = buildingExtra[8];
                var bowP = Math.round(ex.bowWS * (statusPct / 100));
                var swordP = Math.round(ex.swordWS * (statusPct / 100));
                var maceP = Math.round(ex.maceWS * (statusPct / 100));
                production = swordP.toLocaleString() + ' swords, ' + bowP.toLocaleString() + ' bows, ' + maceP.toLocaleString() + ' maces';
            } else if (prodName) {
                production = (bWorking * prodPerB).toLocaleString() + ' ' + prodName;
            }
        }

        // Consumption (skip if nothing working)
        if (bWorking > 0) {
            if (bid === 7) {
                var ex7 = buildingExtra[7];
                consumption = (bWorking * ex7.woodNeed).toLocaleString() + ' wood, ' + (bWorking * ex7.ironNeed).toLocaleString() + ' iron';
            } else if (bid === 8) {
                var ex8 = buildingExtra[8];
                var bowP2 = Math.round(ex8.bowWS * (statusPct / 100));
                var swordP2 = Math.round(ex8.swordWS * (statusPct / 100));
                var maceP2 = Math.round(ex8.maceWS * (statusPct / 100));
                var useWood = bowP2 * ex8.woodNeed + maceP2 * ex8.maceWood;
                var useIron = swordP2 * ex8.ironNeed + maceP2 * ex8.maceIron;
                consumption = useWood.toLocaleString() + ' wood, ' + useIron.toLocaleString() + ' iron';
            } else if (bid === 14) {
                consumption = (bWorking * buildingExtra[14].foodNeed).toLocaleString() + ' food';
            } else if (bid === 15) {
                consumption = (bWorking * buildingExtra[15].goldNeed).toLocaleString() + ' gold';
            } else if (bid === 16) {
                consumption = (bWorking * buildingExtra[16].goldNeed).toLocaleString() + ' gold';
            }
        }

        // Update the DOM
        var statsDiv = card.querySelector('.build-card-stats');
        var prodEl = card.querySelector('.build-card-prod');
        var consEl = card.querySelector('.build-card-cons');
        var wkEl = card.querySelector('.build-card-workers');

        prodEl.textContent = production;
        consEl.textContent = consumption;
        wkEl.textContent = workers > 0 ? workers.toLocaleString() + ' workers' : '';

        // Show/hide stats row
        statsDiv.style.display = (production || consumption || workers > 0) ? '' : 'none';
    }

    // AJAX status updates — save immediately on dropdown change
    document.querySelectorAll('.build-card-status').forEach(function(sel) {
        sel.dataset.savedValue = sel.value;

        sel.addEventListener('change', function() {
            var column = this.dataset.column;
            var value = parseInt(this.value, 10);
            var savedValue = this.dataset.savedValue;
            var selectEl = this;
            var card = selectEl.closest('.build-card');

            // Instantly recalculate production stats on the card
            recalcCardStats(card, value);
            selectEl.style.borderColor = 'var(--border-accent)';

            Game.Ajax.post('/game/build/status', { column: column, value: value }, { silent: true })
                .then(function(data) {
                    if (data.success) {
                        selectEl.dataset.savedValue = value;
                        selectEl.dataset.serverValue = value;
                        selectEl.style.borderColor = 'var(--text-success)';
                        setTimeout(function() { selectEl.style.borderColor = ''; }, 800);
                    } else {
                        selectEl.value = savedValue;
                        recalcCardStats(card, parseInt(savedValue, 10));
                        selectEl.style.borderColor = 'var(--text-error)';
                        setTimeout(function() { selectEl.style.borderColor = ''; }, 3000);
                    }
                })
                .catch(function() {
                    selectEl.value = savedValue;
                    recalcCardStats(card, parseInt(savedValue, 10));
                    selectEl.style.borderColor = 'var(--text-error)';
                    setTimeout(function() { selectEl.style.borderColor = ''; }, 3000);
                });
        });
    });
})();

// Fix Android Chrome form state restoration: force selects to server values
// Chrome restores form values after DOMContentLoaded, so we use a delayed reset
setTimeout(function() {
    document.querySelectorAll('.build-card-status[data-server-value]').forEach(function(sel) {
        var sv = sel.dataset.serverValue;
        if (sel.value !== sv) { sel.value = sv; }
    });
}, 0);
</script>

{{-- Build Queue --}}
@if($buildQueue->count() > 0)
<br>
<b>Your Building Queue:</b>
<table class="game-table">
<tr>
    <td class="header">Building</td>
    <td class="header">No.</td>
    <td class="header">Time Needed</td>
    <td class="header">Cancel?</td>
    <td class="header">Move</td>
</tr>
@foreach($buildQueue as $bq)
    @php
        $b = $buildings[$bq->building_no] ?? null;
        $turnsNeeded = $numBuilders > 0 ? ceil($bq->time_needed / $numBuilders) : $bq->time_needed;
    @endphp
    @if($b)
    <tr>
        <td><x-game-icon :src="buildingIcon($b)" :alt="$b['name']" :size="32" /> {{ $b['name'] }} @if($bq->mission == 1)(Demolish)@endif</td>
        <td>{{ $bq->qty }}</td>
        <td>{{ $turnsNeeded }} turns ({{ $bq->time_needed }} builders)</td>
        <td>
            <form action="{{ route('game.build.cancel') }}" method="POST" class="inline-form">
                @csrf
                <input type="hidden" name="q_id" value="{{ $bq->id }}">
                <a href="#" onclick="this.closest('form').submit(); return false;">Cancel</a>
            </form>
        </td>
        <td>
            <form action="{{ route('game.build.move-top') }}" method="POST" class="inline-form">
                @csrf
                <input type="hidden" name="q_id" value="{{ $bq->id }}">
                <a href="#" onclick="this.closest('form').submit(); return false;">To Top</a>
            </form>
            |
            <form action="{{ route('game.build.move-bottom') }}" method="POST" class="inline-form">
                @csrf
                <input type="hidden" name="q_id" value="{{ $bq->id }}">
                <a href="#" onclick="this.closest('form').submit(); return false;">To Bottom</a>
            </form>
        </td>
    </tr>
    @endif
@endforeach
@if($buildQueue->count() > 1)
<tr>
    <td colspan="5" align="center">
        <form action="{{ route('game.build.cancel-all') }}" method="POST" class="inline-form">
            @csrf
            <a href="#" onclick="this.closest('form').submit(); return false;">Cancel All</a>
        </form>
    </td>
</tr>
@endif
</table>
@endif

{{-- Population Summary --}}
<table class="game-table">
<tr><td colspan="2" class="header"><b>Population:</b></td></tr>
<tr><td align="right">Total:</td><td>{{ number_format($player->people) }}</td></tr>
<tr><td align="right">Working:</td><td>{{ number_format($totalWorkers) }}</td></tr>
<tr><td align="right">Builders:</td><td>{{ number_format($numBuilders) }}</td></tr>
@if($free < 0)
    <tr>
        <td colspan="2">
            <span class="text-error">
                You do not have enough people for your production.<br>
                You need additional {{ number_format(abs($free)) }} people.
            </span>
        </td>
    </tr>
@else
    <tr><td align="right">Not Working:</td><td>{{ number_format($free) }}</td></tr>
@endif
<tr>
    <td align="right">Extra House Space:</td>
    <td>{{ number_format($freeSpace) }}</td>
</tr>
</table>
@endsection
