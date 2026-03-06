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
            @if($e->seek_land == 0)All
            @elseif($e->seek_land == 1)Mountains
            @elseif($e->seek_land == 2)Forest
            @elseif($e->seek_land == 3)Plains
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
<form action="{{ route('game.explore.send') }}" method="POST">
    @csrf
    <input type="hidden" name="eflag" value="send_explorers">
    Send <input type="text" size="5" value="{{ $canSend }}" name="qty"> explorers
    with
    <select name="withHorses">
        <option value="0" @if($lastHorseSetting == 0) selected @endif>No Horses</option>
        <option value="1" @if($lastHorseSetting == 1) selected @endif>1X Horses</option>
        <option value="2" @if($lastHorseSetting == 2) selected @endif>2X Horses</option>
        <option value="3" @if($lastHorseSetting == 3) selected @endif>3X Horses</option>
    </select>
    to look for
    <select name="seekLand">
        <option value="0" selected>All Land</option>
        <option value="1">Mountain Land</option>
        <option value="2">Forest Land</option>
        <option value="3">Plains Land</option>
    </select>
    <input type="submit" value="Send">
</form>
</div>
</div>

@endsection
