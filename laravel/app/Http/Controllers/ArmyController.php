<?php

namespace App\Http\Controllers;

use App\Http\Traits\ReturnsJson;
use App\Models\AttackQueue;
use App\Models\TrainQueue;
use App\Services\GameAdvisorService;
use App\Services\GameDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Army Controller
 *
 * Handles army viewing, training, disbanding, and training queue management.
 * Ported from army.cfm, eflag_army.cfm
 *
 * Original: Andrew Deren, (C) AderSoftware 2000, 2001
 */
class ArmyController extends Controller
{
    use ReturnsJson;

    protected GameDataService $gameData;
    protected GameAdvisorService $advisorService;

    public function __construct(GameDataService $gameData, GameAdvisorService $advisorService)
    {
        $this->gameData = $gameData;
        $this->advisorService = $advisorService;
    }

    /**
     * Show the army page.
     * Ported from army.cfm
     */
    public function index()
    {
        $player = player();
        $buildings = session('buildings');
        $soldiers = session('soldiers');

        $fortB = $buildings[9];
        $townCenterB = $buildings[11];

        // Calculate capacity
        $maxTrain = $player->fort * ($fortB['max_train'] ?? 2) + $player->town_center * ($townCenterB['max_train'] ?? 2);
        $maxSoldiers = $player->town_center * ($townCenterB['max_units'] ?? 10) + $player->fort * ($fortB['max_units'] ?? 15);

        // Compute attacking quantities from AttackQueue
        $aq = AttackQueue::where('player_id', $player->id)
            ->select([
                DB::raw('COALESCE(SUM(swordsman), 0) as s_qty'),
                DB::raw('COALESCE(SUM(archers), 0) as a_qty'),
                DB::raw('COALESCE(SUM(horseman), 0) as h_qty'),
                DB::raw('COALESCE(SUM(catapults), 0) as c_qty'),
                DB::raw('COALESCE(SUM(macemen), 0) as m_qty'),
                DB::raw('COALESCE(SUM(trained_peasants), 0) as p_qty'),
                DB::raw('COALESCE(SUM(thieves), 0) as t_qty'),
                DB::raw('COALESCE(SUM(uunit), 0) as u_qty'),
            ])
            ->first();

        $attackQty = [
            1 => (int) ($aq->a_qty ?? 0),   // archers
            2 => (int) ($aq->s_qty ?? 0),   // swordsman
            3 => (int) ($aq->h_qty ?? 0),   // horseman
            4 => 0,                          // tower (N/A)
            5 => (int) ($aq->c_qty ?? 0),   // catapults
            6 => (int) ($aq->m_qty ?? 0),   // macemen
            7 => (int) ($aq->p_qty ?? 0),   // trained peasants
            8 => (int) ($aq->t_qty ?? 0),   // thieves
            9 => (int) ($aq->u_qty ?? 0),   // unique unit
        ];

        // Total units (owned + attacking)
        $totalUnits = $player->swordsman + $player->archers + $player->horseman
            + $player->macemen + $player->trained_peasants
            + $player->thieves + $player->catapults + $player->uunit
            + $attackQty[1] + $attackQty[2] + $attackQty[3]
            + $attackQty[5] + $attackQty[6] + $attackQty[7] + $attackQty[8] + $attackQty[9];

        // Capacity percentage
        $capacityPercent = $maxSoldiers > 0 ? ($totalUnits / $maxSoldiers) * 100.0 : 0;

        // Calculate military strength
        // Army attack/defense power
        $attackPower = ($player->archers + $attackQty[1]) * $soldiers[1]['attack_pt']
            + ($player->swordsman + $attackQty[2]) * $soldiers[2]['attack_pt']
            + ($player->horseman + $attackQty[3]) * $soldiers[3]['attack_pt']
            + ($player->macemen + $attackQty[6]) * $soldiers[6]['attack_pt']
            + ($player->trained_peasants + $attackQty[7]) * $soldiers[7]['attack_pt']
            + ($player->uunit + $attackQty[9]) * $soldiers[9]['attack_pt'];

        $defensePower = ($player->archers + $attackQty[1]) * $soldiers[1]['defense_pt']
            + ($player->swordsman + $attackQty[2]) * $soldiers[2]['defense_pt']
            + ($player->horseman + $attackQty[3]) * $soldiers[3]['defense_pt']
            + ($player->tower) * $soldiers[4]['defense_pt']
            + ($player->macemen + $attackQty[6]) * $soldiers[6]['defense_pt']
            + ($player->trained_peasants + $attackQty[7]) * $soldiers[7]['defense_pt']
            + ($player->uunit + $attackQty[9]) * $soldiers[9]['defense_pt'];

        // Apply research bonuses
        $attackPower = round($attackPower + $attackPower * ($player->research1 / 100));
        $defensePower = round($defensePower + $defensePower * ($player->research2 / 100));

        // Catapult power (include attacking catapults)
        $cAttackPower = ($player->catapults + $attackQty[5]) * $soldiers[5]['attack_pt'];
        $cAttackPower = round($cAttackPower + $cAttackPower * ($player->research11 / 100));
        $cDefensePower = ($player->catapults + $attackQty[5]) * $soldiers[5]['defense_pt'];
        $cDefensePower = round($cDefensePower + $cDefensePower * ($player->research11 / 100));

        // Thief power (include attacking thieves)
        $tAttackPower = ($player->thieves + $attackQty[8]) * $soldiers[8]['attack_pt'];
        $tAttackPower = round($tAttackPower + $tAttackPower * ($player->research3 / 100));
        $tDefensePower = ($player->thieves + $attackQty[8]) * $soldiers[8]['defense_pt'];
        $tDefensePower = round($tDefensePower + $tDefensePower * ($player->research3 / 100));

        // Get training queue
        $trainQueue = TrainQueue::where('player_id', $player->id)
            ->orderBy('id')
            ->get();

        $numTrain = 0;
        $trainQty = array_fill(1, 9, 0);
        foreach ($trainQueue as $tq) {
            $numTrain += $tq->qty;
            $trainQty[$tq->soldier_type] = ($trainQty[$tq->soldier_type] ?? 0) + $tq->qty;
        }

        $canTrain = max(0, $maxTrain - $numTrain);          // training capacity remaining
        $canHold = max(0, $maxSoldiers - $totalUnits - $numTrain);  // fort space remaining

        // Build soldier display data
        $soldierDisplay = [1, 2, 3, 5, 6, 7, 8, 9]; // skip 4 (tower)
        $armyData = [];
        $totalHave = 0;
        $totalCost = 0;
        $totalFood = 0;

        // Help index mapping (matches original CF code)
        $helpIndex = [
            1 => 1, 2 => 2, 3 => 3, 4 => 8,
            5 => 6, 6 => 4, 7 => 5, 8 => 7, 9 => 1,
        ];
        // Civ-specific help index for unique unit
        $civHelpMap = [1 => 9, 2 => 11, 3 => 10, 4 => 12, 5 => 13, 6 => 14];
        if (isset($civHelpMap[$player->civ])) {
            $helpIndex[9] = $civHelpMap[$player->civ];
        }

        foreach ($soldierDisplay as $i) {
            $s = $soldiers[$i];
            $have = ($player->{$s['db_name']} ?? 0) + $attackQty[$i];

            // Calculate food upkeep (matches TurnService formula: weight / soldiers_eat_one_food)
            $soldiersEatOneFood = session('constants')['soldiers_eat_one_food'] ?? 3;
            $foodWeight = $have;  // default: 1 per unit
            if ($i == 3) $foodWeight = $have * 2;              // horseman
            elseif ($i == 5) $foodWeight = 0;                  // catapults don't eat
            elseif ($i == 7) $foodWeight = round($have * 0.1); // trained peasants
            elseif ($i == 8) $foodWeight = $have * 3;          // thieves
            elseif ($i == 9) $foodWeight = $have * 2;          // unique unit
            $foodUsed = $soldiersEatOneFood > 0 ? (int) ceil($foodWeight / $soldiersEatOneFood) : 0;
            $totalFood += $foodUsed;

            $goldCost = $have * $s['gold_per_turn'];
            $totalCost += $goldCost;
            $totalHave += $have;

            // Calculate max trainable
            $cTrainLimit = $canTrain;
            if ($cTrainLimit > $canHold) {
                $cTrainLimit = $canHold;
            }
            if ($cTrainLimit < 0) {
                $cTrainLimit = 0;
            }

            $neededToTrain = '';
            switch ($i) {
                case 1: // Archer - needs bow
                    $neededToTrain = '1 Bow';
                    if ($player->bows < $cTrainLimit) {
                        $cTrainLimit = $player->bows;
                    }
                    break;
                case 2: // Swordsman - needs sword
                    $neededToTrain = '1 Sword';
                    if ($player->swords < $cTrainLimit) {
                        $cTrainLimit = $player->swords;
                    }
                    break;
                case 3: // Horseman - needs horse + sword
                    $neededToTrain = '1 Horse, 1 Sword';
                    if ($player->horses < $cTrainLimit) {
                        $cTrainLimit = $player->horses;
                    }
                    if ($player->swords < $cTrainLimit) {
                        $cTrainLimit = $player->swords;
                    }
                    break;
                case 5: // Catapult - needs wood + iron
                    $cost = $s['train_cost'] ?? 250;
                    $neededToTrain = "{$cost} Wood, {$cost} Iron";
                    $mCost = $cost * $cTrainLimit;
                    if ($player->wood < $mCost) {
                        $cTrainLimit = intdiv($player->wood, $cost);
                    }
                    $mCost = $cTrainLimit * $cost;
                    if ($player->iron < $mCost) {
                        $cTrainLimit = intdiv($player->iron, $cost);
                    }
                    // Catapults limited to town center count
                    if ($have + $cTrainLimit + $trainQty[$i] > $player->town_center) {
                        $cTrainLimit = $player->town_center - $have - $trainQty[$i];
                    }
                    break;
                case 6: // Macemen - needs mace
                    $neededToTrain = '1 Mace';
                    if ($player->maces < $cTrainLimit) {
                        $cTrainLimit = $player->maces;
                    }
                    break;
                case 7: // Trained Peasant - free
                    $neededToTrain = 'None';
                    break;
                case 8: // Thief - needs 1000 gold
                    $neededToTrain = '1000 Gold';
                    if ($player->gold < $cTrainLimit * 1000) {
                        $cTrainLimit = intdiv($player->gold, 1000);
                    }
                    // Thieves limited to town center count
                    if ($have + $cTrainLimit + $trainQty[$i] > $player->town_center) {
                        $cTrainLimit = $player->town_center - $have - $trainQty[$i];
                    }
                    break;
                case 9: // Unique unit
                    $trainGold = $s['train_gold'] ?? 1000;
                    $trainSwords = $s['train_swords'] ?? 0;
                    $trainBows = $s['train_bows'] ?? 0;
                    $trainHorses = $s['train_horses'] ?? 0;
                    $parts = [];
                    $parts[] = "{$trainGold} gold";
                    if ($trainSwords > 0) $parts[] = "{$trainSwords} swords";
                    if ($trainBows > 0) $parts[] = "{$trainBows} bows";
                    if ($trainHorses > 0) $parts[] = "{$trainHorses} horses";
                    $neededToTrain = implode(', ', $parts);

                    if ($trainGold > 0 && $player->gold < $cTrainLimit * $trainGold) {
                        $cTrainLimit = intdiv($player->gold, $trainGold);
                    }
                    if ($have + $cTrainLimit + $trainQty[$i] > $player->town_center) {
                        $cTrainLimit = $player->town_center - $have - $trainQty[$i];
                    }
                    if ($trainSwords > 0 && $player->swords < $cTrainLimit * $trainSwords) {
                        $cTrainLimit = intdiv($player->swords, $trainSwords);
                    }
                    if ($trainHorses > 0 && $player->horses < $cTrainLimit * $trainHorses) {
                        $cTrainLimit = intdiv($player->horses, $trainHorses);
                    }
                    if ($trainBows > 0 && $player->bows < $cTrainLimit * $trainBows) {
                        $cTrainLimit = intdiv($player->bows, $trainBows);
                    }
                    break;
            }

            if ($cTrainLimit < 0) {
                $cTrainLimit = 0;
            }

            $armyData[$i] = [
                'soldier' => $s,
                'have' => $have,
                'foodUsed' => $foodUsed,
                'goldCost' => $goldCost,
                'attacking' => $attackQty[$i],
                'training' => $trainQty[$i],
                'neededToTrain' => $neededToTrain,
                'maxTrain' => $cTrainLimit,
                'helpIndex' => $helpIndex[$i],
            ];
        }

        // Advisor tips
        $advisorTips = $this->advisorService->getArmyTips(
            $player, $armyData, $trainQueue, $maxSoldiers, $totalHave, $canTrain, $attackPower, $defensePower
        );

        return view('pages.army', compact(
            'armyData',
            'soldierDisplay',
            'trainQueue',
            'maxSoldiers',
            'maxTrain',
            'totalUnits',
            'capacityPercent',
            'attackPower',
            'defensePower',
            'cAttackPower',
            'cDefensePower',
            'tAttackPower',
            'tDefensePower',
            'numTrain',
            'canTrain',
            'canHold',
            'totalHave',
            'totalCost',
            'totalFood',
            'attackQty',
            'trainQty',
            'advisorTips'
        ));
    }

