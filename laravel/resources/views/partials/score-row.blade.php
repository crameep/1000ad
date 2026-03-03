{{-- Score row partial - ported from scores_show.cfm --}}
@php
    // Determine color based on relationship
    if ($p->id === $player->id) {
        $color = 'Aqua';
    } elseif ($p->alliance_id > 0 && $p->alliance_id == $myAllianceID) {
        $color = 'Fuchsia';
    } elseif ($p->alliance_id > 0 && in_array($p->alliance_id, $allies)) {
        $color = 'PeachPuff';
    } elseif ($p->alliance_id > 0 && in_array($p->alliance_id, $wars)) {
        $color = 'Crimson';
    } elseif ($p->turn <= 72) {
        $color = 'Yellow';
    } else {
        $color = 'White';
    }

    $isOnline = $p->last_load && abs(now()->diffInMinutes($p->last_load)) < 10;
@endphp
<tr>
    <td align="right"><span style="font-size:10px; color:{{ $color }}; cursor:pointer;" onclick="showMenu('{{ $p->id }}', '{{ addslashes($p->name) }}', event)">
        @if($isOnline)*@endif{{ $rowNum }}</span>
    </td>
    <td><span style="font-size:10px; color:{{ $color }}; cursor:pointer;" onclick="showMenu('{{ $p->id }}', '{{ addslashes($p->name) }}', event)">{{ $p->name }} ({{ $p->id }})</span></td>
    <td><span style="font-size:10px; color:{{ $color }};">{{ $empireNames[$p->civ] ?? 'Unknown' }}</span></td>
    @if(!$deathmatchMode && $allianceMaxMembers > 0)
    <td align="center"><span style="font-size:10px; color:{{ $color }};">
        @if($p->tag)
            @if($p->id == $p->leader_id)[{{ $p->tag }}]@else{{ $p->tag }}@endif
        @else
            &nbsp;
        @endif
    </span></td>
    @endif
    @if($p->total_land <= 0)
    <td align="center" colspan="5"><span style="font-size:10px; color:red;"><b>DEAD by {{ $p->killed_by_name }} ({{ $p->killed_by }})</b></span></td>
    @else
    <td align="right"><span style="font-size:10px; color:{{ $color }};">{{ number_format($p->research_levels) }}</span></td>
    <td align="right"><span style="font-size:10px; color:{{ $color }};">{{ number_format($p->total_land) }}</span></td>
    <td align="right"><span style="font-size:10px; color:{{ $color }};">{{ number_format($p->score) }}</span></td>
    @endif
</tr>
@if($rowNum % 5 === 0)
<tr><td colspan="9" style="background-color:darkslategray;" height="10"></td></tr>
@endif
