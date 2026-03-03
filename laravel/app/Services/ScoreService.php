<?php

namespace App\Services;

use App\Models\AttackQueue;
use App\Models\Player;

/**
 * Score Calculation Service
 *
 * Ported from calc_score.cfm
 * Calculates and updates the score breakdown for a player.
 */
class ScoreService
{
    /**
     * Calculate and save the score for a player.
     */
    public function calculateScore(Player $player): void
    {
        // Get soldiers currently out on attack missions
        $attacking = AttackQueue::where('player_id', $player->id)
            ->selectRaw('
                COALESCE(SUM(swordsman), 0) as attacking_swordsman,
                COALESCE(SUM(archers), 0) as attacking_archers,
                COALESCE(SUM(horseman), 0) as attacking_horseman,
                COALESCE(SUM(trained_peasants), 0) as attacking_peasants,
                COALESCE(SUM(macemen), 0) as attacking_macemen,
                COALESCE(SUM(catapults), 0) as attacking_catapults,
                COALESCE(SUM(thieves), 0) as attacking_thieves
            ')
            ->first();

        // Total soldiers (home + attacking)
        $totalSwordsman = ($attacking->attacking_swordsman ?? 0) + $player->swordsman;
        $totalHorseman = ($attacking->attacking_horseman ?? 0) + $player->horseman;
        $totalArchers = ($attacking->attacking_archers ?? 0) + $player->archers;
        $totalCatapults = ($attacking->attacking_catapults ?? 0) + $player->catapults;
        $totalMacemen = ($attacking->attacking_macemen ?? 0) + $player->macemen;
        $totalPeasants = ($attacking->attacking_peasants ?? 0) + $player->trained_peasants;
        $totalThieves = ($attacking->attacking_thieves ?? 0) + $player->thieves;

        // Total buildings (weighted)
        $totalBuildings = $player->wood_cutter
            + $player->hunter
            + $player->farmer
            + $player->house
            + $player->iron_mine
            + $player->gold_mine * 2
            + $player->tool_maker
            + $player->weapon_smith
            + $player->fort
            + $player->tower * 5
            + $player->town_center * 10
            + $player->market
            + $player->stable
            + $player->mage_tower * 3;

        // Military score
        $militaryScore = round($totalSwordsman * 2)
            + round($totalArchers * 1.8)
            + round($totalHorseman * 3)
            + round($player->people * 0.25)
            + round($totalMacemen * 1)
            + round($totalPeasants * 0.3)
            + round($totalCatapults * 6)
            + round($totalThieves * 4);

        // Land score
        $landScore = $player->mland * 5
            + $player->fland * 4
            + $player->pland * 3;

        // Goods score
        $goodScore = round($player->wood * 0.005)
            + round($player->food * 0.0005)
            + round($player->iron * 0.005)
            + round($player->gold * 0.00001)
            + round($player->tools * 0.01)
            + round($player->swords * 0.1)
            + round($player->bows * 0.1)
            + round($player->horses * 0.15)
            + round($player->maces * 0.1)
            + $totalBuildings;

        // Total score
        $totalScore = $militaryScore + $landScore + $goodScore;

        // Calculate percentages (avoid division by zero)
        if ($totalScore > 0) {
            $militaryPct = round(($militaryScore / $totalScore) * 100.0);
            $landPct = round(($landScore / $totalScore) * 100.0);
            $goodPct = round(($goodScore / $totalScore) * 100.0);
        } else {
            $militaryPct = 0;
            $landPct = 0;
            $goodPct = 0;
        }

        // Save to player
        $player->update([
            'score' => $totalScore,
            'military_score' => $militaryPct,
            'land_score' => $landPct,
            'good_score' => $goodPct,
        ]);
    }
}
