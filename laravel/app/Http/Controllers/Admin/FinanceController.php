<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\PrizePayout;
use App\Models\Transaction;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function index()
    {
        $totalRevenue = Transaction::sum('amount_cents');
        $totalPrizesAssigned = PrizePayout::sum('amount_cents');
        $totalPrizesPaid = PrizePayout::where('status', 'paid')->sum('amount_cents');
        $pendingPayouts = PrizePayout::where('status', 'pending')->count();

        // Per-game breakdown
        $games = Game::orderBy('created_at', 'desc')->get()->map(function ($game) {
            $game->revenue_cents = Transaction::where('game_id', $game->id)->sum('amount_cents');
            $game->prizes_assigned_cents = PrizePayout::where('game_id', $game->id)->sum('amount_cents');
            $game->prizes_paid_cents = PrizePayout::where('game_id', $game->id)->where('status', 'paid')->sum('amount_cents');
            return $game;
        })->filter(fn($g) => $g->revenue_cents > 0 || $g->prizes_assigned_cents > 0);

        // All payouts
        $payouts = PrizePayout::with(['user', 'game', 'player'])
            ->orderByDesc('created_at')
            ->get();

        // Recent transactions
        $transactions = Transaction::with(['user', 'game'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('admin.finance.index', [
            'totalRevenue' => $totalRevenue,
            'totalPrizesAssigned' => $totalPrizesAssigned,
            'totalPrizesPaid' => $totalPrizesPaid,
            'pendingPayouts' => $pendingPayouts,
            'games' => $games,
            'payouts' => $payouts,
            'transactions' => $transactions,
        ]);
    }

    public function markPaid(PrizePayout $payout)
    {
        $payout->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return back()->with('success', "Payout #{$payout->id} marked as paid.");
    }

    public function cancelPayout(PrizePayout $payout)
    {
        $payout->update(['status' => 'cancelled']);

        return back()->with('success', "Payout #{$payout->id} cancelled.");
    }
}
