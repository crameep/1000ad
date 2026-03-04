<?php

namespace App\Services;

use App\Models\Player;

/**
 * Game Advisor Service
 *
 * Analyzes player state and returns contextual tips for the "Royal Advisor" panel.
 * Pure logic — no database queries beyond what's on the player model.
 */
class GameAdvisorService
{
    protected GameDataService $gameData;

    public function __construct(GameDataService $gameData)
    {
        $this->gameData = $gameData;
    }

    /**
     * Get advisor tips for a player.
     *
     * @return array<array{type: string, message: string}>
     */
    public function getTips(Player $player): array
    {
        $tips = [];
        $buildings = $this->gameData->getBuildings($player->civ);
        $soldiers = $this->gameData->getSoldiers($player->civ);
        $constants = $this->gameData->getConstants($player->civ);

        $turn = $player->turn;
        $month = ($turn % 12) + 1;

        $townCenterB = $buildings[11];
        $warehouseB = $buildings[13];
        $houseB = $buildings[4];
        $fortB = $buildings[9];
        $toolMakerB = $buildings[7];

        // --- Warehouse capacity ---
        $canHold = $player->town_center * $townCenterB['supplies'] + $player->warehouse * $warehouseB['supplies'];
        $canHold = round($canHold + $canHold * ($player->research8 / 100));
        $totalSupplies = $player->wood + $player->iron + $player->food + $player->tools
            + $player->swords + $player->bows + $player->horses + $player->maces + $player->wine;

        if ($canHold > 0) {
            $pct = round(($totalSupplies / $canHold) * 100);
            if ($pct >= 95) {
                $tips[] = ['type' => 'danger', 'message' => "Your warehouses are {$pct}% full! Goods will be stolen if they overflow. Build more warehouses or sell resources."];
            } elseif ($pct >= 80) {
                $tips[] = ['type' => 'warning', 'message' => "Your warehouses are {$pct}% full. Consider building more warehouses or selling excess goods."];
            }
        }

        // --- Winter wood warning ---
        $burnWood = round($player->people / $constants['people_burn_one_wood']);
        if (($month >= 9 && $month <= 10) && $player->wood < $burnWood * 3) {
            $tips[] = ['type' => 'warning', 'message' => "Winter is approaching. You only have enough wood for " . ($burnWood > 0 ? round($player->wood / $burnWood) : 'many') . " months of heating. Stock up on wood!"];
        } elseif (($month >= 11 || $month <= 2) && $player->wood < $burnWood * 2) {
            $tips[] = ['type' => 'danger', 'message' => "It's winter and your wood reserves are dangerously low. Your people will freeze if you run out!"];
        }

        // --- Food supply ---
        $foodEaten = round($player->people / $constants['people_eat_one_food']);
        switch ($player->food_ratio) {
            case 1: $foodEaten = round($foodEaten * 1.5); break;
            case 2: $foodEaten = round($foodEaten * 2.5); break;
            case 3: $foodEaten = round($foodEaten * 4); break;
            case -1: $foodEaten = round($foodEaten * 0.75); break;
            case -2: $foodEaten = round($foodEaten * 0.45); break;
            case -3: $foodEaten = round($foodEaten * 0.25); break;
        }
        $numSoldiers = $player->swordsman + $player->archers + $player->horseman * 2
            + $player->macemen + round($player->trained_peasants * 0.1)
            + $player->thieves * 3 + $player->uunit * 2;
        $soldierFood = $numSoldiers > 0 ? (int)ceil($numSoldiers / $constants['soldiers_eat_one_food']) : 0;
        $totalFoodPerTurn = $foodEaten + $soldierFood;

        if ($totalFoodPerTurn > 0) {
            $turnsOfFood = floor($player->food / $totalFoodPerTurn);
            if ($turnsOfFood <= 3) {
                $tips[] = ['type' => 'danger', 'message' => "Critical food shortage! You only have about {$turnsOfFood} turns of food remaining. People and soldiers will die."];
            } elseif ($turnsOfFood <= 10) {
                $tips[] = ['type' => 'warning', 'message' => "Food reserves are low — roughly {$turnsOfFood} turns remaining. Build more farms or hunters."];
            }
        }

        // --- Farms inactive in winter ---
        if ($player->farmer > 0 && ($month < 4 || $month > 10)) {
            $tips[] = ['type' => 'info', 'message' => "Your farms are idle during winter months (Nov-Mar). Hunters provide food year-round."];
        }

        // --- Housing capacity ---
        $houseSpace = $player->house * $houseB['people'] + $player->town_center * $townCenterB['people'];
        $houseSpace = round($houseSpace + $houseSpace * ($player->research8 / 100));
        if ($player->people >= $houseSpace && $houseSpace > 0) {
            $tips[] = ['type' => 'warning', 'message' => "Your housing is full ({$player->people}/{$houseSpace}). Build more houses to grow your population."];
        }

        // --- Army capacity ---
        $maxSoldiers = $player->fort * $fortB['max_units'] + $player->town_center * $townCenterB['max_units'];
        $totalArmy = $player->swordsman + $player->archers + $player->horseman + $player->macemen
            + $player->trained_peasants + $player->thieves + $player->catapults + $player->uunit;
        if ($totalArmy > 0 && $maxSoldiers > 0 && $totalArmy >= $maxSoldiers) {
            $tips[] = ['type' => 'warning', 'message' => "Your army has reached capacity ({$totalArmy}/{$maxSoldiers}). Build more forts to support additional troops."];
        }

        // --- No army at all ---
        if ($totalArmy == 0 && $player->turn > 5) {
            $tips[] = ['type' => 'danger', 'message' => "You have no military forces! Your empire is defenseless against attack. Train soldiers at your forts."];
        }

        // --- Soldier gold pay ---
        $payGold = round(
            $player->swordsman * $soldiers[2]['gold_per_turn']
            + $player->archers * $soldiers[1]['gold_per_turn']
            + $player->horseman * $soldiers[3]['gold_per_turn']
            + $player->macemen * $soldiers[6]['gold_per_turn']
            + $player->trained_peasants * $soldiers[7]['gold_per_turn']
            + $player->thieves * $soldiers[8]['gold_per_turn']
            + $player->uunit * $soldiers[9]['gold_per_turn']
        );
        if ($payGold > 0 && $player->gold < $payGold * 5) {
            $turnsOfPay = $player->gold > 0 ? floor($player->gold / $payGold) : 0;
            $tips[] = ['type' => 'warning', 'message' => "You can only pay your army for about {$turnsOfPay} more turns ({$payGold} gold/turn). Unpaid soldiers desert."];
        }

        // --- No research active ---
        if ($player->current_research == 0 && $player->mage_tower > 0) {
            $tips[] = ['type' => 'info', 'message' => "You have mage towers but no research topic selected. Visit the Research page to start advancing."];
        }

        // --- Tool shortage ---
        $numBuilders = $toolMakerB['num_builders'] * $player->tool_maker + 3;
        if ($player->tools < $numBuilders && $player->tool_maker > 0) {
            $tips[] = ['type' => 'warning', 'message' => "You don't have enough tools for all your builders ({$player->tools} tools, {$numBuilders} builders needed). Build more tool makers or buy tools."];
        }

        // --- Idle weaponsmiths ---
        if ($player->weapon_smith > 0) {
            $assigned = $player->sword_weapon_smith + $player->bow_weapon_smith + $player->mace_weaponsmith;
            if ($assigned < $player->weapon_smith) {
                $idle = $player->weapon_smith - $assigned;
                $tips[] = ['type' => 'info', 'message' => "You have {$idle} unassigned weaponsmith(s). Visit the Army page to assign them to produce swords, bows, or maces."];
            }
        }

        // --- No wall ---
        $totalLand = $player->mland + $player->fland + $player->pland;
        $maxWall = round($totalLand * 0.05);
        if ($player->wall == 0 && $player->turn > 10) {
            $tips[] = ['type' => 'info', 'message' => "Your empire has no wall defenses. Walls reduce losses during attacks. Set wall construction on the Wall page."];
        } elseif ($maxWall > 0 && $player->wall < $maxWall * 0.25 && $player->turn > 20) {
            $pctWall = round(($player->wall / $maxWall) * 100);
            $tips[] = ['type' => 'info', 'message' => "Your wall is only {$pctWall}% complete ({$player->wall}/{$maxWall}). A stronger wall protects against invaders."];
        }

        // --- No explorers and low land ---
        if ($totalLand < 5000 && $player->turn > 15) {
            $exploringCount = \App\Models\ExploreQueue::where('player_id', $player->id)->count();
            if ($exploringCount == 0) {
                $tips[] = ['type' => 'info', 'message' => "You have no explorers searching for new land. Send explorers from a Town Center to expand your territory."];
            }
        }

        // --- Negative food ratio ---
        if ($player->food_ratio < 0) {
            $labels = [-1 => 'slightly reduced', -2 => 'severely reduced', -3 => 'starvation-level'];
            $tips[] = ['type' => 'warning', 'message' => "Your food rations are {$labels[$player->food_ratio]}. This causes population decline. Increase rations on the Manage page when food allows."];
        }

        // Limit to most important 5 tips
        return array_slice($tips, 0, 5);
    }
}
