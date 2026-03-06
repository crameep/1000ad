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

    <x-advisor-panel :tips="$advisorTips" />

    <div class="info-text">
    You can send goods to the public market.
    You need market places to send goods.
    There is 5% fee after you sell the goods.
    </div>
    <div class="info-text">
    Your markets allow you to send {{ number_format($maxTrades) }} goods each month,
    @if($tradesRemaining == 0)<span class="text-danger">@endif
    out of which {{ number_format($tradesRemaining) }} are still available.
    @if($tradesRemaining == 0)</span>@endif
    </div>

    [<a href="{{ route('game.market', ['type' => 'buy']) }}">Switch to Buy Mode</a>]

    <div class="table-scroll">
    <table class="game-table">
    <form action="{{ route('game.market.sell') }}" method="POST">
        @csrf
        <tr>
            <td class="header">&nbsp;</td>
            <td class="header">You Have</td>
            <td class="header">Sell Amount</td>
            <td class="header">Price <span class="text-small">(per unit)</span></td>
            <td class="header">Min Price</td>
            <td class="header">Max Price</td>
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
                <td class="text-right">{{ number_format($info['qty']) }}</td>
                <td><input type="text" name="sell_{{ $key }}" size="8"></td>
                <td><input type="text" name="price_{{ $key }}" size="8"></td>
                <td class="text-right">{{ number_format($tradePrices[$key]['min']) }}</td>
                <td class="text-right">{{ number_format($tradePrices[$key]['max']) }}</td>
            </tr>
        @endforeach
        <tr>
            <td class="header text-right" colspan="4"><input type="submit" value="    Sell    "></td>
            <td class="header" colspan="2">&nbsp;</td>
        </tr>
    </form>
    </table>
    </div>

    {{-- Dispatched Caravans --}}
    @if($caravans->count() > 0)
        <div class="panel">
        <div class="panel-header">Dispatched Caravans</div>
        <div class="panel-body">
        @foreach($caravans as $caravan)
            <div class="caravan-item">
                @if($caravan->turns_remaining > 0)
                    <div>Caravans departed with:</div>
                    @foreach(['wood','food','iron','gold','tools','swords','bows','horses'] as $good)
                        @if($caravan->{$good} > 0)
                            <div>{{ number_format($caravan->{$good}) }} {{ $good }}</div>
                        @endif
                    @endforeach
                    <div>will reach their destination in {{ $caravan->turns_remaining }} turns.</div>
                @else
                    <div>You have:</div>
                    @foreach(['wood','food','iron','tools','maces','swords','bows','horses'] as $good)
                        @if($caravan->{$good} > 0)
                            <div>{{ number_format($caravan->{$good}) }} {{ $good }} for {{ number_format($caravan->{"{$good}_price"}) }} gold each</div>
                        @endif
                    @endforeach
                    <div>placed on the public market.</div>
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
            </div>
        @endforeach
        </div>
        </div>
    @endif

@else
    {{-- ============================================ --}}
    {{-- BUY MODE --}}
    {{-- ============================================ --}}
    <div class="page-title-bar">
        <h2>Global Market: Buy</h2>
        <a href="javascript:openHelp('trade')" class="help-link">Help</a>
    </div>

    <x-advisor-panel :tips="$advisorTips" />

    <div class="info-text">
    [<a href="{{ route('game.market', ['type' => 'sell']) }}">Switch to Sell Mode</a>]
    </div>

    Buy:
    @foreach($goodTypes as $good)
        <a href="#BUY{{ strtoupper($good) }}">{{ ucfirst($good) }}</a>
        @if(!$loop->last)&nbsp;&nbsp;&nbsp;&nbsp;@endif
    @endforeach

    @foreach($goodTypes as $good)
        <div class="info-text">
        <a name="BUY{{ strtoupper($good) }}"><b>Buy {{ ucfirst($good) }}</b> (You have {{ number_format($player->{$good}) }})</a>
        </div>

        @if($marketOffers[$good]->count() == 0)
            <span class="text-danger">There is no {{ $good }} available to buy.</span>
        @else
            <div class="table-scroll">
            <table class="game-table">
            <form action="{{ route('game.market.buy', ['id' => 0]) }}" method="POST">
                @csrf
                <input type="hidden" name="good" value="{{ $good }}">
                <tr>
                    <td class="header">Available</td>
                    <td class="header">You can buy</td>
                    <td class="header">Price <span class="text-small">(Per Unit)</span></td>
                    <td class="header">Buy Qty.</td>
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
                    <td class="header text-right" colspan="4">
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
        <div class="panel">
        <div class="panel-header">Incoming Caravans</div>
        <div class="panel-body">
        @foreach($incomingCaravans as $caravan)
            <div class="caravan-item">
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
            </div>
        @endforeach
        </div>
        </div>
    @endif
@endif

@endsection
