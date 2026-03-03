{{-- Explore page - ported from explore.cfm --}}
@extends('layouts.game')

@section('content')
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
    <td class="header" align="center" width="92%" style="font-size:16px;"><b>Explore</b></td>
    <td class="header" align="center" width="8%"><b><a href="javascript:openHelp('explore')">Help</a></b></td>
</tr>
</table>

<br>
<br>

{{-- Exploration queue table --}}
@if($explorations->isEmpty())
    <font face="verdana" size="2">You do not have any explorers sent.<br></font>
@else
    <table border="1" cellspacing="1" cellpadding="1" bordercolor="darkslategray">
    <tr>
        <td class="header">No. explorers</td>
        <td class="header">Land Sought</td>
        <td class="header">Months remaining</td>
        <td class="header">Land discovered</td>
    </tr>
    @foreach($explorations as $e)
    <tr>
        <td valign="top">
            @if($e->turn == 0)
                <font color="red">DONE</font>
            @else
                {{ number_format($e->people) }}
            @endif
        </td>
        <td valign="top">
            @if($e->seek_land == 0)All
            @elseif($e->seek_land == 1)Mountains
            @elseif($e->seek_land == 2)Forest
            @elseif($e->seek_land == 3)Plains
            @endif
        </td>
        <td valign="top">
            {{ $e->turn }}
            @if($e->turns_used == 0 && $e->created_on && $e->created_on->gt($cancelTime))
                <br>
                <form action="{{ route('game.explore.send') }}" method="POST" style="display:inline;">
                    @csrf
                    <input type="hidden" name="eflag" value="cancelExplore">
                    <input type="hidden" name="eID" value="{{ $e->id }}">
                    <a href="#" onclick="this.closest('form').submit(); return false;">Cancel Explorers</a>
                </form>
            @endif
        </td>
        <td valign="top">
            @if($e->seek_land == 0 || $e->seek_land == 1){{ number_format($e->mland ?? 0) }} Mountains<br>@endif
            @if($e->seek_land == 0 || $e->seek_land == 2){{ number_format($e->fland ?? 0) }} Forest<br>@endif
            @if($e->seek_land == 0 || $e->seek_land == 3){{ number_format($e->pland ?? 0) }} Plains<br>@endif
        </td>
    </tr>
    @endforeach
    </table>
@endif

<br>

{{-- Explorer stats --}}
You have {{ $totalExplorers }} explorers looking for land.<br>
You can have a maximum of {{ $maxExplorers }} explorers.<br>
Your food reserves allow you to send {{ $sendExplorers }} explorers.<br>
You can send {{ $canSend }} more explorers.<br>
You need {{ $foodPerExplorer }} food for each explorer.<br>
You have {{ number_format($player->horses) }} horses.<br>

<br>
<br>

{{-- Send explorers form --}}
<table border="1" cellpadding="1" cellspacing="1" bordercolor="darkslategray">
<tr><td class="header">
<form action="{{ route('game.explore.send') }}" method="POST">
    @csrf
    <input type="hidden" name="eflag" value="send_explorers">
    <font face="verdana" size="2">
    Send <input type="text" size="5" value="{{ $canSend }}" name="qty" style="font-size:xx-small"> explorers
    with
    <select name="withHorses" style="font-size:xx-small">
        <option value="0" @if($lastHorseSetting == 0) selected @endif>No Horses</option>
        <option value="1" @if($lastHorseSetting == 1) selected @endif>1X Horses</option>
        <option value="2" @if($lastHorseSetting == 2) selected @endif>2X Horses</option>
        <option value="3" @if($lastHorseSetting == 3) selected @endif>3X Horses</option>
    </select>
    to look for
    <select name="seekLand" style="font-size:xx-small">
        <option value="0" selected>All Land</option>
        <option value="1">Mountain Land</option>
        <option value="2">Forest Land</option>
        <option value="3">Plains Land</option>
    </select>
    <input type="submit" value="Send" style="font-size:xx-small">
    </font>
</form>
</td></tr>
</table>

<br>
@endsection
