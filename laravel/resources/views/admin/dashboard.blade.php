@extends('layouts.admin')

@section('content')
<h2>Dashboard</h2>

<div class="admin-stats">
    <div class="admin-stat-card">
        <div class="admin-stat-value">{{ $totalUsers }}</div>
        <div class="admin-stat-label">Total Users</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value">{{ $totalGames }}</div>
        <div class="admin-stat-label">Total Games</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value">{{ $activeGames }}</div>
        <div class="admin-stat-label">Active Games</div>
    </div>
</div>

<div class="panel" style="margin-top:20px;">
    <div class="panel-header">All Games</div>
    <div class="panel-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Preset</th>
                    <th>Status</th>
                    <th>Players</th>
                    <th>Turn Speed</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse($games as $game)
                    <tr>
                        <td>
                            <a href="{{ route('admin.games.edit', $game) }}">{{ $game->name }}</a>
                        </td>
                        <td>
                            <span class="lobby-preset-badge lobby-preset-{{ $game->preset }}">{{ ucfirst($game->preset) }}</span>
                        </td>
                        <td>
                            <span class="status-badge status-{{ $game->status }}">{{ ucfirst($game->status) }}</span>
                        </td>
                        <td>{{ $game->player_count }} active / {{ $game->total_players }} total</td>
                        <td>{{ $game->minutes_per_turn }} min/turn</td>
                        <td>{{ $game->created_at->format('M j, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No games yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
