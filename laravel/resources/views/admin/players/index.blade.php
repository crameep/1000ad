@extends('layouts.admin')

@section('content')
<h2>Manage Users</h2>

<div class="panel">
    <div class="panel-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Login Name</th>
                    <th>Email</th>
                    <th>Admin</th>
                    <th>Games</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>
                            <a href="{{ route('admin.players.show', $user) }}">{{ $user->login_name }}</a>
                        </td>
                        <td class="text-small">{{ $user->email }}</td>
                        <td>
                            @if($user->is_admin)
                                <span class="text-warning">Yes</span>
                            @else
                                No
                            @endif
                        </td>
                        <td>{{ $user->player_count }}</td>
                        <td class="text-small">{{ $user->created_at?->format('M j, Y') ?? 'N/A' }}</td>
                        <td>
                            <a href="{{ route('admin.players.show', $user) }}">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
