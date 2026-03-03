{{-- Search page - ported from search.cfm --}}
@extends('layouts.game')

@section('content')
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
    <td class="header" align="center" width="100%" style="font-size:16px;"><b>Search</b></td>
</tr>
</table>

<br><br>

<table border="1" cellspacing="1" cellpadding="1" style="border-color:darkslategray;" width="300">
<form action="{{ route('game.search.submit') }}" method="POST">
    @csrf
<tr><td align="center" style="background-color:darkslategray;"><span style="color:white;">Search Players where:</span></td></tr>
<tr><td>
    <input type="radio" name="searchType" @checked($searchType === 'playerNumber' || $searchType === '') value="playerNumber">Player Number<br>
    <input type="radio" name="searchType" @checked($searchType === 'playerName') value="playerName">Player Name<br>
    <input type="radio" name="searchType" @checked($searchType === 'allianceName') value="allianceName">Alliance Name<br>
    <input type="radio" name="searchType" @checked($searchType === 'online') value="online">Player Online<br>
    &nbsp;&nbsp;&nbsp;&nbsp;is <input type="text" name="searchString" value="{{ $searchString }}" size="20" style="font-size:10px;">
</td></tr>
<tr><td style="background-color:darkslategray;" align="center"><input type="submit" value="Search" style="font-size:10px; width:80px;"></td></tr>
</form>
</table>

@if($results !== null)
<br><br>

@if($results->isEmpty())
    <span style="color:red;">No players found.</span>
@else
    <table border="1" cellpadding="1" cellspacing="1" width="400" style="border-color:darkslategray;">
    <tr>
        <td colspan="2" class="header" align="center">Search Results ({{ $results->count() }}):</td>
    </tr>
    <tr><td align="center">
    @foreach($results as $member)
        {{ $member->name }} (#{{ $member->id }}) <br>
        {{ $empireNames[$member->civ] ?? 'Unknown' }}<br>
        @if($member->is_online)
            <span style="color:red;">Online Now</span><br>
        @endif
        Rank: {{ $member->rank }}<br>
        Alliance: @if($member->leader_id > 0 && $member->id == $member->leader_id)[{{ $member->tag }}]@else{{ $member->tag ?? '' }}@endif<br>
        Score: {{ number_format($member->score) }}<br>
        Land: {{ number_format($member->total_land) }}<br>
        @if(!$loop->last)
            <hr noshade size="2" style="border:none; border-top:2px solid darkslategray;">
        @endif
    @endforeach
    </td></tr>
    @if($results->count() >= 100)
    <tr><td align="center"><span style="color:red;">More than 100 results found.<br>Displaying first 100 results.</span></td></tr>
    @endif
    </table>
    <br>
@endif
@endif
@endsection
