<?php

namespace App\Models\Traits;

use App\Models\Game;
use Illuminate\Database\Eloquent\Builder;

/**
 * BelongsToGame Trait
 *
 * Adds automatic game_id scoping to any model that uses it.
 * - Queries are auto-filtered to the current active game
 * - New records auto-set game_id on creation
 */
trait BelongsToGame
{
    public static function bootBelongsToGame(): void
    {
        // Auto-filter all queries to the current game
        static::addGlobalScope('game', function (Builder $builder) {
            if ($gameId = app()->bound('current_game_id') ? app('current_game_id') : null) {
                $builder->where($builder->getModel()->getTable() . '.game_id', $gameId);
            }
        });

        // Auto-set game_id on creation
        static::creating(function ($model) {
            if (!$model->game_id) {
                if ($gameId = app()->bound('current_game_id') ? app('current_game_id') : null) {
                    $model->game_id = $gameId;
                }
            }
        });
    }

    /**
     * Get the game this record belongs to.
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }
}
