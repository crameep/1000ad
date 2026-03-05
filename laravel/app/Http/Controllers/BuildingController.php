<?php

namespace App\Http\Controllers;

use App\Http\Traits\ReturnsJson;
use App\Models\BuildQueue;
use App\Services\GameAdvisorService;
use App\Services\GameDataService;
use Illuminate\Http\Request;

/**
 * Building Controller
 *
 * Handles building construction, demolition, queue management, and status updates.
 * Ported from build.cfm, eflag_build.cfm
 *
 * Original: Andrew Deren, (C) AderSoftware 2000, 2001
 */
class BuildingController extends Controller
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
     * Show the buildings page.
     * Ported from build.cfm
     */
    public function index()
    {
        $player = player();
        $buildings = session('buildings');

        // Calculate number of builders
        // numBuilders = toolMakerB.numBuilders * player.toolMaker + 3
        $numBuilders = ($buildings[7]['num_builders'] ?? 6) * $player->tool_maker + 3;
        if ($numBuilders <= 0) {
            $numBuilders = 1;
        }

        // Get build queue
        $buildQueue = BuildQueue::where('player_id', $player->id)
            ->orderBy('pos')
            ->get();

        // Calculate land usage per type
        $mUsed = 0;
        $fUsed = 0;
        $pUsed = 0;
        foreach ($buildings as $i => $b) {
            $have = $player->{$b['db_column']} ?? 0;
            if ($b['land'] === 'M') {
                $mUsed += $have * $b['sq'];
            } elseif ($b['land'] === 'F') {
                $fUsed += $have * $b['sq'];
            } else {
                $pUsed += $have * $b['sq'];
            }
        }
        $freeMountain = $player->mland - $mUsed;
        $freeForest = $player->fland - $fUsed;
        $freePlains = $player->pland - $pUsed;

        // Building display order and color coding
        $displayOrder = [2, 3, 1, 5, 6, 16, 7, 8, 14, 15, 9, 10, 11, 12, 13, 4];
        $colors = [
            2 => '#ff6600', 3 => '#ff6600',
            1 => '#00ff00',
            5 => '#ffcc00', 6 => '#ffcc00',
            7 => '#00ccff', 16 => '#00ccff', 8 => '#00ccff', 14 => '#00ccff', 15 => '#00ccff',
            9 => '#ffffff', 10 => '#ffffff', 11 => '#ffffff', 12 => '#ffffff', 13 => '#ffffff',
            4 => '#ffffff',
        ];

        // Calculate building stats for each building
        $buildingStats = [];
        $totalWorkers = 0;
        $totalBuildings = 0;
        $totalLand = 0;

        foreach ($displayOrder as $i) {
            $b = $buildings[$i];
            $have = $player->{$b['db_column']} ?? 0;
            $land = $have * $b['sq'];
            $totalLand += $land;
            $totalBuildings += $have;

            // Determine working buildings based on status
            $bWorking = $have;
            $status = 100;
            if ($b['allow_off']) {
                $statusColumn = $b['db_column'] . '_status';
                $status = $player->{$statusColumn} ?? 100;
                if ($status == 0) {
                    $bWorking = 0;
                } else {
                    $bWorking = round($have * ($status / 100));
                }
            }

            $workers = $bWorking * $b['workers'];
            $totalWorkers += $workers;

            // Calculate production (only when buildings are working)
            $production = '';
            if ($bWorking > 0) {
                if ($i == 8) {
                    // Weaponsmith - special production
                    $bowProduction = round(($player->bow_weapon_smith ?? 0) * ($status / 100));
                    $swordProduction = round(($player->sword_weapon_smith ?? 0) * ($status / 100));
                    $maceProduction = round(($player->mace_weapon_smith ?? 0) * ($status / 100));
                    $production = number_format($swordProduction) . ' swords, '
                        . number_format($bowProduction) . ' bows, '
                        . number_format($maceProduction) . ' maces';
                } elseif (!empty($b['production_name'])) {
                    $prod = $bWorking * ($b['production'] ?? 0);
                    $production = number_format($prod) . ' ' . $b['production_name'];
                }
            }

            // Calculate consumption (only when buildings are working)
            $consumption = '';
            if ($bWorking > 0) {
                if ($i == 7) {
                    // Tool maker
                    $consumption = number_format($bWorking * ($b['wood_need'] ?? 0)) . ' wood, '
                        . number_format($bWorking * ($b['iron_need'] ?? 0)) . ' iron';
                } elseif ($i == 8) {
                    // Weaponsmith
                    $bowProd = round(($player->bow_weapon_smith ?? 0) * ($status / 100));
                    $swordProd = round(($player->sword_weapon_smith ?? 0) * ($status / 100));
                    $maceProd = round(($player->mace_weapon_smith ?? 0) * ($status / 100));
                    $useWood = $bowProd * ($b['wood_need'] ?? 0) + $maceProd * ($b['mace_wood'] ?? 0);
                    $useIron = $swordProd * ($b['iron_need'] ?? 0) + $maceProd * ($b['mace_iron'] ?? 0);
                    $consumption = number_format($useWood) . ' wood, ' . number_format($useIron) . ' iron';
                } elseif ($i == 14) {
                    // Stable
                    $consumption = number_format($bWorking * ($b['food_need'] ?? 0)) . ' food';
                } elseif ($i == 15) {
                    // Mage Tower
                    $consumption = number_format($bWorking * ($b['gold_need'] ?? 0)) . ' gold';
                } elseif ($i == 16) {
                    // Winery
                    $consumption = number_format($bWorking * ($b['gold_need'] ?? 0)) . ' gold';
                }
            }

            $buildingStats[$i] = [
                'have' => $have,
                'land' => $land,
                'status' => $status,
                'bWorking' => $bWorking,
                'workers' => $workers,
                'production' => $production,
                'consumption' => $consumption,
            ];
        }

        // Population summary
        $free = $player->people - $totalWorkers - $numBuilders;
        if ($free < 0) {
            $totalWorkers = $totalWorkers + $free; // free is negative, so this subtracts
        }

        // House space
        $houseSpace = $player->house * ($buildings[4]['people'] ?? 100)
            + $player->town_center * ($buildings[11]['people'] ?? 100);
        $houseSpace = round($houseSpace + $houseSpace * ($player->research8 / 100));
        $freeSpace = $houseSpace - $player->people;

        // Advisor tips
        $advisorTips = $this->advisorService->getBuildTips(
            $player, $buildingStats, $buildQueue, $free, $freeMountain, $freeForest, $freePlains
        );

        // Building categories for card grid
        $buildingCategories = [
            ['label' => 'Food',             'color' => '#ff6600', 'ids' => [2, 3]],
            ['label' => 'Wood',             'color' => '#00ff00', 'ids' => [1]],
            ['label' => 'Mining',           'color' => '#ffcc00', 'ids' => [5, 6]],
            ['label' => 'Industry',         'color' => '#00ccff', 'ids' => [16, 7, 8, 14, 15]],
            ['label' => 'Military & Other', 'color' => '#ffffff', 'ids' => [9, 10, 11, 12, 13, 4]],
        ];

        return view('pages.build', compact(
            'buildQueue',
            'numBuilders',
            'displayOrder',
            'colors',
            'buildingStats',
            'buildingCategories',
            'totalWorkers',
            'totalBuildings',
            'totalLand',
            'free',
            'freeSpace',
            'freeMountain',
            'freeForest',
            'freePlains',
            'advisorTips'
        ));
    }

    /**
     * Build buildings.
     * Ported from eflag_build.cfm eflag=build
     */
    public function build(Request $request)
    {
        $player = player();
        $buildings = session('buildings');

        $request->validate([
            'building_no' => 'required|integer|min:1|max:16',
            'qty' => 'required|integer|min:1|max:10000000',
        ]);

        $buildingNo = (int) $request->building_no;
        $qty = (int) $request->qty;

        if (!isset($buildings[$buildingNo])) {
            if ($request->expectsJson()) {
                return $this->jsonError('Invalid building to build.');
            }
            session()->flash('game_message', 'Invalid building to build.');
            return redirect()->route('game.build');
        }

        $b = $buildings[$buildingNo];
        $needGold = $b['cost_gold'] * $qty;
        $needWood = $b['cost_wood'] * $qty;
        $needIron = $b['cost_iron'] * $qty;

        // Calculate land usage per type
        $mUsed = 0;
        $fUsed = 0;
        $pUsed = 0;
        foreach ($buildings as $i => $bld) {
            $have = $player->{$bld['db_column']} ?? 0;
            if ($bld['land'] === 'M') {
                $mUsed += $have * $bld['sq'];
            } elseif ($bld['land'] === 'F') {
                $fUsed += $have * $bld['sq'];
            } else {
                $pUsed += $have * $bld['sq'];
            }
        }

        // Determine free land for the building's land type
        if ($b['land'] === 'M') {
            $hasLand = $player->mland - $mUsed;
        } elseif ($b['land'] === 'F') {
            $hasLand = $player->fland - $fUsed;
        } else {
            $hasLand = $player->pland - $pUsed;
        }

        $needLand = $qty * $b['sq'];

        if ($needLand > $hasLand) {
            if ($request->expectsJson()) {
                return $this->jsonError("You do not have that much free land. (needed {$needLand})");
            }
            session()->flash('game_message', "You do not have that much free land. (needed {$needLand})");
        } elseif ($needGold > $player->gold) {
            if ($request->expectsJson()) {
                return $this->jsonError("You do not have enough gold.<br>You need {$needGold}");
            }
            session()->flash('game_message', "You do not have enough gold.<br>You need {$needGold}");
        } elseif ($needWood > $player->wood) {
            if ($request->expectsJson()) {
                return $this->jsonError("You do not have enough wood.<br>You need {$needWood}");
            }
            session()->flash('game_message', "You do not have enough wood.<br>You need {$needWood}");
        } elseif ($needIron > $player->iron) {
            if ($request->expectsJson()) {
                return $this->jsonError("You do not have enough iron.<br>You need {$needIron}");
            }
            session()->flash('game_message', "You do not have enough iron.<br>You need {$needIron}");
        } else {
            // Get next position
            $maxPos = BuildQueue::where('player_id', $player->id)->max('id');
            $newPos = ($maxPos ?? 0) + 1;

            $timeNeeded = $needWood + $needIron;

            BuildQueue::create([
                'player_id' => $player->id,
                'building_no' => $buildingNo,
                'turn_added' => $player->turn,
                'mission' => 0,
                'time_needed' => $timeNeeded,
                'qty' => $qty,
                'pos' => $newPos,
            ]);

            // Deduct resources
            $player->update([
                'gold' => $player->gold - $needGold,
                'wood' => $player->wood - $needWood,
                'iron' => $player->iron - $needIron,
            ]);

            $message = "{$qty} {$b['name']} added to your queue.<br>Total Cost: {$needGold} gold, {$needWood} wood, {$needIron} iron.";
            if ($request->expectsJson()) {
                return $this->jsonSuccess($player, $message);
            }
            session()->flash('game_message', $message);
        }

        return redirect()->route('game.build');
    }

    /**
     * Demolish buildings.
     * Ported from eflag_build.cfm eflag=demolish
     */
    public function demolish(Request $request)
    {
        $player = player();
        $buildings = session('buildings');

        $request->validate([
            'building_no' => 'required|integer|min:1|max:16',
            'qty' => 'required|integer|min:1|max:10000000',
        ]);

        $buildingNo = (int) $request->building_no;
        $qty = (int) $request->qty;

        if (!isset($buildings[$buildingNo])) {
            session()->flash('game_message', 'Invalid building to demolish.');
            return redirect()->route('game.build');
        }

        $b = $buildings[$buildingNo];

        // Check how many are already being demolished
        $beingDemolished = BuildQueue::where('player_id', $player->id)
            ->where('building_no', $buildingNo)
            ->where('mission', 1)
            ->sum('qty');

        $numBuilds = ($player->{$b['db_column']} ?? 0) - $beingDemolished;

        if ($numBuilds < $qty) {
            session()->flash('game_message', 'You do not have that many buildings of this type.');
        } else {
            $timeNeeded = ($b['cost_wood'] + $b['cost_iron']) * $qty;

            BuildQueue::create([
                'player_id' => $player->id,
                'building_no' => $buildingNo,
                'turn_added' => $player->turn,
                'mission' => 1,
                'time_needed' => $timeNeeded,
                'qty' => $qty,
                'pos' => 0,
            ]);

            session()->flash('game_message', "{$qty} {$b['name']} queued for demolition.");
        }

        return redirect()->route('game.build');
    }

    /**
     * Cancel a single build queue item.
     * Ported from eflag_build.cfm eflag=b_dequeue
     */
    public function cancel(Request $request)
    {
        $player = player();
        $buildings = session('buildings');

        $request->validate([
            'q_id' => 'required|integer',
        ]);

        $queueItem = BuildQueue::where('id', $request->q_id)
            ->where('player_id', $player->id)
            ->first();

        if ($queueItem) {
            $b = $buildings[$queueItem->building_no] ?? null;

            if ($b && $queueItem->mission == 0) {
                // Refund resources for build (not demolish)
                $getGold = $b['cost_gold'] * $queueItem->qty;
                $getWood = $b['cost_wood'] * $queueItem->qty;
                $getIron = $b['cost_iron'] * $queueItem->qty;

                $player->update([
                    'gold' => $player->gold + $getGold,
                    'wood' => $player->wood + $getWood,
                    'iron' => $player->iron + $getIron,
                ]);
            }

            $queueItem->delete();
        }

        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, 'Build queue item cancelled.');
        }
        return redirect()->route('game.build');
    }

    /**
     * Cancel all build queue items.
     * Ported from eflag_build.cfm eflag=cancel_all
     */
    public function cancelAll(Request $request)
    {
        $player = player();
        $buildings = session('buildings');

        $queueItems = BuildQueue::where('player_id', $player->id)->get();

        foreach ($queueItems as $queueItem) {
            $b = $buildings[$queueItem->building_no] ?? null;

            if ($b && $queueItem->mission == 0) {
                // Refund resources for build (not demolish)
                $getGold = $b['cost_gold'] * $queueItem->qty;
                $getWood = $b['cost_wood'] * $queueItem->qty;
                $getIron = $b['cost_iron'] * $queueItem->qty;

                $player->update([
                    'gold' => $player->gold + $getGold,
                    'wood' => $player->wood + $getWood,
                    'iron' => $player->iron + $getIron,
                ]);
                $player->refresh();
            }

            $queueItem->delete();
        }

        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, 'All build queue items cancelled.');
        }
        return redirect()->route('game.build');
    }

    /**
     * Move a queue item to top priority.
     * Ported from eflag_build.cfm eflag=to_top
     */
    public function moveToTop(Request $request)
    {
        $player = player();

        $request->validate([
            'q_id' => 'required|integer',
        ]);

        // Increment all positions by 1
        BuildQueue::where('player_id', $player->id)
            ->increment('pos');

        // Set the target item to position 0 (top)
        BuildQueue::where('player_id', $player->id)
            ->where('id', $request->q_id)
            ->update(['pos' => 0]);

        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, 'Build queue item moved to top.');
        }
        return redirect()->route('game.build');
    }

    /**
     * Move a queue item to bottom priority.
     * Ported from eflag_build.cfm eflag=to_bottom
     */
    public function moveToBottom(Request $request)
    {
        $player = player();

        $request->validate([
            'q_id' => 'required|integer',
        ]);

        // Get the max position
        $maxPos = BuildQueue::where('player_id', $player->id)->max('id');
        $newPos = ($maxPos ?? 0) + 1;

        BuildQueue::where('player_id', $player->id)
            ->where('id', $request->q_id)
            ->update(['pos' => $newPos]);

        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, 'Build queue item moved to bottom.');
        }
        return redirect()->route('game.build');
    }

    /**
     * Update building operational status (0-100%).
     * Ported from eflag_build.cfm eflag=changeBuildingStatus
     */
    public function updateStatus(Request $request)
    {
        $player = player();
        $buildings = session('buildings');

        // Single-building AJAX update: { column: "farmer_status", value: 5 }
        if ($request->has('column') && $request->has('value')) {
            $column = $request->input('column');
            $value = (int) $request->input('value');

            // Validate the column is a real building status column with allow_off
            $valid = false;
            foreach ($buildings as $b) {
                if ($b['allow_off'] && $b['db_column'] . '_status' === $column) {
                    $valid = true;
                    break;
                }
            }

            if (!$valid) {
                return $this->jsonError('Invalid building column.');
            }
            if ($value < 0 || $value > 100) {
                return $this->jsonError('Invalid status value.');
            }

            $player->update([$column => $value]);

            if ($request->expectsJson()) {
                return $this->jsonSuccess($player, 'Building status updated.');
            }
            return redirect()->route('game.build');
        }

        // Bulk update (legacy form POST)
        $updates = [];

        foreach ($buildings as $i => $b) {
            if ($b['allow_off']) {
                $statusField = $b['db_column'] . '_status';
                $inputName = str_replace('_', '_', $b['db_column']) . '_status';

                if ($request->has($inputName)) {
                    $status = (int) $request->input($inputName);
                    if ($status >= 0 && $status <= 10) {
                        $updates[$statusField] = $status * 10;
                    }
                }
            }
        }

        if (!empty($updates)) {
            $player->update($updates);
        }

        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, 'Building status updated.');
        }
        return redirect()->route('game.build');
    }
}
