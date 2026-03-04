@extends('layouts.admin')

@section('content')
<h2>User: {{ $user->login_name }}</h2>

<div class="panel">
    <div class="panel-header">Account Details</div>
    <div class="panel-body">
        <table style="width:auto;">
            <tr><td style="padding-right:20px;"><b>ID:</b></td><td>{{ $user->id }}</td></tr>
            <tr><td><b>Login Name:</b></td><td>{{ $user->login_name }}</td></tr>
            <tr><td><b>Email:</b></td><td>{{ $user->email }}</td></tr>
            <tr><td><b>Admin:</b></td><td>{{ $user->is_admin ? 'Yes' : 'No' }}</td></tr>
            <tr><td><b>Joined:</b></td><td>{{ $user->created_at?->format('M j, Y g:i A') ?? 'N/A' }}</td></tr>
        </table>
    </div>
</div>

<div class="panel" style="margin-top:16px;">
    <div class="panel-header">Game Memberships ({{ $players->count() }})</div>
    <div class="panel-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Game</th>
                    <th>Empire</th>
                    <th>Civ</th>
                    <th>Score</th>
                    <th>Turns</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($players as $player)
                    <tr>
                        <td>{{ $player->game->name ?? 'Unknown' }}</td>
                        <td>{{ $player->name }} (#{{ $player->id }})</td>
                        <td>{{ config('game.empires')[$player->civ] ?? 'Unknown' }}</td>
                        <td>{{ number_format($player->score) }}</td>
                        <td>{{ $player->turns_free }}</td>
                        <td>
                            @if($player->killed_by > 0)
                                <span class="text-error">Dead</span>
                            @else
                                <span class="text-success">Active</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.players.edit', $player) }}">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">This user has not joined any games.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:16px;">
    <a href="{{ route('admin.players.index') }}" class="btn">Back to Users</a>
</div>
@endsection
