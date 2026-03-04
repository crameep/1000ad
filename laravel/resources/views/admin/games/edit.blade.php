@extends('layouts.admin')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
    <h2>Edit Game: {{ $game->name }}</h2>
    <span class="status-badge status-{{ $game->status }}">{{ ucfirst($game->status) }}</span>
</div>

<div class="admin-stats" style="margin-bottom:16px;">
    <div class="admin-stat-card">
        <div class="admin-stat-value">{{ $playerCount }}</div>
        <div class="admin-stat-label">Active Players</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value">{{ $game->minutes_per_turn }}m</div>
        <div class="admin-stat-label">Turn Speed</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value">{{ ucfirst($game->preset) }}</div>
        <div class="admin-stat-label">Preset</div>
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
    <div class="panel-header">Game Settings</div>
    <div class="panel-body">
        <form action="{{ route('admin.games.update', $game) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-section">
                <h3>Basic Info</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Game Name *</label>
                        <input type="text" name="name" value="{{ old('name', $game->name) }}" required maxlength="100">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="setup" {{ $game->status === 'setup' ? 'selected' : '' }}>Setup</option>
                            <option value="active" {{ $game->status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="paused" {{ $game->status === 'paused' ? 'selected' : '' }}>Paused</option>
                            <option value="ended" {{ $game->status === 'ended' ? 'selected' : '' }}>Ended</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" value="{{ old('description', $game->description) }}" maxlength="500" style="width:100%;">
                </div>
                <div class="form-group">
                    <label class="text-muted">Slug: {{ $game->slug }}</label>
                </div>
            </div>

            <div class="form-section">
                <h3>Turn Settings</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Minutes Per Turn *</label>
                        <input type="number" name="minutes_per_turn" value="{{ old('minutes_per_turn', $game->minutes_per_turn) }}" required min="1" max="60">
                    </div>
                    <div class="form-group">
                        <label>Max Turns Stored *</label>
                        <input type="number" name="max_turns_stored" value="{{ old('max_turns_stored', $game->max_turns_stored) }}" required min="10" max="9999">
                    </div>
                    <div class="form-group">
                        <label>Starting Turns *</label>
                        <input type="number" name="start_turns" value="{{ old('start_turns', $game->start_turns) }}" required min="0" max="9999">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Limits</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Max Attacks Per Turn *</label>
                        <input type="number" name="max_attacks" value="{{ old('max_attacks', $game->max_attacks) }}" required min="1" max="50">
                    </div>
                    <div class="form-group">
                        <label>Max Build Queue *</label>
                        <input type="number" name="max_builds" value="{{ old('max_builds', $game->max_builds) }}" required min="1" max="200">
                    </div>
                    <div class="form-group">
                        <label>Alliance Max Members *</label>
                        <input type="number" name="alliance_max_members" value="{{ old('alliance_max_members', $game->alliance_max_members) }}" required min="0" max="50">
                    </div>
                    <div class="form-group">
                        <label>Max Empires Per User *</label>
                        <input type="number" name="max_empires_per_user" value="{{ old('max_empires_per_user', $game->setting('max_empires_per_user') ?? 1) }}" required min="1" max="10">
                        <small style="color:#8a8a6d;">Players can purchase extra empire slots up to this limit</small>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Dates</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="datetime-local" name="start_date" value="{{ old('start_date', $game->start_date?->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="datetime-local" name="end_date" value="{{ old('end_date', $game->end_date?->format('Y-m-d\TH:i')) }}">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Tournament Prizes</h3>
                <p class="text-muted text-small">Prize payouts auto-assigned to top scorers when the game ends.</p>
                <div id="prize-tiers">
                    @php $tiers = $game->setting('prize_tiers') ?? []; @endphp
                    @forelse($tiers as $i => $tier)
                        <div class="form-row prize-tier-row">
                            <div class="form-group" style="flex:0 0 80px;">
                                <label>Place</label>
                                <input type="number" name="prize_place[]" value="{{ $tier['place'] }}" min="1" max="20">
                            </div>
                            <div class="form-group" style="flex:0 0 120px;">
                                <label>Amount ($)</label>
                                <input type="number" name="prize_amount[]" value="{{ $tier['amount_cents'] / 100 }}" min="0" step="0.01">
                            </div>
                            <div class="form-group" style="flex:0 0 auto; align-self:flex-end;">
                                <button type="button" class="btn btn-sm" onclick="this.closest('.prize-tier-row').remove()">Remove</button>
                            </div>
                        </div>
                    @empty
                        {{-- No prize tiers yet --}}
                    @endforelse
                </div>
                <button type="button" class="btn btn-sm" onclick="addPrizeTier()">+ Add Prize Tier</button>
            </div>

            <div class="form-section">
                <h3>Special Modes</h3>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="deathmatch_mode" value="1" {{ old('deathmatch_mode', $game->deathmatch_mode) ? 'checked' : '' }}>
                        Deathmatch Mode
                    </label>
                </div>
                <div class="form-group">
                    <label>Deathmatch Start Date</label>
                    <input type="datetime-local" name="deathmatch_start" value="{{ old('deathmatch_start', $game->deathmatch_start?->format('Y-m-d\TH:i')) }}">
                </div>
            </div>

            <div style="margin-top:16px; display:flex; gap:8px;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('admin.games.index') }}" class="btn">Back</a>
                <form action="{{ route('admin.games.duplicate', $game) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn">Duplicate</button>
                </form>
                @if($game->status !== 'ended')
                    <form action="{{ route('admin.games.destroy', $game) }}" method="POST" style="display:inline;"
                          onsubmit="return confirm('End this game? Players will no longer be able to play.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">End Game</button>
                    </form>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection

<script>
function addPrizeTier() {
    var container = document.getElementById('prize-tiers');
    var count = container.querySelectorAll('.prize-tier-row').length;
    var row = document.createElement('div');
    row.className = 'form-row prize-tier-row';
    row.innerHTML = '<div class="form-group" style="flex:0 0 80px;"><label>Place</label><input type="number" name="prize_place[]" value="' + (count + 1) + '" min="1" max="20"></div>' +
        '<div class="form-group" style="flex:0 0 120px;"><label>Amount ($)</label><input type="number" name="prize_amount[]" value="0" min="0" step="0.01"></div>' +
        '<div class="form-group" style="flex:0 0 auto; align-self:flex-end;"><button type="button" class="btn btn-sm" onclick="this.closest(\'.prize-tier-row\').remove()">Remove</button></div>';
    container.appendChild(row);
}
</script>
