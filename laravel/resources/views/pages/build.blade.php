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
    <form action="{{ route('game.build.status') }}" method="POST" id="statusForm">
    @csrf

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
                 data-have="{{ $stats['have'] }}">
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
                            <select name="{{ $b['db_column'] }}_status" class="build-card-status" onclick="event.stopPropagation()">
                                @for($s = 0; $s <= 10; $s++)
                                    @php $sIndex = $s * 10; @endphp
                                    <option value="{{ $s }}" @if($sIndex == $stats['status']) selected @endif>{{ $sIndex }}%</option>
                                @endfor
                            </select>
                        @endif
                    </div>
                    @if(!empty($stats['production']) || !empty($stats['consumption']) || $stats['workers'] > 0)
                    <div class="build-card-stats">
                        @if(!empty($stats['production']))
                            <span class="build-card-prod">{!! $stats['production'] !!}</span>
                        @endif
                        @if(!empty($stats['consumption']))
                            <span class="build-card-cons">{!! $stats['consumption'] !!}</span>
                        @endif
                        @if($stats['workers'] > 0)
                            <span class="build-card-workers">{{ number_format($stats['workers']) }} workers</span>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="build-status-bar">
        <span class="build-totals">
            {{ number_format($totalBuildings) }} buildings &middot; {{ number_format($totalLand) }} land &middot; {{ number_format($totalWorkers) }} workers
        </span>
        <input type="submit" value="Update Status" class="build-status-btn">
    </div>

    </form>

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
