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
                                <select class="build-card-status" data-column="{{ $b['db_column'] }}_status" title="Production rate — how much of this building's capacity to use">
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
    </div>

    <div class="build-status-bar">
        <span class="build-totals">
            {{ number_format($totalBuildings) }} buildings &middot; {{ number_format($totalLand) }} land &middot; {{ number_format($totalWorkers) }} workers
        </span>
    </div>

    <div class="build-legend">W = Wood, I = Iron, G = Gold, P = Plains, F = Forest, M = Mountains</div>

    {{-- Action panel — appears when a building is selected --}}
    <div class="build-action-panel" id="buildActionPanel" style="display:none;">
        <div class="build-action-header">
            <img src="" alt="" class="build-action-icon" id="actionIcon">
            <div class="build-action-title">
                <span id="actionName"></span>
                <span class="build-action-have" id="actionHave"></span>
            </div>
        </div>
        <div class="build-action-afford" id="buildAfford"></div>
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

<script>
(function() {
    var freePLand = {{ $freePlains }};
    var freeMLand = {{ $freeMountain }};
    var freeFLand = {{ $freeForest }};
    var gold = {{ $player->gold }};
    var iron = {{ $player->iron }};
    var wood = {{ $player->wood }};
    var selectedCard = null;

    var panel = document.getElementById('buildActionPanel');
    var actionIcon = document.getElementById('actionIcon');
    var actionName = document.getElementById('actionName');
    var actionHave = document.getElementById('actionHave');
    var affordEl = document.getElementById('buildAfford');
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

            // Update action panel header
            var img = card.querySelector('.build-card-icon');
            if (img && img.style.display !== 'none') {
                actionIcon.src = img.src;
                actionIcon.style.display = '';
            } else {
                actionIcon.style.display = 'none';
            }
            actionName.textContent = card.dataset.bname;
            actionName.style.color = card.querySelector('.build-card-name').style.color;
            actionHave.textContent = '(Have: ' + parseInt(card.dataset.have, 10).toLocaleString() + ')';

            // Show the panel
            panel.style.display = '';

            updateAfford(card);
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

        return canBuild;
    }

    function updateAfford(card) {
        affordEl.textContent = 'You can build up to ' + calcMaxBuild(card).toLocaleString();
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
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    var statusUrl = '{{ route("game.build.status") }}';

    document.querySelectorAll('.build-card-status').forEach(function(sel) {
        sel.addEventListener('change', function() {
            var column = this.dataset.column;
            var value = parseInt(this.value, 10);
            var selectEl = this;
            var card = selectEl.closest('.build-card');

            // Instantly recalculate production stats on the card
            recalcCardStats(card, value);

            // Brief visual feedback
            selectEl.style.borderColor = 'var(--border-accent)';

            fetch(statusUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ column: column, value: value })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    selectEl.style.borderColor = 'var(--text-success)';
                    setTimeout(function() { selectEl.style.borderColor = ''; }, 800);
                } else {
                    selectEl.style.borderColor = 'var(--text-error)';
                    setTimeout(function() { selectEl.style.borderColor = ''; }, 1500);
                }
            })
            .catch(function() {
                selectEl.style.borderColor = 'var(--text-error)';
                setTimeout(function() { selectEl.style.borderColor = ''; }, 1500);
            });
        });
    });
})();
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
