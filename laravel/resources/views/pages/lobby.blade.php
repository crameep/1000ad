@extends('layouts.lobby')

@section('content')

{{-- Your Active Games --}}
<div class="panel">
    <div class="panel-header">Your Active Games</div>
    <div class="panel-body">
        @if(empty($myGames))
            <p class="text-center text-muted">You haven't joined any games yet. Pick one below!</p>
        @else
            <div class="lobby-games">
                @foreach($myGames as $entry)
                    @php
                        $game = $entry['game'];
                        $playerInGame = $entry['player'];
                        $empireName = $entry['empireName'];
                        $playerCount = $entry['playerCount'];
                    @endphp
                    <div class="lobby-game-card">
                        <div class="lobby-game-header">
                            <span class="lobby-game-name">{{ $game->name }}</span>
                            <span class="lobby-preset-badge lobby-preset-{{ $game->preset }}">{{ ucfirst($game->preset) }}</span>
                        </div>
                        <div class="lobby-game-info">
                            <div><b>Empire:</b> {{ $playerInGame->name }} ({{ $empireName }})</div>
                            <div><b>Score:</b> {{ number_format($playerInGame->score) }} &nbsp; <b>Turns:</b> {{ $playerInGame->turns_free }}</div>
                            <div class="text-muted text-small">
                                {{ $playerCount }} players &bull;
                                1 turn / {{ $game->minutes_per_turn }} min &bull;
                                @if($game->end_date)
                                    Ends {{ $game->end_date->format('M j, Y') }}
                                @else
                                    No end date
                                @endif
                                @if($game->status !== 'active')
                                    &bull; <span class="text-warning">{{ ucfirst($game->status) }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="lobby-game-actions">
                            <form action="{{ route('lobby.switch', $game) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary">Play</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- Available Games to Join --}}
<div class="panel">
    <div class="panel-header">Join a Game</div>
    <div class="panel-body">
        @if($availableGames->isEmpty())
            <p class="text-center text-muted">No new games available to join right now.</p>
        @else
            <div class="lobby-games">
                @foreach($availableGames as $game)
                    <div class="lobby-game-card">
                        <div class="lobby-game-header">
                            <span class="lobby-game-name">{{ $game->name }}</span>
                            <span class="lobby-preset-badge lobby-preset-{{ $game->preset }}">{{ ucfirst($game->preset) }}</span>
                        </div>
                        <div class="lobby-game-info">
                            @if($game->description)
                                <div class="text-muted">{{ $game->description }}</div>
                            @endif
                            <div class="text-small">
                                {{ $game->player_count }} players &bull;
                                1 turn / {{ $game->minutes_per_turn }} min &bull;
                                Max {{ $game->max_turns_stored }} turns stored &bull;
                                @if($game->deathmatch_mode) Deathmatch &bull; @endif
                                @if($game->end_date)
                                    Ends {{ $game->end_date->format('M j, Y') }}
                                @else
                                    No end date
                                @endif
                            </div>
                        </div>
                        <div class="lobby-game-actions">
                            <button type="button" class="btn btn-success" onclick="toggleJoinForm({{ $game->id }})">Join</button>
                        </div>

                        {{-- Inline join form (hidden by default) --}}
                        <div class="lobby-join-form" id="join-form-{{ $game->id }}" style="display:none;">
                            <form action="{{ route('lobby.join', $game) }}" method="POST">
                                @csrf
                                <div style="margin-bottom:8px;">
                                    <label>Empire Name:</label><br>
                                    <input type="text" name="empire_name" required maxlength="20"
                                           pattern="[a-zA-Z0-9 _]+" title="Only letters, numbers, spaces and underscores"
                                           style="width:200px;">
                                </div>
                                <div style="margin-bottom:8px;">
                                    <label>Civilization:</label><br>
                                    <select name="civ" style="width:200px;">
                                        @foreach($empires as $id => $name)
                                            <option value="{{ $id }}">{{ $name }} ({{ $uniqueUnits[$id] ?? 'Unique Unit' }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Create Empire</button>
                                <button type="button" class="btn" onclick="toggleJoinForm({{ $game->id }})">Cancel</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- Links --}}
<div class="panel">
    <div class="panel-body text-center">
        <a href="/game/docs" target="_blank">Game Documentation</a>
        &nbsp;|&nbsp;
        <a href="{{ route('rankings', 'top10') }}">Public Rankings</a>
    </div>
</div>

<script>
function toggleJoinForm(gameId) {
    var form = document.getElementById('join-form-' + gameId);
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}
</script>
@endsection
