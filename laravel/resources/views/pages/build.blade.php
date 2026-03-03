{{-- Buildings page - ported from build.cfm --}}
@extends('layouts.game')

@section('content')
<div class="page-title-bar">
    <h2>Buildings</h2>
    <a href="javascript:openHelp('buildings')" class="help-link">Help</a>
</div>
<br>

{{-- Build / Demolish Form --}}
<table class="game-table">
<tr>
    <td class="header">Build or Demolish Buildings</td>
</tr>
<script type="text/javascript">
function showBuild() {
    var freePLand = {{ $freePlains }};
    var freeMLand = {{ $freeMountain }};
    var freeFLand = {{ $freeForest }};
    var gold = {{ $player->gold }};
    var iron = {{ $player->iron }};
    var wood = {{ $player->wood }};

    var sel = document.buildForm.building_no;
    if (sel.selectedIndex == 0) return;

    var box = sel.options[sel.selectedIndex];
    var s = "Your resources allow you to build ";
    var canBuild = 1000000000;
    var temp = 0;

    if (box.dataset.gold > 0) {
        temp = Math.floor(gold / box.dataset.gold);
        if (temp < canBuild) canBuild = temp;
    }
    if (box.dataset.iron > 0) {
        temp = Math.floor(iron / box.dataset.iron);
        if (temp < canBuild) canBuild = temp;
    }
    if (box.dataset.wood > 0) {
        temp = Math.floor(wood / box.dataset.wood);
        if (temp < canBuild) canBuild = temp;
    }

    if (box.dataset.land == "P") {
        temp = Math.floor(freePLand / box.dataset.sq);
        if (temp < canBuild) canBuild = temp;
    }
    if (box.dataset.land == "M") {
        temp = Math.floor(freeMLand / box.dataset.sq);
        if (temp < canBuild) canBuild = temp;
    }
    if (box.dataset.land == "F") {
        temp = Math.floor(freeFLand / box.dataset.sq);
        if (temp < canBuild) canBuild = temp;
    }

    s = s + " " + canBuild + " " + box.dataset.bname;
    document.getElementById('allowBuild').innerHTML = s;
}
</script>
<tr><td>
    <table width="100%">
    <tr><td>
        {{-- Build form --}}
        <form action="{{ route('game.build.submit') }}" method="POST" name="buildForm" id="buildActionForm">
            @csrf
            <input type="hidden" name="action_type" id="actionType" value="build">
            <select name="action_type_select" onchange="document.getElementById('actionType').value = this.value;">
                <option value="build">Build</option>
                <option value="demolish">Demolish</option>
            </select>
            <input type="text" name="qty" value="1" maxlength="8" size="5">
            <select name="building_no" onchange="showBuild()">
                <option value="0">--- Select a building to build or demolish ---</option>
                @foreach($displayOrder as $i)
                    @php $b = $buildings[$i]; @endphp
                    <option value="{{ $i }}"
                        data-bname="{{ $b['name'] }}"
                        data-wood="{{ $b['cost_wood'] }}"
                        data-iron="{{ $b['cost_iron'] }}"
                        data-gold="{{ $b['cost_gold'] }}"
                        data-sq="{{ $b['sq'] }}"
                        data-land="{{ $b['land'] }}">
                        {{ $b['name'] }} ({{ $b['cost_wood'] }} W, {{ $b['cost_iron'] }} I, {{ $b['cost_gold'] }} G, {{ $b['sq'] }} {{ $b['land'] }})
                    </option>
                @endforeach
            </select>
            <input type="submit" value="Go">
        </form>
    </td></tr>
    <tr>
        <td class="small" align="right">W - Wood, I - Iron, G - Gold, P - Plains, F - Forest, M - Mountains</td>
    </tr>
    <tr>
        <td class="header" id="allowBuild"></td>
    </tr>
    </table>
</td></tr>
</table>

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
        <td>{{ $b['name'] }} @if($bq->mission == 1)(Demolish)@endif</td>
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

<br>
<br>

{{-- Building List Table --}}
<form action="{{ route('game.build.status') }}" method="POST">
@csrf
<div class="table-scroll">
<table class="game-table building-table">
<tr>
    <td class="header">&nbsp;</td>
    <td class="header">Building</td>
    <td class="header">You Have</td>
    <td class="header">Land</td>
    <td class="header">Status</td>
    <td class="header">Working</td>
    <td class="header hide-mobile">Workers</td>
    <td class="header hide-mobile">Production</td>
    <td class="header hide-mobile">Consumption</td>
</tr>
@foreach($displayOrder as $i)
    @php
        $b = $buildings[$i];
        $stats = $buildingStats[$i];
        $color = $colors[$i];
    @endphp
    <tr>
        <td width="8" style="color:{{ $color }}"><b><a href="javascript:openHelp('buildings#{{ $b['db_column'] }}')">?</a></b></td>
        <td height="22" style="color:{{ $color }}">{{ $b['name'] }}</td>
        <td align="right" style="color:{{ $color }}">{{ number_format($stats['have']) }}</td>
        <td align="right" style="color:{{ $color }}">{{ $stats['land'] }} {{ $b['land'] }}</td>

        {{-- Status dropdown or empty --}}
        @if($b['allow_off'])
            <td style="color:{{ $color }}">
                <select name="{{ $b['db_column'] }}_status">
                    @for($s = 0; $s <= 10; $s++)
                        @php $sIndex = $s * 10; @endphp
                        <option value="{{ $s }}" @if($sIndex == $stats['status']) selected @endif>{{ $sIndex }} %</option>
                    @endfor
                </select>
            </td>
        @else
            <td style="color:{{ $color }}">&nbsp;</td>
        @endif

        <td style="color:{{ $color }}" align="right">{{ number_format($stats['bWorking']) }}</td>
        <td class="hide-mobile" style="color:{{ $color }}" align="right">{{ number_format($stats['workers']) }}</td>

        {{-- Production --}}
        <td class="hide-mobile" style="color:{{ $color }}">
            @if(!empty($stats['production']))
                {!! $stats['production'] !!}
            @else
                &nbsp;
            @endif
        </td>

        {{-- Consumption --}}
        <td class="hide-mobile" style="color:{{ $color }}">
            @if(!empty($stats['consumption']))
                {!! $stats['consumption'] !!}
            @else
                &nbsp;
            @endif
        </td>
    </tr>
@endforeach
<tr>
    <td class="header" colspan="2" align="right"><b>Totals</b></td>
    <td class="header" align="right">{{ number_format($totalBuildings) }}</td>
    <td class="header" align="right">{{ number_format($totalLand) }}</td>
    <td class="header"><input type="submit" value="Update" style="width:60px;"></td>
    <td class="header">&nbsp;</td>
    <td class="header hide-mobile" align="right">{{ number_format($totalWorkers) }}</td>
    <td class="header hide-mobile">&nbsp;</td>
    <td class="header hide-mobile">&nbsp;</td>
</tr>
</table>
</div>
</form>

<br>
<br>

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
