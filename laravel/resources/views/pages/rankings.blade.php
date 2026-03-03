{{-- Public Rankings page - ported from rank.cfm --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>1000 A.D. Rankings</title>
    <style>
        body { background-color: #000; color: #fff; font-family: Verdana, sans-serif; font-size: 12px; }
        a { color: aqua; text-decoration: none; }
        a:hover { color: red; }
        td { font-family: Verdana, sans-serif; font-size: 10px; }
        td.header {
            background-color: darkslategray;
            color: white;
            font-size: 10px;
        }
    </style>
</head>
<body>

@if($type === 'top10')
    <b>1000 AD Top 10 Empires for {{ $gameName }}</b><br>
    from {{ $startDate->format('m/d/Y') }} to {{ $endDate->format('m/d/Y') }}<br>
    There are {{ $totalPlayers }} players in the game.<br>
    <br>

    <table border="1" cellspacing="1" cellpadding="1" style="border-color:darkslategray;">
    <tr>
        <td style="background-color:darkslategray;"><span style="color:white;">&nbsp;</span></td>
        <td style="background-color:darkslategray;"><span style="color:white;">Player</span></td>
        <td style="background-color:darkslategray;"><span style="color:white;">Civilization</span></td>
        @if(!$deathmatchMode && $allianceMaxMembers > 0)
        <td style="background-color:darkslategray;"><span style="color:white;">Alliance</span></td>
        @endif
        <td style="background-color:darkslategray;"><span style="color:white;">R/L</span></td>
        <td style="background-color:darkslategray;"><span style="color:white;">Land</span></td>
        <td style="background-color:darkslategray;"><span style="color:white;">Score</span></td>
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
        <td align="center" colspan="5"><span style="color:red;"><b>DEAD by {{ $p->killed_by_name }} ({{ $p->killed_by }})</b></span></td>
        @else
        <td align="right"><span style="color:{{ $color }};">{{ number_format($p->research_levels) }}</span></td>
        <td align="right"><span style="color:{{ $color }};">{{ number_format($p->total_land) }}</span></td>
        <td align="right"><span style="color:{{ $color }};">{{ number_format($p->score) }}</span></td>
        @endif
    </tr>
    @if(($idx + 1) % 5 === 0)
    <tr><td colspan="9" style="background-color:darkslategray;" height="10"></td></tr>
    @endif
    @endforeach
    </table>

@else
    {{-- Alliance Rankings --}}
    <span style="font-size:16px;"><b>Alliance Scores</b></span><br>
    (with at least 3 members)<br><br>

    @if(isset($alliances) && $alliances->count() > 0)
    <table border="1" cellpadding="2" cellspacing="0" style="border-color:darkslategray;">
    <tr>
        <td class="header">#</td>
        <td class="header">Alliance</td>
        <td class="header">Members</td>
        <td class="header">Avg. Score</td>
        <td class="header">Total Score</td>
    </tr>
    @foreach($alliances as $idx => $a)
    <tr>
        <td nowrap align="right">{{ $idx + 1 }}&nbsp;</td>
        <td nowrap>{{ $a->tag }}</td>
        <td nowrap align="right">{{ $a->members }}</td>
        <td nowrap align="right">{{ number_format((int)$a->avg_score) }}</td>
        <td nowrap align="right">{{ number_format((int)$a->total_score) }}</td>
    </tr>
    @endforeach
    </table>
    @else
        There are no alliances with at least 3 members.
    @endif
@endif

</body>
</html>
