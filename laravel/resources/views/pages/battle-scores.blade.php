@extends('layouts.game')

@section('content')
<div class="page-title-bar">
    <h2>Battle Scores - Top 25</h2>
</div>

<div class="table-scroll">
<table class="game-table">
    <tr class="bg-header">
        <td class="small"><b>#</b></td>
        <td class="small"><b>Empire</b></td>
        <td class="small">
            <a href="{{ route('game.battle-scores', ['ostring' => 'total_battles']) }}"><b>Total Battles</b></a>
        </td>
        <td class="small">
            <a href="{{ route('game.battle-scores', ['ostring' => 'total_wins']) }}"><b>Total Wins</b></a>
        </td>
        <td class="small">
            <a href="{{ route('game.battle-scores', ['ostring' => 'num_attacks']) }}"><b>Attacks</b></a>
        </td>
        <td class="small">
            <a href="{{ route('game.battle-scores', ['ostring' => 'num_attack_wins']) }}"><b>Attack Wins</b></a>
        </td>
        <td class="small">
            <a href="{{ route('game.battle-scores', ['ostring' => 'num_defenses']) }}"><b>Defenses</b></a>
        </td>
        <td class="small">
            <a href="{{ route('game.battle-scores', ['ostring' => 'num_defense_wins']) }}"><b>Defense Wins</b></a>
        </td>
    </tr>

    @forelse($topPlayers as $index => $p)
    <tr class="{{ $index % 2 === 0 ? 'row-even' : 'row-odd' }}">
        <td class="small">{{ $index + 1 }}</td>
        <td class="small">
            <a href="{{ route('game.search.submit', ['searchType' => 'empireNo', 'empireNo' => $p->id]) }}">
                {{ $p->name ?? "Empire #$p->id" }}
            </a>
        </td>
        <td class="small">{{ $p->num_attacks + $p->num_defenses }}</td>
        <td class="small">{{ $p->num_attack_wins + $p->num_defense_wins }}</td>
        <td class="small">{{ $p->num_attacks }}</td>
        <td class="small">{{ $p->num_attack_wins }}</td>
        <td class="small">{{ $p->num_defenses }}</td>
        <td class="small">{{ $p->num_defense_wins }}</td>
    </tr>
    @empty
    <tr>
        <td colspan="8" class="small text-center">No battle data yet.</td>
    </tr>
    @endforelse
</table>
</div>

@if($myStats)
<br>
<table class="game-table">
    <tr>
        <td class="bg-header" colspan="2">Your Battle Stats</td>
    </tr>
    <tr>
        <td class="small" width="50%">Total Battles:</td>
        <td class="small"><b>{{ $myStats->num_attacks + $myStats->num_defenses }}</b></td>
    </tr>
    <tr>
        <td class="small">Total Wins:</td>
        <td class="small"><b>{{ $myStats->num_attack_wins + $myStats->num_defense_wins }}</b></td>
    </tr>
    <tr>
        <td class="small">Attacks:</td>
        <td class="small">{{ $myStats->num_attacks }} ({{ $myStats->num_attack_wins }} wins)</td>
    </tr>
    <tr>
        <td class="small">Defenses:</td>
        <td class="small">{{ $myStats->num_defenses }} ({{ $myStats->num_defense_wins }} wins)</td>
    </tr>
</table>
@endif

<br>
<div class="text-center">
    <a href="{{ route('game.scores') }}">Empire Scores</a> |
    <a href="{{ route('game.alliance-scores') }}">Alliance Scores</a> |
    <a href="{{ route('game.recent-battles') }}">Recent Battles</a>
</div>
@endsection
