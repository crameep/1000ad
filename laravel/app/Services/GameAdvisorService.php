<?php

namespace App\Services;

use App\Models\Player;

/**
 * Game Advisor Service
 *
 * Analyzes player state and returns contextual tips for the "Royal Advisor" panel.
 * Provides page-specific advice for every game page.
 *
 * Tip categories:
 * - Reactive alerts: respond to recent attacks, raids, spy events
 * - Early game guidance: step-by-step help for new players (turn-gated)
 * - Threshold alerts: warehouse full, low food, etc. (original tips)
 * - Strategic analysis: economy balance, army composition, civ-specific advice
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

        // ===== REACTIVE ALERTS (highest priority) =====
        $events = $this->getRecentEvents($player);

        if ($events['attacked']) {
            $tips[] = ['type' => 'danger', 'message' => "Your empire was recently attacked! Prioritize rebuilding your army and strengthening <a href=\"/game/wall\">wall defenses</a>."];
        }
        if ($events['raided']) {
            $tips[] = ['type' => 'warning', 'message' => "Thieves have raided your empire. Build more <a href=\"/game/build\">warehouses</a> and <a href=\"/game/wall\">walls</a> to reduce future losses."];
        }
        if ($events['spied']) {
            $tips[] = ['type' => 'warning', 'message' => "Enemy spies have infiltrated your lands. Train <a href=\"/game/army\">thieves</a> and build <a href=\"/game/build\">towers</a> to improve counter-espionage."];
        }
        if ($player->has_new_messages) {
            $tips[] = ['type' => 'info', 'message' => "You have unread <a href=\"/game/messages\">messages</a>. They may contain intelligence or diplomacy from other rulers."];
        }

        // ===== EARLY GAME GUIDANCE (turn-gated) =====
        $deathmatchMode = gameConfig('deathmatch_mode');

        if ($turn <= 5) {
            $tips[] = ['type' => 'info', 'message' => "Welcome, my liege! Focus on building <a href=\"/game/build\">houses</a> and <a href=\"/game/build\">hunters</a> first to grow your population and food supply."];
        } elseif ($turn > 5 && $turn <= 15 && $player->tool_maker <= 10) {
            $tips[] = ['type' => 'info', 'message' => "Your builders need tools to work. Build more <a href=\"/game/build\">tool makers</a> to speed up all construction."];
        }

        if ($turn > 10 && $turn <= 30 && $player->mage_tower == 0) {
            $tips[] = ['type' => 'info', 'message' => "Build <a href=\"/game/build\">mage towers</a> soon. Research bonuses compound over time — the earlier you start, the greater your advantage."];
        }

        if ($turn >= 60 && $turn <= 72 && !$deathmatchMode) {
            $remaining = 72 - $turn;
            $tips[] = ['type' => 'warning', 'message' => "Your protection ends in {$remaining} months! Ensure you have soldiers, a wall building, and food stockpiled before enemies can strike."];
        }

        if ($turn > 72 && $turn <= 85 && $player->thieves == 0 && !$deathmatchMode) {
            $tips[] = ['type' => 'info', 'message' => "You are no longer protected. Train <a href=\"/game/army\">thieves</a> to spy on neighbors before launching attacks — knowledge is power."];
        }

        // ===== THRESHOLD ALERTS (original tips) =====

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

        // ===== STRATEGIC RECOMMENDATIONS =====

        // --- Economy imbalance ---
        if ($player->wood_cutter > $player->iron_mine * 3 && $player->iron < 500 && $turn > 20) {
            $tips[] = ['type' => 'info', 'message' => "Your economy is wood-heavy but iron-starved. Build more iron mines to balance resource production."];
        } elseif ($player->iron_mine > $player->wood_cutter * 3 && $player->wood < 500 && $turn > 20) {
            $tips[] = ['type' => 'info', 'message' => "You have many iron mines but few wood cutters. Wood is needed for buildings, tools, and heating — build more lumber mills."];
        }

        // --- Building status neglect ---
        if ($turn > 20) {
            $statusChecks = [
                ['field' => 'hunter_status', 'count' => 'hunter', 'name' => 'Hunters'],
                ['field' => 'farmer_status', 'count' => 'farmer', 'name' => 'Farms'],
                ['field' => 'iron_mine_status', 'count' => 'iron_mine', 'name' => 'Iron mines'],
                ['field' => 'gold_mine_status', 'count' => 'gold_mine', 'name' => 'Gold mines'],
                ['field' => 'tool_maker_status', 'count' => 'tool_maker', 'name' => 'Tool makers'],
                ['field' => 'weapon_smith_status', 'count' => 'weapon_smith', 'name' => 'Weaponsmiths'],
                ['field' => 'wood_cutter_status', 'count' => 'wood_cutter', 'name' => 'Wood cutters'],
            ];
            foreach ($statusChecks as $check) {
                if ($player->{$check['count']} > 0 && $player->{$check['field']} < 50) {
                    $tips[] = ['type' => 'warning', 'message' => "{$check['name']} are at {$player->{$check['field']}}% efficiency. Increase their status on the <a href=\"/game/build\">Build</a> page to get full production."];
                    break; // only show one status warning
                }
            }
        }

        // --- Civilization-specific strategic advice ---
        if ($turn > 20) {
            $civTip = $this->getCivSpecificTip($player);
            if ($civTip) {
                $tips[] = $civTip;
            }
        }

        // --- Score composition: weak military ---
        if ($player->military_score < 20 && $turn > 50) {
            $tips[] = ['type' => 'warning', 'message' => "Only {$player->military_score}% of your score comes from military. A stronger army deters attacks and earns more score."];
        }

        // --- Gold income vs army upkeep ---
        if ($payGold > 0 && $player->gold_mine > 0) {
            $goldMineB = $buildings[6];
            $goldIncome = round($player->gold_mine * $goldMineB['production'] * ($player->gold_mine_status / 100));
            if ($payGold > $goldIncome * 2 && $goldIncome > 0) {
                $tips[] = ['type' => 'warning', 'message' => "Your army costs {$payGold} gold/turn but your mines produce only ~{$goldIncome}. Build more gold mines or reduce your forces."];
            }
        }

        // Limit to most important 6 tips
        return array_slice($tips, 0, 6);
    }

    /**
     * Get advisor tips for the Build page.
     *
     * @return array<array{type: string, message: string}>
     */
    public function getBuildTips(Player $player, array $buildingStats, $buildQueue, int $free, int $freeMountain, int $freeForest, int $freePlains, array $economy = []): array
    {
        $tips = [];
        $buildings = $this->gameData->getBuildings($player->civ);
        $month = ($player->turn % 12) + 1;

        // ===== REACTIVE ALERTS =====
        if ($player->people < 500 && $player->turn > 30) {
            $tips[] = ['type' => 'danger', 'message' => "Your population has fallen critically low. Increase food rations on the <a href=\"/game/manage\">Manage</a> page and build more houses to recover."];
        }

        // ===== EARLY GAME GUIDANCE =====
        if ($player->turn <= 10) {
            $tips[] = ['type' => 'info', 'message' => "Early priorities: Build houses for population, hunters for food, then wood cutters and iron mines for construction materials."];
        }
        if ($player->turn > 10 && $player->turn <= 40 && $player->fort == 0) {
            $tips[] = ['type' => 'info', 'message' => "Build forts to train soldiers. Without forts, your empire is defenseless once protection ends."];
        }
        if ($player->turn > 20 && $player->turn <= 50 && $player->town_center <= 10) {
            $tips[] = ['type' => 'info', 'message' => "Town centers provide housing, storage, explorer capacity, and army space. Building more is always worthwhile."];
        }

        // ===== THRESHOLD ALERTS (original tips) =====

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
            $tips[] = ['type' => 'danger', 'message' => "You have no food production! Build <a href=\"javascript:openHelp('buildings#hunters')\">hunters</a> (year-round) or <a href=\"javascript:openHelp('buildings#farms')\">farms</a> (summer only)."];
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

        // ===== ECONOMY DEFICITS =====
        if (!empty($economy)) {
            foreach (['food', 'wood', 'iron', 'gold'] as $res) {
                $net = $economy[$res] ?? 0;
                if ($net < 0) {
                    $tips[] = ['type' => 'warning', 'message' => ucfirst($res) . " deficit: <b>" . number_format($net) . "/turn</b>. Build more " . $res . " producers to cover the shortfall."];
                }
            }
        }

        // ===== STRATEGIC RECOMMENDATIONS =====

        // Iron bottleneck: more weaponsmiths than iron mines
        if ($player->weapon_smith > 0 && $player->iron_mine > 0 && $player->iron < 100 && $player->iron_mine < $player->weapon_smith) {
            $tips[] = ['type' => 'warning', 'message' => "You have more weaponsmiths than iron mines. Build more mines to keep weapon production flowing."];
        }

        // Winery suggestion for mid-game
        if ($player->winery == 0 && $player->turn > 40 && $player->fort > 3) {
            $tips[] = ['type' => 'info', 'message' => "Wine doubles your army's attack strength. Build a winery to give your soldiers a decisive advantage."];
        }

        return array_slice($tips, 0, 4);
    }

    /**
     * Get advisor tips for the Army page.
     *
     * @return array<array{type: string, message: string}>
     */
    public function getArmyTips(Player $player, array $armyData, $trainQueue, int $maxSoldiers, int $totalHave, int $canTrain, int $attackPower, int $defensePower, $recentDefenses = null): array
    {
        $tips = [];
        $soldiers = $this->gameData->getSoldiers($player->civ);

        // ===== REACTIVE ALERTS =====

        // Army wiped out post-protection
        if ($totalHave == 0 && $player->turn > 72) {
            $tips[] = ['type' => 'danger', 'message' => "Your army has been destroyed! Train peasants first for quick numbers, then proper soldiers as weapons become available."];
        }

        // Massive army loss: few soldiers but many forts
        if ($totalHave > 0 && $totalHave < 10 && $player->turn > 50 && $player->fort > 5) {
            $tips[] = ['type' => 'danger', 'message' => "Your army is nearly wiped out — only {$totalHave} soldiers remain with {$player->fort} forts. Fill your training queue immediately!"];
        }

        // ===== BATTLE ANALYSIS =====
        if ($recentDefenses && $recentDefenses->isNotEmpty()) {
            $battleTips = $this->analyzeBattleTips($player, $recentDefenses, $soldiers);
            $tips = array_merge($tips, $battleTips);
        }

        // ===== EARLY GAME GUIDANCE =====

        // Under protection, small army
        if ($player->turn <= 72 && $totalHave < 20 && $totalHave > 0) {
            $tips[] = ['type' => 'info', 'message' => "You're still under protection. Use this time to stockpile weapons and train a strong army before the real fighting begins."];
        }

        // Only one unit type — suggest diversity
        if ($player->turn < 50) {
            $types = 0;
            if ($player->swordsman > 0) $types++;
            if ($player->archers > 0) $types++;
            if ($player->horseman > 0) $types++;
            if ($types == 1 && $totalHave > 10) {
                $tips[] = ['type' => 'info', 'message' => "A diverse army is stronger. Archers excel at defense, swordsmen at attack, and horsemen take the most land."];
            }
        }

        // ===== THRESHOLD ALERTS (original tips) =====

        // No army at all (early/mid game)
        if ($totalHave == 0 && $player->turn > 5 && $player->turn <= 72) {
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

        // ===== STRATEGIC RECOMMENDATIONS =====

        // Attack/defense ratio imbalance
        if ($attackPower > 0 && $defensePower > 0 && $defensePower < $attackPower * 0.3 && $player->turn > 40) {
            $tips[] = ['type' => 'warning', 'message' => "Your defense ({$defensePower}) is weak compared to your attack ({$attackPower}). If your army is sent out, your empire is vulnerable. Train archers or build towers."];
        }

        // Towers as free passive defense
        if ($player->tower < 5 && $player->turn > 25 && $totalHave > 0) {
            $towerDef = $soldiers[4]['defense_pt'];
            $tips[] = ['type' => 'info', 'message' => "Towers provide {$towerDef} defense each with zero upkeep cost. Build more for powerful passive defense."];
        }

        // Incas horseman warning
        if ($player->civ == 6 && $player->horseman > 0) {
            $tips[] = ['type' => 'warning', 'message' => "Inca horsemen are nearly useless (1 attack, 1 defense, 80 turns to train). Focus on swordsmen, macemen, and Shamans instead."];
        }

        return array_slice($tips, 0, 4);
    }

    /**
     * Analyze recent battles where the player was defender and generate tips.
     */
    private function analyzeBattleTips(Player $player, $recentDefenses, array $soldiers): array
    {
        $tips = [];

        $losses = $recentDefenses->where('attacker_wins', 1);
        $wins = $recentDefenses->where('attacker_wins', 0);

        // Repeat attacker check
        $attackerCounts = $recentDefenses->groupBy('attack_id');
        foreach ($attackerCounts as $attackerId => $attacks) {
            if ($attacks->count() >= 2) {
                $name = $attacks->first()->attacker->name ?? 'Unknown';
                $tips[] = ['type' => 'danger', 'message' => "Empire \"{$name}\" has attacked you {$attacks->count()} times recently. Bolster your defenses!"];
                break;
            }
        }

        if ($losses->isNotEmpty()) {
            $lastLoss = $losses->first();

            // Summarize attacker's army composition (top 3 unit types by count)
            $enemyArmy = [];
            if ($lastLoss->attack_swordsman > 0) $enemyArmy['swordsmen'] = $lastLoss->attack_swordsman;
            if ($lastLoss->attack_archers > 0) $enemyArmy['archers'] = $lastLoss->attack_archers;
            if ($lastLoss->attack_horseman > 0) $enemyArmy['horsemen'] = $lastLoss->attack_horseman;
            if ($lastLoss->attack_catapults > 0) $enemyArmy['catapults'] = $lastLoss->attack_catapults;
            if ($lastLoss->attack_macemen > 0) $enemyArmy['macemen'] = $lastLoss->attack_macemen;
            if ($lastLoss->attack_thieves > 0) $enemyArmy['thieves'] = $lastLoss->attack_thieves;
            if ($lastLoss->attack_uunit > 0) $enemyArmy['elite'] = $lastLoss->attack_uunit;
            if ($lastLoss->attack_peasants > 0) $enemyArmy['peasants'] = $lastLoss->attack_peasants;

            arsort($enemyArmy);
            $summary = [];
            foreach (array_slice($enemyArmy, 0, 3, true) as $type => $count) {
                $summary[] = number_format($count) . ' ' . $type;
            }
            $armySummary = implode(', ', $summary);
            $tips[] = ['type' => 'danger', 'message' => "You lost a recent battle! The attacker sent {$armySummary}. Strengthen your defense."];

            // Counter-suggestion based on dominant enemy unit type
            $enemyUnits = [
                'swordsman' => $lastLoss->attack_swordsman,
                'archers' => $lastLoss->attack_archers,
                'horseman' => $lastLoss->attack_horseman,
                'catapults' => $lastLoss->attack_catapults,
                'macemen' => $lastLoss->attack_macemen,
            ];
            arsort($enemyUnits);
            $dominant = key($enemyUnits);

            $counters = [
                'swordsman' => 'Archers have the highest defense (12 DEF). Train archers and build towers to counter sword attacks.',
                'archers' => 'Horsemen are fast strikers that can overwhelm archer-heavy attackers. Train horsemen.',
                'horseman' => 'Macemen and strong walls can blunt cavalry charges. Train macemen and upgrade your wall.',
                'catapults' => 'Catapults are devastating but slow to train. Build more towers and forts for passive defense.',
                'macemen' => 'Swordsmen and archers together provide balanced defense against macemen.',
            ];

            if (isset($counters[$dominant])) {
                $tips[] = ['type' => 'warning', 'message' => "Enemy relied on <b>{$dominant}</b>. " . $counters[$dominant]];
            }

            // Strength gap from battle_details
            if ($lastLoss->battle_details) {
                if (preg_match('/Attack Strength:\s*([\d,]+)\s*\|\s*Defense Strength:\s*([\d,]+)/', $lastLoss->battle_details, $m)) {
                    $atkStr = (int) str_replace(',', '', $m[1]);
                    $defStr = (int) str_replace(',', '', $m[2]);
                    if ($atkStr > $defStr * 2) {
                        $tips[] = ['type' => 'warning', 'message' => "Your defense (" . number_format($defStr) . ") was overwhelmed by attack strength of " . number_format($atkStr) . ". You need at least double your current army."];
                    }
                }
            }
        }

        // Won all recent defenses
        if ($wins->isNotEmpty() && $losses->isEmpty()) {
            $tips[] = ['type' => 'success', 'message' => "You've defended successfully in recent battles. Keep building your army to maintain the edge."];
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
            $tips[] = ['type' => 'info', 'message' => "Your army is idle. Scout enemies with <a href=\"javascript:openHelp('army#UNIT7')\">thieves</a> first, then attack to gain land and resources."];
        }

        // Wine reminder — with ratio info
        if ($player->wine > 0 && $totalArmy > 0) {
            if ($player->wine < $totalArmy) {
                $tips[] = ['type' => 'info', 'message' => "You have " . number_format($player->wine) . " wine for " . number_format($totalArmy) . " soldiers. You need 1 wine per soldier for the full attack bonus — build more wineries or send smaller forces."];
            } else {
                $tips[] = ['type' => 'info', 'message' => "You have " . number_format($player->wine) . " wine — enough for your full army. Send wine with attacks for double attack strength."];
            }
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

        // ===== STRATEGIC: No catapults for siege =====
        if ($player->catapults == 0 && $player->turn > 50 && $totalArmy > 50) {
            $tips[] = ['type' => 'info', 'message' => "Catapults can devastate enemy empires by destroying buildings and population. Consider training some for siege attacks."];
        }

        return array_slice($tips, 0, 4);
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

        // ===== EARLY GAME: First research recommendation =====
        $totalLevels = 0;
        for ($i = 1; $i <= 12; $i++) {
            $totalLevels += $player->{"research{$i}"};
        }
        if ($totalLevels == 0 && $player->current_research == 0) {
            $tips[] = ['type' => 'info', 'message' => "Start with <b>Food Production</b> or <b>Attack Points</b> research — they provide the best early-game advantage."];
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
        if ($totalLevels > 36) {
            $tips[] = ['type' => 'success', 'message' => "Your research is well-advanced at {$totalLevels} total levels! Keep investing in mage towers for faster progress."];
        }

        // Mage tower status off
        if ($player->mage_tower_status < 50 && $player->mage_tower > 0) {
            $tips[] = ['type' => 'warning', 'message' => "Your mage towers are at {$player->mage_tower_status}% capacity. Increase their status on the <a href=\"/game/build\">Build</a> page for faster research."];
        }

        return array_slice($tips, 0, 4);
    }

    /**
     * Get advisor tips for the Explore page.
     *
     * @return array<array{type: string, message: string}>
     */
    public function getExploreTips(Player $player, $explorations, int $canSend, int $totalLand): array
    {
        $tips = [];

        // ===== EARLY GAME =====
        if ($player->turn < 30) {
            $tips[] = ['type' => 'info', 'message' => "Land is the foundation of your empire. Send as many explorers as possible now — every acre counts in the early game."];
        }

        // Can send explorers and none active
        $activeCount = $explorations->where('turn', '>', 0)->count();
        if ($canSend > 0 && $activeCount == 0) {
            $tips[] = ['type' => 'info', 'message' => "You can send up to " . number_format($canSend) . " explorers. Explore to expand your territory!"];
        }

        // Land priority suggestions based on current distribution
        if ($totalLand > 0) {
            $mPct = round($player->mland / $totalLand * 100);
            $fPct = round($player->fland / $totalLand * 100);
            $pPct = round($player->pland / $totalLand * 100);

            if ($player->pland < $player->mland * 0.3 && $player->pland < 1000) {
                $tips[] = ['type' => 'info', 'message' => "You're low on plains (most buildings need plains). Try priorities like <b>10% mountains, 20% forest, 70% plains</b>."];
            } elseif ($player->mland < 200) {
                $tips[] = ['type' => 'info', 'message' => "You're low on mountain land (needed for mines). Try priorities like <b>50% mountains, 25% forest, 25% plains</b>."];
            } elseif ($player->fland < 200) {
                $tips[] = ['type' => 'info', 'message' => "You're low on forest land (needed for hunters/woodcutters). Try priorities like <b>15% mountains, 50% forest, 35% plains</b>."];
            } else {
                $tips[] = ['type' => 'info', 'message' => "Your land: {$mPct}% mountains, {$fPct}% forest, {$pPct}% plains. Adjust exploration priorities to target what you need most."];
            }
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

        return array_slice($tips, 0, 4);
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

        // ===== STRATEGIC: One-type weapon production =====
        if ($player->weapon_smith >= 3 && $freeWeaponsmiths == 0) {
            $sword = $player->sword_weapon_smith;
            $bow = $player->bow_weapon_smith;
            $mace = $player->mace_weaponsmith;
            if ($sword > 0 && $bow == 0 && $mace == 0) {
                $tips[] = ['type' => 'info', 'message' => "All weaponsmiths produce only swords. Diversify — archers need bows and macemen need maces for a balanced army."];
            } elseif ($bow > 0 && $sword == 0 && $mace == 0) {
                $tips[] = ['type' => 'info', 'message' => "All weaponsmiths produce only bows. Consider producing swords too — swordsmen are strong attackers."];
            } elseif ($mace > 0 && $sword == 0 && $bow == 0) {
                $tips[] = ['type' => 'info', 'message' => "All weaponsmiths produce only maces. Diversify production to equip different unit types."];
            }
        }

        // All weaponsmiths assigned — positive
        if ($player->weapon_smith > 0 && $freeWeaponsmiths == 0) {
            $tips[] = ['type' => 'success', 'message' => "All weaponsmiths are fully assigned and producing weapons. Well managed!"];
        }

        return array_slice($tips, 0, 4);
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

        // ===== STRATEGIC: Wall resource awareness =====
        if ($player->wall_build_per_turn > 0 && $player->iron < 50) {
            $tips[] = ['type' => 'warning', 'message' => "Wall construction requires iron, wood, and gold. Your iron is running low — build more mines to keep construction going."];
        }

        return array_slice($tips, 0, 4);
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

        // ===== REACTIVE: All resources critically low =====
        if ($player->wood < 100 && $player->food < 100 && $player->iron < 100 && $player->turn > 10) {
            $tips[] = ['type' => 'danger', 'message' => "All your resources are critically low! Buy food and wood immediately — your people will starve and freeze without them."];
        }

        // ===== EARLY GAME: Spend starting gold =====
        if ($player->turn < 15 && $player->gold > 50000) {
            $tips[] = ['type' => 'info', 'message' => "You start with generous gold reserves. Spend it on tools and iron at the market to jumpstart construction."];
        }

        // ===== THRESHOLD ALERTS (original tips) =====

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

        // ===== STRATEGIC: Auto-sell bleeding =====
        if ($player->auto_sell_wood > 0 && $player->wood < 1000) {
            $tips[] = ['type' => 'warning', 'message' => "You're auto-selling wood but only have " . number_format($player->wood) . " left. Disable wood auto-sell before you run out."];
        } elseif ($player->auto_sell_food > 0 && $player->food < 1000) {
            $tips[] = ['type' => 'warning', 'message' => "You're auto-selling food but only have " . number_format($player->food) . " left. Disable food auto-sell before your people starve."];
        } elseif ($player->auto_sell_iron > 0 && $player->iron < 500) {
            $tips[] = ['type' => 'warning', 'message' => "You're auto-selling iron but only have " . number_format($player->iron) . " left. Disable iron auto-sell before production halts."];
        }

        // Buy what you lack most
        if ($player->gold > 5000 && $player->tools < 50 && $player->tool_maker > 0) {
            $tips[] = ['type' => 'info', 'message' => "You have gold but few tools. Buy tools at the market to keep your builders working."];
        }

        return array_slice($tips, 0, 4);
    }

    // ===================================================================
    // Private helpers
    // ===================================================================

    /**
     * Detect recent events from attack/system news for reactive tips.
     * Lightweight query — max 5 rows, indexed columns.
     */
    private function getRecentEvents(Player $player): array
    {
        $recentNews = \App\Models\PlayerMessage::where('to_player_id', $player->id)
            ->where('message_type', 1)
            ->where('created_on', '>=', now()->subHours(24))
            ->orderBy('created_on', 'desc')
            ->limit(5)
            ->pluck('message')
            ->toArray();

        $events = ['attacked' => false, 'raided' => false, 'spied' => false];

        foreach ($recentNews as $msg) {
            $msg = strtolower($msg);
            if (str_contains($msg, 'attacked') || str_contains($msg, 'invasion') || str_contains($msg, 'lost land')) {
                $events['attacked'] = true;
            }
            if (str_contains($msg, 'stole') || str_contains($msg, 'raided') || str_contains($msg, 'stolen')) {
                $events['raided'] = true;
            }
            if (str_contains($msg, 'thief') || str_contains($msg, 'thieves') || str_contains($msg, 'spy') || str_contains($msg, 'poisoned') || str_contains($msg, 'fire')) {
                $events['spied'] = true;
            }
        }

        return $events;
    }

    /**
     * Get a civilization-specific strategic tip.
     */
    private function getCivSpecificTip(Player $player): ?array
    {
        switch ($player->civ) {
            case 1: // Vikings
                if ($player->hunter < $player->farmer && $player->farmer > 5) {
                    return ['type' => 'info', 'message' => "As Vikings, your hunters produce 5 food year-round and lumber mills are cheap. Lean into hunters over farms for reliable food."];
                }
                if ($player->wood_cutter < 15 && $player->turn > 25) {
                    return ['type' => 'info', 'message' => "Viking lumber mills produce 6 wood on just 2 forest land each. Build more to exploit your civilization's strongest economic advantage."];
                }
                break;

            case 2: // Franks
                if ($player->tower < 10 && $player->turn > 30) {
                    return ['type' => 'info', 'message' => "Franks have the strongest towers (65 defense each) and they're cheap to build. A tower wall makes your empire nearly impregnable."];
                }
                if ($player->farmer < 10 && $player->turn > 15) {
                    return ['type' => 'info', 'message' => "Frankish farms use only 2 land each — the cheapest in the world. Build many farms for efficient food production."];
                }
                break;

            case 3: // Japanese
                if ($player->farmer < $player->hunter && $player->turn > 20) {
                    return ['type' => 'info', 'message' => "Japanese farms produce 10 food each — the best of any civilization. Build more farms to leverage your bonus."];
                }
                if ($player->mage_tower < 5 && $player->turn > 30) {
                    return ['type' => 'info', 'message' => "Japanese mage towers produce 50% more research points. Invest heavily in research to outpace your rivals."];
                }
                break;

            case 4: // Byzantines
                if ($player->gold_mine < 15 && $player->turn > 25) {
                    return ['type' => 'info', 'message' => "Byzantine gold mines produce 200 gold each — double the standard. Build more to fund your powerful catapults and army."];
                }
                if ($player->warehouse < 10 && $player->turn > 20) {
                    return ['type' => 'info', 'message' => "Byzantine warehouses hold 5000 supplies each — double normal capacity. You need fewer warehouses than other civilizations."];
                }
                break;

            case 5: // Mongols
                if ($player->uunit < 10 && $player->turn > 40) {
                    return ['type' => 'info', 'message' => "Mongol Horse Archers are cheap (100 gold) and fast to train. Mass-produce them to overwhelm your enemies with numbers."];
                }
                if ($player->weapon_smith < 5 && $player->turn > 20) {
                    return ['type' => 'info', 'message' => "Mongol weaponsmiths produce double weapons. Build more to arm your horde faster than any rival."];
                }
                break;

            case 6: // Incas
                if ($player->horseman > 0) {
                    return ['type' => 'warning', 'message' => "Inca horsemen take 80 turns to train and have only 1 attack/defense — they're useless. Focus on swordsmen, macemen, and Shamans."];
                }
                if ($player->uunit < 3 && $player->turn > 40) {
                    return ['type' => 'info', 'message' => "Inca Shamans grab 5 land per unit — massive territory gains. They're expensive but worth the investment for expansion."];
                }
                break;
        }

        return null;
    }
}
