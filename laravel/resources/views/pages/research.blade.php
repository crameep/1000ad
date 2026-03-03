{{-- Research page - ported from research.cfm --}}
@extends('layouts.game')

@section('content')
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
    <td class="header" align="center" width="92%" style="font-size:16px;"><b>Research</b></td>
    <td class="header" align="center" width="8%"><b><a href="javascript:openHelp('research')">Help</a></b></td>
</tr>
</table>

<br>

@if(!$hasMageTowers)
    <font face="verdana" size="2" color="red">
    <b>Build mage towers to start research.</b>
    </font>
    <br>
    <br>
@else
    <font face="verdana" size="2">
    You have a total of {{ $totalResearchLevels }} research levels.<br>
    </font>
    <br>
    <br>

    {{-- Current research selection --}}
    <table border="1" cellpadding="1" cellspacing="1" bordercolor="darkslategray" width="100%">
    <form action="{{ route('game.research.change') }}" method="POST">
        @csrf
    <tr>
        <td class="header">Current Research:</td>
    </tr>
    <tr>
        <td valign="middle">
            Set current research:
            <select name="newCurrentResearch" style="font-size:xx-small">
                <option value="0">--- None ---</option>
                @foreach($researchNames as $id => $name)
                    <option value="{{ $id }}" @if($id == $player->current_research) selected @endif>{{ $name }}</option>
                @endforeach
            </select>

            @if($player->current_research > 0)
                <br>
                {{ number_format($player->research_points) }} out of {{ number_format($nextLevelPoints) }}
                ({{ number_format($percent, 2) }}% complete)
                <br>
                <font face="verdana" size="1">
                You have {{ number_format($activeMageTowers) }} mage towers active producing {{ number_format($researchProduced) }} research points
                <br>and using {{ number_format($goldCost) }} gold every month.
                @if($researchProduced > 0)
                    <br>It takes your mage towers {{ number_format($turnsToNextLevel, 2) }} months to advance research level.
                @endif
                </font>
            @endif
        </td>
    </tr>
    <tr><td align="center"><input type="submit" value="Change Research"></td></tr>
    </form>
    </table>

    <br>
    <br>
@endif

{{-- Research levels table --}}
<table border="1" cellpadding="1" cellspacing="1" bordercolor="darkslategray">
<tr>
    <td class="header">Research Name</td>
    <td class="header">Current</td>
    <td class="header">Description</td>
</tr>

@foreach($researchGroups as $groupName => $researchIds)
    <tr>
        <td colspan="10"><b>{{ $groupName }}</b></td>
    </tr>
    @foreach($researchIds as $id)
        <tr>
            <td>{{ $researchNames[$id] }}</td>
            <td align="center">{{ $player->{"research{$id}"} }}</td>
            <td>{{ $researchDescriptions[$id] }}</td>
        </tr>
    @endforeach
@endforeach

</table>
@endsection
