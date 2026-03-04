<?php

namespace App\Services;

use App\Models\Player;
use App\Models\BuildQueue;
use App\Models\TrainQueue;
use App\Models\ExploreQueue;
use App\Models\AttackQueue;
use App\Models\TransferQueue;
use App\Models\PlayerMessage;

/**
 * Turn Processing Service
 *
 * The most critical service in the game -- processes everything that happens
 * when a player ends their turn. Faithful port of end_turn.cfm.
 *
 * Original: Andrew Deren, (C) AderSoftware 2000, 2001
 */
class TurnService
{
    protected GameDataService $gameDataService;
    protected ScoreService $scoreService;
    protected CombatService $combatService;

    /**
     * Flag to stop multi-turn processing (food crisis, warehouse overflow, etc.)
     */
    protected bool $shouldStopProcessing = false;

    public function __construct(GameDataService $gameDataService, ScoreService $scoreService, CombatService $combatService)
    {
        $this->gameDataService = $gameDataService;
        $this->scoreService = $scoreService;
        $this->combatService = $combatService;
    }

    /**
     * Process a single turn for a player.
     *
     * @param Player $player The player model (will be refreshed from DB)
     * @return string HTML message string describing what happened this turn
     */
    public function processTurn(Player $player): string
    {
        $this->shouldStopProcessing = false;

        // Load game data for this player's civilization
        $buildings = $this->gameDataService->getBuildings($player->civ);
        $soldiers = $this->gameDataService->getSoldiers($player->civ);
        $constants = $this->gameDataService->getConstants($player->civ);
        $localPrices = $this->gameDataService->getLocalPrices();
        $researchNames = $this->gameDataService->getResearchNames();
        $wallCosts = gameConfig('wall');

        // Shorthand references to specific building data
        $hunterB = $buildings[2];
        $farmerB = $buildings[3];
        $houseB = $buildings[4];
        $ironMineB = $buildings[5];
        $goldMineB = $buildings[6];
        $toolMakerB = $buildings[7];
        $weaponSmithB = $buildings[8];
        $fortB = $buildings[9];
        $townCenterB = $buildings[11];
        $warehouseB = $buildings[13];
        $stableB = $buildings[14];
        $mageTowerB = $buildings[15];
        $wineryB = $buildings[16];
        $woodCutterB = $buildings[1];

        // Soldier data references
        $swordsmanA = $soldiers[2];
        $archerA = $soldiers[1];
        $horsemanA = $soldiers[3];
        $macemanA = $soldiers[6];
        $trainedPeasantA = $soldiers[7];
        $thievesA = $soldiers[8];
        $uunitA = $soldiers[9];

        // Create a working copy of player data (newP equivalent)
        // We modify this array and only save at the end
        $newP = (object) $player->getAttributes();

        // Calculate the new turn number and date
        $tempTurn = $newP->turn + 1;
        $month = $tempTurn % 12 + 1;
        $year = intval($tempTurn / 12) + 1000;
        $monthName = date('F', mktime(0, 0, 0, $month, 1));

        $message = "<font color=yellow><b>------------------------------ {$monthName} {$year} ------------------------------</b></font><br>";

        // Current date for messages
        $theDate = now()->format('m/d/Y h:i A');

        // =====================================================================
        // A. Process incoming public market transfers
        // =====================================================================
        TransferQueue::where(function ($query) use ($player) {
            $query->where('from_player_id', $player->id)
                  ->where('transfer_type', 0)
                  ->where('turns_remaining', '>', 0);
        })->orWhere(function ($query) use ($player) {
            $query->where('to_player_id', $player->id)
                  ->where('transfer_type', 2)
                  ->where('turns_remaining', '>', 0);
        })->decrement('turns_remaining');

        $arrivedTransfers = TransferQueue::where('to_player_id', $player->id)
            ->where('transfer_type', 2)
            ->where('turns_remaining', 0)
            ->get();

        foreach ($arrivedTransfers as $tq) {
            $message .= "<font color=yellow>A transport with {$tq->wood} wood, {$tq->food} food, {$tq->iron} iron, {$tq->tools} tools, {$tq->maces} maces, {$tq->swords} swords, {$tq->bows} bows and {$tq->horses} horses arrived from public market.</font><br>";
            $newP->wood += $tq->wood;
            $newP->food += $tq->food;
            $newP->iron += $tq->iron;
            $newP->tools += $tq->tools;
            $newP->maces += $tq->maces;
            $newP->swords += $tq->swords;
            $newP->bows += $tq->bows;
            $newP->horses += $tq->horses;
            $tq->delete();
        }

        // =====================================================================
        // B. Calculate builders
        // =====================================================================
        $numBuilders = $toolMakerB['num_builders'] * $newP->tool_maker + 3;
        if ($numBuilders > $newP->people) {
            $numBuilders = round($newP->people / 2);
        }
        if ($numBuilders > $newP->tools) {
            $message .= "<font color=red>You do not have enough tools for all of your builders</font><br>";
            $numBuilders = $newP->tools;
        }

        // =====================================================================
        // C. Resource Production
        // =====================================================================

        // Remaining resources (working copies)
        $rPeople = $newP->people;
        $rFood = $newP->food;
        $rWood = $newP->wood;
        $rIron = $newP->iron;
        $rGold = $newP->gold;
        $rTools = $newP->tools;
        $rSwords = $newP->swords;
        $rBows = $newP->bows;
        $rHorses = $newP->horses;
        $rMaces = $newP->maces;
        $rWine = $newP->wine;

        // Produced this turn
        $pFood = 0;
        $pWood = 0;
        $pIron = 0;
        $pGold = 0;
        $pTools = 0;
        $pSwords = 0;
        $pBows = 0;
        $pHorses = 0;
        $pMaces = 0;
        $pWine = 0;

        // Consumed this turn
        $cFood = 0;
        $cWood = 0;
        $cIron = 0;
        $cGold = 0;
        $cTools = 0;
        $cSwords = 0;
        $cBows = 0;
        $cHorses = 0;
        $cMaces = 0;
        $cWine = 0;

        // --- Hunters produce food ---
        if ($newP->hunter_status > 0) {
            $canProduce = round($newP->hunter * ($newP->hunter_status / 100));
            $peopleNeed = $canProduce * $hunterB['workers'];
            if ($rPeople < $peopleNeed) {
                $canProduce = intval($rPeople / $hunterB['workers']);
                $message .= "<font color=Red>Not enough people to work at hunters.<br></font>";
            }
            $rPeople -= $canProduce * $hunterB['workers'];
            $getFood = $canProduce * $hunterB['production'];
            $getFood = $getFood + round($getFood * ($newP->research5 / 100));
            $pFood += $getFood;
        }

        // --- Farms produce food (only months 4-10) ---
        if ($newP->farmer_status > 0) {
            if ($month >= 4 && $month <= 10) {
                $canProduce = round($newP->farmer * ($newP->farmer_status / 100));
                $peopleNeed = $canProduce * $farmerB['workers'];
                if ($rPeople < $peopleNeed) {
                    $canProduce = intval($rPeople / $farmerB['workers']);
                    $message .= "<font color=Red>Not enough people to work on farms.<br></font>";
                }
                $rPeople -= $canProduce * $farmerB['workers'];
                $getFood = $canProduce * $farmerB['production'];
                $getFood = $getFood + round($getFood * ($newP->research5 / 100));
                $pFood += $getFood;
            }
        }
        $rFood = $rFood + $pFood; // new remaining food

        // --- Wood cutters produce wood ---
        if ($newP->wood_cutter_status > 0) {
            $canProduce = round($newP->wood_cutter * ($newP->wood_cutter_status / 100));
            $peopleNeed = $canProduce * $woodCutterB['workers'];
            if ($rPeople < $peopleNeed) {
                $canProduce = intval($rPeople / $woodCutterB['workers']);
                $message .= "<font color=Red>Not enough people to work at woodcutters.<br></font>";
            }
            $rPeople -= $canProduce * $woodCutterB['workers'];
            $getWood = $canProduce * $woodCutterB['production'];
            $getWood = $getWood + round($getWood * ($newP->research12 / 100));
            $pWood = $getWood;
            $rWood = $rWood + $pWood;
        }

        // --- Winter wood burning (months 11, 12, 1, 2) ---
        $peopleDied = 0;
        $burnWood = round($newP->people / $constants['people_burn_one_wood']);
        if ($month >= 11 || $month <= 2) {
            $rWood -= $burnWood;
            $cWood += $burnWood;
            $message .= "{$burnWood} wood was used for heat<br>";
            if ($rWood < 0) {
                $peopleWithNoHeat = (int) ceil((abs($rWood) * $constants['people_burn_one_wood']) / 8);
                if ($peopleWithNoHeat > $newP->people) {
                    $peopleWithNoHeat = $newP->people - 1;
                }
                $peopleFreeze = mt_rand(max(1, intval($peopleWithNoHeat / 2)), max(1, $peopleWithNoHeat));
                $newP->people -= $peopleFreeze;
                $message .= "<font color=red>{$peopleFreeze} people froze to death due to the lack of wood for heat</font><br>";
                $rWood = 0;
            }
        }

        // --- Soldiers eat food ---
        $numSoldiers = $newP->swordsman + $newP->archers + $newP->horseman * 2
            + $newP->macemen + round($newP->trained_peasants * 0.1)
            + $newP->thieves * 3 + $newP->uunit * 2;

        if ($numSoldiers > 0) {
            $eatFood = (int) ceil($numSoldiers / $constants['soldiers_eat_one_food']);
            $cFood += $eatFood;
            $rFood -= $eatFood;
            $message .= "Your soldiers ate {$eatFood} food<br>";
            if ($rFood < 0) {
                $sDie = (int) ceil((abs($rFood) * $constants['soldiers_eat_one_food']) / 15);
                $message .= "<font color=red>some soldiers died due to the lack of food</font><br>";
                $newP->swordsman -= $sDie;
                $newP->archers -= $sDie;
                $newP->horseman -= $sDie;
                $newP->macemen -= $sDie;
                $newP->trained_peasants -= $sDie;
                $newP->thieves -= $sDie;
                $newP->uunit -= $sDie;
                if ($newP->swordsman < 0) $newP->swordsman = 0;
                if ($newP->archers < 0) $newP->archers = 0;
                if ($newP->horseman < 0) $newP->horseman = 0;
                if ($newP->macemen < 0) $newP->macemen = 0;
                if ($newP->trained_peasants < 0) $newP->trained_peasants = 0;
                if ($newP->thieves < 0) $newP->thieves = 0;
                if ($newP->uunit < 0) $newP->uunit = 0;
                $rFood = 0;
            }
        }

        // --- Gold mines produce gold ---
        if ($newP->gold_mine_status > 0) {
            $canProduce = round($newP->gold_mine * ($newP->gold_mine_status / 100));
            $peopleNeed = $canProduce * $goldMineB['workers'];
            if ($rPeople < $peopleNeed) {
                $canProduce = intval($rPeople / $goldMineB['workers']);
                $message .= "<font color=Red>Not enough people to work at gold mines.<br></font>";
            }
            $rPeople -= $canProduce * $goldMineB['workers'];
            $getGold = $goldMineB['production'] * $canProduce;
            $getGold = $getGold + round($getGold * ($newP->research6 / 100));
            $pGold = $getGold;
            $rGold += $pGold;
        }

        // --- Iron mines produce iron ---
        if ($newP->iron_mine_status > 0) {
            $canProduce = round($newP->iron_mine * ($newP->iron_mine_status / 100));
            $peopleNeed = $canProduce * $ironMineB['workers'];
            if ($rPeople < $peopleNeed) {
                $canProduce = intval($rPeople / $ironMineB['workers']);
                $message .= "<font color=Red>Not enough people to work at iron mines.<br></font>";
            }
            $rPeople -= $canProduce * $ironMineB['workers'];
            $getIron = $ironMineB['production'] * $canProduce;
            $getIron = $getIron + round($getIron * ($newP->research6 / 100));
            $pIron = $getIron;
            $rIron += $pIron;
        }

        // --- Tool makers produce tools (consume wood + iron) ---
        if ($newP->tool_maker_status > 0) {
            $canProduce = round($newP->tool_maker * ($newP->tool_maker_status / 100));
            $peopleNeed = $canProduce * $toolMakerB['workers'];
            if ($rPeople < $peopleNeed) {
                $canProduce = intval($rPeople / $toolMakerB['workers']);
                $message .= "<font color=Red>Not enough people to work at tool makers.<br></font>";
            }
            $woodNeed = $canProduce * $toolMakerB['wood_need'];
            if ($rWood < $woodNeed) {
                $canProduce = intval($rWood / $toolMakerB['wood_need']);
                $message .= "<font color=Red>Not enough wood to produce all tools.<br></font>";
            }
            $ironNeed = $canProduce * $toolMakerB['iron_need'];
            if ($rIron < $ironNeed) {
                $canProduce = intval($rIron / $toolMakerB['iron_need']);
                $message .= "<font color=Red>Not enough iron to produce all tools.<br></font>";
            }
            $rPeople -= $canProduce * $toolMakerB['workers'];
            $cWood += $canProduce * $toolMakerB['wood_need'];
            $rWood -= $canProduce * $toolMakerB['wood_need'];
            $cIron += $canProduce * $toolMakerB['iron_need'];
            $rIron -= $canProduce * $toolMakerB['iron_need'];
            $pTools = $canProduce * $toolMakerB['production'];
            $pTools = $pTools + round($pTools * ($newP->research7 / 100));
            $rTools += $pTools;
        }

        // --- Weapon smiths produce weapons ---
        if ($newP->weapon_smith_status > 0) {
            // If total assigned weaponsmiths exceed actual count, equalize
            if ($newP->sword_weapon_smith + $newP->bow_weapon_smith + $newP->mace_weaponsmith > $newP->weapon_smith) {
                $newP->sword_weapon_smith = intval($newP->weapon_smith / 3);
                $newP->mace_weaponsmith = intval($newP->weapon_smith / 3);
                $newP->bow_weapon_smith = intval($newP->weapon_smith / 3);
            }

            // -- Swords --
            $canProduce = round($newP->sword_weapon_smith * ($newP->weapon_smith_status / 100));
            $peopleNeed = $canProduce * $weaponSmithB['workers'];
            if ($rPeople < $peopleNeed) {
                $canProduce = intval($rPeople / $weaponSmithB['workers']);
                $message .= "<font color=Red>Not enough people to produce swords.<br></font>";
            }
            $ironNeed = $canProduce * $weaponSmithB['iron_need'];
            if ($rIron < $ironNeed) {
                $canProduce = intval($rIron / $weaponSmithB['iron_need']);
                $message .= "<font color=Red>Not enough iron to produce all swords.<br></font>";
            }
            $rPeople -= $canProduce * $weaponSmithB['workers'];
            $cIron += $canProduce * $weaponSmithB['iron_need'];
            $rIron -= $canProduce * $weaponSmithB['iron_need'];
            $pSwords = $canProduce * $weaponSmithB['production'];
            $pSwords = $pSwords + round($pSwords * ($newP->research7 / 100));
            $rSwords += $pSwords;

            // -- Bows --
            $canProduce = round($newP->bow_weapon_smith * ($newP->weapon_smith_status / 100));
            $peopleNeed = $canProduce * $weaponSmithB['workers'];
            if ($rPeople < $peopleNeed) {
                $canProduce = intval($rPeople / $weaponSmithB['workers']);
                $message .= "<font color=Red>Not enough people to produce bows.<br></font>";
            }
            $woodNeed = $canProduce * $weaponSmithB['wood_need'];
            if ($rWood < $woodNeed) {
                $canProduce = intval($rWood / $weaponSmithB['wood_need']);
                $message .= "<font color=Red>Not enough wood to produce all bows.<br></font>";
            }
            $rPeople -= $canProduce * $weaponSmithB['workers'];
            $cWood += $canProduce * $weaponSmithB['wood_need'];
            $rWood -= $canProduce * $weaponSmithB['wood_need'];
            $pBows = $canProduce * $weaponSmithB['production'];
            $pBows = $pBows + round($pBows * ($newP->research7 / 100));
            $rBows += $pBows;

            // -- Maces --
            $canProduce = round($newP->mace_weaponsmith * ($newP->weapon_smith_status / 100));
            $peopleNeed = $canProduce * $weaponSmithB['workers'];
            if ($rPeople < $peopleNeed) {
                $canProduce = intval($rPeople / $weaponSmithB['workers']);
                $message .= "<font color=Red>Not enough people to produce maces.<br></font>";
            }
            $woodNeed = $canProduce * $weaponSmithB['mace_wood'];
            if ($rWood < $woodNeed) {
                $canProduce = intval($rWood / $weaponSmithB['mace_wood']);
                $message .= "<font color=Red>Not enough wood to produce all maces.<br></font>";
            }
            $ironNeed = $canProduce * $weaponSmithB['mace_iron'];
            if ($rIron < $ironNeed) {
                $canProduce = intval($rIron / $weaponSmithB['mace_iron']);
                $message .= "<font color=Red>Not enough iron to produce all maces.<br></font>";
            }
            $rPeople -= $canProduce * $weaponSmithB['workers'];
            $cWood += $canProduce * $weaponSmithB['mace_wood'];
            $rWood -= $canProduce * $weaponSmithB['mace_wood'];
            $cIron += $canProduce * $weaponSmithB['mace_iron'];
            $rIron -= $canProduce * $weaponSmithB['mace_iron'];
            $pMaces = $canProduce * $weaponSmithB['production'];
            $pMaces = $pMaces + round($pMaces * ($newP->research7 / 100));
            $rMaces += $pMaces;
        }

        // --- Stables produce horses (consume food) ---
        if ($newP->stable_status > 0) {
            $canProduce = round($newP->stable * ($newP->stable_status / 100));
            $peopleNeed = $canProduce * $stableB['workers'];
            if ($rPeople < $peopleNeed) {
                $canProduce = intval($rPeople / $stableB['workers']);
                $message .= "<font color=Red>Not enough people to work at stables.<br></font>";
            }
            $foodNeed = $canProduce * $stableB['food_need'];
            if ($rFood < $foodNeed) {
                $canProduce = intval($rFood / $stableB['food_need']);
                $message .= "<font color=Red>Not enough food to produce all horses.<br></font>";
            }
            $rPeople -= $canProduce * $stableB['workers'];
            $cFood += $canProduce * $stableB['food_need'];
            $rFood -= $canProduce * $stableB['food_need'];
            $pHorses = $stableB['production'] * $canProduce;
            $rHorses += $stableB['production'] * $canProduce;
        }

        // --- Wineries produce wine (consume gold) ---
        if ($newP->winery_status > 0) {
            $canProduce = round($newP->winery * ($newP->winery_status / 100));
            $peopleNeed = $canProduce * $wineryB['workers'];
            if ($rPeople < $peopleNeed) {
                $canProduce = intval($rPeople / $wineryB['workers']);
                $message .= "<font color=Red>Not enough people to work at winery.<br></font>";
            }
            $goldNeed = $canProduce * $wineryB['gold_need'];
            if ($rGold < $goldNeed) {
                $canProduce = intval($rGold / $wineryB['gold_need']);
                $message .= "<font color=Red>Not enough gold to produce wine.<br></font>";
            }
            // Note: original CF code used mageTowerB.workers here (likely a bug), porting faithfully
            $rPeople -= $canProduce * $mageTowerB['workers'];
            $cGold += $canProduce * $wineryB['gold_need'];
            $rGold -= $canProduce * $wineryB['gold_need'];
            $getWine = $wineryB['production'] * $canProduce;
            $pWine = $getWine;
            $rWine += $pWine;
        }

        // --- Mage towers produce research points (consume gold) ---
        if ($newP->mage_tower_status > 0 && $newP->current_research > 0) {
            $canProduce = round($newP->mage_tower * ($newP->mage_tower_status / 100));
            $peopleNeed = $canProduce * $mageTowerB['workers'];
            if ($rPeople < $peopleNeed) {
                $canProduce = intval($rPeople / $mageTowerB['workers']);
                $message .= "<font color=Red>Not enough people to work at magetowers.<br></font>";
            }
            $goldNeed = $canProduce * $mageTowerB['gold_need'];
            if ($rGold < $goldNeed) {
                $canProduce = intval($rGold / $mageTowerB['gold_need']);
                $message .= "<font color=Red>Not enough gold to do all research.<br></font>";
            }
            $rPeople -= $canProduce * $mageTowerB['workers'];
            $cGold += $canProduce * $mageTowerB['gold_need'];
            $rGold -= $canProduce * $mageTowerB['gold_need'];
            $newP->research_points += round($canProduce * $mageTowerB['production']);
        }

        // =====================================================================
        // D. Research completion
        // =====================================================================
        if ($newP->current_research > 0 && $newP->research_points > 0) {
            $totalResearches = $newP->research1 + $newP->research2 + $newP->research3
                + $newP->research4 + $newP->research5 + $newP->research6
                + $newP->research7 + $newP->research8 + $newP->research9
                + $newP->research10 + $newP->research11 + $newP->research12;

            $needResearchPoints = round($totalResearches * $totalResearches * sqrt($totalResearches) + 10);

            while ($newP->research_points >= $needResearchPoints) {
                $newP->research_points -= $needResearchPoints;
                $totalResearches++;

                if ($newP->current_research == 4 && $newP->research4 >= 50) {
                    $message .= "<font color=red>You can only have up 50 research levels for military loss<br></font>";
                    $newP->research_points = $needResearchPoints;
                    break;
                } else {
                    $researchField = 'research' . $newP->current_research;
                    $newP->$researchField = $newP->$researchField + 1;
                    $researchName = $researchNames[$newP->current_research] ?? 'Unknown';
                    $message .= "<font color=yellow>Finished research of {$researchName}<br></font>";
                    $needResearchPoints = round($totalResearches * $totalResearches * sqrt($totalResearches) + 10);
                }
            }
        }

        // =====================================================================
        // E. Wall construction and decay
        // =====================================================================
        $totalLand = $player->mland + $player->fland + $player->pland;
        $totalWall = round($totalLand * 0.05);

        // 25% chance of wall decay
        $dRange = mt_rand(1, 100);
        if ($dRange <= 25 && $newP->wall > 10) {
            $decay = round($newP->wall * ($dRange / 2000));
            if ($decay > 0) {
                $message .= "<font color=red>" . number_format($decay) . " units of wall detoriated</font><br>";
                $newP->wall -= $decay;
            }
        }

        // Wall construction
        $wallUseGold = $wallCosts['gold'];
        $wallUseWood = $wallCosts['wood'];
        $wallUseIron = $wallCosts['iron'];
        $wallUseWine = $wallCosts['wine'];

        if ($newP->wall_build_per_turn > 0 && $newP->wall < $totalWall) {
            $bPercent = $newP->wall_build_per_turn / 100;
            $wallBuilders = round($numBuilders * $bPercent);
            $canProduce = intval($wallBuilders / 10);
            if ($canProduce + $newP->wall > $totalWall) {
                $canProduce = $totalWall - $newP->wall;
            }

            $goldNeed = $canProduce * $wallUseGold;
            if ($rGold < $goldNeed) {
                $canProduce = intval($rGold / $wallUseGold);
                $message .= "<font color=Red>Not enough gold for constuction of the great wall.<br></font>";
            }
            $woodNeed = $canProduce * $wallUseWood;
            if ($rWood < $woodNeed) {
                $canProduce = intval($rWood / $wallUseWood);
                $message .= "<font color=Red>Not enough wood for constuction of the great wall.<br></font>";
            }
            $ironNeed = $canProduce * $wallUseIron;
            if ($rIron < $ironNeed) {
                $canProduce = intval($rIron / $wallUseIron);
                $message .= "<font color=Red>Not enough iron for constuction of the great wall.<br></font>";
            }
            $wineNeed = $canProduce * $wallUseWine;
            if ($rWine < $wineNeed) {
                $canProduce = intval($rWine / $wallUseWine);
                $message .= "<font color=Red>Not enough wine for constuction of the great wall.<br></font>";
            }

            if ($canProduce > 0) {
                $cGold += $canProduce * $wallUseGold;
                $rGold -= $canProduce * $wallUseGold;
                $cWood += $canProduce * $wallUseWood;
                $rWood -= $canProduce * $wallUseWood;
                $cIron += $canProduce * $wallUseIron;
                $rIron -= $canProduce * $wallUseIron;
                $cWine += $canProduce * $wallUseWine;
                $rWine -= $canProduce * $wallUseWine;
                $newP->wall += $canProduce;
                $message .= "<font color=yellow>Constructed {$canProduce} units of wall.<br></font>";
                $numBuilders -= ($canProduce * 10);
            }
        }

        // =====================================================================
        // F. People eat food
        // =====================================================================
        $foodEaten = round($newP->people / $constants['people_eat_one_food']);

        // Adjust food eaten based on food ratio
        switch ($newP->food_ratio) {
            case 1:  $foodEaten = round($foodEaten * 1.5); break;
            case 2:  $foodEaten = round($foodEaten * 2.5); break;
            case 3:  $foodEaten = round($foodEaten * 4); break;
            case -1: $foodEaten = round($foodEaten * 0.75); break;
            case -2: $foodEaten = round($foodEaten * 0.45); break;
            case -3: $foodEaten = round($foodEaten * 0.25); break;
        }

        $message .= "Your people ate {$foodEaten} food<br>";
        $rFood -= $foodEaten;
        $cFood += $foodEaten;

        // Population growth/decline based on food ratio
        $growth = 0;
        switch ($player->food_ratio) {
            case -3: $growth = mt_rand(-500, -300); break;
            case -2: $growth = mt_rand(-300, -150); break;
            case -1: $growth = mt_rand(-150, -75); break;
            case 0:  $growth = mt_rand(-15, 75); break;
            case 1:  $growth = mt_rand(75, 150); break;
            case 2:  $growth = mt_rand(150, 300); break;
            case 3:  $growth = mt_rand(300, 500); break;
        }

        if ($rFood < 0) {
            // Some people did not get food
            $peopleDie = round((abs($rFood) * $constants['people_eat_one_food']) / 6);
            if ($peopleDie > $newP->people * 0.1) {
                $peopleDie = round($newP->people * 0.1);
            }
            $message .= "<font color=red>{$peopleDie} people died due to lack of food</font><br>";
            $newP->people -= $peopleDie;
            if ($newP->people < $newP->town_center + $newP->house) {
                $newP->people = $newP->town_center + $newP->house;
            }
            $rFood = 0;
            $growth = 0;
            $this->shouldStopProcessing = true;
        }

        // Housing capacity
        $houseSpace = $newP->house * $houseB['people'] + $newP->town_center * $townCenterB['people'];
        $houseSpace = round($houseSpace + $houseSpace * ($newP->research8 / 100));

        if ($growth > 0 && $houseSpace > $newP->people) {
            // People can only grow if they get enough food and have housing
            $peopleCome = round(($growth / 10000) * $player->people);
            $message .= "Your population increased by {$peopleCome}<br>";
            $rPeople += $peopleCome;
            $newP->people += $peopleCome;
            if ($newP->people > $houseSpace) {
                $newP->people = $houseSpace;
            }
        } elseif ($growth < 0) {
            // Negative food ratio causes people to leave
            $peopleLeave = abs(round(($growth / 10000) * $player->people));
            $message .= "Due to poor food rationing your population decreased by {$peopleLeave} people<br>";
            $newP->people -= $peopleLeave;
        } elseif ($growth > 0 && $houseSpace == $newP->people) {
            $message .= "Lack of housing prevents further growth of population.<br>";
        }

        // Check if enough housing
        if ($newP->people > $houseSpace) {
            $peopleLeave = (int) ceil(($newP->people - $houseSpace) / 2);
            $newP->people -= $peopleLeave;
            $message .= "<font color=red>Due to lack of housing {$peopleLeave} people emigrated from your empire</font><br>";
        }

        // =====================================================================
        // G. Building queue processing
        // =====================================================================
        $buildQueueItems = BuildQueue::where('player_id', $player->id)
            ->orderBy('pos')
            ->get();

        // Calculate used land and total buildings
        $mUsed = 0;
        $fUsed = 0;
        $pUsed = 0;
        $totalBuildings = 0;

        for ($i = 1; $i <= count($buildings); $i++) {
            if (!isset($buildings[$i])) continue;
            $thisB = $buildings[$i];
            $dbCol = $thisB['db_column'];
            $has = $player->$dbCol;
            $totalBuildings += $has;
            if ($thisB['land'] === 'M') {
                $mUsed += $has * $thisB['sq'];
            } elseif ($thisB['land'] === 'F') {
                $fUsed += $has * $thisB['sq'];
            } else {
                $pUsed += $has * $thisB['sq'];
            }
        }

        $buildMoves = $numBuilders;

        foreach ($buildQueueItems as $b) {
            $building = $buildings[$b->building_no];
            $needLand = $building['sq'];

            if ($building['land'] === 'M') {
                $hasLand = $newP->mland - $mUsed;
            } elseif ($building['land'] === 'F') {
                $hasLand = $newP->fland - $fUsed;
            } else {
                $hasLand = $newP->pland - $pUsed;
            }

            if ($hasLand <= 0 && $b->mission == 0) {
                $message .= "<font color=red>You do not have any free {$building['land']} land to build {$building['name']}</font><br>";
                $timeRemaining = -1;
            } else {
                $buildMovesSave = $buildMoves;
                $bNeedTime = $building['cost_wood'] + $building['cost_iron']; // time needed for one building

                $timeRemaining = $b->time_needed;
                if ($buildMoves > $timeRemaining) {
                    $buildMoves -= $timeRemaining;
                    $timeRemaining = 0;
                } else {
                    $timeRemaining -= $buildMoves;
                    $buildMoves = 0;
                }

                // How many buildings were built or demolished
                $qtyRemaining = (int) ceil($timeRemaining / max(1, $bNeedTime));
                $qtyBuild = $b->qty - $qtyRemaining;

                $landTaken = $qtyBuild * $building['sq'];
                if ($landTaken > $hasLand && $b->mission == 0) {
                    // Cannot build, not enough land
                    $qtyBuild = intval($hasLand / $building['sq']);
                    $qtyRemaining = $b->qty - $qtyBuild;
                    $buildMoves += $qtyRemaining * $bNeedTime;
                    $timeRemaining = $qtyRemaining * $bNeedTime;
                    $landTaken = $qtyBuild * $building['sq'];
                    $message .= "<font color=red>Not enough land (" . ($qtyRemaining * $building['sq']) . " {$building['land']}) to process construction of {$building['name']}</font><br>";
                }

                $dbCol = $building['db_column'];
                if ($qtyBuild > 0 && $b->mission == 0) {
                    // Built some buildings
                    $message .= "<font color=yellow>Finished construction of {$qtyBuild} {$building['name']}s</font><br>";
                    if ($building['land'] === 'M') {
                        $mUsed += $landTaken;
                    } elseif ($building['land'] === 'F') {
                        $fUsed += $landTaken;
                    } else {
                        $pUsed += $landTaken;
                    }

                    $hasBuildings = $newP->$dbCol + $qtyBuild;
                    if ($dbCol === 'weapon_smith') {
                        // When building weaponsmiths, distribute evenly
                        if ($newP->bow_weapon_smith * 2 <= $newP->sword_weapon_smith) {
                            $newP->bow_weapon_smith += $qtyBuild;
                        } else {
                            $newP->sword_weapon_smith += $qtyBuild;
                        }
                    }
                    $newP->$dbCol = $hasBuildings;
                } elseif ($qtyBuild > 0 && $b->mission == 1) {
                    // Demolished some buildings
                    $message .= "<font color=yellow>Demolished {$qtyBuild} {$building['name']}s</font><br>";
                    if ($building['land'] === 'M') {
                        $mUsed -= $landTaken;
                    } elseif ($building['land'] === 'F') {
                        $fUsed -= $landTaken;
                    } else {
                        $pUsed -= $landTaken;
                    }

                    $hasBuildings = $newP->$dbCol - $qtyBuild;
                    if ($dbCol === 'weapon_smith') {
                        if ($newP->bow_weapon_smith > $newP->sword_weapon_smith * 2) {
                            $newP->bow_weapon_smith -= $qtyBuild;
                        } else {
                            $newP->sword_weapon_smith -= $qtyBuild;
                        }
                    }
                    $newP->$dbCol = $hasBuildings;
                }
            }

            // Update or delete build queue items
            if ($timeRemaining == 0) {
                BuildQueue::where('id', $b->id)->delete();
            } elseif ($timeRemaining > 0) {
                BuildQueue::where('id', $b->id)->update([
                    'time_needed' => $timeRemaining,
                    'qty' => $qtyRemaining,
                ]);
            }
        }

        // =====================================================================
        // H. Training queue processing
        // =====================================================================
        TrainQueue::where('player_id', $player->id)->decrement('turns_remaining');

        $trainedItems = TrainQueue::where('player_id', $player->id)
            ->where('turns_remaining', '<=', 0)
            ->get();

        $pSwordsman = 0;
        $pArchers = 0;
        $pHorseman = 0;
        $pMacemen = 0;
        $pCatapults = 0;
        $pTrainedPeasants = 0;
        $pThieves = 0;
        $pUunit = 0;
        $maxSoldiers = $newP->town_center * $townCenterB['max_units'] + $newP->fort * $fortB['max_units'];

        foreach ($trainedItems as $t) {
            $totalArmy = $newP->archers + $newP->swordsman + $newP->horseman
                + $newP->catapults + $newP->macemen + $newP->thieves + $newP->trained_peasants;
            $done = true;
            $trainQty = $t->qty;

            if ($t->turns_remaining < 0) {
                // Disbanded due to lack of forts
                $message .= "<font color=red>{$trainQty} training army units were disbanded because of lack of forts</font><br>";
                $newP->people += $trainQty;
            } else {
                if ($totalArmy + $t->qty > $maxSoldiers) {
                    $done = false;
                    $trainQty = $maxSoldiers - $totalArmy;
                    if ($trainQty < 0) $trainQty = 0;
                    $message .= "<font color=red>Not enough forts to finish training army</font><br>";
                }

                switch ($t->soldier_type) {
                    case 1:
                        $pArchers += $trainQty;
                        $newP->archers += $trainQty;
                        break;
                    case 2:
                        $pSwordsman += $trainQty;
                        $newP->swordsman += $trainQty;
                        break;
                    case 3:
                        $pHorseman += $trainQty;
                        $newP->horseman += $trainQty;
                        break;
                    case 5:
                        $pCatapults += $trainQty;
                        $newP->catapults += $trainQty;
                        break;
                    case 6:
                        $pMacemen += $trainQty;
                        $newP->macemen += $trainQty;
                        break;
                    case 7:
                        $pTrainedPeasants += $trainQty;
                        $newP->trained_peasants += $trainQty;
                        break;
                    case 8:
                        $pThieves += $trainQty;
                        $newP->thieves += $trainQty;
                        break;
                    case 9:
                        $pUunit += $trainQty;
                        $newP->uunit += $trainQty;
                        break;
                }
            }

            if ($done) {
                TrainQueue::where('id', $t->id)->delete();
            } else {
                TrainQueue::where('id', $t->id)->update([
                    'qty' => $t->qty - $trainQty,
                ]);
            }
        }

        // Training completion messages
        if ($pSwordsman > 0)
            $message .= "<font color=yellow>{$pSwordsman} swordsman have finished their training and are ready to serve you</font><br>";
        if ($pArchers > 0)
            $message .= "<font color=yellow>{$pArchers} archers have finished their training and are ready to serve you</font><br>";
        if ($pHorseman > 0)
            $message .= "<font color=yellow>{$pHorseman} horsemen have finished their training and are ready to serve you</font><br>";
        if ($pMacemen > 0)
            $message .= "<font color=yellow>{$pMacemen} macemen have finished their training and are ready to serve you</font><br>";
        if ($pCatapults > 0)
            $message .= "<font color=yellow>{$pCatapults} catapults have finished their training and are ready to serve you</font><br>";
        if ($pTrainedPeasants > 0)
            $message .= "<font color=yellow>{$pTrainedPeasants} trained peasants have finished their training and are ready to serve you</font><br>";
        if ($pThieves > 0)
            $message .= "<font color=yellow>{$pThieves} thieves have finished their training and are ready to serve you</font><br>";
        if ($pUunit > 0)
            $message .= "<font color=yellow>{$pUunit} {$uunitA['name']} have finished their training and are ready to serve you</font><br>";

        // =====================================================================
        // I. Tool wear (months 5 and 10)
        // =====================================================================
        if ($month == 5 || $month == 10) {
            $toolsUsedPct = mt_rand(10, 20);
            $toolsUsed = round($numBuilders * $toolsUsedPct / 100);
            if ($toolsUsed > 0) {
                $message .= "{$toolsUsed} tools wore out<br>";
                if ($rTools >= $toolsUsed) {
                    $rTools -= $toolsUsed;
                } else {
                    $rTools = 0;
                }
            }
        }

        // Resource production/consumption summary
        if ($pWood != 0 || $cWood != 0) {
            $c = ($pWood - $cWood < 0) ? 'red' : 'yellow';
            $net = $pWood - $cWood;
            $netFormatted = ($net >= 0 ? '+' : '') . number_format($net);
            $message .= "Produced {$pWood} wood and used {$cWood} wood (<font color={$c}>{$netFormatted}</font>)<br>";
        }
        if ($pFood != 0 || $cFood != 0) {
            $c = ($pFood - $cFood < 0) ? 'red' : 'yellow';
            $net = $pFood - $cFood;
            $netFormatted = ($net >= 0 ? '+' : '') . number_format($net);
            $message .= "Produced {$pFood} food and used {$cFood} food (<font color={$c}>{$netFormatted}</font>)<br>";
        }
        if ($pIron != 0 || $cIron != 0) {
            $c = ($pIron - $cIron < 0) ? 'red' : 'yellow';
            $net = $pIron - $cIron;
            $netFormatted = ($net >= 0 ? '+' : '') . number_format($net);
            $message .= "Produced {$pIron} iron and used {$cIron} iron (<font color={$c}>{$netFormatted}</font>)<br>";
        }
        if ($pGold != 0 || $cGold != 0) {
            $c = ($pGold - $cGold < 0) ? 'red' : 'yellow';
            $net = $pGold - $cGold;
            $netFormatted = ($net >= 0 ? '+' : '') . number_format($net);
            $message .= "Produced {$pGold} gold and used {$cGold} gold (<font color={$c}>{$netFormatted}</font>)<br>";
        }
        if ($pWine != 0 || $cWine != 0) {
            $c = ($pWine - $cWine < 0) ? 'red' : 'yellow';
            $net = $pWine - $cWine;
            $netFormatted = ($net >= 0 ? '+' : '') . number_format($net);
            $message .= "Produced {$pWine} wine and used {$cWine} wine (<font color={$c}>{$netFormatted}</font>)<br>";
        }
        $message .= "Produced {$pTools} tools, {$pSwords} swords, {$pBows} bows, {$pMaces} maces and {$pHorses} horses<br>";

        // =====================================================================
        // J. Explorer processing
        // =====================================================================
        $exploreItems = ExploreQueue::where('player_id', $player->id)
            ->orderBy('id')
            ->get();

        foreach ($exploreItems as $e) {
            if ($e->turn == 0) {
                ExploreQueue::where('id', $e->id)->delete();
            } else {
                // Discover land
                $m = (int) ceil($e->people * 0.15);
                $f = (int) ceil($e->people * 0.30);
                $p = (int) ceil($e->people * 0.65);

                $mHalf = round($m / 3);
                $fHalf = round($f / 3);
                $pHalf = round($p / 3);

                // Seek land modifier
                if ($e->seek_land == 1) {
                    $m *= 3; $mHalf *= 3;
                    $f = 0; $fHalf = 0;
                    $p = 0; $pHalf = 0;
                } elseif ($e->seek_land == 2) {
                    $m = 0; $mHalf = 0;
                    $f = round($f * 2.5); $fHalf = round($fHalf * 2.5);
                    $p = 0; $pHalf = 0;
                } elseif ($e->seek_land == 3) {
                    $m = 0; $mHalf = 0;
                    $f = 0; $fHalf = 0;
                    $p *= 2; $pHalf *= 2;
                }

                $m = mt_rand(max(0, $mHalf), max(0, $m));
                $f = mt_rand(max(0, $fHalf), max(0, $f));
                $p = mt_rand(max(0, $pHalf), max(0, $p));

                // Research 10 bonus (Explorers)
                if ($newP->research10 > 0) {
                    $m = round($m + $m * ($newP->research10 / 100));
                    $f = round($f + $f * ($newP->research10 / 100));
                    $p = round($p + $p * ($newP->research10 / 100));
                }

                // Land multiplier (currently disabled in original, kept as 0)
                $mult = 0;
                if ($mult > 0) {
                    $m = round($m * $mult);
                    $f = round($f * $mult);
                    $p = round($p * $mult);
                }

                $newP->mland += $m;
                $newP->fland += $f;
                $newP->pland += $p;

                if ($m != 0 || $f != 0 || $p != 0) {
                    $message .= "<font color=yellow>Your explorers have discovered {$m} mountain land, {$f} forest land and {$p} plain land</font><br>";
                } else {
                    $message .= "Your explorers did not discover any land this turn<br>";
                }

                $newTurn = $e->turn - 1;
                if ($newTurn == 0) {
                    $message .= "<font color=yellow>Your explorers ended their mission discovering total " . ($e->mland + $m) . " mountain land, " . ($e->fland + $f) . " forest land and " . ($e->pland + $p) . " plain land</font><br>";
                }

                ExploreQueue::where('id', $e->id)->update([
                    'turn' => $newTurn,
                    'turns_used' => $e->turns_used + 1,
                    'mland' => $e->mland + $m,
                    'fland' => $e->fland + $f,
                    'pland' => $e->pland + $p,
                ]);
            }
        }

        // =====================================================================
        // K. Attack queue processing
        // =====================================================================
        AttackQueue::where('player_id', $player->id)->increment('status');

        $attacks = AttackQueue::where('player_id', $player->id)
            ->where(function ($query) {
                $query->where('status', 3)
                      ->orWhere('status', '>=', 6);
            })
            ->get();

        foreach ($attacks as $attack) {
            if ($attack->status == 3) {
                // Attacking -- combat resolution via CombatService
                // (doAttack.cfm, doAttack2.cfm, doAttack3.cfm)
                $combatData = [];
                $this->combatService->processAttack($attack, $player, $combatData, $message);

                // Apply land/resource gains to working player data
                foreach (['mland', 'fland', 'pland', 'gold', 'wood', 'food', 'iron', 'tools'] as $key) {
                    if (isset($combatData[$key]) && $combatData[$key] > 0) {
                        $newP->$key += $combatData[$key];
                    }
                }

                // Update attack queue with surviving troops for the return journey
                $troopUpdate = [];
                foreach (['swordsman', 'archers', 'horseman', 'macemen', 'catapults', 'trained_peasants', 'thieves', 'uunit'] as $col) {
                    $troopUpdate[$col] = $combatData[$col] ?? 0;
                }
                AttackQueue::where('id', $attack->id)->update($troopUpdate);
            } elseif ($attack->status >= 6) {
                // Army returned home
                $newP->swordsman += $attack->swordsman;
                $newP->archers += $attack->archers;
                $newP->horseman += $attack->horseman;
                $newP->macemen += $attack->macemen;
                $newP->trained_peasants += $attack->trained_peasants;
                $newP->thieves += $attack->thieves;
                $newP->catapults += $attack->catapults;
                $newP->uunit += $attack->uunit;
                AttackQueue::where('id', $attack->id)->delete();
                $message .= "<font color=yellow>Your army has returned to the empire</font><br>";
            }
        }

        // =====================================================================
        // L. Soldier capacity and pay
        // =====================================================================

        // Check if forts can support army
        $canHaveSoldiers = $newP->fort * $fortB['max_units'] + $newP->town_center * $townCenterB['max_units'];
        $numSoldiersAll = $newP->swordsman + $newP->archers + $newP->horseman + $newP->macemen
            + $newP->trained_peasants + $newP->thieves + $newP->catapults + $newP->uunit;

        if ($numSoldiersAll > $canHaveSoldiers) {
            $tooMuch = ($numSoldiersAll - $canHaveSoldiers) * 0.25;
            $runS = round(($newP->swordsman / $numSoldiersAll) * $tooMuch);
            $runA = round(($newP->archers / $numSoldiersAll) * $tooMuch);
            $runH = round(($newP->horseman / $numSoldiersAll) * $tooMuch);
            $runM = round(($newP->macemen / $numSoldiersAll) * $tooMuch);
            $runP = round(($newP->trained_peasants / $numSoldiersAll) * $tooMuch);
            $runT = round(($newP->thieves / $numSoldiersAll) * $tooMuch);
            $runC = round(($newP->catapults / $numSoldiersAll) * $tooMuch);
            $runU = round(($newP->uunit / $numSoldiersAll) * $tooMuch);

            $newP->swordsman -= $runS;
            $newP->archers -= $runA;
            $newP->horseman -= $runH;
            $newP->macemen -= $runM;
            $newP->trained_peasants -= $runP;
            $newP->thieves -= $runT;
            $newP->catapults -= $runC;
            $newP->uunit -= $runU;
            $numSoldiersAll -= ($runS + $runA + $runH + $runM + $runP + $runT + $runC + $runU);
            $message .= "<font color=red>Due to the lack of place to live some of your soldiers run away ({$runU} {$uunitA['name']}, {$runS} swordsman, {$runA} archers, {$runH} horseman, {$runM} macemen, {$runP} trained peasants, {$runC} catapults and {$runT} thieves)</font><br>";
        }

        // Pay soldiers gold
        $payGold = round(
            $newP->swordsman * $swordsmanA['gold_per_turn']
            + $newP->archers * $archerA['gold_per_turn']
            + $newP->horseman * $horsemanA['gold_per_turn']
            + $newP->macemen * $macemanA['gold_per_turn']
            + $newP->trained_peasants * $trainedPeasantA['gold_per_turn']
            + $newP->thieves * $thievesA['gold_per_turn']
            + $newP->uunit * $uunitA['gold_per_turn']
        );

        if ($payGold > $rGold) {
            // Not enough gold to pay soldiers
            $tempSoldiers = $newP->swordsman + $newP->archers + $newP->horseman
                + $newP->macemen + $newP->trained_peasants + $newP->thieves + $newP->uunit;

            if ($tempSoldiers > 0) {
                $notPaid = ($payGold - $rGold) * 0.1;
                $runS = round(($newP->swordsman / $tempSoldiers) * $notPaid);
                $runA = round(($newP->archers / $tempSoldiers) * $notPaid);
                $runH = round(($newP->horseman / $tempSoldiers) * $notPaid);
                $runM = round(($newP->macemen / $tempSoldiers) * $notPaid);
                $runP = round(($newP->trained_peasants / $tempSoldiers) * $notPaid);
                $runT = round(($newP->thieves / $tempSoldiers) * $notPaid);
                $runU = round(($newP->uunit / $tempSoldiers) * $notPaid);

                $newP->swordsman -= $runS;
                $newP->archers -= $runA;
                $newP->horseman -= $runH;
                $newP->macemen -= $runM;
                $newP->trained_peasants -= $runP;
                $newP->thieves -= $runT;
                $newP->uunit -= $runU;
                $message .= "<font color=red>Because you did not have enough gold to pay your soldiers some of them run away ({$runU} {$uunitA['name']}, {$runS} swordsman, {$runA} archers, {$runH} horseman, {$runM} macemen, {$runP} trained peasants and {$runT} thieves)</font><br>";
            }

            $this->shouldStopProcessing = true;
            $payGold = $rGold;
            $rGold = 0;
            $cGold += $payGold;
        } else {
            $rGold -= $payGold;
            $cGold += $payGold;
            $message .= "Your soldiers have been paid " . number_format($payGold) . " gold<br>";
        }

        // Thieves need town centers (1 per town center max)
        if ($newP->thieves > $newP->town_center) {
            $runT = $newP->thieves - $newP->town_center;
            $newP->thieves -= $runT;
            $numSoldiersAll -= $runT;
            $message .= "<font color=red>You do not have enough town centers for your thieves. {$runT} thieves run away</font><br>";
        }

        // Unique units need town centers (1 per town center max)
        if ($newP->uunit > $newP->town_center) {
            $runU = $newP->uunit - $newP->town_center;
            $newP->uunit -= $runU;
            $numSoldiersAll -= $runU;
            $message .= "<font color=red>You do not have enough town centers for your {$uunitA['name']}s. {$runU} {$uunitA['name']}s run away</font><br>";
        }

        // Catapults need town centers (1 per town center max)
        if ($newP->catapults > $newP->town_center) {
            $runC = $newP->catapults - $newP->town_center;
            $newP->catapults -= $runC;
            $numSoldiersAll -= $runC;
            $message .= "<font color=red>You do not have enough town centers for your catapults. {$runC} catapults run away</font><br>";
        }

        // Catapult upkeep: wood and iron
        $needWood = $newP->catapults;
        if ($newP->wood < $needWood && $newP->catapults > 0) {
            $runC = round(($needWood - $newP->wood) * 0.25);
            if ($runC > $newP->catapults) {
                $runC = $newP->catapults;
            }
            $message .= "<font color=red>You did not have enough wood to upkeep your catapults. {$runC} of them were destroyed<br></font>";
            $newP->catapults -= $runC;
        } else {
            $rWood -= $needWood;
        }

        $needIron = round($newP->catapults / 5);
        if ($newP->iron < $needIron && $newP->catapults > 0) {
            $runC = round(($needIron - $newP->iron) * 0.25);
            if ($runC > $newP->catapults) {
                $runC = $newP->catapults;
            }
            $message .= "<font color=red>You did not have enough iron to upkeep your catapults. {$runC} of them were destroyed<br></font>";
            $newP->catapults -= $runC;
        } else {
            $rIron -= $needIron;
        }

        if ($needWood > 0 && $needIron > 0) {
            $message .= "{$needWood} wood and {$needIron} iron was used to upkeep catapults<br>";
        }

        // =====================================================================
        // M. Warehouse capacity
        // =====================================================================
        $canHold = $newP->town_center * $townCenterB['supplies'] + $newP->warehouse * $warehouseB['supplies'];
        $canHold = round($canHold + $canHold * ($newP->research8 / 100));

        // Apply remaining resources to newP
        $newP->wood = $rWood;
        $newP->iron = $rIron;
        $newP->food = $rFood;
        $newP->gold = $rGold;
        $newP->tools = $rTools;
        $newP->swords = $rSwords;
        $newP->bows = $rBows;
        $newP->maces = $rMaces;
        $newP->horses = $rHorses;
        $newP->wine = $rWine;

        // =====================================================================
        // N. Auto-trade processing
        // =====================================================================
        $localTradeMulti = gameConfig('local_trade_multiplier');
        $extra = 1;
        $s = $newP->score;
        while ($s > 100000) {
            $extra += $localTradeMulti;
            $s = $s / 2;
        }

        // Auto buy
        if ($newP->auto_buy_wood > 0) {
            $woodBuyPrice = round($localPrices['wood']['buy'] * $extra);
            $useGold = $woodBuyPrice * $newP->auto_buy_wood;
            if ($newP->gold >= $useGold) {
                $newP->wood += $newP->auto_buy_wood;
                $newP->gold -= $useGold;
                $message .= "Bought {$newP->auto_buy_wood} wood for {$useGold}<br>";
            }
        }
        if ($newP->auto_buy_food > 0) {
            $foodBuyPrice = round($localPrices['food']['buy'] * $extra);
            $useGold = $foodBuyPrice * $newP->auto_buy_food;
            if ($newP->gold >= $useGold) {
                $newP->food += $newP->auto_buy_food;
                $newP->gold -= $useGold;
                $message .= "Bought {$newP->auto_buy_food} food for {$useGold}<br>";
            }
        }
        if ($newP->auto_buy_iron > 0) {
            $ironBuyPrice = round($localPrices['iron']['buy'] * $extra);
            $useGold = $ironBuyPrice * $newP->auto_buy_iron;
            if ($newP->gold >= $useGold) {
                $newP->iron += $newP->auto_buy_iron;
                $newP->gold -= $useGold;
                $message .= "Bought {$newP->auto_buy_iron} iron for {$useGold}<br>";
            }
        }
        if ($newP->auto_buy_tools > 0) {
            $toolsBuyPrice = round($localPrices['tools']['buy'] * $extra);
            $useGold = $toolsBuyPrice * $newP->auto_buy_tools;
            if ($newP->gold >= $useGold) {
                $newP->tools += $newP->auto_buy_tools;
                $newP->gold -= $useGold;
            }
            $message .= "Bought {$newP->auto_buy_tools} tools for {$useGold}<br>";
        }

        // Auto sell
        if ($newP->auto_sell_wood > 0) {
            if ($newP->wood >= $newP->auto_sell_wood) {
                $woodSellPrice = round($localPrices['wood']['sell'] * (1.0 / $extra));
                $getGold = $woodSellPrice * $newP->auto_sell_wood;
                $newP->wood -= $newP->auto_sell_wood;
                $newP->gold += $getGold;
                $message .= "Sold {$newP->auto_sell_wood} wood for {$getGold}<br>";
            }
        }
        if ($newP->auto_sell_food > 0) {
            if ($newP->food >= $newP->auto_sell_food) {
                $foodSellPrice = round($localPrices['food']['sell'] * (1.0 / $extra));
                $getGold = $foodSellPrice * $newP->auto_sell_food;
                $newP->food -= $newP->auto_sell_food;
                $newP->gold += $getGold;
                $message .= "Sold {$newP->auto_sell_food} food for {$getGold}<br>";
            }
        }
        if ($newP->auto_sell_iron > 0) {
            if ($newP->iron >= $newP->auto_sell_iron) {
                $ironSellPrice = round($localPrices['iron']['sell'] * (1.0 / $extra));
                $getGold = $ironSellPrice * $newP->auto_sell_iron;
                $newP->iron -= $newP->auto_sell_iron;
                $newP->gold += $getGold;
                $message .= "Sold {$newP->auto_sell_iron} iron for {$getGold}<br>";
            }
        }
        if ($newP->auto_sell_tools > 0) {
            if ($newP->tools >= $newP->auto_sell_tools) {
                $toolsSellPrice = round($localPrices['tools']['sell'] * (1.0 / $extra));
                $getGold = $toolsSellPrice * $newP->auto_sell_tools;
                $newP->tools -= $newP->auto_sell_tools;
                $newP->gold += $getGold;
                $message .= "Sold {$newP->auto_sell_tools} tools for {$getGold}<br>";
            }
        }

        // Check warehouse overflow (after auto-trade)
        $totalSupplies = $newP->wood + $newP->iron + $newP->food + $newP->tools
            + $newP->swords + $newP->bows + $newP->horses + $newP->maces + $newP->wine;

        if ($canHold < $totalSupplies) {
            $tooMuch = $totalSupplies - $canHold;
            $stealW = round(($newP->wood / $totalSupplies) * $tooMuch);
            $stealF = round(($newP->food / $totalSupplies) * $tooMuch);
            $stealI = round(($newP->iron / $totalSupplies) * $tooMuch);
            $stealT = round(($newP->tools / $totalSupplies) * $tooMuch);
            $stealS = round(($newP->swords / $totalSupplies) * $tooMuch);
            $stealB = round(($newP->bows / $totalSupplies) * $tooMuch);
            $stealH = round(($newP->horses / $totalSupplies) * $tooMuch);
            $stealM = round(($newP->maces / $totalSupplies) * $tooMuch);
            $stealWine = round(($newP->wine / $totalSupplies) * $tooMuch);

            $comma = '';
            $stolen = '';
            if ($stealW > 0) { $stolen .= "{$comma} {$stealW} wood"; $comma = ','; }
            if ($stealF > 0) { $stolen .= "{$comma} {$stealF} food"; $comma = ','; }
            if ($stealI > 0) { $stolen .= "{$comma} {$stealI} iron"; $comma = ','; }
            if ($stealT > 0) { $stolen .= "{$comma} {$stealT} tools"; $comma = ','; }
            if ($stealS > 0) { $stolen .= "{$comma} {$stealS} swords"; $comma = ','; }
            if ($stealB > 0) { $stolen .= "{$comma} {$stealB} bows"; $comma = ','; }
            if ($stealH > 0) { $stolen .= "{$comma} {$stealH} horses"; $comma = ','; }
            if ($stealM > 0) { $stolen .= "{$comma} {$stealM} maces"; $comma = ','; }
            if ($stealWine > 0) { $stolen .= "{$comma} {$stealWine} wine"; $comma = ','; }

            if ($stolen !== '') {
                $message .= "<font color=red>Because your warehouses could not fit all your good some of them were stolen ({$stolen})</font><br>";
                $this->shouldStopProcessing = true;
            }

            $newP->wood -= $stealW;
            $newP->food -= $stealF;
            $newP->iron -= $stealI;
            $newP->tools -= $stealT;
            $newP->swords -= $stealS;
            $newP->bows -= $stealB;
            $newP->horses -= $stealH;
            $newP->maces -= $stealM;
            $newP->wine -= $stealWine;
        }

        // =====================================================================
        // O. Save player state
        // =====================================================================

        // Ensure minimum population
        if ($newP->people < 100) {
            $newP->people = 100;
        }

        // Clamp all values to non-negative and build the update array
        $updateColumns = [
            'archers', 'swordsman', 'horseman', 'wood_cutter', 'hunter', 'farmer',
            'house', 'iron_mine', 'gold_mine', 'tool_maker', 'weapon_smith',
            'sword_weapon_smith', 'bow_weapon_smith', 'fort', 'tower', 'town_center',
            'market', 'warehouse', 'stable', 'fland', 'mland', 'pland',
            'food', 'iron', 'gold', 'wood', 'bows', 'swords', 'horses', 'people',
            'tools', 'maces', 'catapults', 'trained_peasants', 'macemen', 'thieves',
            'mage_tower', 'research1', 'research2', 'research3', 'research4',
            'research5', 'research6', 'research7', 'research8', 'research9',
            'research10', 'research11', 'research12', 'research_points',
            'uunit', 'wine', 'winery', 'wall',
        ];

        $updateData = [];
        foreach ($updateColumns as $col) {
            $val = $newP->$col;
            if ($val < 0) $val = 0;
            $updateData[$col] = $val;
        }

        // Also update mace_weaponsmith
        $updateData['mace_weaponsmith'] = max(0, $newP->mace_weaponsmith);

        // Increment turn, decrement turns_free, reset trades
        $updateData['turn'] = $player->turn + 1;
        $updateData['message'] = $message;
        $updateData['trades_this_turn'] = 0;
        $updateData['turns_free'] = max(0, $player->turns_free - 1);
        $updateData['num_attacks'] = $newP->num_attacks > 0 ? $newP->num_attacks - 1 : 0;

        $player->update($updateData);

        // =====================================================================
        // P. Calculate score
        // =====================================================================
        $player->refresh();
        $this->scoreService->calculateScore($player);

        // =====================================================================
        // Q. Process outgoing aid
        // =====================================================================
        TransferQueue::where('from_player_id', $player->id)
            ->where('transfer_type', 1)
            ->where('turns_remaining', '>', 0)
            ->decrement('turns_remaining');

        $completedAid = TransferQueue::where('from_player_id', $player->id)
            ->where('turns_remaining', 0)
            ->where('transfer_type', 1)
            ->get();

        foreach ($completedAid as $tq) {
            // Give goods to the target player
            $targetPlayer = Player::find($tq->to_player_id);
            if ($targetPlayer) {
                $targetPlayer->update([
                    'wood' => $targetPlayer->wood + $tq->wood,
                    'iron' => $targetPlayer->iron + $tq->iron,
                    'food' => $targetPlayer->food + $tq->food,
                    'gold' => $targetPlayer->gold + $tq->gold,
                    'tools' => $targetPlayer->tools + $tq->tools,
                    'maces' => $targetPlayer->maces + $tq->maces,
                    'swords' => $targetPlayer->swords + $tq->swords,
                    'bows' => $targetPlayer->bows + $tq->bows,
                    'horses' => $targetPlayer->horses + $tq->horses,
                    'has_main_news' => 1,
                ]);

                // Send message to recipient
                PlayerMessage::create([
                    'from_player_id' => $player->id,
                    'to_player_id' => $tq->to_player_id,
                    'from_player_name' => $newP->name,
                    'to_player_name' => '',
                    'message' => "On {$theDate} you have received aid from {$newP->name} ({$player->id})<br>{$tq->wood} wood, {$tq->food} food, {$tq->iron} iron, {$tq->gold} gold, {$tq->tools} tools, {$tq->maces} maces, {$tq->swords} swords, {$tq->bows} bows, {$tq->horses} horses",
                    'viewed' => 0,
                    'created_on' => now(),
                    'message_type' => 1,
                ]);
            }

            $tq->delete();
        }

        return $message;
    }

    /**
     * Process multiple turns for a player.
     *
     * Loops up to $turns times (max 12), calling processTurn each iteration.
     * Re-loads the player from the database each iteration. If any turn sets
     * the shouldStopProcessing flag (food crisis, warehouse overflow, unpaid soldiers),
     * processing breaks.
     *
     * @param Player $player The player model
     * @param int $turns Number of turns to process (max 12)
     * @return string Combined HTML message string from all turns
     */
    public function endMultipleTurns(Player $player, int $turns): string
    {
        if ($turns <= 0) {
            return 'Cannot end less than 0 turns.<br>';
        }
        if ($turns > 12) {
            return 'Due to the processing time of ending each turn, this operation is limited to 12 turns at a time.<br>';
        }

        $bigMessage = '';

        for ($i = 0; $i < $turns; $i++) {
            // Re-load player from database for fresh state
            $player->refresh();

            // Check if processing should stop (set by previous turn)
            if ($this->shouldStopProcessing) {
                break;
            }

            // Check if player has free turns
            if ($player->turns_free <= 0) {
                $bigMessage .= 'No more turns left...<br>';
                break;
            }

            $bigMessage .= $this->processTurn($player);
        }

        return $bigMessage;
    }
}
