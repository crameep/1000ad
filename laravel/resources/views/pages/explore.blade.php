{{-- Explore page - ported from explore.cfm --}}
@extends('layouts.game')

@section('content')
<div class="page-title-bar">
    <h2>Explore</h2>
    <a href="javascript:openHelp('explore')" class="help-link">Help</a>
</div>

<x-advisor-panel :tips="$advisorTips" />

{{-- Exploration queue table --}}
@if($explorations->isEmpty())
    <div class="info-text">You do not have any explorers sent.</div>
@else
    <table class="game-table">
    <tr>
        <td class="header">No. explorers</td>
        <td class="header">Land Sought</td>
        <td class="header">Months remaining</td>
        <td class="header">Land discovered</td>
    </tr>
    @foreach($explorations as $e)
    <tr>
        <td class="vtop">
            @if($e->turn == 0)
                <span class="text-error">DONE</span>
            @else
                {{ number_format($e->people) }}
            @endif
        </td>
        <td class="vtop">
            @if($e->seek_land == 0)
                All
                @if($e->pct_mountain > 0 || $e->pct_forest > 0 || $e->pct_plains > 0)
                    <div class="text-small text-muted" style="margin-top:2px;">
                        <img class="land-icon" src="{{ landIcon('mountain') }}" alt="M" style="width:12px;height:12px;">{{ $e->pct_mountain }}
                        <img class="land-icon" src="{{ landIcon('forest') }}" alt="F" style="width:12px;height:12px;">{{ $e->pct_forest }}
                        <img class="land-icon" src="{{ landIcon('plains') }}" alt="P" style="width:12px;height:12px;">{{ $e->pct_plains }}%
                    </div>
                @endif
            @elseif($e->seek_land == 1)<img class="land-icon" src="{{ landIcon('mountain') }}" alt="M"> Mountains
            @elseif($e->seek_land == 2)<img class="land-icon" src="{{ landIcon('forest') }}" alt="F"> Forest
            @elseif($e->seek_land == 3)<img class="land-icon" src="{{ landIcon('plains') }}" alt="P"> Plains
            @endif
        </td>
        <td class="vtop">
            {{ $e->turn }}
            @if($e->turns_used == 0 && $e->created_on && $e->created_on->gt($cancelTime))
                <div>
                <form action="{{ route('game.explore.send') }}" method="POST" class="inline-form">
                    @csrf
                    <input type="hidden" name="eflag" value="cancelExplore">
                    <input type="hidden" name="eID" value="{{ $e->id }}">
                    <a href="#" onclick="this.closest('form').submit(); return false;">Cancel Explorers</a>
                </form>
                </div>
            @endif
        </td>
        <td class="vtop">
            @if($e->seek_land == 0 || $e->seek_land == 1)<div>{{ number_format($e->mland ?? 0) }} Mountains</div>@endif
            @if($e->seek_land == 0 || $e->seek_land == 2)<div>{{ number_format($e->fland ?? 0) }} Forest</div>@endif
            @if($e->seek_land == 0 || $e->seek_land == 3)<div>{{ number_format($e->pland ?? 0) }} Plains</div>@endif
        </td>
    </tr>
    @endforeach
    </table>
@endif

{{-- Explorer stats --}}
<div class="stat-list">
    <div>You have {{ $totalExplorers }} explorers looking for land.</div>
    <div>You can have a maximum of {{ $maxExplorers }} explorers.</div>
    <div>Your food reserves allow you to send {{ $sendExplorers }} explorers.</div>
    <div>You can send {{ $canSend }} more explorers.</div>
    <div>You need {{ $foodPerExplorer }} food for each explorer.</div>
    <div>You have {{ number_format($player->horses) }} horses.</div>
</div>

