<?php

namespace App\Http\Controllers;

use App\Models\PrizePayout;
use App\Models\Transaction;
use App\Services\StripeConnectService;
use Illuminate\Support\Facades\Auth;

/**
 * Earnings Controller
 *
 * Player-facing page showing prize history, pending payouts,
 * the revenue-based prize pool breakdown, and Stripe Connect onboarding.
 */
class EarningsController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // User's prize payouts across all games
        $payouts = PrizePayout::where('user_id', $user->id)
            ->with('game')
            ->orderByDesc('created_at')
            ->get();

        $totalEarned = $payouts->sum('amount_cents');
        $totalPaid = $payouts->where('status', 'paid')->sum('amount_cents');
        $totalPending = $payouts->where('status', 'pending')->sum('amount_cents');

        // Global revenue & pool info
        $totalRevenue = Transaction::sum('amount_cents');
        $tournamentPool = (int) round($totalRevenue * 0.50);
        $gamePoolTotal = (int) round($totalRevenue * 0.25);

        // Current game's revenue pool
        $currentGameId = session('active_game_id');
        $currentGameRevenue = 0;
        $currentGamePool = 0;
        if ($currentGameId) {
            $currentGameRevenue = Transaction::where('game_id', $currentGameId)->sum('amount_cents');
            $currentGamePool = (int) round($currentGameRevenue * 0.25);
        }

        // Stripe Connect status
        $connectStatus = 'not_connected';
        if ($user->hasStripeConnect()) {
            try {
                $connectService = new StripeConnectService();
                $connectStatus = $connectService->isAccountReady($user->stripe_connect_account_id)
                    ? 'active'
                    : 'pending';
            } catch (\Exception $e) {
                $connectStatus = 'pending';
            }
        }

        return view('pages.earnings', [
            'payouts' => $payouts,
            'totalEarned' => $totalEarned,
            'totalPaid' => $totalPaid,
            'totalPending' => $totalPending,
            'totalRevenue' => $totalRevenue,
            'tournamentPool' => $tournamentPool,
            'gamePoolTotal' => $gamePoolTotal,
            'currentGamePool' => $currentGamePool,
            'connectStatus' => $connectStatus,
        ]);
    }

    /**
     * Start Stripe Connect onboarding — create Express account if needed,
     * then redirect to Stripe's hosted onboarding.
     */
    public function startConnect()
    {
        $user = Auth::user();

        try {
            $connectService = new StripeConnectService();

            // Create account if user doesn't have one yet
            if (!$user->hasStripeConnect()) {
                $connectService->createConnectedAccount($user);
                $user->refresh();
            }

            // Generate onboarding link
            $url = $connectService->createAccountLink(
                $user->stripe_connect_account_id,
                route('game.earnings.connect.refresh'),
                route('game.earnings.connect.return')
            );

            return redirect($url);
        } catch (\Exception $e) {
            \Log::error('Stripe Connect onboarding error: ' . $e->getMessage());

            return redirect()->route('game.earnings')
                ->with('error', 'Unable to connect to Stripe. Please ensure Stripe Connect is enabled on the platform account, or try again later.');
        }
    }

    /**
     * Return URL after completing Stripe onboarding.
     */
    public function connectReturn()
    {
        return redirect()->route('game.earnings')
            ->with('success', 'Stripe account setup complete! You can now receive prize payouts.');
    }

    /**
     * Refresh URL — Stripe sends user here if the onboarding link expired.
     * Generates a new link and redirects.
     */
    public function connectRefresh()
    {
        $user = Auth::user();

        if (!$user->hasStripeConnect()) {
            return redirect()->route('game.earnings')
                ->with('error', 'Please try connecting your account again.');
        }

        try {
            $connectService = new StripeConnectService();
            $url = $connectService->createAccountLink(
                $user->stripe_connect_account_id,
                route('game.earnings.connect.refresh'),
                route('game.earnings.connect.return')
            );

            return redirect($url);
        } catch (\Exception $e) {
            \Log::error('Stripe Connect refresh error: ' . $e->getMessage());

            return redirect()->route('game.earnings')
                ->with('error', 'Unable to connect to Stripe. Please try again later.');
        }
    }

    /**
     * Redirect to the Stripe Express dashboard for the connected account.
     */
    public function stripeDashboard()
    {
        $user = Auth::user();

        if (!$user->hasStripeConnect()) {
            return redirect()->route('game.earnings');
        }

        try {
            $connectService = new StripeConnectService();
            $url = $connectService->createDashboardLink($user->stripe_connect_account_id);

            return redirect($url);
        } catch (\Exception $e) {
            return redirect()->route('game.earnings')
                ->with('error', 'Unable to access Stripe dashboard. Please try again.');
        }
    }
}
