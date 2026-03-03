{{-- Army page - ported from army.cfm --}}
@extends('layouts.game')

@section('content')
<div class="page-title-bar">
    <h2>Army</h2>
    <a href="javascript:openHelp('army')" class="help-link">Help</a>
</div>

{{-- Capacity Info --}}
<div style="margin: 8px 0;">
    Your Forts and Town Centers can hold up to
    {{ number_format($maxSoldiers) }} units<br>
    and you can train {{ number_format($maxTrain) }} units at a time.
    <br>
    You are using {{ number_format($capacityPercent, 2) }}% of your maximum capacity.<br>
    You also have {{ number_format($player->swords) }} swords, {{ number_format($player->bows) }} bows,
    {{ number_format($player->horses) }} horses and {{ number_format($player->maces) }} maces.
</div>

{{-- Military Strength --}}
<hr>
<b>Military Strength:</b>
<table class="game-table">
<tr>
    <td class="header">&nbsp;</td>
    <td class="header">Attacking Power</td>
    <td class="header">Defense Power</td>
</tr>
<tr>
    <td>Army</td>
    <td>{{ number_format($attackPower) }}</td>
    <td>{{ number_format($defensePower) }}</td>
</tr>
<tr>
    <td>Catapults</td>
    <td>{{ number_format($cAttackPower) }}</td>
    <td>{{ number_format($cDefensePower) }}</td>
</tr>
<tr>
    <td>Thieves</td>
    <td>{{ number_format($tAttackPower) }}</td>
    <td>{{ number_format($tDefensePower) }}</td>
</tr>
</table>
<hr>

{{-- Training Queue --}}
@if($trainQueue->count() > 0)
<b>Training Queue:</b>
<table class="game-table">
<tr>
    <td class="header">Type</td>
    <td class="header">Number</td>
    <td class="header">Turns Remaining</td>
    <td class="header">&nbsp;</td>
</tr>
@foreach($trainQueue as $tq)
    <tr>
        <td>{{ $soldiers[$tq->soldier_type]['name'] ?? 'Unknown' }}</td>
        <td>{{ $tq->qty }}</td>
        <td>{{ $tq->turns_remaining }}</td>
        <td>
            <form action="{{ route('game.army.cancel') }}" method="POST" class="inline-form">
                @csrf
                <input type="hidden" name="q_id" value="{{ $tq->id }}">
                <a href="#" onclick="this.closest('form').submit(); return false;">Cancel</a>
            </form>
        </td>
    </tr>
@endforeach
</table>
@endif
<br>

{{-- Army Table --}}
<b>Your Army:</b>
<div class="table-scroll">
<table class="game-table">
<script type="text/javascript">
function disbandArmy() {
    var form = document.getElementById('armyForm');
    if (confirm("Are you sure you want to disband some of your army?")) {
        form.action = "{{ route('game.army.disband') }}";
        form.submit();
    }
}
</script>
<form action="{{ route('game.army.train') }}" method="POST" name="aForm" id="armyForm">
@csrf
<tr>
    <td class="header" valign="bottom">&nbsp;</td>
    <td class="header" valign="bottom">Unit Type</td>
    <td class="header" valign="bottom">You Have</td>
    <td class="header" valign="bottom">Upkeep<br>Cost</td>
    <td class="header" valign="bottom">Attacking</td>
    <td class="header" valign="bottom">Training</td>
    <td class="header" valign="bottom">Needed<br>To Train</td>
    <td class="header" valign="bottom">Max.<br>Train</td>
    <td class="header" valign="bottom">Qty.</td>
</tr>
@foreach($soldierDisplay as $i)
    @php $data = $armyData[$i]; @endphp
    <tr>
        <td><a href="javascript:openHelp('army#UNIT{{ $data['helpIndex'] }}')"><b>?</b></a></td>
        <td>{{ $data['soldier']['name'] }}</td>
        <td>{{ number_format($data['have']) }}</td>
        <td valign="top">
            {{ number_format($data['goldCost']) }} gold<br>
            {{ number_format($data['foodUsed']) }} food
        </td>
        <td>{{ number_format($data['attacking']) }}</td>
        <td>{{ $data['training'] }}</td>
        <td>{!! $data['neededToTrain'] !!}</td>
        <td>{{ number_format($data['maxTrain']) }}</td>
        <td align="center"><input type="text" name="qty{{ $i }}" value="" size="5"></td>
    </tr>
@endforeach
<tr>
    <td class="header" colspan="2"><a href="javascript:openHelp('army')">Units Help</a></td>
    <td class="header">{{ number_format($totalHave) }}</td>
    <td class="header" valign="top">
        {{ number_format($totalCost) }} gold<br>
        {{ number_format($totalFood) }} food
    </td>
    <td class="header">{{ number_format(array_sum($attackQty)) }}</td>
    <td class="header">{{ number_format(array_sum($trainQty)) }}</td>
    <td class="header">&nbsp;</td>
    <td class="header">{{ number_format($canTrain) }}</td>
    <td class="header" align="center"><input type="submit" value="Train" style="width:55px;"></td>
</tr>
<tr>
    <td colspan="9"><br>
        @if($canHold == 0)
            Your forts and town center are full.<br>
        @elseif($canHold > 0)
            You have room for {{ number_format($canHold) }} more soldiers.<br>
        @else
            <span class="text-error">{{ number_format(abs($canHold)) }} of your soldiers don't have any place to live.</span><br>
        @endif
        <br>
        <br>
        If you want to disband some of your soldiers,<br>
        fill up the quantities above and press the button below
        <br>
        <input type="button" value="Disband Army" onclick="disbandArmy()">
    </td>
</tr>
</form>
</table>
</div>
@endsection
