{{-- Public Rankings page - ported from rank.cfm --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>1000 A.D. Rankings</title>
    <link rel="stylesheet" href="/css/game.css">
</head>
<body>

@if($type === 'top10')
    <b>1000 AD Top 10 Empires for {{ $gameName }}</b><br>
    from {{ $startDate->format('m/d/Y') }} to {{ $endDate->format('m/d/Y') }}<br>
    There are {{ $totalPlayers }} players in the game.<br>
    <br>

    <div class="table-scroll">
    <table class="game-table">
    <tr>
        <td class="bg-header"><span class="text-white">&nbsp;</span></td>
        <td class="bg-header"><span class="text-white">Player</span></td>
        <td class="bg-header"><span class="text-white">Civilization</span></td>
        @if(!$deathmatchMode && $allianceMaxMembers > 0)
        <td class="bg-header"><span class="text-white">Alliance</span></td>
        @endif
        <td class="bg-header"><span class="text-white">R/L</span></td>
        <td class="bg-header"><span class="text-white">Land</span></td>
        <td class="bg-header"><span class="text-white">Score</span></td>
    </tr>
    @foreach($players as $idx => $p)
    @php
        $color = 'White';
        if ($p->turn <= 72) $color = 'Yellow';
    @endphp
    <tr>
        <td align="right"><span style="color:{{ $color }};">{{ $idx + 1 }}</span></td>
        <td><span style="color:{{ $color }};">{{ $p->name }} ({{ $p->id }})</span></td>
        <td><span style="color:{{ $color }};">{{ $empireNames[$p->civ] ?? 'Unknown' }}</span></td>
        @if(!$deathmatchMode && $allianceMaxMembers > 0)
        <td align="center"><span style="color:{{ $color }};">
            @if($p->tag)
                @if($p->id == $p->leader_id)[{{ $p->tag }}]@else{{ $p->tag }}@endif
            @else &nbsp;
            @endif
        </span></td>
        @endif
        @if($p->total_land <= 0)
        <td align="center" colspan="5"><span class="text-danger"><b>DEAD by {{ $p->killed_by_name }} ({{ $p->killed_by }})</b></span></td>
        @else
        <td align="right"><span style="color:{{ $color }};">{{ number_format($p->research_levels) }}</span></td>
        <td align="right"><span style="color:{{ $color }};">{{ number_format($p->total_land) }}</span></td>
        <td align="right"><span style="color:{{ $color }};">{{ number_format($p->score) }}</span></td>
        @endif
    </tr>
    @if(($idx + 1) % 5 === 0)
    <tr><td colspan="9" class="bg-header" height="10"></td></tr>
    @endif
    @endforeach
    </table>
    </div>

@else
    {{-- Alliance Rankings --}}
    <span class="text-lg"><b>Alliance Scores</b></span><br>
    (with at least 3 members)<br><br>

    @if(isset($alliances) && $alliances->count() > 0)
    <div class="table-scroll">
    <table class="game-table">
    <tr>
        <td class="bg-header">#</td>
        <td class="bg-header">Alliance</td>
        <td class="bg-header">Members</td>
        <td class="bg-header">Avg. Score</td>
        <td class="bg-header">Total Score</td>
    </tr>
    @foreach($alliances as $idx => $a)
    <tr>
        <td class="nowrap" align="right">{{ $idx + 1 }}&nbsp;</td>
        <td class="nowrap">{{ $a->tag }}</td>
        <td class="nowrap" align="right">{{ $a->members }}</td>
        <td class="nowrap" align="right">{{ number_format((int)$a->avg_score) }}</td>
        <td class="nowrap" align="right">{{ number_format((int)$a->total_score) }}</td>
    </tr>
    @endforeach
    </table>
    </div>
    @else
        There are no alliances with at least 3 members.
    @endif
@endif

</body>
</html>
