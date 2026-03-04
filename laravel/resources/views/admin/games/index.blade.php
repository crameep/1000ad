@extends('layouts.admin')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
    <h2>Manage Games</h2>
    <a href="{{ route('admin.games.create') }}" class="btn btn-primary">+ New Game</a>
</div>

<div class="panel">
    <div class="panel-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Preset</th>
                    <th>Status</th>
                    <th>Players</th>
                    <th>Turn Speed</th>
                    <th>Dates</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($games as $game)
                    <tr>
                        <td>{{ $game->id }}</td>
                        <td><a href="{{ route('admin.games.edit', $game) }}">{{ $game->name }}</a></td>
                        <td>
                            <span class="lobby-preset-badge lobby-preset-{{ $game->preset }}">{{ ucfirst($game->preset) }}</span>
                        </td>
                        <td>
                            <span class="status-badge status-{{ $game->status }}">{{ ucfirst($game->status) }}</span>
                        </td>
                        <td>{{ $game->player_count }}</td>
                        <td>{{ $game->minutes_per_turn }} min</td>
                        <td class="text-small">
                            @if($game->start_date)
                                {{ $game->start_date->format('M j, Y') }}
                            @endif
                            @if($game->end_date)
                                &mdash; {{ $game->end_date->format('M j, Y') }}
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.games.edit', $game) }}">Edit</a>
                            &nbsp;
                            <form action="{{ route('admin.games.duplicate', $game) }}" method="POST" style="display:inline;">
                                @csrf
                                <a href="#" onclick="this.closest('form').submit(); return false;">Duplicate</a>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">No games created yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
