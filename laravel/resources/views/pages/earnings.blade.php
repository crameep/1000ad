@extends('layouts.game')

@section('content')
<div class="page-title">Earnings</div>

@if(session('success'))
    <div class="info-text text-success"><b>{{ session('success') }}</b></div>
@endif
@if(session('error'))
    <div class="info-text text-error"><b>{{ session('error') }}</b></div>
@endif

{{-- Revenue Split Overview --}}
<div class="panel">
    <div class="panel-header">Prize Pools</div>
    <div class="panel-body">
        @if($totalRevenue > 0)
            <div class="earnings-pool-cards">
                <div class="earnings-pool-card">
                    <div class="earnings-pool-amount earnings-pool-green">${{ number_format($tournamentPool / 100, 2) }}</div>
                    <div class="earnings-pool-label">Tournament Pool</div>
                    <div class="earnings-pool-note">50% of all proceeds</div>
                </div>
                <div class="earnings-pool-card">
                    <div class="earnings-pool-amount earnings-pool-blue">${{ number_format($gamePoolTotal / 100, 2) }}</div>
                    <div class="earnings-pool-label">Game Prizes</div>
                    <div class="earnings-pool-note">25% split across games</div>
                </div>
                <div class="earnings-pool-card earnings-pool-highlight">
                    <div class="earnings-pool-amount earnings-pool-green">${{ number_format($currentGamePool / 100, 2) }}</div>
                    <div class="earnings-pool-label">This Game's Pool</div>
                    <div class="earnings-pool-note">25% of this game's revenue</div>
                </div>
            </div>
            <div class="info-text text-small text-muted">
                Revenue is split: 50% tournament prizes, 25% per-game prizes for top scorers, 25% server costs.
                Prize pools grow as players purchase extra empire slots.
            </div>
        @else
            <div class="info-text text-muted">
                No revenue has been generated yet. Prize pools will appear here as players purchase extra empire slots.
            </div>
        @endif
    </div>
</div>

{{-- Your Earnings Summary --}}
<div class="panel">
    <div class="panel-header">Your Earnings</div>
    <div class="panel-body">
        <div class="earnings-summary-row">
            <div class="earnings-stat">
                <div class="earnings-stat-value earnings-pool-green">${{ number_format($totalEarned / 100, 2) }}</div>
                <div class="earnings-stat-label">Total Earned</div>
            </div>
            <div class="earnings-stat">
                <div class="earnings-stat-value">${{ number_format($totalPaid / 100, 2) }}</div>
                <div class="earnings-stat-label">Paid Out</div>
            </div>
            <div class="earnings-stat">
                <div class="earnings-stat-value" style="color:#e8c84c;">${{ number_format($totalPending / 100, 2) }}</div>
                <div class="earnings-stat-label">Pending</div>
            </div>
        </div>
    </div>
</div>

{{-- Stripe Connect --}}
<div class="panel">
    <div class="panel-header">Receive Payouts</div>
    <div class="panel-body">
        @if($connectStatus === 'not_connected')
            <div class="info-text">
                Connect your bank account to receive prize payouts directly via Stripe.
            </div>
            <form action="{{ route('game.earnings.connect') }}" method="POST" style="margin-top:8px;">
                @csrf
                <button type="submit" class="btn btn-success">Connect Bank Account</button>
            </form>
        @elseif($connectStatus === 'pending')
            <div class="info-text">
                <span class="text-warning">&#9888; Your Stripe account setup is incomplete.</span>
                Complete the onboarding process to start receiving payouts.
            </div>
            <form action="{{ route('game.earnings.connect') }}" method="POST" style="margin-top:8px;">
                @csrf
                <button type="submit" class="btn btn-primary">Complete Setup</button>
            </form>
        @elseif($connectStatus === 'active')
            <div class="info-text">
                <span class="text-success">&#10003; Your bank account is connected.</span>
                Pending prize payouts will be transferred to your account.
            </div>
            <a href="{{ route('game.earnings.dashboard') }}" class="btn btn-sm" style="margin-top:8px;">
                View Stripe Dashboard
            </a>
        @endif
    </div>
</div>

{{-- Payout History --}}
<div class="panel">
    <div class="panel-header">Payout History</div>
    <div class="panel-body" style="padding:0;">
        @if($payouts->isEmpty())
            <div class="info-text text-center text-muted" style="padding:16px;">
                No prize payouts yet. Finish in the top ranks when a game ends to earn prizes!
            </div>
        @else
            <table class="game-table" style="width:100%;">
                <tr>
                    <td class="header">Game</td>
                    <td class="header">Place</td>
                    <td class="header text-right">Amount</td>
                    <td class="header">Status</td>
                </tr>
                @foreach($payouts as $payout)
                    <tr class="{{ $loop->even ? 'row-even' : 'row-odd' }}">
                        <td>{{ $payout->game->name ?? 'Unknown' }}</td>
                        <td>{{ ordinal($payout->place) }}</td>
                        <td class="text-right">${{ number_format($payout->amount_cents / 100, 2) }}</td>
                        <td>
                            @if($payout->status === 'paid')
                                <span class="text-success">Paid</span>
                                @if($payout->paid_at)
                                    <span class="text-muted text-small">{{ $payout->paid_at->format('M j, Y') }}</span>
                                @endif
                            @elseif($payout->status === 'pending')
                                <span class="text-warning">Pending</span>
                            @else
                                <span class="text-muted">{{ ucfirst($payout->status) }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>
</div>
@endsection
