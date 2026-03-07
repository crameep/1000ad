<?php

namespace App\Http\Controllers;

use App\Models\EmpireSlot;
use App\Models\Game;
use App\Models\PrizePayout;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeController extends Controller
{
    /**
     * Create a Stripe Checkout session for purchasing an extra empire slot.
     */
    public function checkout(Game $game)
    {
        $user = auth()->user();

        // Check if user can even benefit from another slot
        $maxAllowed = $game->setting('max_empires_per_user') ?? 1;
        if ($maxAllowed <= 1) {
            return redirect()->route('lobby')->with('error', 'This game does not allow multiple empires.');
        }

        $extraSlots = EmpireSlot::slotsFor($user->id, $game->id);
        if (1 + $extraSlots >= $maxAllowed) {
            return redirect()->route('lobby')->with('error', 'You already have the maximum number of empire slots for this game.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'line_items' => [[
                'price' => config('services.stripe.empire_slot_price_id'),
                'quantity' => 1,
            ]],
            'metadata' => [
                'user_id' => $user->id,
                'game_id' => $game->id,
            ],
            'customer_email' => $user->email,
            'success_url' => route('stripe.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('lobby'),
        ]);

        return redirect($session->url);
    }

    /**
     * Handle successful checkout — create the empire slot record.
     */
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');
        if (!$sessionId) {
            return redirect()->route('lobby')->with('error', 'Invalid payment session.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $session = StripeSession::retrieve($sessionId);
        } catch (\Exception $e) {
            return redirect()->route('lobby')->with('error', 'Could not verify payment.');
        }

        if ($session->payment_status !== 'paid') {
            return redirect()->route('lobby')->with('error', 'Payment was not completed.');
        }

        $userId = $session->metadata->user_id;
        $gameId = $session->metadata->game_id;

        // Only grant if this is the authenticated user
        if ((int) $userId !== (int) auth()->id()) {
            return redirect()->route('lobby')->with('error', 'Payment session mismatch.');
        }

        $this->grantEmpireSlot($userId, $gameId, $session->id, $session->customer, (int) ($session->amount_total ?? 0));

        return redirect()->route('lobby')->with('success', 'Extra empire slot purchased! You can now create another empire in this game.');
    }

    /**
     * Handle Stripe webhook as a backup for granting slots.
     */
    public function webhook(Request $request)
    {
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature'),
                $secret
            );
        } catch (\Exception $e) {
            return response('Invalid signature', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            if ($session->payment_status === 'paid') {
                $userId = $session->metadata->user_id ?? null;
                $gameId = $session->metadata->game_id ?? null;

                if ($userId && $gameId) {
                    $this->grantEmpireSlot($userId, $gameId, $session->id, $session->customer, (int) ($session->amount_total ?? 0));
                }
            }
        }

        // Stripe Connect: account onboarding updates
        if ($event->type === 'account.updated') {
            $account = $event->data->object;
            $user = \App\Models\User::where('stripe_connect_account_id', $account->id)->first();
            if ($user) {
                Log::info('Stripe Connect account updated', [
                    'account_id' => $account->id,
                    'user_id' => $user->id,
                    'charges_enabled' => $account->charges_enabled ?? false,
                    'payouts_enabled' => $account->payouts_enabled ?? false,
                ]);
            }
        }

        // Stripe Connect: transfer completed (backup confirmation)
        if ($event->type === 'transfer.paid') {
            $transfer = $event->data->object;
            $payoutId = $transfer->metadata->payout_id ?? null;
            if ($payoutId) {
                $payout = PrizePayout::find($payoutId);
                if ($payout && $payout->status !== 'paid') {
                    $payout->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                        'stripe_transfer_id' => $transfer->id,
                    ]);
                    Log::info("Stripe transfer confirmed for payout #{$payoutId}", [
                        'transfer_id' => $transfer->id,
                    ]);
                }
            }
        }

        // Stripe Connect: transfer failed
        if ($event->type === 'transfer.failed') {
            $transfer = $event->data->object;
            $payoutId = $transfer->metadata->payout_id ?? null;
            if ($payoutId) {
                $payout = PrizePayout::find($payoutId);
                if ($payout) {
                    $payout->update([
                        'status' => 'pending',
                        'stripe_transfer_id' => null,
                        'notes' => 'Stripe transfer failed: ' . ($transfer->failure_message ?? 'unknown error'),
                    ]);
                    Log::error("Stripe transfer failed for payout #{$payoutId}", [
                        'transfer_id' => $transfer->id,
                        'failure' => $transfer->failure_message ?? 'unknown',
                    ]);
                }
            }
        }

        return response('OK', 200);
    }

    /**
     * Grant an empire slot (idempotent — won't duplicate).
     */
    private function grantEmpireSlot(int $userId, int $gameId, ?string $paymentId, ?string $customerId, int $amountCents = 0): void
    {
        // Idempotency: skip if we already logged this payment
        if ($paymentId && Transaction::where('stripe_payment_id', $paymentId)->exists()) {
            return;
        }

        $slot = EmpireSlot::where('user_id', $userId)
            ->where('game_id', $gameId)
            ->first();

        if ($slot) {
            $slot->increment('extra_slots');
            $slot->update([
                'stripe_payment_id' => $paymentId,
                'purchased_at' => now(),
            ]);
        } else {
            EmpireSlot::create([
                'user_id' => $userId,
                'game_id' => $gameId,
                'extra_slots' => 1,
                'stripe_payment_id' => $paymentId,
                'stripe_customer_id' => $customerId,
                'purchased_at' => now(),
            ]);
        }

        // Log the transaction
        if ($amountCents > 0) {
            Transaction::create([
                'user_id' => $userId,
                'game_id' => $gameId,
                'stripe_payment_id' => $paymentId,
                'type' => 'empire_slot',
                'amount_cents' => $amountCents,
                'description' => 'Extra empire slot purchase',
            ]);
        }

        // Store Stripe customer ID on user for future use
        if ($customerId) {
            \App\Models\User::where('id', $userId)
                ->whereNull('stripe_customer_id')
                ->update(['stripe_customer_id' => $customerId]);
        }
    }
}
