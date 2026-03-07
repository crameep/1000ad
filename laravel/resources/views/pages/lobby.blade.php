@extends('layouts.lobby')

@section('content')

{{-- Prize Pools & Revenue Split --}}
@if($totalRevenue > 0 || $userPayouts->isNotEmpty())
<div class="panel">
    <div class="panel-header">Prize Pools</div>
    <div class="panel-body">
        @if($totalRevenue > 0)
            <div class="lobby-pool-cards">
                <div class="lobby-pool-card">
                    <div class="lobby-pool-amount">${{ number_format($tournamentPool / 100, 2) }}</div>
                    <div class="lobby-pool-label">Tournament Pool</div>
                    <div class="lobby-pool-note">50% of all proceeds</div>
                </div>
                <div class="lobby-pool-card">
                    <div class="lobby-pool-amount lobby-pool-amount-secondary">${{ number_format($totalRevenue * 0.25 / 100, 2) }}</div>
                    <div class="lobby-pool-label">Game Prizes</div>
                    <div class="lobby-pool-note">25% split across games</div>
                </div>
                <div class="lobby-pool-card">
                    <div class="lobby-pool-amount lobby-pool-amount-muted">${{ number_format($totalRevenue * 0.25 / 100, 2) }}</div>
                    <div class="lobby-pool-label">Server Costs</div>
                    <div class="lobby-pool-note">25% for hosting</div>
                </div>
            </div>
        @endif
        @if($userPayouts->isNotEmpty())
            <div class="lobby-earnings-section">
                <div class="lobby-earnings-summary">
                    <div class="lobby-earnings-total">
                        <span class="lobby-earnings-amount">${{ number_format($totalEarnings / 100, 2) }}</span>
                        <span class="lobby-earnings-label">Your Earnings</span>
                    </div>
                    @php $pendingCount = $userPayouts->where('status', 'pending')->count(); @endphp
                    @if($pendingCount > 0)
                        <div class="lobby-earnings-pending">
                            {{ $pendingCount }} pending {{ Str::plural('payout', $pendingCount) }}
                        </div>
                    @endif
                </div>
                <div class="lobby-earnings-breakdown">
                    @foreach($userPayouts as $payout)
                        <div class="lobby-earnings-row">
                            <span>{{ $payout->game->name ?? 'Unknown Game' }} &mdash; {{ ordinal($payout->place) }} Place</span>
                            <span>
                                ${{ number_format($payout->amount_cents / 100, 2) }}
                                @if($payout->status === 'paid')
                                    <span class="text-success">Paid</span>
                                @elseif($payout->status === 'pending')
                                    <span class="text-warning">Pending</span>
                                @else
                                    <span class="text-muted">{{ ucfirst($payout->status) }}</span>
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endif

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
                        $playerCount = $entry['playerCount'];
                        $empiresList = $entry['empires'] ?? [];
                        $canCreateMore = $entry['canCreateMore'] ?? false;
                        $slotsUsed = $entry['slotsUsed'] ?? 1;
                        $slotsTotal = $entry['slotsTotal'] ?? 1;
                        $maxAllowed = $entry['maxAllowed'] ?? 1;
                        $prizePool = $entry['prizePool'] ?? 0;
                        $prizeSplit = $entry['prizeSplit'] ?? [];
                    @endphp
                    <div class="lobby-game-card">
                        <div class="lobby-game-header">
                            <span class="lobby-game-name">{{ $game->name }}</span>
                            <span class="lobby-preset-badge lobby-preset-{{ $game->preset }}">{{ ucfirst($game->preset) }}</span>
                        </div>
                        <div class="lobby-game-info">
                            @foreach($empiresList as $emp)
                                <div class="lobby-empire-row">
                                    <div>
                                        <b>{{ $emp['player']->name }}</b> ({{ $emp['empireName'] }})
                                        &mdash; Score: {{ number_format($emp['player']->score) }} / Turns: {{ $emp['player']->turns_free }}
                                    </div>
                                    <form action="{{ route('lobby.switch-empire', $emp['player']) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm">Play</button>
                                    </form>
                                </div>
                            @endforeach
                            @if($maxAllowed > 1)
                                <div class="text-muted text-small" style="margin-top:4px;">
                                    Empire slots: {{ $slotsUsed }}/{{ $slotsTotal }}
                                    @if($canCreateMore)
                                        &bull; <span style="color:#6a9a3a;">Can create more</span>
                                    @endif
                                </div>
                            @endif
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
                            <div class="lobby-prize-pool">
                                <span class="lobby-prize-pool-label">Prize Pool:</span>
                                <span class="lobby-prize-pool-amount">${{ number_format($prizePool / 100, 2) }}</span>
                                @if($prizePool > 0)
                                    <span class="lobby-prize-tiers">
                                        @foreach($prizeSplit as $tier)
                                            {{ ordinal($tier['place']) }}: ${{ number_format($tier['amount'] / 100, 2) }}@if(!$loop->last) &bull; @endif
                                        @endforeach
                                    </span>
                                @else
                                    <span class="lobby-prize-tiers text-muted">1st: 50% &bull; 2nd: 30% &bull; 3rd: 20%</span>
                                @endif
                            </div>
                        </div>
                        @if($maxAllowed > 1 && $slotsUsed < $slotsTotal && $canCreateMore)
                            <div class="lobby-game-actions">
                                <button type="button" class="btn btn-success btn-sm" onclick="toggleJoinForm({{ $game->id }})">New Empire</button>
                            </div>
                            {{-- Inline join form for additional empire --}}
                            <div class="lobby-join-form" id="join-form-{{ $game->id }}" style="display:none;">
                                <form action="{{ route('lobby.join', $game) }}" method="POST">
                                    @csrf
                                    <div style="margin-bottom:12px;">
                                        <label><b>Empire Name:</b></label><br>
                                        <input type="text" name="empire_name" required maxlength="20"
                                               pattern="[a-zA-Z0-9 _]+" title="Only letters, numbers, spaces and underscores"
                                               class="civ-empire-input" placeholder="Enter your empire name">
                                    </div>
                                    <div style="margin-bottom:12px;">
                                        <label><b>Choose Your Civilization:</b></label>
                                        <input type="hidden" name="civ" id="civ-input-{{ $game->id }}" value="1">
                                        <div class="civ-grid" id="civ-grid-{{ $game->id }}">
                                            @foreach($empires as $id => $name)
                                                @php $summary = $civSummaries[$id] ?? []; @endphp
                                                <div class="civ-card {{ $id === 1 ? 'civ-card-selected' : '' }}"
                                                     data-civ="{{ $id }}" data-game="{{ $game->id }}"
                                                     onclick="selectCiv({{ $game->id }}, {{ $id }})"
                                                     style="border-color: {{ $id === 1 ? ($summary['color'] ?? '#c9a85c') : 'transparent' }}">
                                                    <div class="civ-card-header">
                                                        <span class="civ-card-icon" style="color:{{ $summary['color'] ?? '#c9a85c' }}">{!! $summary['icon'] ?? '&#9733;' !!}</span>
                                                        <div>
                                                            <div class="civ-card-name" style="color:{{ $summary['color'] ?? '#c9a85c' }}">{{ $name }}</div>
                                                            <div class="civ-card-unit">{{ $uniqueUnits[$id] ?? 'Unique Unit' }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="civ-card-body">
                                                        @if(!empty($summary['strengths']))
                                                            <div class="civ-pros">
                                                                @foreach($summary['strengths'] as $s)
                                                                    <div>&#10003; {{ $s }}</div>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                        @if(!empty($summary['weaknesses']))
                                                            <div class="civ-cons">
                                                                @foreach($summary['weaknesses'] as $w)
                                                                    <div>&#10007; {{ $w }}</div>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="civ-form-actions">
                                        <button type="submit" class="btn btn-primary">Create Empire</button>
                                        <button type="button" class="btn" onclick="toggleJoinForm({{ $game->id }})">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        @elseif($maxAllowed > 1 && !$canCreateMore && $slotsUsed >= $slotsTotal)
                            <div class="lobby-game-actions">
                                <a href="{{ route('stripe.checkout', $game) }}" class="btn btn-sm" style="background:#635bff; color:#fff;">Buy Extra Slot</a>
                            </div>
                        @endif
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
                            <div class="lobby-prize-pool">
                                <span class="lobby-prize-pool-label">Prize Pool:</span>
                                <span class="lobby-prize-pool-amount">${{ number_format($game->prize_pool / 100, 2) }}</span>
                                @if($game->prize_pool > 0)
                                    <span class="lobby-prize-tiers">
                                        @foreach($game->prize_split as $tier)
                                            {{ ordinal($tier['place']) }}: ${{ number_format($tier['amount'] / 100, 2) }}@if(!$loop->last) &bull; @endif
                                        @endforeach
                                    </span>
                                @else
                                    <span class="lobby-prize-tiers text-muted">1st: 50% &bull; 2nd: 30% &bull; 3rd: 20%</span>
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
                                <div style="margin-bottom:12px;">
                                    <label><b>Empire Name:</b></label><br>
                                    <input type="text" name="empire_name" required maxlength="20"
                                           pattern="[a-zA-Z0-9 _]+" title="Only letters, numbers, spaces and underscores"
                                           class="civ-empire-input" placeholder="Enter your empire name">
                                </div>
                                <div style="margin-bottom:12px;">
                                    <label><b>Choose Your Civilization:</b></label>
                                    <input type="hidden" name="civ" id="civ-input-{{ $game->id }}" value="1">
                                    <div class="civ-grid" id="civ-grid-{{ $game->id }}">
                                        @foreach($empires as $id => $name)
                                            @php $summary = $civSummaries[$id] ?? []; @endphp
                                            <div class="civ-card {{ $id === 1 ? 'civ-card-selected' : '' }}"
                                                 data-civ="{{ $id }}" data-game="{{ $game->id }}"
                                                 onclick="selectCiv({{ $game->id }}, {{ $id }})"
                                                 style="border-color: {{ $id === 1 ? ($summary['color'] ?? '#c9a85c') : 'transparent' }}">
                                                <div class="civ-card-header">
                                                    <span class="civ-card-icon" style="color:{{ $summary['color'] ?? '#c9a85c' }}">{!! $summary['icon'] ?? '&#9733;' !!}</span>
                                                    <div>
                                                        <div class="civ-card-name" style="color:{{ $summary['color'] ?? '#c9a85c' }}">{{ $name }}</div>
                                                        <div class="civ-card-unit">{{ $uniqueUnits[$id] ?? 'Unique Unit' }}</div>
                                                    </div>
                                                </div>
                                                <div class="civ-card-body">
                                                    @if(!empty($summary['strengths']))
                                                        <div class="civ-pros">
                                                            @foreach($summary['strengths'] as $s)
                                                                <div>&#10003; {{ $s }}</div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                    @if(!empty($summary['weaknesses']))
                                                        <div class="civ-cons">
                                                            @foreach($summary['weaknesses'] as $w)
                                                                <div>&#10007; {{ $w }}</div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="civ-form-actions">
                                    <button type="submit" class="btn btn-primary">Create Empire</button>
                                    <button type="button" class="btn" onclick="toggleJoinForm({{ $game->id }})">Cancel</button>
                                </div>
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
function selectCiv(gameId, civId) {
    document.getElementById('civ-input-' + gameId).value = civId;
    var grid = document.getElementById('civ-grid-' + gameId);
    var cards = grid.querySelectorAll('.civ-card');
    for (var i = 0; i < cards.length; i++) {
        var card = cards[i];
        var isSel = parseInt(card.dataset.civ) === civId;
        card.classList.toggle('civ-card-selected', isSel);
        card.style.borderColor = isSel ? (card.querySelector('.civ-card-icon').style.color || '#c9a85c') : 'transparent';
    }
}
</script>
@endsection
