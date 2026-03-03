{{-- Search page - ported from search.cfm --}}
@extends('layouts.game')

@section('content')
<div class="page-title-bar">
    <h2>Search</h2>
</div>

<br><br>

<div class="form-panel" style="max-width:300px;">
<form action="{{ route('game.search.submit') }}" method="POST">
    @csrf
    <div class="bg-header text-center"><span>Search Players where:</span></div>
    <div class="form-panel-body">
        <input type="radio" name="searchType" @checked($searchType === 'playerNumber' || $searchType === '') value="playerNumber">Player Number<br>
        <input type="radio" name="searchType" @checked($searchType === 'playerName') value="playerName">Player Name<br>
        <input type="radio" name="searchType" @checked($searchType === 'allianceName') value="allianceName">Alliance Name<br>
        <input type="radio" name="searchType" @checked($searchType === 'online') value="online">Player Online<br>
        &nbsp;&nbsp;&nbsp;&nbsp;is <input type="text" name="searchString" value="{{ $searchString }}" size="20">
    </div>
    <div class="bg-header text-center"><input type="submit" value="Search" style="width:80px;"></div>
</form>
</div>

@if($results !== null)
<br><br>

@if($results->isEmpty())
    <span class="text-danger">No players found.</span>
@else
    <table class="game-table" style="max-width:400px;">
    <tr>
        <td colspan="2" class="header" align="center">Search Results ({{ $results->count() }}):</td>
    </tr>
    <tr><td align="center">
    @foreach($results as $member)
        {{ $member->name }} (#{{ $member->id }}) <br>
        {{ $empireNames[$member->civ] ?? 'Unknown' }}<br>
        @if($member->is_online)
            <span class="text-danger">Online Now</span><br>
        @endif
        Rank: {{ $member->rank }}<br>
        Alliance: @if($member->leader_id > 0 && $member->id == $member->leader_id)[{{ $member->tag }}]@else{{ $member->tag ?? '' }}@endif<br>
        Score: {{ number_format($member->score) }}<br>
        Land: {{ number_format($member->total_land) }}<br>
        @if(!$loop->last)
            <hr>
        @endif
    @endforeach
    </td></tr>
    @if($results->count() >= 100)
    <tr><td align="center"><span class="text-danger">More than 100 results found.<br>Displaying first 100 results.</span></td></tr>
    @endif
    </table>
    <br>
@endif
@endif
@endsection
