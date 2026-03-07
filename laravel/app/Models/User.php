<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * User Model
 *
 * Represents a user account, separate from game state.
 * One user can have multiple Player records across different games.
 */
class User extends Authenticatable
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_admin' => 'boolean',
    ];

    /**
     * Get all player records for this user across all games.
     */
    public function players()
    {
        return $this->hasMany(Player::class, 'user_id');
    }

    public function empireSlots()
    {
        return $this->hasMany(EmpireSlot::class, 'user_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function prizePayouts()
    {
        return $this->hasMany(PrizePayout::class);
    }

    /**
     * Get the player record for a specific game.
     */
    public function playerInGame(int $gameId): ?Player
    {
        return $this->players()
            ->withoutGlobalScope('game')
            ->where('game_id', $gameId)
            ->first();
    }

    /**
     * Get all alive player records for a specific game.
     */
    public function playersInGame(int $gameId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->players()
            ->withoutGlobalScope('game')
            ->where('game_id', $gameId)
            ->where('killed_by', 0)
            ->get();
    }

    /**
     * Check if this user is an administrator.
     */
    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /**
     * Whether this user has a Stripe Connect account linked.
     */
    public function hasStripeConnect(): bool
    {
        return !empty($this->stripe_connect_account_id);
    }
}
