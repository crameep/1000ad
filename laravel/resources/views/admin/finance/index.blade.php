@extends('layouts.admin')

@section('content')
<h2>Finance Overview</h2>

{{-- Summary cards --}}
<div class="admin-stats">
    <div class="admin-stat-card">
        <div class="admin-stat-value">${{ number_format($totalRevenue / 100, 2) }}</div>
        <div class="admin-stat-label">Total Revenue</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value">${{ number_format($totalPrizesAssigned / 100, 2) }}</div>
        <div class="admin-stat-label">Prizes Assigned</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value">${{ number_format($totalPrizesPaid / 100, 2) }}</div>
        <div class="admin-stat-label">Prizes Paid</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value">${{ number_format(($totalRevenue - $totalPrizesPaid) / 100, 2) }}</div>
        <div class="admin-stat-label">Net Balance</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value">{{ $pendingPayouts }}</div>
        <div class="admin-stat-label">Pending Payouts</div>
    </div>
</div>

{{-- Per-game revenue breakdown --}}
@if($games->isNotEmpty())
<div class="panel" style="margin-top:16px;">
    <div class="panel-header">Revenue by Game</div>
    <div class="panel-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Game</th>
                    <th>Status</th>
                    <th style="text-align:right;">Revenue</th>
                    <th style="text-align:right;">Prizes</th>
                    <th style="text-align:right;">Net</th>
                </tr>
            </thead>
            <tbody>
                @foreach($games as $game)
                <tr>
                    <td><a href="{{ route('admin.games.edit', $game) }}">{{ $game->name }}</a></td>
                    <td><span class="status-badge status-{{ $game->status }}">{{ ucfirst($game->status) }}</span></td>
                    <td style="text-align:right;">${{ number_format($game->revenue_cents / 100, 2) }}</td>
                    <td style="text-align:right;">${{ number_format($game->prizes_paid_cents / 100, 2) }}</td>
                    <td style="text-align:right;">${{ number_format(($game->revenue_cents - $game->prizes_paid_cents) / 100, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Prize payouts --}}
<div class="panel" style="margin-top:16px;">
    <div class="panel-header">Prize Payouts</div>
    <div class="panel-body" style="padding:0;">
        @if($payouts->isEmpty())
            <p class="text-center text-muted" style="padding:16px;">No prize payouts yet.</p>
        @else
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Game</th>
                        <th>Player</th>
                        <th>User</th>
                        <th>Place</th>
                        <th style="text-align:right;">Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payouts as $payout)
                    <tr>
                        <td>{{ $payout->game->name ?? '?' }}</td>
                        <td>{{ $payout->player->name ?? '?' }} #{{ $payout->player_id }}</td>
                        <td>{{ $payout->user->login_name ?? '?' }}</td>
                        <td>{{ ordinal($payout->place) }}</td>
                        <td style="text-align:right;">${{ number_format($payout->amount_cents / 100, 2) }}</td>
                        <td><span class="status-badge status-{{ $payout->status }}">{{ ucfirst($payout->status) }}</span></td>
                        <td>
                            @if($payout->status === 'pending')
                                <form action="{{ route('admin.finance.mark-paid', $payout) }}" method="POST" class="inline-form" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">Mark Paid</button>
                                </form>
                                <form action="{{ route('admin.finance.cancel-payout', $payout) }}" method="POST" class="inline-form" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm">Cancel</button>
                                </form>
                            @elseif($payout->status === 'paid')
                                <span class="text-muted text-small">{{ $payout->paid_at?->format('M j, Y') }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

{{-- Recent transactions --}}
<div class="panel" style="margin-top:16px;">
    <div class="panel-header">Recent Transactions</div>
    <div class="panel-body" style="padding:0;">
        @if($transactions->isEmpty())
            <p class="text-center text-muted" style="padding:16px;">No transactions yet.</p>
        @else
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Game</th>
                        <th>Type</th>
                        <th style="text-align:right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $txn)
                    <tr>
                        <td>{{ $txn->created_at->format('M j, Y H:i') }}</td>
                        <td>{{ $txn->user->login_name ?? '?' }}</td>
                        <td>{{ $txn->game->name ?? '-' }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $txn->type)) }}</td>
                        <td style="text-align:right;">${{ number_format($txn->amount_cents / 100, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
