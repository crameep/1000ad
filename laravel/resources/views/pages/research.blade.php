{{-- Research page - ported from research.cfm --}}
@extends('layouts.game')

@section('content')
<div class="page-title-bar">
    <h2>Research</h2>
    <a href="javascript:openHelp('research')" class="help-link">Help</a>
</div>

<x-advisor-panel :tips="$advisorTips" />

@if(!$hasMageTowers)
    <div class="info-text text-error"><b>Build mage towers to start research.</b></div>
@else
    <div class="info-text">You have a total of {{ $totalResearchLevels }} research levels.</div>

    {{-- Current research selection --}}
    <div class="form-panel">
    <div class="form-header">Current Research:</div>
    <div class="form-body">
    <form action="{{ route('game.research.change') }}" method="POST">
        @csrf
        Set current research:
        <select name="newCurrentResearch">
            <option value="0">--- None ---</option>
            @foreach($researchNames as $id => $name)
                <option value="{{ $id }}" @if($id == $player->current_research) selected @endif>{{ $name }}</option>
            @endforeach
        </select>

        @if($player->current_research > 0)
            <div>
            {{ number_format($player->research_points) }} out of {{ number_format($nextLevelPoints) }}
            ({{ number_format($percent, 2) }}% complete)
            </div>
            <div class="text-small">
            <div>You have {{ number_format($activeMageTowers) }} mage towers active producing {{ number_format($researchProduced) }} research points
            and using {{ number_format($goldCost) }} gold every month.</div>
            @if($researchProduced > 0)
                <div>It takes your mage towers {{ number_format($turnsToNextLevel, 2) }} months to advance research level.</div>
            @endif
            </div>
        @endif
    </div>
    <div class="form-footer">
        <input type="submit" value="Change Research">
    </div>
    </div>
    </form>
@endif

{{-- Research levels table --}}
<div class="table-scroll">
<table class="game-table">
<tr>
    <td class="header">Research Name</td>
    <td class="header">Current</td>
    <td class="header">Description</td>
</tr>

@foreach($researchGroups as $groupName => $researchIds)
    <tr>
        <td colspan="3"><b>{{ $groupName }}</b></td>
    </tr>
    @foreach($researchIds as $id)
        <tr>
            <td>{{ $researchNames[$id] }}</td>
            <td class="text-center">{{ $player->{"research{$id}"} }}</td>
            <td>{{ $researchDescriptions[$id] }}</td>
        </tr>
    @endforeach
@endforeach

</table>
</div>
@endsection