    /**
     * Train soldiers.
     * Ported from eflag_army.cfm eflag=train
     */
    public function train(Request $request)
    {
        $player = player();
        $soldiers = session('soldiers');
        $buildings = session('buildings');

        $fortB = $buildings[9];
        $townCenterB = $buildings[11];
        $maxTrain = $player->fort * ($fortB['max_train'] ?? 2) + $player->town_center * ($townCenterB['max_train'] ?? 2);

        // Calculate fort/TC capacity
        $maxSoldiers = $player->town_center * ($townCenterB['max_units'] ?? 10) + $player->fort * ($fortB['max_units'] ?? 15);
        $totalUnits = $player->swordsman + $player->archers + $player->horseman
            + $player->macemen + $player->trained_peasants
            + $player->thieves + $player->catapults + $player->uunit;
        $attackingTotal = (int) AttackQueue::where('player_id', $player->id)
            ->selectRaw('COALESCE(SUM(swordsman+archers+horseman+catapults+macemen+trained_peasants+thieves+uunit),0) as total')
            ->value('total');
        $totalUnits += $attackingTotal;

        // Read quantities for each soldier type
        $quantities = [];
        $totalQty = 0;
        foreach ([1, 2, 3, 5, 6, 7, 8, 9] as $i) {
            $quantities[$i] = abs((int) $request->input("qty{$i}", 0));
            $totalQty += $quantities[$i];
        }

        if ($totalQty <= 0) {
            return redirect()->route('game.army');
        }

        $catapultCost = ($soldiers[5]['train_cost'] ?? 250) * $quantities[5];

        $needBows = $quantities[1] + $quantities[9] * ($soldiers[9]['train_bows'] ?? 0);
        $needSwords = $quantities[2] + $quantities[3] + $quantities[9] * ($soldiers[9]['train_swords'] ?? 0);
        $needHorses = $quantities[3] + $quantities[9] * ($soldiers[9]['train_horses'] ?? 0);
        $needGold = $quantities[8] * 1000 + $quantities[9] * ($soldiers[9]['train_gold'] ?? 1000);

        // Check how many are currently training
        $currentTraining = TrainQueue::where('player_id', $player->id)->sum('qty');
        $newTrain = $currentTraining + $totalQty;
        $canHold = $maxSoldiers - $totalUnits - $currentTraining;

        // Per-type counts for TC-capped units (attacking + training + owned)
        $aqTypes = AttackQueue::where('player_id', $player->id)
            ->selectRaw('COALESCE(SUM(catapults),0) as c, COALESCE(SUM(thieves),0) as t, COALESCE(SUM(uunit),0) as u')
            ->first();
        $tqTypes = TrainQueue::where('player_id', $player->id)
            ->selectRaw('COALESCE(SUM(CASE WHEN soldier_type=5 THEN qty ELSE 0 END),0) as c, COALESCE(SUM(CASE WHEN soldier_type=8 THEN qty ELSE 0 END),0) as t, COALESCE(SUM(CASE WHEN soldier_type=9 THEN qty ELSE 0 END),0) as u')
            ->first();
        $totalCatapults = $player->catapults + (int) $aqTypes->c + (int) $tqTypes->c;
        $totalThieves = $player->thieves + (int) $aqTypes->t + (int) $tqTypes->t;
        $totalUunits = $player->uunit + (int) $aqTypes->u + (int) $tqTypes->u;

        $error = '';

        // Civ restrictions first (clearest error messages)
        if ($player->civ == 6 && $quantities[3] > 0) {
            $error = 'Incas cannot train horseman.';
        } elseif ($newTrain > $maxTrain) {
            $error = "You can only train " . number_format($maxTrain) . " soldiers at a time.";
        } elseif ($totalQty > $canHold) {
            $error = "Not enough fort/town center space. You can hold " . number_format(max(0, $canHold)) . " more soldiers.";
        } elseif ($quantities[5] > 0 && ($totalCatapults + $quantities[5]) > $player->town_center) {
            $error = 'Catapults are limited to ' . $player->town_center . ' (one per town center).';
        } elseif ($quantities[8] > 0 && ($totalThieves + $quantities[8]) > $player->town_center) {
            $error = 'Thieves are limited to ' . $player->town_center . ' (one per town center).';
        } elseif ($quantities[9] > 0 && ($totalUunits + $quantities[9]) > $player->town_center) {
            $error = $soldiers[9]['name'] . ' are limited to ' . $player->town_center . ' (one per town center).';
        } elseif ($player->people < $totalQty) {
            $error = 'You do not have enough people.';
        } elseif ($player->bows < $needBows) {
            $error = 'You do not have enough bows for training.';
        } elseif ($player->swords < $needSwords) {
            $error = 'You do not have enough swords for training.';
        } elseif ($player->horses < $needHorses) {
            $error = 'You do not have enough horses for training.';
        } elseif ($quantities[5] > 0 && $player->wood < $catapultCost) {
            $error = "You do not have enough wood to train {$quantities[5]} catapults.";
        } elseif ($quantities[5] > 0 && $player->iron < $catapultCost) {
            $error = "You do not have enough iron to train {$quantities[5]} catapults.";
        } elseif ($quantities[6] > 0 && $player->maces < $quantities[6]) {
            $error = "You do not have enough maces to train {$quantities[6]} macemen.";
        } elseif ($player->gold < $needGold) {
            $error = 'You do not have enough gold for training.';
        }

        if (!empty($error)) {
            if ($request->expectsJson()) {
                return $this->jsonError($error);
            }
            session()->flash('game_message', $error);
            return redirect()->route('game.army');
        }

        // Create training queue entries
        foreach ([1, 2, 3, 5, 6, 7, 8, 9] as $i) {
            $qty = $quantities[$i];
            if ($qty > 0) {
                TrainQueue::create([
                    'player_id' => $player->id,
                    'soldier_type' => $i,
                    'turns_remaining' => $soldiers[$i]['turns'],
                    'qty' => $qty,
                ]);
            }
        }

        // Deduct resources
        $player->update([
            'swords' => $player->swords - $needSwords,
            'bows' => $player->bows - $needBows,
            'maces' => $player->maces - $quantities[6],
            'horses' => $player->horses - $needHorses,
            'wood' => $player->wood - ($quantities[5] * ($soldiers[5]['train_cost'] ?? 250)),
            'iron' => $player->iron - ($quantities[5] * ($soldiers[5]['train_cost'] ?? 250)),
            'gold' => $player->gold - $needGold,
            'people' => $player->people - $totalQty,
        ]);

        $message = "Training {$totalQty} soldiers.";
        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, $message);
        }
        session()->flash('game_message', $message);
        return redirect()->route('game.army');
    }

    /**
     * Cancel a training queue item.
     * Ported from eflag_army.cfm eflag=dequeue
     */
    public function cancelTraining(Request $request)
    {
        $player = player();
        $soldiers = session('soldiers');

        $request->validate([
            'q_id' => 'required|integer',
        ]);

        $tq = TrainQueue::where('player_id', $player->id)
            ->where('id', $request->q_id)
            ->first();

        if ($tq) {
            // Determine what resources to refund
            $getGold = 0;
            $getSwords = 0;
            $getHorses = 0;
            $getBows = 0;
            $getMaces = 0;
            $getWood = 0;
            $getIron = 0;

            switch ($tq->soldier_type) {
                case 1: // Archer
                    $getBows = $tq->qty;
                    break;
                case 2: // Swordsman
                    $getSwords = $tq->qty;
                    break;
                case 3: // Horseman
                    $getSwords = $tq->qty;
                    $getHorses = $tq->qty;
                    break;
                case 5: // Catapult
                    $trainCost = $soldiers[5]['train_cost'] ?? 250;
                    $getWood = $tq->qty * $trainCost;
                    $getIron = $tq->qty * $trainCost;
                    break;
                case 6: // Macemen
                    $getMaces = $tq->qty;
                    break;
                case 8: // Thief
                    $getGold = $tq->qty * 1000;
                    break;
                case 9: // Unique unit
                    $getGold = $tq->qty * ($soldiers[9]['train_gold'] ?? 1000);
                    $getSwords = $tq->qty * ($soldiers[9]['train_swords'] ?? 0);
                    $getBows = $tq->qty * ($soldiers[9]['train_bows'] ?? 0);
                    $getHorses = $tq->qty * ($soldiers[9]['train_horses'] ?? 0);
                    break;
            }

            $player->update([
                'gold' => $player->gold + $getGold,
                'swords' => $player->swords + $getSwords,
                'bows' => $player->bows + $getBows,
                'maces' => $player->maces + $getMaces,
                'horses' => $player->horses + $getHorses,
                'wood' => $player->wood + $getWood,
                'iron' => $player->iron + $getIron,
                'people' => $player->people + $tq->qty,
            ]);

            $tq->delete();
        }

        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, 'Training cancelled.');
        }
        return redirect()->route('game.army');
    }

    /**
     * Disband soldiers.
     * Ported from eflag_army.cfm eflag=disbandArmy
     */
    public function disband(Request $request)
    {
        $player = player();
        $soldiers = session('soldiers');

        // Read quantities
        $qty = [];
        foreach ([1, 2, 3, 5, 6, 7, 8, 9] as $i) {
            $qty[$i] = (int) $request->input("qty{$i}", 0);
        }

        $totalDisband = array_sum($qty);

        if ($totalDisband <= 0) {
            if ($request->expectsJson()) {
                return $this->jsonError('No soldiers selected to disband.');
            }
            return redirect()->route('game.army');
        }

        // Validate no negative values
        foreach ($qty as $val) {
            if ($val < 0) {
                if ($request->expectsJson()) {
                    return $this->jsonError('Sorry, cannot disband negative army.');
                }
                session()->flash('game_message', 'Sorry, cannot disband negative army.');
                return redirect()->route('game.army');
            }
        }

        // Validate quantities don't exceed owned
        $checks = [
            1 => ['field' => 'archers', 'name' => 'archers'],
            2 => ['field' => 'swordsman', 'name' => 'swordsman'],
            3 => ['field' => 'horseman', 'name' => 'horseman'],
            5 => ['field' => 'catapults', 'name' => 'catapults'],
            6 => ['field' => 'macemen', 'name' => 'macemen'],
            7 => ['field' => 'trained_peasants', 'name' => 'trained peasants'],
            8 => ['field' => 'thieves', 'name' => 'thieves'],
            9 => ['field' => 'uunit', 'name' => $soldiers[9]['name']],
        ];

        foreach ($checks as $i => $check) {
            if ($qty[$i] > ($player->{$check['field']} ?? 0)) {
                $errorMsg = "Cannot disband {$qty[$i]} {$check['name']}. You have only {$player->{$check['field']}}.";
                if ($request->expectsJson()) {
                    return $this->jsonError($errorMsg);
                }
                session()->flash('game_message', $errorMsg);
                return redirect()->route('game.army');
            }
        }

        // Calculate returned resources (50% of training cost)
        $getIron = 0;
        $getWood = 0;
        $getSwords = 0;
        $getHorses = 0;
        $getBows = 0;
        $getMaces = 0;

        if ($qty[1] > 0) $getBows = $qty[1];
        if ($qty[2] > 0) $getSwords = $qty[2];
        if ($qty[3] > 0) { $getSwords += $qty[3]; $getHorses = $qty[3]; }
        if ($qty[5] > 0) {
            $trainCost = $soldiers[5]['train_cost'] ?? 250;
            $getWood = round($qty[5] * $trainCost);
            $getIron = round($qty[5] * $trainCost);
        }
        if ($qty[6] > 0) $getMaces = $qty[6];
        if ($qty[9] > 0) {
            $getSwords += $qty[9] * ($soldiers[9]['train_swords'] ?? 0);
            $getBows += $qty[9] * ($soldiers[9]['train_bows'] ?? 0);
            $getHorses += $qty[9] * ($soldiers[9]['train_horses'] ?? 0);
        }

        // Get only 1/2 of what it was (matches original CF code)
        $getIron = intdiv($getIron, 2);
        $getWood = intdiv($getWood, 2);
        $getSwords = intdiv($getSwords, 2);
        $getBows = intdiv($getBows, 2);
        $getMaces = intdiv($getMaces, 2);
        $getHorses = intdiv($getHorses, 2);

        $player->update([
            'archers' => $player->archers - $qty[1],
            'swordsman' => $player->swordsman - $qty[2],
            'horseman' => $player->horseman - $qty[3],
            'catapults' => $player->catapults - $qty[5],
            'macemen' => $player->macemen - $qty[6],
            'trained_peasants' => $player->trained_peasants - $qty[7],
            'thieves' => $player->thieves - $qty[8],
            'uunit' => $player->uunit - $qty[9],
            'wood' => $player->wood + $getWood,
            'iron' => $player->iron + $getIron,
            'swords' => $player->swords + $getSwords,
            'bows' => $player->bows + $getBows,
            'maces' => $player->maces + $getMaces,
            'horses' => $player->horses + $getHorses,
            'people' => $player->people + $totalDisband,
        ]);

        $msg = "You have disbanded {$qty[1]} archers, {$qty[2]} swordsman, {$qty[3]} horsemen, "
            . "{$qty[5]} catapults, {$qty[6]} macemen, {$qty[7]} trained peasants, {$qty[8]} thieves, "
            . "{$qty[9]} {$soldiers[9]['name']}."
            . "<br>You have received {$getIron} iron, {$getWood} wood, {$getSwords} swords, "
            . "{$getBows} bows, {$getHorses} horses and {$getMaces} maces.";

        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, $msg);
        }
        session()->flash('game_message', $msg);
        return redirect()->route('game.army');
    }
}
