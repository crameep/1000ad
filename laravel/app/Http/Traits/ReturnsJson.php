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
        // Compute free land from buildings config (same logic as GameSession middleware)
        $buildings = app(\App\Services\GameDataService::class)->getBuildings($player->civ);
        $usedM = ($player->iron_mine * $buildings[5]['sq'])
            + ($player->gold_mine * $buildings[6]['sq']);
        $usedF = ($player->hunter * $buildings[2]['sq'])
            + ($player->wood_cutter * $buildings[1]['sq']);
        $usedP = ($player->farmer * $buildings[3]['sq'])
            + ($player->house * $buildings[4]['sq'])
            + ($player->tool_maker * $buildings[7]['sq'])
            + ($player->weapon_smith * $buildings[8]['sq'])
            + ($player->fort * $buildings[9]['sq'])
            + ($player->tower * $buildings[10]['sq'])
            + ($player->town_center * $buildings[11]['sq'])
            + ($player->market * $buildings[12]['sq'])
            + ($player->warehouse * $buildings[13]['sq'])
            + ($player->stable * $buildings[14]['sq'])
            + ($player->mage_tower * $buildings[15]['sq'])
            + ($player->winery * $buildings[16]['sq']);

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
            'horses' => $player->horses,
            'wine' => $player->wine,
            'swords' => $player->swords,
            'bows' => $player->bows,
            'maces' => $player->maces,
            'free_mland' => $player->mland - $usedM,
            'free_fland' => $player->fland - $usedF,
            'free_pland' => $player->pland - $usedP,
            'turns_free' => $player->turns_free,
        ];
    }
}
