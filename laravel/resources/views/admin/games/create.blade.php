@extends('layouts.admin')

@section('content')
<h2>Create New Game</h2>

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
        {{-- Preset selector --}}
        <div class="form-group">
            <label>Quick Preset:</label>
            <div style="display:flex; gap:8px; margin-top:4px;">
                @foreach($presets as $key => $preset)
                    <button type="button" class="btn" onclick="applyPreset('{{ $key }}')">
                        {{ $preset['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        <form action="{{ route('admin.games.store') }}" method="POST">
            @csrf

            <div class="form-section">
                <h3>Basic Info</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Game Name *</label>
                        <input type="text" name="name" value="{{ old('name') }}" required maxlength="100" id="game-name">
                    </div>
                    <div class="form-group">
                        <label>Preset Type</label>
                        <select name="preset" id="game-preset">
                            <option value="standard">Standard</option>
                            <option value="blitz">Blitz</option>
                            <option value="tournament">Tournament</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" value="{{ old('description') }}" maxlength="500" id="game-description" style="width:100%;">
                </div>
            </div>

            <div class="form-section">
                <h3>Turn Settings</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Minutes Per Turn *</label>
                        <input type="number" name="minutes_per_turn" value="{{ old('minutes_per_turn', 5) }}" required min="1" max="60" id="f-mpt">
                    </div>
                    <div class="form-group">
                        <label>Max Turns Stored *</label>
                        <input type="number" name="max_turns_stored" value="{{ old('max_turns_stored', 500) }}" required min="10" max="9999" id="f-mts">
                    </div>
                    <div class="form-group">
                        <label>Starting Turns *</label>
                        <input type="number" name="start_turns" value="{{ old('start_turns', 100) }}" required min="0" max="9999" id="f-st">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Limits</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Max Attacks Per Turn *</label>
                        <input type="number" name="max_attacks" value="{{ old('max_attacks', 5) }}" required min="1" max="50" id="f-ma">
                    </div>
                    <div class="form-group">
                        <label>Max Build Queue *</label>
                        <input type="number" name="max_builds" value="{{ old('max_builds', 50) }}" required min="1" max="200" id="f-mb">
                    </div>
                    <div class="form-group">
                        <label>Alliance Max Members *</label>
                        <input type="number" name="alliance_max_members" value="{{ old('alliance_max_members', 10) }}" required min="0" max="50" id="f-amm">
                    </div>
                    <div class="form-group">
                        <label>Max Empires Per User *</label>
                        <input type="number" name="max_empires_per_user" value="{{ old('max_empires_per_user', 1) }}" required min="1" max="10" id="f-mepu">
                        <small style="color:#8a8a6d;">Players can purchase extra empire slots up to this limit</small>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Dates</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="datetime-local" name="start_date" value="{{ old('start_date', now()->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="datetime-local" name="end_date" value="{{ old('end_date', now()->addMonths(6)->format('Y-m-d\TH:i')) }}">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Prizes</h3>
                <p class="text-muted text-small">When a game ends, 25% of game revenue is split among the top 3 scorers: 1st 50%, 2nd 30%, 3rd 20%.</p>
            </div>

            <div class="form-section">
                <h3>Special Modes</h3>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="deathmatch_mode" value="1" {{ old('deathmatch_mode') ? 'checked' : '' }} id="f-dm">
                        Deathmatch Mode
                    </label>
                    <span class="text-small text-muted">Last empire standing wins. No new players after deathmatch starts.</span>
                </div>
                <div class="form-group">
                    <label>Deathmatch Start Date</label>
                    <input type="datetime-local" name="deathmatch_start" value="{{ old('deathmatch_start') }}">
                </div>
            </div>

            <div style="margin-top:16px;">
                <button type="submit" class="btn btn-primary">Create Game</button>
                <a href="{{ route('admin.games.index') }}" class="btn">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
var presets = @json($presets);

function applyPreset(key) {
    var p = presets[key];
    if (!p) return;
    document.getElementById('game-preset').value = key;
    document.getElementById('game-description').value = p.description || '';
    document.getElementById('f-mpt').value = p.minutes_per_turn;
    document.getElementById('f-mts').value = p.max_turns_stored;
    document.getElementById('f-st').value = p.start_turns;
    document.getElementById('f-ma').value = p.max_attacks;
    document.getElementById('f-mb').value = p.max_builds;
    document.getElementById('f-amm').value = p.alliance_max_members;
    document.getElementById('f-dm').checked = p.deathmatch_mode || false;
    if (!document.getElementById('game-name').value) {
        document.getElementById('game-name').value = p.label + ' Game';
    }
}
</script>
@endsection
