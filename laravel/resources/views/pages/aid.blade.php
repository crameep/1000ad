{{-- Aid page - ported from aid.cfm --}}
@extends('layouts.game')

@section('content')
<div class="page-title-bar">
    <h2>Aid</h2>
    <a href="javascript:openHelp('aid')" class="help-link">Help</a>
</div>

{{-- Sending aid --}}
<div class="info-text">
    You can send aid to your friends. You need market places to send goods.
    There is 5% fee for sending goods.
</div>
<div class="info-text">
    Your markets allow you to send {{ number_format($maxTrades) }} goods each month,
    @if($tradesRemaining == 0)<span class="text-error">@endif
    out of which {{ number_format($tradesRemaining) }} are still available.
    @if($tradesRemaining == 0)</span>@endif
</div>

<table class="game-table">
<form action="{{ route('game.aid.send') }}" method="POST">
    @csrf
    <tr>
        <td class="header">&nbsp;</td>
        <td class="header">You Have</td>
        <td class="header">Send</td>
    </tr>
    <tr>
        <td>Wood</td>
        <td class="text-right">{{ number_format($player->wood) }}</td>
        <td><input type="text" name="send_wood" size="8"></td>
    </tr>
    <tr>
        <td>Food</td>
        <td class="text-right">{{ number_format($player->food) }}</td>
        <td><input type="text" name="send_food" size="8"></td>
    </tr>
    <tr>
        <td>Iron</td>
        <td class="text-right">{{ number_format($player->iron) }}</td>
        <td><input type="text" name="send_iron" size="8"></td>
    </tr>
    <tr>
        <td>Gold</td>
        <td class="text-right">{{ number_format($player->gold) }}</td>
        <td><input type="text" name="send_gold" size="8"></td>
    </tr>
    <tr>
        <td>Tools</td>
        <td class="text-right">{{ number_format($player->tools) }}</td>
        <td><input type="text" name="send_tools" size="8"></td>
    </tr>
    <tr>
        <td>Maces</td>
        <td class="text-right">{{ number_format($player->maces) }}</td>
        <td><input type="text" name="send_maces" size="8"></td>
    </tr>
    <tr>
        <td>Swords</td>
        <td class="text-right">{{ number_format($player->swords) }}</td>
        <td><input type="text" name="send_swords" size="8"></td>
    </tr>
    <tr>
        <td>Bows</td>
        <td class="text-right">{{ number_format($player->bows) }}</td>
        <td><input type="text" name="send_bows" size="8"></td>
    </tr>
    <tr>
        <td>Horses</td>
        <td class="text-right">{{ number_format($player->horses) }}</td>
        <td><input type="text" name="send_horses" size="8"></td>
    </tr>
    <tr>
        <td colspan="2" class="header"> Send to empire #<input type="text" name="send_empire_no" size="3" value="0"></td>
        <td class="header"><input type="submit" value="Send"></td>
    </tr>
</form>
</table>

{{-- Dispatched Caravans --}}
@if($caravans->count() > 0)
    <div class="panel">
    <div class="panel-header">Dispatched Caravans</div>
    <div class="panel-body">
    @foreach($caravans as $caravan)
        <div class="caravan-item">
            Sent to {{ $caravan->recipient_name }} (#{{ $caravan->to_player_id }}) with
            {{ number_format($caravan->wood) }} wood,
            {{ number_format($caravan->food) }} food,
            {{ number_format($caravan->iron) }} iron,
            {{ number_format($caravan->gold) }} gold,
            {{ number_format($caravan->tools) }} tools,
            {{ number_format($caravan->maces) }} maces,
            {{ number_format($caravan->swords) }} swords,
            {{ number_format($caravan->bows) }} bows
            and {{ number_format($caravan->horses) }} horses
            and will reach their destination in {{ $caravan->turns_remaining }} turns.
            @if($caravan->turns_remaining == 3 && $caravan->created_on && $caravan->created_on->gt($cancelCutoff))
                <form action="{{ route('game.aid.send') }}" method="POST" class="inline-form">
                    @csrf
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="aid_id" value="{{ $caravan->id }}">
                    <a href="#" onclick="if(confirm('Cancel this aid?')) this.closest('form').submit(); return false;">Cancel this aid</a>
                </form>
            @endif
        </div>
    @endforeach
    </div>
    </div>
@endif
@endsection
