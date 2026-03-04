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
     * Check if this user is an administrator.
     */
    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }
}
