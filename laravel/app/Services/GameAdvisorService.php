<?php

namespace App\Services;

use App\Models\Player;

/**
 * Game Advisor Service
 *
 * Analyzes player state and returns contextual tips for the "Royal Advisor" panel.
 * Provides page-specific advice for every game page.
 *
 * Pure logic — no database queries beyond what's on the player model
 * (except the existing explore count on the main page).
 */
class GameAdvisorService
{
    protected GameDataService $gameData;

    public function __construct(GameDataService $gameData)
    {
        $this->gameData = $gameData;
    }

    /**
     * Get advisor tips for the Main page (general overview).
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
            $tips[] = ['type' => 'info', 'message' => "You have mage towers but no research topic selected. Visit the <a href=\"/game/research\">Research</a> page to start advancing."];
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
                $tips[] = ['type' => 'info', 'message' => "You have {$idle} unassigned weaponsmith(s). Visit the <a href=\"/game/manage\">Manage</a> page to assign them to produce swords, bows, or maces."];
            }
        }

        // --- No wall ---
        $totalLand = $player->mland + $player->fland + $player->pland;
        $maxWall = round($totalLand * 0.05);
        if ($player->wall == 0 && $player->turn > 10) {
            $tips[] = ['type' => 'info', 'message' => "Your empire has no wall defenses. Walls reduce losses during attacks. Set wall construction on the <a href=\"/game/wall\">Wall</a> page."];
        } elseif ($maxWall > 0 && $player->wall < $maxWall * 0.25 && $player->turn > 20) {
            $pctWall = round(($player->wall / $maxWall) * 100);
            $tips[] = ['type' => 'info', 'message' => "Your wall is only {$pctWall}% complete ({$player->wall}/{$maxWall}). A stronger wall protects against invaders."];
        }

        // --- No explorers and low land ---
        if ($totalLand < 5000 && $player->turn > 15) {
            $exploringCount = \App\Models\ExploreQueue::where('player_id', $player->id)->count();
            if ($exploringCount == 0) {
                $tips[] = ['type' => 'info', 'message' => "You have no explorers searching for new land. Send explorers from the <a href=\"/game/explore\">Explore</a> page to expand your territory."];
            }
        }

        // --- Negative food ratio ---
        if ($player->food_ratio < 0) {
            $labels = [-1 => 'slightly reduced', -2 => 'severely reduced', -3 => 'starvation-level'];
            $tips[] = ['type' => 'warning', 'message' => "Your food rations are {$labels[$player->food_ratio]}. This causes population decline. Increase rations on the <a href=\"/game/manage\">Manage</a> page when food allows."];
        }

        // Limit to most important 5 tips
        return array_slice($tips, 0, 5);
    }

    /**
     * Get advisor tips for the Build page.
     *
     * @return array<array{type: string, message: string}>
     */
    public function getBuildTips(Player $player, array $buildingStats, $buildQueue, int $free, int $freeMountain, int $freeForest, int $freePlains): array
    {
        $tips = [];
        $buildings = $this->gameData->getBuildings($player->civ);
        $month = ($player->turn % 12) + 1;

        // Worker shortage
        if ($free < 0) {
            $shortage = abs($free);
            $tips[] = ['type' => 'danger', 'message' => "You need {$shortage} more people for your buildings. Build houses or increase food rations to grow population."];
        }

        // Empty build queue with builders available
        $numBuilders = ($buildings[7]['num_builders'] ?? 6) * $player->tool_maker + 3;
        if ($buildQueue->isEmpty() && $numBuilders > 3) {
            $tips[] = ['type' => 'info', 'message' => "Your build queue is empty. Your {$numBuilders} builders are idle — queue buildings to put them to work."];
        }

        // No food production at all
        if ($player->hunter == 0 && $player->farmer == 0) {
            $tips[] = ['type' => 'danger', 'message' => "You have no food production! Build <a href=\"javascript:openHelp('hunter')\">hunters</a> (year-round) or <a href=\"javascript:openHelp('farm')\">farms</a> (summer only)."];
        }
        // Has farmers but no hunters, winter approaching
        elseif ($player->farmer > 0 && $player->hunter == 0 && ($month >= 9 || $month <= 3)) {
            $tips[] = ['type' => 'warning', 'message' => "Farms are idle in winter. Build hunters for year-round food production."];
        }

        // Land type exhaustion
        if ($freeMountain <= 0 && ($freeForest > 0 || $freePlains > 0)) {
            $tips[] = ['type' => 'info', 'message' => "You're out of mountain land. Convert land on the <a href=\"/game/manage\">Manage</a> page or explore for more."];
        } elseif ($freePlains <= 0 && ($freeForest > 0 || $freeMountain > 0)) {
            $tips[] = ['type' => 'info', 'message' => "You're out of plains land. Convert forest to plains on the <a href=\"/game/manage\">Manage</a> page or explore for more."];
        } elseif ($freeForest <= 0 && ($freeMountain > 0 || $freePlains > 0)) {
            $tips[] = ['type' => 'info', 'message' => "You're out of forest land. Convert mountain to forest on the <a href=\"/game/manage\">Manage</a> page or explore for more."];
        }

        // No tool makers
        if ($player->tool_maker == 0 && $player->turn > 5) {
            $tips[] = ['type' => 'warning', 'message' => "You have no tool makers. Builders need tools — build tool makers to speed up construction."];
        }

        // No weaponsmiths but has forts
        if ($player->weapon_smith == 0 && $player->fort > 0) {
            $tips[] = ['type' => 'info', 'message' => "You have forts but no weaponsmiths. Build weaponsmiths to produce weapons for training soldiers."];
        }

        // No gold mines and low gold
        if ($player->gold_mine == 0 && $player->gold < 1000 && $player->turn > 10) {
            $tips[] = ['type' => 'warning', 'message' => "Your gold is low with no gold mines. Build gold mines or sell goods at the <a href=\"/game/localtrade\">market</a>."];
        }

        // Early game: no houses
        if ($player->turn < 20 && $player->house == 0) {
            $tips[] = ['type' => 'danger', 'message' => "Build houses to grow your population. More people = more workers for your buildings."];
        }

        // Housing full
        $houseSpace = $player->house * ($buildings[4]['people'] ?? 100)
            + $player->town_center * ($buildings[11]['people'] ?? 100);
        $houseSpace = round($houseSpace + $houseSpace * ($player->research8 / 100));
        if ($player->people >= $houseSpace && $houseSpace > 0) {
            $tips[] = ['type' => 'warning', 'message' => "Housing is full. Build more houses to allow population growth."];
        }

        return array_slice($tips, 0, 3);
    }

