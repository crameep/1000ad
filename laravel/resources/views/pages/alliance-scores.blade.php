@extends('layouts.game')

@section('content')
<table width="100%" cellpadding="2" cellspacing="0">
    <tr>
        <td class="header" colspan="5">Alliance Scores</td>
    </tr>
    <tr>
        <td class="small" colspan="5">
            Total alliances: {{ $totalAlliances }} |
            Players in alliances: {{ $playersInAlliances }} / {{ $totalPlayers }}
        </td>
    </tr>
    <tr style="background-color: #333;">
        <td class="small"><b>#</b></td>
        <td class="small">
            <a href="{{ route('game.alliance-scores', ['orderString' => 'tag']) }}"><b>Alliance</b></a>
        </td>
        <td class="small">
            <a href="{{ route('game.alliance-scores', ['orderString' => 'members']) }}"><b>Members</b></a>
        </td>
        <td class="small">
            <a href="{{ route('game.alliance-scores', ['orderString' => 'avg_score']) }}"><b>Avg Score</b></a>
        </td>
        <td class="small">
            <a href="{{ route('game.alliance-scores', ['orderString' => 'total_score']) }}"><b>Total Score</b></a>
        </td>
    </tr>

    @forelse($alliances as $index => $a)
    <tr style="background-color: {{ $index % 2 === 0 ? '#1a1a2e' : '#16213e' }};">
        <td class="small">{{ $index + 1 }}</td>
        <td class="small"><b>{{ $a->tag }}</b></td>
        <td class="small">{{ $a->members }}</td>
        <td class="small">{{ number_format($a->avg_score) }}</td>
        <td class="small">{{ number_format($a->total_score) }}</td>
    </tr>
    @empty
    <tr>
        <td colspan="5" class="small" style="text-align: center;">
            No alliances with 3+ members yet.
        </td>
    </tr>
    @endforelse
</table>

<br>
<div style="text-align: center;">
    <a href="{{ route('game.scores') }}">Empire Scores</a> |
    <a href="{{ route('game.battle-scores') }}">Battle Scores</a> |
    <a href="{{ route('game.recent-battles') }}">Recent Battles</a>
</div>
@endsection
