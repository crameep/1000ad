{{-- Score row partial --}}
@php
    // Determine color class based on relationship
    if ($p->id === $player->id) {
        $colorClass = 'score-self';
    } elseif ($p->alliance_id > 0 && $p->alliance_id == $myAllianceID) {
        $colorClass = 'score-alliance';
    } elseif ($p->alliance_id > 0 && in_array($p->alliance_id, $allies)) {
        $colorClass = 'score-ally';
    } elseif ($p->alliance_id > 0 && in_array($p->alliance_id, $wars)) {
        $colorClass = 'score-enemy';
    } elseif ($p->turn <= 72) {
        $colorClass = 'score-protected';
    } else {
        $colorClass = 'score-default';
    }

    $isOnline = $p->last_load && abs(now()->diffInMinutes($p->last_load)) < 10;
@endphp
<tr class="{{ $colorClass }} scores-clickable" onclick="showMenu('{{ $p->id }}', '{{ addslashes($p->name) }}', event)">
    <td class="text-center scores-rank">
        @if($isOnline)<span class="online-dot"></span>@endif{{ $rowNum }}
    </td>
    <td>{{ $p->name }} ({{ $p->id }})</td>
    <td class="hide-mobile">{{ $empireNames[$p->civ] ?? 'Unknown' }}</td>
    @if(!$deathmatchMode && $allianceMaxMembers > 0)
    <td class="text-center hide-mobile">
        @if($p->tag)
            @if($p->id == $p->leader_id)[{{ $p->tag }}]@else{{ $p->tag }}@endif
        @endif
    </td>
    @endif
    @if($p->total_land <= 0)
    <td class="text-center text-error" colspan="3"><b>DEAD by {{ $p->killed_by_name }} ({{ $p->killed_by }})</b></td>
    @else
    <td class="text-right hide-mobile">{{ number_format($p->research_levels) }}</td>
    <td class="text-right">{{ number_format($p->total_land) }}</td>
    <td class="text-right">{{ number_format($p->score) }}</td>
    @endif
</tr>
@if($rowNum % 5 === 0)
<tr class="scores-stripe"><td colspan="7"></td></tr>
@endif
