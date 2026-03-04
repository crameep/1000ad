<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tracks per-game extra empire slot purchases.
 * Each record grants a user extra empire slots in a specific game.
 */
class EmpireSlot extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'purchased_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Get the number of extra slots a user has purchased for a game.
     */
    public static function slotsFor(int $userId, int $gameId): int
    {
        return (int) static::where('user_id', $userId)
            ->where('game_id', $gameId)
            ->value('extra_slots') ?? 0;
    }

    /**
     * Check if a user can create another empire in a game.
     * Everyone gets 1 free slot. Extra slots come from purchases.
     * Capped by game's max_empires_per_user setting.
     */
    public static function canCreateEmpire(int $userId, int $gameId): bool
    {
        $game = Game::find($gameId);
        if (!$game) {
            return false;
        }

        $maxAllowed = $game->setting('max_empires_per_user') ?? 1;
        $extraSlots = static::slotsFor($userId, $gameId);
        $totalSlots = min(1 + $extraSlots, $maxAllowed);

        $currentEmpires = Player::withoutGlobalScope('game')
            ->where('user_id', $userId)
            ->where('game_id', $gameId)
            ->where('killed_by', 0)
            ->count();

        return $currentEmpires < $totalSlots;
    }
}
