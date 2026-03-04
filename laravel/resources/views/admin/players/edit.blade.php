@extends('layouts.admin')

@section('content')
<h2>Edit Player: {{ $player->name }} (#{{ $player->id }})</h2>

<div class="admin-stats" style="margin-bottom:16px;">
    <div class="admin-stat-card">
        <div class="admin-stat-value">{{ number_format($player->score) }}</div>
        <div class="admin-stat-label">Score</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value">{{ $empireName }}</div>
        <div class="admin-stat-label">Civilization</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value">{{ $player->game->name ?? 'Unknown' }}</div>
        <div class="admin-stat-label">Game</div>
    </div>
</div>

@if($errors->any())
    <div class="admin-alert admin-alert-error">
        @foreach($errors->all() as $error)
            {{ $error }}<br>
        @endforeach
    </div>
@endif

<div class="panel">
    <div class="panel-header">Player Resources</div>
    <div class="panel-body">
        <form action="{{ route('admin.players.update', $player) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-row">
                <div class="form-group">
                    <label>Gold</label>
                    <input type="number" name="gold" value="{{ old('gold', $player->gold) }}" min="0">
                </div>
                <div class="form-group">
                    <label>Wood</label>
                    <input type="number" name="wood" value="{{ old('wood', $player->wood) }}" min="0">
                </div>
                <div class="form-group">
                    <label>Food</label>
                    <input type="number" name="food" value="{{ old('food', $player->food) }}" min="0">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Iron</label>
                    <input type="number" name="iron" value="{{ old('iron', $player->iron) }}" min="0">
                </div>
                <div class="form-group">
                    <label>Tools</label>
                    <input type="number" name="tools" value="{{ old('tools', $player->tools) }}" min="0">
                </div>
                <div class="form-group">
                    <label>People</label>
                    <input type="number" name="people" value="{{ old('people', $player->people) }}" min="0">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Turns Available</label>
                    <input type="number" name="turns_free" value="{{ old('turns_free', $player->turns_free) }}" min="0">
                </div>
                <div class="form-group">
                    <label>Killed By (0 = alive)</label>
                    <input type="number" name="killed_by" value="{{ old('killed_by', $player->killed_by) }}" min="0">
                </div>
            </div>

            <div style="margin-top:16px;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<div class="panel" style="margin-top:16px;">
    <div class="panel-header">Grant Bonus Turns</div>
    <div class="panel-body">
        <form action="{{ route('admin.players.grant-turns', $player) }}" method="POST" style="display:flex; gap:8px; align-items:end;">
            @csrf
            <div class="form-group" style="margin:0;">
                <label>Turns to Grant</label>
                <input type="number" name="turns" value="100" min="1" max="9999" style="width:100px;">
            </div>
            <button type="submit" class="btn btn-success">Grant Turns</button>
        </form>
    </div>
</div>

<div class="panel" style="margin-top:16px;">
    <div class="panel-header">Player Info</div>
    <div class="panel-body">
        <table style="width:auto;">
            <tr><td style="padding-right:20px;"><b>Empire:</b></td><td>{{ $player->name }} (#{{ $player->id }})</td></tr>
            <tr><td><b>Civilization:</b></td><td>{{ $empireName }}</td></tr>
            <tr><td><b>Game:</b></td><td>{{ $player->game->name ?? 'Unknown' }} (ID: {{ $player->game_id }})</td></tr>
            <tr><td><b>Turn:</b></td><td>{{ $player->turn }}</td></tr>
            <tr><td><b>Score:</b></td><td>{{ number_format($player->score) }}</td></tr>
            <tr><td><b>Land:</b></td><td>P: {{ $player->pland }}, F: {{ $player->fland }}, M: {{ $player->mland }}</td></tr>
            <tr><td><b>Last Turn:</b></td><td>{{ $player->last_turn?->format('M j, Y g:i A') ?? 'Never' }}</td></tr>
            <tr><td><b>Last Load:</b></td><td>{{ $player->last_load?->format('M j, Y g:i A') ?? 'Never' }}</td></tr>
            <tr><td><b>Created:</b></td><td>{{ $player->created_on?->format('M j, Y g:i A') ?? 'N/A' }}</td></tr>
            <tr><td><b>Status:</b></td><td>{{ $player->killed_by > 0 ? 'Dead (killed by #' . $player->killed_by . ')' : 'Active' }}</td></tr>
        </table>
    </div>
</div>

<div style="margin-top:16px;">
    <a href="{{ route('admin.players.index') }}" class="btn">Back to Users</a>
</div>
@endsection
