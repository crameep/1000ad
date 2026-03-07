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

        // Compute game date from turn number
        $month = ($player->turn % 12) + 1;
        $year = intdiv($player->turn, 12) + 1000;
        $gameDate = date('F', mktime(0, 0, 0, $month, 1)) . ' ' . $year;

        // Progress indicators (wall, research, warehouse)
        $totalLand = $player->mland + $player->fland + $player->pland;
        $totalWallNeeded = round($totalLand * 0.05);
        $wallPercent = ($totalWallNeeded > 0) ? min(100, round(($player->wall / $totalWallNeeded) * 100)) : 0;

        $totalResearchLevels = 0;
        for ($i = 1; $i <= 12; $i++) {
            $totalResearchLevels += $player->{"research{$i}"};
        }
        $nextLevelPoints = 10 + round($totalResearchLevels * $totalResearchLevels * sqrt($totalResearchLevels));
        $researchPercent = ($nextLevelPoints > 0) ? min(100, round(($player->research_points / $nextLevelPoints) * 100)) : 0;
        $researchNames = [
            1 => 'Attack', 2 => 'Defense', 3 => 'Thieves', 4 => 'Losses',
            5 => 'Food', 6 => 'Mining', 7 => 'Weapons', 8 => 'Storage',
            9 => 'Markets', 10 => 'Explorers', 11 => 'Catapults', 12 => 'Wood',
        ];
        $currentResearchName = $researchNames[$player->current_research] ?? 'None';
        $currentResearchLevel = $player->current_research > 0
            ? $player->{"research{$player->current_research}"} : 0;
        $highestResearchLevel = 0;
        for ($i = 1; $i <= 12; $i++) {
            $highestResearchLevel = max($highestResearchLevel, $player->{"research{$i}"});
        }
        $researchRefMax = max(100, $highestResearchLevel);
        $researchLevelPercent = min(100, round(($currentResearchLevel / $researchRefMax) * 100));
        $remainingPercent = 100 - $researchLevelPercent;
        $researchProgressPercent = ($currentResearchLevel < $researchRefMax && $remainingPercent > 0)
            ? round($remainingPercent * ($researchPercent / 100), 1) : 0;

        $totalGoods = $player->wood + $player->iron + $player->food + $player->tools
            + $player->swords + $player->bows + $player->horses + $player->maces + $player->wine;
        $warehouseSpace = ($player->town_center * $buildings[11]['supplies'])
            + ($player->warehouse * $buildings[13]['supplies']);
        $warehouseSpace = round($warehouseSpace + $warehouseSpace * ($player->research8 / 100));
        $warehousePercent = ($warehouseSpace > 0) ? min(100, round(($totalGoods / $warehouseSpace) * 100)) : 0;

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
            'game_date' => $gameDate,
            // Progress indicators
            'wall_pct' => $wallPercent,
            'research_name' => $currentResearchName,
            'research_level' => $currentResearchLevel,
            'research_pct' => $researchPercent,
            'research_level_pct' => $researchLevelPercent,
            'research_progress_pct' => $researchProgressPercent,
            'warehouse_pct' => $warehousePercent,
        ];
    }
}
