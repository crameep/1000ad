{{-- Local Trade page - ported from localtrade.cfm --}}
@extends('layouts.game')

@section('content')
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
    <td class="header" align="center" width="92%"><b>Local Trade</b></td>
    <td class="header" align="center" width="8%"><b><a href="javascript:openHelp('trade')">Help</a></b></td>
</tr>
</table>

<center>
Local trade lets you trade small amount of necessary goods between your people.<br>
Number of goods traded depends on how many markets you have.<br>
<br>
You have traded {{ number_format($player->trades_this_turn) }} goods this turn<br>

@if($tradesRemaining > 0)
    and you can still trade {{ number_format($tradesRemaining) }} goods this turn.

    <br>
    <table border="1" cellspacing="0" cellpadding="2">
    <tr>
        <td bgcolor="darkslategray">&nbsp;</td>
        <td align="center" bgcolor="darkslategray"><b>Wood</b><br><font size="1">(You have {{ number_format($player->wood) }})</font></td>
        <td align="center" bgcolor="darkslategray"><b>Food</b><br><font size="1">(You have {{ number_format($player->food) }})</font></td>
        <td align="center" bgcolor="darkslategray"><b>Iron</b><br><font size="1">(You have {{ number_format($player->iron) }})</font></td>
        <td align="center" bgcolor="darkslategray"><b>Tools</b><br><font size="1">(You have {{ number_format($player->tools) }})</font></td>
        <td bgcolor="darkslategray">&nbsp;</td>
    </tr>
    {{-- Buy row --}}
    <form action="{{ route('game.localtrade.buy') }}" method="POST">
        @csrf
        <tr>
            <td>Buy</td>
            <td><input type="text" size="3" maxlength="10" name="buy_wood" value="0"> <font size="1">{{ number_format($woodBuyPrice) }} gold each</font></td>
            <td><input type="text" size="3" maxlength="10" name="buy_food" value="0"> <font size="1">{{ number_format($foodBuyPrice) }} gold each</font></td>
            <td><input type="text" size="3" maxlength="10" name="buy_iron" value="0"> <font size="1">{{ number_format($ironBuyPrice) }} gold each</font></td>
            <td><input type="text" size="3" maxlength="10" name="buy_tools" value="0"> <font size="1">{{ number_format($toolBuyPrice) }} gold each</font></td>
            <td><input type="submit" value="Buy"></td>
        </tr>
    </form>
    <tr><td colspan="6" bgcolor="darkslategray" height="5"></td></tr>
    {{-- Sell row --}}
    <form action="{{ route('game.localtrade.sell') }}" method="POST">
        @csrf
        <tr>
            <td>Sell</td>
            <td><input type="text" size="3" maxlength="10" name="sell_wood" value="0"> <font size="1">{{ number_format($woodSellPrice) }} gold each</font></td>
            <td><input type="text" size="3" maxlength="10" name="sell_food" value="0"> <font size="1">{{ number_format($foodSellPrice) }} gold each</font></td>
            <td><input type="text" size="3" maxlength="10" name="sell_iron" value="0"> <font size="1">{{ number_format($ironSellPrice) }} gold each</font></td>
            <td><input type="text" size="3" maxlength="10" name="sell_tools" value="0"> <font size="1">{{ number_format($toolSellPrice) }} gold each</font></td>
            <td><input type="submit" value="Sell"></td>
        </tr>
    </form>
    <tr><td colspan="6" bgcolor="darkslategray" height="5"></td></tr>
    </table>
@else
    and you already sold maximum amount available.
@endif

<br>
<br>
<font size="3"><b>Automatic Trade</b></font>
<br>
You might also automate your local trade and create automatic trades.<br>
Those trades will occur each time you end your turn.<br>
Maximum number of goods you can autotrade is the same<br>
as number of goods you can trade normally ({{ number_format($maxTrades) }}).<br>
You are currently auto trading {{ number_format($totalAutoTrade) }} goods<br>
and you can auto trade {{ number_format($remAutoTrade) }} more goods.

<table border="1" cellpadding="2" cellspacing="0">
<form action="{{ route('game.localtrade.autotrade') }}" method="POST">
    @csrf

    <tr>
        <td bgcolor="darkslategray">Type</td>
        <td align="center" bgcolor="darkslategray"><b>Wood</b></td>
        <td align="center" bgcolor="darkslategray"><b>Food</b></td>
        <td align="center" bgcolor="darkslategray"><b>Iron</b></td>
        <td align="center" bgcolor="darkslategray"><b>Tools</b></td>
        <td align="center" bgcolor="darkslategray"><b>Gold</b></td>
    </tr>
    <tr>
        <td nowrap>Auto Buy</td>
        <td><input type="text" name="auto_buy_wood" value="{{ number_format($player->auto_buy_wood) }}" size="10" maxlength="12"></td>
        <td><input type="text" name="auto_buy_food" value="{{ number_format($player->auto_buy_food) }}" size="10" maxlength="12"></td>
        <td><input type="text" name="auto_buy_iron" value="{{ number_format($player->auto_buy_iron) }}" size="10" maxlength="12"></td>
        <td><input type="text" name="auto_buy_tools" value="{{ number_format($player->auto_buy_tools) }}" size="10" maxlength="12"></td>
        <td>@if($autoTradeGoldUsed > 0)-@endif{{ number_format($autoTradeGoldUsed) }}</td>
    </tr>
    <tr><td colspan="6" bgcolor="darkslategray" height="5"></td></tr>
    <tr>
        <td nowrap>Auto Sell</td>
        <td><input type="text" name="auto_sell_wood" value="{{ number_format($player->auto_sell_wood) }}" size="10" maxlength="12"></td>
        <td><input type="text" name="auto_sell_food" value="{{ number_format($player->auto_sell_food) }}" size="10" maxlength="12"></td>
        <td><input type="text" name="auto_sell_iron" value="{{ number_format($player->auto_sell_iron) }}" size="10" maxlength="12"></td>
        <td><input type="text" name="auto_sell_tools" value="{{ number_format($player->auto_sell_tools) }}" size="10" maxlength="12"></td>
        <td>{{ number_format($autoTradeGoldEarned) }}</td>
    </tr>
    <tr bgcolor="darkslategray">
        <td><input type="submit" value="Update" style="font-size:xx-small;width:80px"></td>
        <td align="right" colspan="4">Total:</td>
        <td>{{ number_format($autoTradeGoldEarned - $autoTradeGoldUsed) }}</td>
    </tr>
</form>
</table>
</center>
@endsection