    /**
     * Get advisor tips for the Army page.
     *
     * @return array<array{type: string, message: string}>
     */
    public function getArmyTips(Player $player, array $armyData, $trainQueue, int $maxSoldiers, int $totalHave, int $canTrain, int $attackPower, int $defensePower): array
    {
        $tips = [];
        $soldiers = $this->gameData->getSoldiers($player->civ);

        // No army at all
        if ($totalHave == 0 && $player->turn > 5) {
            $tips[] = ['type' => 'danger', 'message' => "You have no army! You're defenseless against attacks. Train soldiers immediately."];
        }

        // At max capacity
        if ($canTrain <= 0 && $maxSoldiers > 0) {
            $tips[] = ['type' => 'warning', 'message' => "Army at max capacity ({$totalHave}/{$maxSoldiers}). Build more forts to train additional soldiers."];
        }

        // Has weapons but no matching soldiers
        if ($player->swords > 10 && $player->swordsman == 0 && $player->horseman == 0) {
            $tips[] = ['type' => 'info', 'message' => "You have " . number_format($player->swords) . " swords but no swordsmen. Train swordsmen to put them to use."];
        }
        if ($player->bows > 10 && $player->archers == 0) {
            $tips[] = ['type' => 'info', 'message' => "You have " . number_format($player->bows) . " bows but no archers. Train archers to put them to use."];
        }
        if ($player->maces > 10 && $player->macemen == 0) {
            $tips[] = ['type' => 'info', 'message' => "You have " . number_format($player->maces) . " maces but no macemen. Train macemen to put them to use."];
        }

        // Army is mostly trained peasants
        $totalReal = $player->swordsman + $player->archers + $player->horseman + $player->macemen + $player->uunit;
        if ($player->trained_peasants > 0 && $totalReal == 0 && $totalHave > 5) {
            $tips[] = ['type' => 'info', 'message' => "Your army is all trained peasants, which are weak fighters. Train swordsmen, archers, or your unique unit for real fighting power."];
        }

        // No unique units and late enough
        $uniqueName = $soldiers[9]['name'] ?? 'Unique Unit';
        if ($player->uunit == 0 && $player->turn > 30) {
            $tips[] = ['type' => 'info', 'message' => "You haven't trained any {$uniqueName}. They're your civilization's strongest unit!"];
        }

        // Gold running out for army upkeep
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
            $tips[] = ['type' => 'warning', 'message' => "You can only pay your army for ~{$turnsOfPay} turns. Unpaid soldiers desert. Increase gold production."];
        }

