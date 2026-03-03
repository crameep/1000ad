<?php

namespace App\Http\Traits;

use App\Models\Player;

/**
 * Provides standardized JSON response methods for AJAX-enabled controllers.
 * Used with $request->expectsJson() to return JSON instead of redirects.
 */
trait ReturnsJson
{
    /**
     * Return a successful JSON response with player state.
     */
    protected function jsonSuccess(Player $player, string $message, array $extra = [])
    {
        $player->refresh();

        return response()->json(array_merge([
            'success' => true,
            'message' => $message,
            'state' => $this->playerState($player),
        ], $extra));
    }

    /**
     * Return a JSON error response.
     */
    protected function jsonError(string $message, int $status = 422)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    /**
     * Build the player state array for resource bar updates.
     */
    protected function playerState(Player $player): array
    {
        return [
            'score' => $player->score,
            'gold' => $player->gold,
            'wood' => $player->wood,
            'iron' => $player->iron,
            'food' => $player->food,
            'tools' => $player->tools,
            'people' => $player->people,
            'mland' => $player->mland,
            'fland' => $player->fland,
            'pland' => $player->pland,
            'turns_free' => $player->turns_free,
        ];
    }
}
