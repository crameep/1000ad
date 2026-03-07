<?php

namespace App\Services;

use App\Models\PrizePayout;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

/**
 * Stripe Connect Service
 *
 * Handles all Stripe Connect operations: creating Express connected accounts,
 * generating onboarding links, and transferring prize payouts.
 */
class StripeConnectService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Create a Stripe Express connected account for the user.
     */
    public function createConnectedAccount(User $user): string
    {
        $account = $this->stripe->accounts->create([
            'country' => 'US',
            'email' => $user->email,
            'type' => 'express',
            'controller' => [
                'fees' => ['payer' => 'application'],
                'losses' => ['payments' => 'application'],
                'stripe_dashboard' => ['type' => 'express'],
            ],
        ]);

        $user->update(['stripe_connect_account_id' => $account->id]);

        return $account->id;
    }

    /**
     * Generate an Account Link URL for onboarding.
     */
    public function createAccountLink(string $accountId, string $refreshUrl, string $returnUrl): string
    {
        $accountLink = $this->stripe->accountLinks->create([
            'account' => $accountId,
            'type' => 'account_onboarding',
            'refresh_url' => $refreshUrl,
            'return_url' => $returnUrl,
        ]);

        return $accountLink->url;
    }

    /**
     * Create a login link for an already-onboarded Express account
     * so they can access their Stripe Express dashboard.
     */
    public function createDashboardLink(string $accountId): string
    {
        $loginLink = $this->stripe->accounts->createLoginLink($accountId);

        return $loginLink->url;
    }

    /**
     * Check if a connected account has completed onboarding.
     */
    public function isAccountReady(string $accountId): bool
    {
        try {
            $account = $this->stripe->accounts->retrieve($accountId);

            return $account->charges_enabled && $account->payouts_enabled;
        } catch (\Exception $e) {
            Log::error("Stripe Connect: Failed to check account {$accountId}", [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Transfer funds to a connected account for a prize payout.
     * Returns the Stripe Transfer ID, or throws on failure.
     */
    public function transferPayout(PrizePayout $payout): string
    {
        $user = $payout->user;

        if (!$user->hasStripeConnect()) {
            throw new \RuntimeException("User #{$user->id} has no Stripe Connect account.");
        }

        // Idempotency: don't double-pay
        if ($payout->stripe_transfer_id) {
            throw new \RuntimeException("Payout #{$payout->id} already has transfer {$payout->stripe_transfer_id}.");
        }

        if (!$payout->isPending()) {
            throw new \RuntimeException("Payout #{$payout->id} is not pending (status: {$payout->status}).");
        }

        $game = $payout->game;
        $description = "Prize payout - {$game->name} - " . ordinal($payout->place) . ' place';

        $transfer = $this->stripe->transfers->create([
            'amount' => $payout->amount_cents,
            'currency' => 'usd',
            'destination' => $user->stripe_connect_account_id,
            'description' => $description,
            'metadata' => [
                'payout_id' => $payout->id,
                'user_id' => $user->id,
                'game_id' => $game->id,
                'place' => $payout->place,
            ],
        ]);

        // Update the payout record
        $payout->update([
            'status' => 'paid',
            'paid_at' => now(),
            'stripe_transfer_id' => $transfer->id,
        ]);

        return $transfer->id;
    }
}