{{-- Send explorers form --}}
<div class="form-panel">
<div class="form-body">
<form action="{{ route('game.explore.send') }}" method="POST" id="explore-form">
    @csrf
    <input type="hidden" name="eflag" value="send_explorers">
    <div>
        Send <input type="text" size="5" value="{{ $canSend }}" name="qty"> explorers
        with
        <select name="withHorses">
            <option value="0" @if($lastHorseSetting == 0) selected @endif>No Horses</option>
            <option value="1" @if($lastHorseSetting == 1) selected @endif>1X Horses</option>
            <option value="2" @if($lastHorseSetting == 2) selected @endif>2X Horses</option>
            <option value="3" @if($lastHorseSetting == 3) selected @endif>3X Horses</option>
        </select>
        to look for
        <select name="seekLand" id="seekLand-select">
            <option value="0" selected>All Land</option>
            <option value="1">Mountain Land</option>
            <option value="2">Forest Land</option>
            <option value="3">Plains Land</option>
        </select>
        <input type="submit" value="Send">
    </div>

    {{-- Land priority controls (visible when "All Land" selected) --}}
    <div id="land-priorities" class="land-priorities">
        <div class="land-priorities-header">
            <b>Land Priorities</b>
            <span id="pct-total" class="pct-total-badge"></span>
        </div>
        <div class="land-priorities-rows">
            <div class="land-pct-row">
                <img class="land-icon" src="{{ landIcon('mountain') }}" alt="M">
                <span class="land-pct-label">Mountains</span>
                <input type="range" name="pct_mountain_range" id="pct-mountain-range" value="{{ $pctMountain }}"
                       min="0" max="100" step="5" class="pct-slider">
                <input type="number" name="pct_mountain" id="pct-mountain" value="{{ $pctMountain }}"
                       min="0" max="100" step="5" class="pct-input">
                <span class="pct-sign">%</span>
            </div>
            <div class="land-pct-row">
                <img class="land-icon" src="{{ landIcon('forest') }}" alt="F">
                <span class="land-pct-label">Forest</span>
                <input type="range" name="pct_forest_range" id="pct-forest-range" value="{{ $pctForest }}"
                       min="0" max="100" step="5" class="pct-slider">
                <input type="number" name="pct_forest" id="pct-forest" value="{{ $pctForest }}"
                       min="0" max="100" step="5" class="pct-input">
                <span class="pct-sign">%</span>
            </div>
            <div class="land-pct-row">
                <img class="land-icon" src="{{ landIcon('plains') }}" alt="P">
                <span class="land-pct-label">Plains</span>
                <input type="range" name="pct_plains_range" id="pct-plains-range" value="{{ $pctPlains }}"
                       min="0" max="100" step="5" class="pct-slider">
                <input type="number" name="pct_plains" id="pct-plains" value="{{ $pctPlains }}"
                       min="0" max="100" step="5" class="pct-input">
                <span class="pct-sign">%</span>
            </div>
        </div>
    </div>
</form>
</div>
</div>

<script>
(function() {
    var seekSelect = document.getElementById('seekLand-select');
    var priorityDiv = document.getElementById('land-priorities');
    var pctM = document.getElementById('pct-mountain');
    var pctF = document.getElementById('pct-forest');
    var pctP = document.getElementById('pct-plains');
    var sliderM = document.getElementById('pct-mountain-range');
    var sliderF = document.getElementById('pct-forest-range');
    var sliderP = document.getElementById('pct-plains-range');
    var pctTotal = document.getElementById('pct-total');

    var pairs = [
        { input: pctM, slider: sliderM },
        { input: pctF, slider: sliderF },
        { input: pctP, slider: sliderP }
    ];

    function togglePriorities() {
        priorityDiv.style.display = seekSelect.value === '0' ? '' : 'none';
    }

    function syncSliders() {
        pairs.forEach(function(p) { p.slider.value = p.input.value; });
    }

    function updateTotal() {
        var m = parseInt(pctM.value) || 0;
        var f = parseInt(pctF.value) || 0;
        var p = parseInt(pctP.value) || 0;
        var total = m + f + p;
        if (total === 100) {
            pctTotal.textContent = '100%';
            pctTotal.className = 'pct-total-badge pct-ok';
        } else {
            pctTotal.textContent = total + '%';
            pctTotal.className = 'pct-total-badge pct-err';
        }
    }

    function autoAdjust(changedInput) {
        var m = parseInt(pctM.value) || 0;
        var f = parseInt(pctF.value) || 0;
        var p = parseInt(pctP.value) || 0;
        var total = m + f + p;
        var diff = total - 100;

        if (diff === 0) return;

        var others = [];
        if (changedInput !== pctM) others.push(pctM);
        if (changedInput !== pctF) others.push(pctF);
        if (changedInput !== pctP) others.push(pctP);

        others.sort(function(a, b) { return (parseInt(b.value) || 0) - (parseInt(a.value) || 0); });

        for (var i = 0; i < others.length && diff !== 0; i++) {
            var val = parseInt(others[i].value) || 0;
            var adjust = Math.min(diff, val);
            if (diff < 0) adjust = diff;
            var newVal = val - adjust;
            if (newVal < 0) { adjust = val; newVal = 0; }
            if (newVal > 100) { adjust = val - 100; newVal = 100; }
            others[i].value = newVal;
            diff -= adjust;
        }

        syncSliders();
        updateTotal();
    }

    function handleChange(input) {
        var v = parseInt(input.value) || 0;
        if (v < 0) input.value = 0;
        if (v > 100) input.value = 100;
        autoAdjust(input);
        updateTotal();
    }

    seekSelect.addEventListener('change', togglePriorities);

    // Number inputs
    pairs.forEach(function(p) {
        p.input.addEventListener('input', function() { handleChange(p.input); });
    });

    // Range sliders
    pairs.forEach(function(p) {
        p.slider.addEventListener('input', function() {
            p.input.value = p.slider.value;
            handleChange(p.input);
        });
    });

    togglePriorities();
    syncSliders();
    updateTotal();
})();
</script>
@endsection
