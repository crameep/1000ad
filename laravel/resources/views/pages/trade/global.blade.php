{{-- Global Market page - ported from globalMarket.cfm --}}
@extends('layouts.game')

@section('content')

@if($mType === 'sell')
    {{-- ============================================ --}}
    {{-- SELL MODE --}}
    {{-- ============================================ --}}
    <div class="page-title-bar">
        <h2>Global Market: Sell</h2>
        <a href="javascript:openHelp('trade')" class="help-link">Help</a>
    </div>

    You can send goods to the public market.<br>
    You need market places to send goods.<br>
    There is 5% fee after you sell the goods.
    <br>
    Your markets allow you to send {{ number_format($maxTrades) }} goods each month,<br>
    @if($tradesRemaining == 0)<span class="text-danger">@endif
    out of which {{ number_format($tradesRemaining) }} are still available.
    @if($tradesRemaining == 0)</span>@endif
    <br>
    <br>
    [<a href="{{ route('game.market', ['type' => 'buy']) }}">Switch to Buy Mode</a>]
    <br>
    <br>

    <div class="table-scroll">
    <table class="game-table">
    <form action="{{ route('game.market.sell') }}" method="POST">
        @csrf
        <tr>
            <td class="bg-header">&nbsp;</td>
            <td class="bg-header">You Have</td>
            <td class="bg-header">Sell Amount</td>
            <td class="bg-header">Price <span class="text-small">(per unit)</span></td>
            <td class="bg-header">Min Price</td>
            <td class="bg-header">Max Price</td>
        </tr>
        @php
            $goods = [
                'wood' => ['label' => 'Wood', 'qty' => $player->wood],
                'food' => ['label' => 'Food', 'qty' => $player->food],
                'iron' => ['label' => 'Iron', 'qty' => $player->iron],
                'tools' => ['label' => 'Tools', 'qty' => $player->tools],
                'maces' => ['label' => 'Maces', 'qty' => $player->maces],
                'swords' => ['label' => 'Swords', 'qty' => $player->swords],
                'bows' => ['label' => 'Bows', 'qty' => $player->bows],
                'horses' => ['label' => 'Horses', 'qty' => $player->horses],
            ];
        @endphp
        @foreach($goods as $key => $info)
            <tr>
                <td>{{ $info['label'] }}</td>
                <td align="right">{{ number_format($info['qty']) }}</td>
                <td><input type="text" name="sell_{{ $key }}" size="8"></td>
                <td><input type="text" name="price_{{ $key }}" size="8"></td>
                <td align="right">{{ number_format($tradePrices[$key]['min']) }}</td>
                <td align="right">{{ number_format($tradePrices[$key]['max']) }}</td>
            </tr>
        @endforeach
        <tr>
            <td class="bg-header" colspan="4" align="right"><input type="submit" value="    Sell    "></td>
            <td class="bg-header" colspan="2">&nbsp;</td>
        </tr>
    </form>
    </table>
    </div>

    <br><br>

    {{-- Dispatched Caravans --}}
    @if($caravans->count() > 0)
        <table class="game-table">
        <tr>
            <td class="bg-header">Dispatched Caravans:</td>
        </tr>
        @foreach($caravans as $caravan)
            <tr>
                <td>
                @if($caravan->turns_remaining > 0)
                    Caravans departed with:<br>
                    @foreach(['wood','food','iron','gold','tools','swords','bows','horses'] as $good)
                        @if($caravan->{$good} > 0)
                            {{ number_format($caravan->{$good}) }} {{ $good }}<br>
                        @endif
                    @endforeach
                    will reach their destination in {{ $caravan->turns_remaining }} turns.
                @else
                    You have:<br>
                    @foreach(['wood','food','iron','tools','maces','swords','bows','horses'] as $good)
                        @if($caravan->{$good} > 0)
                            {{ number_format($caravan->{$good}) }} {{ $good }} for {{ number_format($caravan->{"{$good}_price"}) }} gold each<br>
                        @endif
                    @endforeach
                    placed on the public market.
                    <br>
                    <span class="text-small">
                    <form action="{{ route('game.market.sell') }}" method="POST" class="inline-form">
                        @csrf
                        <input type="hidden" name="action" value="withdraw">
                        <input type="hidden" name="tid" value="{{ $caravan->id }}">
                        <a href="#" onclick="if(confirm('Withdraw from market? There is a 10% withdrawal fee.')) this.closest('form').submit(); return false;">Withdraw from market</a>
                    </span>
                    There is a 10% withdrawal fee.
                    </form>
                @endif
                </td>
            </tr>
        @endforeach
        </table>
    @endif