        // Training queue empty with capacity
        if ($trainQueue->isEmpty() && $canTrain > 0 && $maxSoldiers > 0) {
            $tips[] = ['type' => 'info', 'message' => "Your training queue is empty with room for " . number_format($canTrain) . " more soldiers."];
        }

        // Has horses but no horsemen
        if ($player->horses > 10 && $player->horseman == 0 && $player->civ != 6) {
            $tips[] = ['type' => 'info', 'message' => "You have " . number_format($player->horses) . " horses. Train horsemen — they're powerful attackers."];
        }

        // Low defense power
        if ($defensePower < 100 && $player->turn > 20 && $totalHave > 0) {
            $tips[] = ['type' => 'warning', 'message' => "Your defense power is very low ({$defensePower}). Build up your army to deter attacks."];
        }

        return array_slice($tips, 0, 3);
    }

    /**
     * Get advisor tips for the Attack page.
     *
     * @return array<array{type: string, message: string}>
     */
    public function getAttackTips(Player $player, $attacks): array
    {
        $tips = [];
        $deathmatchMode = gameConfig('deathmatch_mode');

        // Under protection
        if ($player->turn <= 72 && !$deathmatchMode) {
            $remaining = 72 - $player->turn;
            $tips[] = ['type' => 'info', 'message' => "You're under protection for {$remaining} more months. Use this time to build your economy and army."];
            return $tips;
        }

        $totalArmy = $player->swordsman + $player->archers + $player->horseman + $player->macemen
            + $player->trained_peasants + $player->uunit;

        // Has army but no active attacks
        if ($totalArmy > 0 && $attacks->isEmpty()) {
            $tips[] = ['type' => 'info', 'message' => "Your army is idle. Scout enemies with <a href=\"javascript:openHelp('thief')\">thieves</a> first, then attack to gain land and resources."];
        }

        // Wine reminder
        if ($player->wine > 0 && $totalArmy > 0) {
            $tips[] = ['type' => 'info', 'message' => "You have " . number_format($player->wine) . " wine. Send wine with your army (1 per soldier = 100% bonus attack strength)."];
        } elseif ($player->wine == 0 && $player->winery == 0 && $player->turn > 30) {
            $tips[] = ['type' => 'info', 'message' => "Wine boosts army attack power. Build a <a href=\"/game/build\">winery</a> to produce wine for your soldiers."];
        }

        // Score range suggestion
        if ($player->score > 0 && !$deathmatchMode) {
            $min = number_format(intdiv($player->score, 2));
            $max = number_format($player->score * 2);
            $tips[] = ['type' => 'info', 'message' => "Target empires between score {$min} and {$max} (half to double your size) to avoid attack penalties."];
        }

        // Has catapults but never used them
        if ($player->catapults > 0) {
            $tips[] = ['type' => 'info', 'message' => "You have " . number_format($player->catapults) . " catapults. Use them to destroy enemy buildings, population, or army/towers."];
        }

        // Has thieves
        if ($player->thieves > 0 && $attacks->isEmpty()) {
            $tips[] = ['type' => 'info', 'message' => "Your " . number_format($player->thieves) . " thieves can steal army info, goods, building info, or even poison water and set fires."];
        }

        // No army to attack with
        if ($totalArmy == 0 && $player->catapults == 0 && $player->thieves == 0) {
            $tips[] = ['type' => 'warning', 'message' => "You have no soldiers, catapults, or thieves. Train units on the <a href=\"/game/army\">Army</a> page before attacking."];
        }

        return array_slice($tips, 0, 3);
    }

    /**
     * Get advisor tips for the Research page.
     *
     * @return array<array{type: string, message: string}>
     */
    public function getResearchTips(Player $player): array
    {
        $tips = [];

        // No mage towers
        if ($player->mage_tower == 0) {
            $tips[] = ['type' => 'danger', 'message' => "Build <a href=\"/game/build\">mage towers</a> to unlock research. Research provides powerful economy and military bonuses."];
            return $tips;
        }

        // Has mage towers but no research selected
        if ($player->current_research == 0) {
            $tips[] = ['type' => 'warning', 'message' => "You have mage towers but no research selected! Choose a research topic to start advancing."];
        }

        // Suggest specific research based on player state
        if ($player->research1 == 0 && $player->current_research != 1) {
            $tips[] = ['type' => 'info', 'message' => "Consider researching <b>Attack</b> — it increases your army's attack power in battles."];
        }
        if ($player->research5 == 0 && $player->current_research != 5 && ($player->farmer > 0 || $player->hunter > 0)) {
            $tips[] = ['type' => 'info', 'message' => "Research <b>Food Production</b> to increase food output from your farms and hunters."];
        }
        if ($player->research2 == 0 && $player->current_research != 2 && $player->turn > 20) {
            $tips[] = ['type' => 'info', 'message' => "Research <b>Defense</b> to make your soldiers stronger when defending your empire."];
        }

        // Well-researched player
        $totalLevels = 0;
        for ($i = 1; $i <= 12; $i++) {
            $totalLevels += $player->{"research{$i}"};
        }
        if ($totalLevels > 36) {
            $tips[] = ['type' => 'success', 'message' => "Your research is well-advanced at {$totalLevels} total levels! Keep investing in mage towers for faster progress."];
        }

        // Mage tower status off
        if ($player->mage_tower_status < 50 && $player->mage_tower > 0) {
            $tips[] = ['type' => 'warning', 'message' => "Your mage towers are at {$player->mage_tower_status}% capacity. Increase their status on the <a href=\"/game/build\">Build</a> page for faster research."];
        }

        return array_slice($tips, 0, 3);
    }

    /**
     * Get advisor tips for the Explore page.
     *
     * @return array<array{type: string, message: string}>
     */
    public function getExploreTips(Player $player, $explorations, int $canSend, int $totalLand): array
    {
        $tips = [];

        // Can send explorers and none active
        $activeCount = $explorations->where('turn', '>', 0)->count();
        if ($canSend > 0 && $activeCount == 0) {
            $tips[] = ['type' => 'info', 'message' => "You can send up to " . number_format($canSend) . " explorers. Explore to expand your territory!"];
        }

        // Low on specific land type
        if ($player->pland < $player->mland * 0.3 && $player->pland < 1000) {
            $tips[] = ['type' => 'info', 'message' => "You're low on plains land (most buildings need plains). Send explorers seeking <b>Plains</b> specifically."];
        } elseif ($player->mland < 200) {
            $tips[] = ['type' => 'info', 'message' => "You're low on mountain land (needed for mines). Send explorers seeking <b>Mountains</b> specifically."];
        } elseif ($player->fland < 200) {
            $tips[] = ['type' => 'info', 'message' => "You're low on forest land (needed for hunters/woodcutters). Send explorers seeking <b>Forest</b> specifically."];
        }

        // Horses speed up exploration
        if ($player->horses > 0 && $canSend > 0) {
            $tips[] = ['type' => 'info', 'message' => "Sending horses with explorers finds more land per trip (1-3 horses per explorer)."];
        }

        // Large empire congratulation
        if ($totalLand > 10000) {
            $tips[] = ['type' => 'success', 'message' => "Your empire spans " . number_format($totalLand) . " land — excellent expansion!"];
        }

        // No town centers (can't explore)
        if ($player->town_center == 0) {
            $tips[] = ['type' => 'danger', 'message' => "You need at least one town center to send explorers. Build one on the <a href=\"/game/build\">Build</a> page."];
        }

        return array_slice($tips, 0, 3);
    }

    /**
     * Get advisor tips for the Manage page.
     *
     * @return array<array{type: string, message: string}>
     */
    public function getManageTips(Player $player, int $freeWeaponsmiths): array
    {
        $tips = [];

        // Idle weaponsmiths
        if ($freeWeaponsmiths > 0) {
            $tips[] = ['type' => 'warning', 'message' => "You have {$freeWeaponsmiths} idle weaponsmith(s) not producing anything. Assign them to make swords, bows, or maces."];
        }

        // Food ratio negative
        if ($player->food_ratio < 0) {
            $labels = [-1 => 'slightly reduced', -2 => 'severely reduced', -3 => 'starvation-level'];
            $tips[] = ['type' => 'warning', 'message' => "Food rations are {$labels[$player->food_ratio]} — your population is declining. Increase rations when food allows."];
        }

        // Food ratio could be higher
        if ($player->food_ratio == 0 && $player->food > $player->people * 5) {
            $tips[] = ['type' => 'info', 'message' => "You have surplus food. Consider raising rations to grow your population faster."];
        }

        // Land conversion suggestions
        $freeM = $player->mland;
        $freeP = $player->pland;
        if ($freeM > 2000 && $freeP < 500) {
            $tips[] = ['type' => 'info', 'message' => "You have lots of mountain land but need plains. Convert mountain to forest, then forest to plains for building space."];
        }

        // All weaponsmiths assigned — positive
        if ($player->weapon_smith > 0 && $freeWeaponsmiths == 0) {
            $tips[] = ['type' => 'success', 'message' => "All weaponsmiths are fully assigned and producing weapons. Well managed!"];
        }

        return array_slice($tips, 0, 3);
    }

    /**
     * Get advisor tips for the Wall page.
     *
     * @return array<array{type: string, message: string}>
     */
    public function getWallTips(Player $player, int $protection, int $totalWall): array
    {
        $tips = [];

        // No wall at all
        if ($player->wall == 0 && $player->turn > 10) {
            $tips[] = ['type' => 'info', 'message' => "No wall defenses yet. Set builder dedication above to start building your wall."];
        }

        // Low protection
        if ($protection > 0 && $protection < 25 && $player->turn > 30) {
            $tips[] = ['type' => 'warning', 'message' => "Your wall provides only {$protection}% protection. Increase builder dedication to strengthen defenses."];
        }

        // Good protection
        if ($protection >= 75) {
            $tips[] = ['type' => 'success', 'message' => "Excellent wall defenses at {$protection}%! Your empire is well fortified."];
        }

        // Wall construction paused
        if ($player->wall_build_per_turn == 0 && $player->wall < $totalWall) {
            $tips[] = ['type' => 'info', 'message' => "Wall construction is paused (0% builders). Increase builder dedication to build your wall."];
        }

        // Wall complete
        if ($totalWall > 0 && $player->wall >= $totalWall) {
            $tips[] = ['type' => 'success', 'message' => "Your wall is complete! It will grow automatically as your empire expands."];
        }

        return array_slice($tips, 0, 3);
    }

    /**
     * Get advisor tips for the Trade pages.
     *
     * @return array<array{type: string, message: string}>
     */
    public function getTradeTips(Player $player): array
    {
        $tips = [];
        $buildings = $this->gameData->getBuildings($player->civ);

        $townCenterB = $buildings[11];
        $warehouseB = $buildings[13];

        // Warehouse capacity check
        $canHold = $player->town_center * $townCenterB['supplies'] + $player->warehouse * $warehouseB['supplies'];
        $canHold = round($canHold + $canHold * ($player->research8 / 100));
        $totalSupplies = $player->wood + $player->iron + $player->food + $player->tools
            + $player->swords + $player->bows + $player->horses + $player->maces + $player->wine;

        if ($canHold > 0) {
            $pct = round(($totalSupplies / $canHold) * 100);
            if ($pct >= 80) {
                $tips[] = ['type' => 'warning', 'message' => "Warehouses {$pct}% full! Sell excess goods before they overflow."];
            }
        }

        // Auto-trade not configured
        $totalAuto = $player->auto_buy_wood + $player->auto_buy_food + $player->auto_buy_iron + $player->auto_buy_tools
            + $player->auto_sell_wood + $player->auto_sell_food + $player->auto_sell_iron + $player->auto_sell_tools;
        if ($totalAuto == 0 && $player->market > 0) {
            $tips[] = ['type' => 'info', 'message' => "Set up auto-trade to automatically buy or sell resources each turn."];
        }

        // Low gold but has excess resources
        if ($player->gold < 500) {
            if ($player->wood > 5000) {
                $tips[] = ['type' => 'info', 'message' => "Low on gold? Sell your surplus wood (" . number_format($player->wood) . ") at the local market."];
            } elseif ($player->iron > 5000) {
                $tips[] = ['type' => 'info', 'message' => "Low on gold? Sell your surplus iron (" . number_format($player->iron) . ") at the local market."];
            } elseif ($player->food > 5000) {
                $tips[] = ['type' => 'info', 'message' => "Low on gold? Sell your surplus food (" . number_format($player->food) . ") at the local market."];
            }
        }

        // No markets
        if ($player->market == 0 && $player->turn > 15) {
            $tips[] = ['type' => 'info', 'message' => "Build <a href=\"/game/build\">markets</a> to increase your maximum trades per turn."];
        }

        return array_slice($tips, 0, 3);
    }
}
