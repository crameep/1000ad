{{-- Great Wall page --}}
@extends('layouts.game')

@section('content')
<div class="page-title-bar">
    <h2>Great Wall</h2>
    <a href="javascript:openHelp('wall')" class="help-link">Help</a>
</div>

<x-advisor-panel :tips="$advisorTips" />

{{-- Wall Status Panel --}}
<div class="panel">
    <div class="panel-header">Wall Defense</div>
    <div class="panel-body">
        <div class="wall-protection">{{ $protection }}%</div>
        <div class="progress-bar" style="height: 10px; margin-top: 8px;">
            <div class="progress-bar-fill {{ $protection >= 100 ? '' : ($protection >= 50 ? 'warning' : 'danger') }}" style="width: {{ min($protection, 100) }}%"></div>
        </div>
        <div class="wall-progress-label">
            {{ number_format($player->wall) }} / {{ number_format($totalWall) }} units
        </div>
        @if($protection >= 100)
            <div style="text-align: center; margin-top: 6px;">
                <span class="wall-badge-full">Fully Fortified</span>
            </div>
        @endif
    </div>
</div>

{{-- Builder Assignment Panel --}}
<div class="form-panel">
    <div class="form-header">Builder Assignment</div>
    <div class="form-body">
        <form action="{{ route('game.wall.update') }}" method="POST">
            @csrf
            <div class="wall-input-row">
                <label>Builders dedicated to wall construction:</label>
                <div class="wall-input-group">
                    <input type="text" name="wallBuildPerTurn" value="{{ $player->wall_build_per_turn }}" size="4" class="input-sm">
                    <span>%</span>
                </div>
            </div>
            <div class="wall-summary">
                {{ $wallBuilders }} of {{ $builders }} builders will construct <strong>{{ $wallBuild }}</strong> units per month.
            </div>

            @if($wallBuild > 0)
            <div class="wall-cost-section">
                <div class="wall-cost-label">Monthly Cost</div>
                <div class="wall-cost-row">
                    <span class="wall-cost-item">&#x1FA99; {{ number_format($wallBuild * $wallCosts['gold']) }}</span>
                    <span class="wall-cost-item"><img src="{{ resourceIcon('wood') }}" alt="Wood"> {{ number_format($wallBuild * $wallCosts['wood']) }}</span>
                    <span class="wall-cost-item"><img src="{{ resourceIcon('iron') }}" alt="Iron"> {{ number_format($wallBuild * $wallCosts['iron']) }}</span>
                    <span class="wall-cost-item"><img src="{{ resourceIcon('wine') }}" alt="Wine"> {{ number_format($wallBuild * $wallCosts['wine']) }}</span>
                </div>
            </div>
            @endif

            <hr>
            <div class="wall-cost-section">
                <div class="wall-cost-label">Cost Per Unit</div>
                <div class="wall-cost-row">
                    <span class="wall-cost-item">&#x1FA99; {{ $wallCosts['gold'] }}</span>
                    <span class="wall-cost-item"><img src="{{ resourceIcon('wood') }}" alt="Wood"> {{ $wallCosts['wood'] }}</span>
                    <span class="wall-cost-item"><img src="{{ resourceIcon('iron') }}" alt="Iron"> {{ $wallCosts['iron'] }}</span>
                    <span class="wall-cost-item"><img src="{{ resourceIcon('wine') }}" alt="Wine"> {{ $wallCosts['wine'] }}</span>
                </div>
            </div>

            <div class="form-footer">
                <input type="submit" value="Update" class="btn">
            </div>
        </form>
    </div>
</div>
@endsection