@else
    {{-- ============================================ --}}
    {{-- BUY MODE --}}
    {{-- ============================================ --}}
    <div class="page-title-bar">
        <h2>Global Market: Buy</h2>
        <a href="javascript:openHelp('trade')" class="help-link">Help</a>
    </div>

    [<a href="{{ route('game.market', ['type' => 'sell']) }}">Switch to Sell Mode</a>]
    <br><br>

    Buy:
    @foreach($goodTypes as $good)
        <a href="#BUY{{ strtoupper($good) }}">{{ ucfirst($good) }}</a>
        @if(!$loop->last)&nbsp;&nbsp;&nbsp;&nbsp;@endif
    @endforeach

    @foreach($goodTypes as $good)
        <br><br>
        <a name="BUY{{ strtoupper($good) }}"><b>Buy {{ ucfirst($good) }}</b> (You have {{ number_format($player->{$good}) }})</a>
        <br>

        @if($marketOffers[$good]->count() == 0)
            <span class="text-danger">There is no {{ $good }} available to buy.</span>
        @else
            <div class="table-scroll">
            <table class="game-table">
            <form action="{{ route('game.market.buy', ['id' => 0]) }}" method="POST">
                @csrf
                <input type="hidden" name="good" value="{{ $good }}">
                <tr>
                    <td class="bg-header">Available</td>
                    <td class="bg-header">You can buy</td>
                    <td class="bg-header">Price <span class="text-small">(Per Unit)</span></td>
                    <td class="bg-header">Buy Qty.</td>
                </tr>
                @foreach($marketOffers[$good] as $offer)
                    <tr>
                        <td>{{ number_format($offer->stuff) }}</td>
                        <td>{{ number_format($offer->can_afford) }}</td>
                        <td>{{ number_format($offer->stuff_price) }}</td>
                        <td><input type="text" size="8" name="qty{{ $offer->id }}"></td>
                    </tr>
                @endforeach
                <tr>
                    <td class="bg-header" colspan="4" align="right">
                        <input type="submit" value="Buy {{ ucfirst($good) }}">
                    </td>
                </tr>
            </form>
            </table>
            </div>
        @endif
    @endforeach

    {{-- Incoming Caravans --}}
    @if($incomingCaravans->count() > 0)
        <br><br>
        <table class="game-table">
        <tr>
            <td class="bg-header">Incoming Caravans:</td>
        </tr>
        @foreach($incomingCaravans as $caravan)
            <tr>
                <td>
                Transport with
                {{ number_format($caravan->wood) }} wood,
                {{ number_format($caravan->food) }} food,
                {{ number_format($caravan->iron) }} iron,
                {{ number_format($caravan->gold) }} gold,
                {{ number_format($caravan->tools) }} tools,
                {{ number_format($caravan->maces) }} maces,
                {{ number_format($caravan->swords) }} swords,
                {{ number_format($caravan->bows) }} bows and
                {{ number_format($caravan->horses) }} horses
                will reach your empire in {{ $caravan->turns_remaining }} turns.
                </td>
            </tr>
        @endforeach
        </table>
    @endif
@endif

@endsection
