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
<tr class="{{ $colorClass }}">
    <td class="text-right text-small" style="cursor:pointer;" onclick="showMenu('{{ $p->id }}', '{{ addslashes($p->name) }}', event)">
        @if($isOnline)*@endif{{ $rowNum }}
    </td>
    <td class="text-small" style="cursor:pointer;" onclick="showMenu('{{ $p->id }}', '{{ addslashes($p->name) }}', event)">{{ $p->name }} ({{ $p->id }})</td>
    <td class="text-small">{{ $empireNames[$p->civ] ?? 'Unknown' }}</td>
    @if(!$deathmatchMode && $allianceMaxMembers > 0)
    <td class="text-center text-small">
        @if($p->tag)
            @if($p->id == $p->leader_id)[{{ $p->tag }}]@else{{ $p->tag }}@endif
        @else
            &nbsp;
        @endif
    </td>
    @endif
    @if($p->total_land <= 0)
    <td class="text-center text-small text-error" colspan="5"><b>DEAD by {{ $p->killed_by_name }} ({{ $p->killed_by }})</b></td>
    @else
    <td class="text-right text-small">{{ number_format($p->research_levels) }}</td>
    <td class="text-right text-small">{{ number_format($p->total_land) }}</td>
    <td class="text-right text-small">{{ number_format($p->score) }}</td>
    @endif
</tr>
@if($rowNum % 5 === 0)
<tr><td colspan="9" class="bg-header" style="height:4px; padding:0;"></td></tr>
@endif
