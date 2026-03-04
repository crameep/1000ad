{{-- Local Trade page - ported from localtrade.cfm --}}
@extends('layouts.game')

@section('content')
<div class="page-title-bar">
    <h2>Local Trade</h2>
    <a href="javascript:openHelp('trade')" class="help-link">Help</a>
</div>

<x-advisor-panel :tips="$advisorTips" />

<div class="text-center">
Local trade lets you trade small amount of necessary goods between your people.<br>
Number of goods traded depends on how many markets you have.<br>
<br>
You have traded {{ number_format($player->trades_this_turn) }} goods this turn<br>

@if($tradesRemaining > 0)
    and you can still trade {{ number_format($tradesRemaining) }} goods this turn.

    <br>
    <div class="table-scroll">
    <table class="game-table">
    <tr>
        <td class="bg-header">&nbsp;</td>
        <td align="center" class="bg-header"><b>Wood</b><br><span class="text-small">(You have {{ number_format($player->wood) }})</span></td>
        <td align="center" class="bg-header"><b>Food</b><br><span class="text-small">(You have {{ number_format($player->food) }})</span></td>
        <td align="center" class="bg-header"><b>Iron</b><br><span class="text-small">(You have {{ number_format($player->iron) }})</span></td>
        <td align="center" class="bg-header"><b>Tools</b><br><span class="text-small">(You have {{ number_format($player->tools) }})</span></td>
        <td class="bg-header">&nbsp;</td>
    </tr>
    {{-- Buy row --}}
    <form action="{{ route('game.localtrade.buy') }}" method="POST">
        @csrf
        <tr>
            <td>Buy</td>
            <td><input type="text" size="3" maxlength="10" name="buy_wood" value="0"> <span class="text-small">{{ number_format($woodBuyPrice) }} gold each</span></td>
            <td><input type="text" size="3" maxlength="10" name="buy_food" value="0"> <span class="text-small">{{ number_format($foodBuyPrice) }} gold each</span></td>
            <td><input type="text" size="3" maxlength="10" name="buy_iron" value="0"> <span class="text-small">{{ number_format($ironBuyPrice) }} gold each</span></td>
            <td><input type="text" size="3" maxlength="10" name="buy_tools" value="0"> <span class="text-small">{{ number_format($toolBuyPrice) }} gold each</span></td>
            <td><input type="submit" value="Buy"></td>
        </tr>
    </form>
    <tr><td colspan="6" class="bg-header" height="5"></td></tr>
    {{-- Sell row --}}
    <form action="{{ route('game.localtrade.sell') }}" method="POST">
        @csrf
        <tr>
            <td>Sell</td>
            <td><input type="text" size="3" maxlength="10" name="sell_wood" value="0"> <span class="text-small">{{ number_format($woodSellPrice) }} gold each</span></td>
            <td><input type="text" size="3" maxlength="10" name="sell_food" value="0"> <span class="text-small">{{ number_format($foodSellPrice) }} gold each</span></td>
            <td><input type="text" size="3" maxlength="10" name="sell_iron" value="0"> <span class="text-small">{{ number_format($ironSellPrice) }} gold each</span></td>
            <td><input type="text" size="3" maxlength="10" name="sell_tools" value="0"> <span class="text-small">{{ number_format($toolSellPrice) }} gold each</span></td>
            <td><input type="submit" value="Sell"></td>
        </tr>
    </form>
    <tr><td colspan="6" class="bg-header" height="5"></td></tr>
    </table>
    </div>
@else
    and you already sold maximum amount available.
@endif

<br>
<br>
<span class="text-lg"><b>Automatic Trade</b></span>
<br>
You might also automate your local trade and create automatic trades.<br>
Those trades will occur each time you end your turn.<br>
Maximum number of goods you can autotrade is the same<br>
as number of goods you can trade normally ({{ number_format($maxTrades) }}).<br>
You are currently auto trading {{ number_format($totalAutoTrade) }} goods<br>
and you can auto trade {{ number_format($remAutoTrade) }} more goods.

<div class="table-scroll">
<table class="game-table">
<form action="{{ route('game.localtrade.autotrade') }}" method="POST">
    @csrf

    <tr>
        <td class="bg-header">Type</td>
        <td align="center" class="bg-header"><b>Wood</b></td>
        <td align="center" class="bg-header"><b>Food</b></td>
        <td align="center" class="bg-header"><b>Iron</b></td>
        <td align="center" class="bg-header"><b>Tools</b></td>
        <td align="center" class="bg-header"><b>Gold</b></td>
    </tr>
    <tr>
        <td class="nowrap">Auto Buy</td>
        <td><input type="text" name="auto_buy_wood" value="{{ number_format($player->auto_buy_wood) }}" size="10" maxlength="12"></td>
        <td><input type="text" name="auto_buy_food" value="{{ number_format($player->auto_buy_food) }}" size="10" maxlength="12"></td>
        <td><input type="text" name="auto_buy_iron" value="{{ number_format($player->auto_buy_iron) }}" size="10" maxlength="12"></td>
        <td><input type="text" name="auto_buy_tools" value="{{ number_format($player->auto_buy_tools) }}" size="10" maxlength="12"></td>
        <td>@if($autoTradeGoldUsed > 0)-@endif{{ number_format($autoTradeGoldUsed) }}</td>
    </tr>
    <tr><td colspan="6" class="bg-header" height="5"></td></tr>
    <tr>
        <td class="nowrap">Auto Sell</td>
        <td><input type="text" name="auto_sell_wood" value="{{ number_format($player->auto_sell_wood) }}" size="10" maxlength="12"></td>
        <td><input type="text" name="auto_sell_food" value="{{ number_format($player->auto_sell_food) }}" size="10" maxlength="12"></td>
        <td><input type="text" name="auto_sell_iron" value="{{ number_format($player->auto_sell_iron) }}" size="10" maxlength="12"></td>
        <td><input type="text" name="auto_sell_tools" value="{{ number_format($player->auto_sell_tools) }}" size="10" maxlength="12"></td>
        <td>{{ number_format($autoTradeGoldEarned) }}</td>
    </tr>
    <tr class="bg-header">
        <td><input type="submit" value="Update"></td>
        <td align="right" colspan="4">Total:</td>
        <td>{{ number_format($autoTradeGoldEarned - $autoTradeGoldUsed) }}</td>
    </tr>
</form>
</table>
</div>
</div>
@endsection
