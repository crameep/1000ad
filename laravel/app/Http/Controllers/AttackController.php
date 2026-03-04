<?php

namespace App\Http\Controllers;

use App\Http\Traits\ReturnsJson;
use App\Models\Alliance;
use App\Models\AttackNews;
use App\Models\AttackQueue;
use App\Models\Player;
use App\Services\GameAdvisorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Attack Controller
 *
 * Ported from attack.cfm and eflag_attack.cfm
 * Original: Andrew Deren, (C) AderSoftware 2000, 2001
 */
class AttackController extends Controller
{
    use ReturnsJson;

    protected GameAdvisorService $advisorService;

    public function __construct(GameAdvisorService $advisorService)
    {
        $this->advisorService = $advisorService;
    }

    /**
     * Show attack page.
     * Ported from attack.cfm
     */
    public function index()
    {
        $player = player();
        $soldiers = session('soldiers');
        $deathmatchMode = gameConfig('deathmatch_mode');

        // Protection check
        if ($player->turn <= 72 && !$deathmatchMode) {
            $advisorTips = $this->advisorService->getAttackTips($player, collect());
            return view('pages.attack', [
                'underProtection' => true,
                'attacks' => collect(),
                'advisorTips' => $advisorTips,
            ]);
        }

        // Get active attacks with target player info
        $attacks = AttackQueue::where('player_id', $player->id)
            ->join('players', 'attack_queues.attack_player_id', '=', 'players.id')
            ->select('attack_queues.*', 'players.name as empire_attacked', 'players.score as dscore')
            ->orderBy('attack_queues.id')
            ->get();

        // Status labels
        $statusLabels = [
            0 => 'Preparing',
            1 => 'On their way',
            2 => 'Almost there',
            3 => 'Done Fighting',
            4 => 'Returning',
            5 => 'Almost Home',
        ];

        // Attack type labels
        $attackTypeLabels = [
            0 => 'Conquer',
            1 => 'Raid',
            2 => 'Rob',
            3 => 'Slaughter',
            10 => 'Catapult Army and Towers',
            11 => 'Catapult Population',
            12 => 'Catapult Buildings',
            20 => 'Steal Army Information',
            21 => 'Steal Goods',
            22 => 'Poison Water',
            23 => 'Set Fire',
            24 => 'Steal Building Information',
            25 => 'Steal Research Information',
        ];

        // Calculate attack power for each active attack
        $sDate = Carbon::now()->subHours(24);

        foreach ($attacks as $attack) {
            // Determine attack category for news lookup
            if ($attack->attack_type >= 20) {
                $aType = 3;
            } elseif ($attack->attack_type >= 10) {
                $aType = 2;
            } else {
                $aType = 1;
            }

            // Count recent attacks against same target
            $myWon = AttackNews::where('attack_id', $player->id)
                ->where('defense_id', $attack->attack_player_id)
                ->where('created_on', '>=', $sDate)
                ->where('attack_type', $aType)
                ->where('attacker_wins', 1)
                ->count();

            $myLost = AttackNews::where('attack_id', $player->id)
                ->where('defense_id', $attack->attack_player_id)
                ->where('created_on', '>=', $sDate)
                ->where('attack_type', $aType)
                ->where('attacker_wins', 0)
                ->count();

            $othersWon = AttackNews::where('attack_id', '!=', $player->id)
                ->where('defense_id', $attack->attack_player_id)
                ->where('created_on', '>=', $sDate)
                ->where('attack_type', $aType)
                ->where('attacker_wins', 1)
                ->count();

            $hasAttacks = round($myWon + $myLost / 3 + $othersWon / 5);

            // Calculate base attack power
            $attackPower = 100;
            if (!$deathmatchMode) {
                if ($hasAttacks >= 15) {
                    $attackPower = 25;
                } elseif ($hasAttacks >= 12) {
                    $attackPower = 60;
                } elseif ($hasAttacks >= 10) {
                    $attackPower = 68;
                } elseif ($hasAttacks >= 8) {
                    $attackPower = 76;
                } elseif ($hasAttacks >= 5) {
                    $attackPower = 84;
                } elseif ($hasAttacks >= 3) {
                    $attackPower = 92;
                }
            }

            // Add wine bonus for army attacks that haven't finished fighting
            if ($attack->attack_type < 10 && $attack->status < 3) {
                $totalArmy = $attack->uunit + $attack->trained_peasants + $attack->macemen
                    + $attack->swordsman + $attack->archers + $attack->horseman;
                if ($totalArmy > 0) {
                    $percentWine = round(($attack->cost_wine / $totalArmy) * 100);
                } else {
                    $percentWine = 0;
                }
                $attackPower += $percentWine;
            }

            $attack->attack_power = round($attackPower);
            $attack->status_label = $statusLabels[$attack->status] ?? '?';
            $attack->type_label = $attackTypeLabels[$attack->attack_type] ?? 'Unknown';
        }

        // Unique unit name for this civ
        $uniqueUnitName = $soldiers[9]['name'] ?? 'Unique Unit';

        // Advisor tips
        $advisorTips = $this->advisorService->getAttackTips($player, $attacks);

        return view('pages.attack', [
            'underProtection' => false,
            'attacks' => $attacks,
            'statusLabels' => $statusLabels,
            'attackTypeLabels' => $attackTypeLabels,
            'uniqueUnitName' => $uniqueUnitName,
            'deathmatchMode' => $deathmatchMode,
            'advisorTips' => $advisorTips,
        ]);
    }

    /**
     * Launch an attack (army, catapult, or thief).
     * Ported from eflag_attack.cfm (attack_empire, catapult_attack, thief_attack, cancel_attack)
     */
    public function launch(Request $request)
    {
        $player = player();
        $deathmatchMode = gameConfig('deathmatch_mode');

        // Check deathmatch restriction
        if ($deathmatchMode) {
            $deathmatchStart = gameConfig('deathmatch_start')
                ? Carbon::parse(gameConfig('deathmatch_start'))
                : null;
            $deathmatchStarted = $deathmatchStart && $deathmatchStart->isPast();
            if (!$deathmatchStarted) {
                if ($request->expectsJson()) {
                    return $this->jsonError('Cannot attack before official deathmatch starts.');
                }
                return back()->with('game_message', 'Cannot attack before official deathmatch starts.');
            }
        }

        $eflag = $request->input('eflag');

        if ($eflag === 'attack_empire') {
            return $this->attackEmpire($request, $player);
        } elseif ($eflag === 'catapult_attack') {
            return $this->catapultAttack($request, $player);
        } elseif ($eflag === 'thief_attack') {
            return $this->thiefAttack($request, $player);
        } elseif ($eflag === 'cancel_attack') {
            return $this->cancelAttack($request, $player);
        }

        return redirect()->route('game.attack');
    }

    /**
     * Launch army attack.
     * Ported from eflag_attack.cfm eflag=attack_empire
     */
    protected function attackEmpire(Request $request, Player $player): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $soldiers = session('soldiers');
        $constants = session('constants');
        $deathmatchMode = gameConfig('deathmatch_mode');

        $sendAll = $request->input('sendAll', 0);
        $sendWine = max(0, (int) $request->input('sendwine', 0));

        if ($sendAll) {
            $sendSwordsman = $player->swordsman;
            $sendArchers = $player->archers;
            $sendHorseman = $player->horseman;
            $sendMacemen = $player->macemen;
            $sendTrainedPeasants = $player->trained_peasants;
            $sendUunit = $player->uunit;
        } else {
            $sendSwordsman = max(0, (int) $request->input('send_swordsman', 0));
            $sendArchers = max(0, (int) $request->input('send_archers', 0));
            $sendHorseman = max(0, (int) $request->input('send_horseman', 0));
            $sendMacemen = max(0, (int) $request->input('send_macemen', 0));
            $sendTrainedPeasants = max(0, (int) $request->input('send_trainedPeasants', 0));
            $sendUunit = max(0, (int) $request->input('send_uunit', 0));
        }

        $attackPlayerId = (int) $request->input('attackPlayerID', 0);
        $attackType = (int) $request->input('attack_type', 0);

        // Look up target player
        $attackPlayer = Player::find($attackPlayerId);

        // Count existing army attacks
        $existingAttacks = AttackQueue::where('player_id', $player->id)
            ->whereBetween('attack_type', [0, 9])
            ->count();

        // Check if attacking an ally
        $attackAlly = false;
        if ($player->alliance_id > 0 && $attackPlayer && $attackPlayer->alliance_id > 0) {
            $alliance = Alliance::find($player->alliance_id);
            if ($alliance) {
                $allies = [$alliance->ally1, $alliance->ally2, $alliance->ally3, $alliance->ally4, $alliance->ally5];
                if (in_array($attackPlayer->alliance_id, $allies)) {
                    $attackAlly = true;
                }
            }
        }

        $totalArmy = $sendSwordsman + $sendArchers + $sendHorseman + $sendMacemen + $sendTrainedPeasants + $sendUunit;
        $numSoldiers = $sendSwordsman + $sendArchers + ($sendHorseman * 2) + $sendMacemen
            + round($sendTrainedPeasants * 0.1) + ($sendUunit * 2);
        $soldiersEatOneFood = $constants['soldiers_eat_one_food'];
        $eatSoldiersFood = (int) ceil($numSoldiers / $soldiersEatOneFood) * 3;

        // Handle max wine
        $sendMaxWine = $request->input('sendmaxwine', 0);
        if ($sendMaxWine) {
            $sendWine = $player->wine;
            if ($sendWine > $totalArmy) {
                $sendWine = $totalArmy;
            }
        }

        // Calculate gold cost
        $needGold = round(
            $sendSwordsman * $soldiers[2]['gold_per_turn']
            + $sendArchers * $soldiers[1]['gold_per_turn']
            + $sendHorseman * $soldiers[3]['gold_per_turn']
            + $sendMacemen * $soldiers[6]['gold_per_turn']
            + $sendTrainedPeasants * $soldiers[7]['gold_per_turn']
            + $sendUunit * $soldiers[9]['gold_per_turn']
        ) * 6;

        $uniqueUnitName = $soldiers[9]['name'] ?? 'Unique Unit';

        // Validation chain (matching original CF logic order)
        $error = null;
        if ($attackPlayerId === $player->id) {
            $error = "Are you nuts? You can't attack yourself!!!";
        } elseif ($attackPlayer && $attackPlayer->user_id === $player->user_id) {
            $error = "You cannot attack your own empires.";
        } elseif ($player->food < $eatSoldiersFood) {
            $error = "You do not have enough food to send your soldiers. You need " . number_format($eatSoldiersFood) . " to send that much army.";
        } elseif ($attackAlly) {
            $error = 'You cannot attack your allies.';
        } elseif ($player->turn <= 72 && !$deathmatchMode) {
            $error = 'Cannot attack under protection.';
        } elseif ($attackPlayer && $attackPlayer->turn <= 72 && !$deathmatchMode) {
            $error = 'Cannot attack players under protection.';
        } elseif ($attackPlayer && $attackPlayer->killed_by > 0) {
            $error = 'Cannot attack dead empires.';
        } elseif ($attackPlayer && $attackPlayer->alliance_id === $player->alliance_id && $player->alliance_id > 0) {
            $error = 'Cannot attack empires in your alliance.';
        } elseif ($attackType < 0 || $attackType > 3) {
            $error = 'Invalid attack type!';
        } elseif ($sendSwordsman < 0) {
            $error = 'Cannot send negative swordsman!';
        } elseif ($sendArchers < 0) {
            $error = 'Cannot send negative archers!';
        } elseif ($sendHorseman < 0) {
            $error = 'Cannot send negative horseman!';
        } elseif ($sendMacemen < 0) {
            $error = 'Cannot send negative macemen!';
        } elseif ($sendUunit < 0) {
            $error = "Cannot send negative {$uniqueUnitName}s";
        } elseif ($sendTrainedPeasants < 0) {
            $error = 'Cannot send negative trained peasants!';
        } elseif ($totalArmy === 0) {
            $error = 'Cannot send 0 total army!';
        } elseif ($sendWine > $totalArmy) {
            $error = 'You can only send 1 wine per soldier.';
        } elseif ($sendWine < 0) {
            $error = 'Cannot send less than 0 wine.';
        } elseif ($sendWine > $player->wine) {
            $error = 'You do not have that much wine.';
        } elseif ($sendUunit > $player->uunit) {
            $error = "You do not have that many {$uniqueUnitName}";
        } elseif ($sendSwordsman > $player->swordsman) {
            $error = 'You do not have that many swordsman.';
        } elseif ($sendArchers > $player->archers) {
            $error = 'You do not have that many archers.';
        } elseif ($sendHorseman > $player->horseman) {
            $error = 'You do not have that many horseman.';
        } elseif ($sendMacemen > $player->macemen) {
            $error = 'You do not have that many macemen.';
        } elseif ($sendTrainedPeasants > $player->trained_peasants) {
            $error = 'You do not have that many trained peasants.';
        } elseif (!$attackPlayer) {
            $error = "Empire No. {$attackPlayerId} does not exist.";
        } elseif ($existingAttacks >= 1 && !$deathmatchMode) {
            $error = 'Your armies are already attacking someone. Please wait for them to come back.';
        } elseif ($needGold > $player->gold) {
            $error = "You do not have enough gold to pay your soldiers to fight<br>(You need {$needGold} gold)";
        }

        if ($error !== null) {
            if ($request->expectsJson()) {
                return $this->jsonError($error);
            }
            return back()->with('game_message', $error);
        }

        // All checks passed - create the attack
        AttackQueue::create([
            'player_id' => $player->id,
            'attack_player_id' => $attackPlayerId,
            'uunit' => $sendUunit,
            'swordsman' => $sendSwordsman,
            'archers' => $sendArchers,
            'horseman' => $sendHorseman,
            'macemen' => $sendMacemen,
            'trained_peasants' => $sendTrainedPeasants,
            'turn' => $player->turn,
            'status' => 0,
            'attack_type' => $attackType,
            'cost_wine' => $sendWine,
            'cost_food' => $eatSoldiersFood,
            'cost_gold' => $needGold,
        ]);

        // Deduct resources from player
        $player->update([
            'uunit' => DB::raw("uunit - {$sendUunit}"),
            'swordsman' => DB::raw("swordsman - {$sendSwordsman}"),
            'archers' => DB::raw("archers - {$sendArchers}"),
            'horseman' => DB::raw("horseman - {$sendHorseman}"),
            'macemen' => DB::raw("macemen - {$sendMacemen}"),
            'trained_peasants' => DB::raw("trained_peasants - {$sendTrainedPeasants}"),
            'gold' => DB::raw("gold - {$needGold}"),
            'food' => DB::raw("food - {$eatSoldiersFood}"),
            'wine' => DB::raw("wine - {$sendWine}"),
        ]);

        $message = "<b>Your army is preparing to attack {$attackPlayer->name} (#{$attackPlayerId}).<br>They will reach their destination in 3 months.</b><br>Your soldiers have been paid " . number_format($needGold) . " and given " . number_format($eatSoldiersFood) . " food for this expedition.<br>";

        if ($sendWine > 0) {
            $percentWine = round(($sendWine / $totalArmy) * 100);
            $message .= number_format($sendWine) . " units of wine will boost army strength by " . number_format($percentWine) . "%<br>";
        }

        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, $message);
        }
        return redirect()->route('game.attack')->with('game_message', $message);
    }

    /**
     * Launch catapult attack.
     * Ported from eflag_attack.cfm eflag=catapult_attack
     */
    protected function catapultAttack(Request $request, Player $player): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $deathmatchMode = gameConfig('deathmatch_mode');

        $sendAll = $request->input('sendAll', 0);
        if ($sendAll) {
            $sendCatapults = $player->catapults;
        } else {
            $sendCatapults = max(0, (int) $request->input('send_catapults', 0));
        }

        $attackPlayerId = (int) $request->input('attackPlayerID', 0);
        $attackType = (int) $request->input('attack_type', 10);

        $attackPlayer = Player::find($attackPlayerId);

        $existingAttacks = AttackQueue::where('player_id', $player->id)
            ->whereBetween('attack_type', [10, 19])
            ->count();

        // Check ally status
        $attackAlly = false;
        if ($player->alliance_id > 0 && $attackPlayer && $attackPlayer->alliance_id > 0) {
            $alliance = Alliance::find($player->alliance_id);
            if ($alliance) {
                $allies = [$alliance->ally1, $alliance->ally2, $alliance->ally3, $alliance->ally4, $alliance->ally5];
                if (in_array($attackPlayer->alliance_id, $allies)) {
                    $attackAlly = true;
                }
            }
        }

        $needGold = $sendCatapults * 5;

        // Validation
        $error = null;
        if ($attackPlayerId === $player->id) {
            $error = "Are you nuts? You can't attack yourself!!!";
        } elseif ($attackPlayer && $attackPlayer->user_id === $player->user_id) {
            $error = "You cannot attack your own empires.";
        } elseif ($attackAlly) {
            $error = 'You cannot attack your allies.';
        } elseif ($player->turn <= 72 && !$deathmatchMode) {
            $error = 'Cannot attack under protection.';
        } elseif ($attackPlayer && $attackPlayer->turn <= 72 && !$deathmatchMode) {
            $error = 'Cannot attack players under protection.';
        } elseif ($attackPlayer && $attackPlayer->alliance_id === $player->alliance_id && $player->alliance_id > 0) {
            $error = 'Cannot attack empires in your alliance.';
        } elseif ($attackPlayer && $attackPlayer->killed_by > 0) {
            $error = 'Cannot attack dead empires.';
        } elseif ($attackType < 10 || $attackType > 12) {
            $error = 'Invalid attack type!';
        } elseif ($sendCatapults <= 0) {
            $error = 'Cannot send negative or 0 catapults!';
        } elseif ($sendCatapults > $player->catapults) {
            $error = 'You do not have that many catapults.';
        } elseif ($sendCatapults > $player->town_center) {
            $error = 'You do not have that many town centers to support your catapults.';
        } elseif (!$attackPlayer) {
            $error = "Empire No. {$attackPlayerId} does not exist.";
        } elseif ($needGold > $player->gold) {
            $error = "You do not have enough gold to pay for the catapults<br>(You need {$needGold} gold)";
        } elseif ($existingAttacks >= 1) {
            $error = 'Your armies are already attacking someone. Please wait for them to come back.';
        }

        if ($error !== null) {
            if ($request->expectsJson()) {
                return $this->jsonError($error);
            }
            return back()->with('game_message', $error);
        }

        // Create the attack
        AttackQueue::create([
            'player_id' => $player->id,
            'attack_player_id' => $attackPlayerId,
            'catapults' => $sendCatapults,
            'turn' => $player->turn,
            'status' => 0,
            'attack_type' => $attackType,
        ]);

        $player->update([
            'catapults' => DB::raw("catapults - {$sendCatapults}"),
            'gold' => DB::raw("gold - {$needGold}"),
        ]);

        $message = "<b>Your catapults are preparing to attack {$attackPlayer->name} (#{$attackPlayerId}).<br>They will reach their destination in 3 turns.</b><br>Your catapults have been paid {$needGold} for this expedition.<br>";
        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, $message);
        }
        return redirect()->route('game.attack')->with('game_message', $message);
    }

    /**
     * Launch thief attack.
     * Ported from eflag_attack.cfm eflag=thief_attack
     */
    protected function thiefAttack(Request $request, Player $player): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $soldiers = session('soldiers');
        $deathmatchMode = gameConfig('deathmatch_mode');

        $sendAll = $request->input('sendAll', 0);
        if ($sendAll) {
            $sendThieves = $player->thieves;
        } else {
            $sendThieves = max(0, (int) $request->input('send_thieves', 0));
        }

        $attackPlayerId = (int) $request->input('attackPlayerID', 0);
        $attackType = (int) $request->input('attack_type', 20);

        $attackPlayer = Player::find($attackPlayerId);

        $existingAttacks = AttackQueue::where('player_id', $player->id)
            ->whereBetween('attack_type', [20, 29])
            ->count();

        // Check ally status
        $attackAlly = false;
        if ($player->alliance_id > 0 && $attackPlayer && $attackPlayer->alliance_id > 0) {
            $alliance = Alliance::find($player->alliance_id);
            if ($alliance) {
                $allies = [$alliance->ally1, $alliance->ally2, $alliance->ally3, $alliance->ally4, $alliance->ally5];
                if (in_array($attackPlayer->alliance_id, $allies)) {
                    $attackAlly = true;
                }
            }
        }

        $needGold = round($sendThieves * $soldiers[8]['gold_per_turn']);

        // Validation
        $error = null;
        if ($attackPlayerId === $player->id) {
            $error = "Are you nuts? You can't attack yourself!!!";
        } elseif ($attackPlayer && $attackPlayer->user_id === $player->user_id) {
            $error = "You cannot attack your own empires.";
        } elseif ($attackAlly) {
            $error = 'You cannot attack your allies.';
        } elseif ($player->turn <= 72 && !$deathmatchMode) {
            $error = 'Cannot attack under protection.';
        } elseif ($attackPlayer && $attackPlayer->turn <= 72 && !$deathmatchMode) {
            $error = 'Cannot attack players under protection.';
        } elseif ($attackPlayer && $attackPlayer->alliance_id === $player->alliance_id && $player->alliance_id > 0) {
            $error = 'Cannot attack empires in your alliance.';
        } elseif ($attackPlayer && $attackPlayer->killed_by > 0) {
            $error = 'Cannot attack dead empires.';
        } elseif ($attackType < 20 || $attackType > 25) {
            $error = 'Invalid attack type!';
        } elseif ($sendThieves <= 0) {
            $error = 'Cannot send negative or 0 thieves!';
        } elseif ($sendThieves > $player->thieves) {
            $error = 'You do not have that many thieves.';
        } elseif ($sendThieves > $player->town_center) {
            $error = 'You do not have that many town centers to support your thieves.';
        } elseif (!$attackPlayer) {
            $error = "Empire No. {$attackPlayerId} does not exist.";
        } elseif ($needGold > $player->gold) {
            $error = "You do not have enough gold to pay your thieves for the attack.<br>(You need {$needGold} gold)";
        } elseif ($existingAttacks >= 1) {
            $error = 'Your armies are already attacking someone. Please wait for them to come back.';
        } elseif ($player->score > $attackPlayer->score * 2 && !$deathmatchMode) {
            $error = 'Cannot attack empires that are half as small as you.';
        } elseif ($player->score * 2 < $attackPlayer->score && !$deathmatchMode) {
            $error = 'Cannot attack empires that are twice as big as you.';
        }

        if ($error !== null) {
            if ($request->expectsJson()) {
                return $this->jsonError($error);
            }
            return back()->with('game_message', $error);
        }

        // Create the attack
        AttackQueue::create([
            'player_id' => $player->id,
            'attack_player_id' => $attackPlayerId,
            'thieves' => $sendThieves,
            'turn' => $player->turn,
            'status' => 0,
            'attack_type' => $attackType,
        ]);

        $player->update([
            'thieves' => DB::raw("thieves - {$sendThieves}"),
            'gold' => DB::raw("gold - {$needGold}"),
        ]);

        $message = "<b>Your thieves are preparing to attack {$attackPlayer->name} (#{$attackPlayerId}).<br>They will reach their destination in 3 turns.</b><br>Your thieves have been paid {$needGold} for this expedition.";
        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, $message);
        }
        return redirect()->route('game.attack')->with('game_message', $message);
    }

    /**
     * Public cancel route handler.
     */
    public function cancel(Request $request): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        return $this->cancelAttack($request, player());
    }

    /**
     * Cancel an active attack.
     * Ported from eflag_attack.cfm eflag=cancel_attack
     */
    protected function cancelAttack(Request $request, Player $player): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $queueId = (int) $request->input('armyID', 0);

        $attack = AttackQueue::where('id', $queueId)
            ->where('player_id', $player->id)
            ->first();

        if (!$attack) {
            if ($request->expectsJson()) {
                return $this->jsonError('Attack not found.');
            }
            return redirect()->route('game.attack');
        }

        $targetName = Player::where('id', $attack->attack_player_id)->value('name') ?? 'Unknown';

        if ($attack->status === 0) {
            // Has not left fort yet - return everything
            $player->update([
                'uunit' => DB::raw("uunit + {$attack->uunit}"),
                'swordsman' => DB::raw("swordsman + {$attack->swordsman}"),
                'archers' => DB::raw("archers + {$attack->archers}"),
                'horseman' => DB::raw("horseman + {$attack->horseman}"),
                'macemen' => DB::raw("macemen + {$attack->macemen}"),
                'trained_peasants' => DB::raw("trained_peasants + {$attack->trained_peasants}"),
                'catapults' => DB::raw("catapults + {$attack->catapults}"),
                'thieves' => DB::raw("thieves + {$attack->thieves}"),
                'food' => DB::raw("food + {$attack->cost_food}"),
                'wine' => DB::raw("wine + {$attack->cost_wine}"),
                'gold' => DB::raw("gold + {$attack->cost_gold}"),
            ]);

            $attack->delete();

            $message = "<b>Your army stopped preparing to attack {$targetName} (#{$attack->attack_player_id}).</b>";
            if ($request->expectsJson()) {
                return $this->jsonSuccess($player, $message);
            }
            return redirect()->route('game.attack')->with('game_message', $message);
        } elseif ($attack->status === 1 || $attack->status === 2) {
            // On their way - set to returning
            $attack->update(['status' => 4]);

            $message = '<b>Your army is returning to your empire as you requested and should be back soon.</b>';
            if ($request->expectsJson()) {
                return $this->jsonSuccess($player, $message);
            }
            return redirect()->route('game.attack')->with('game_message', $message);
        }

        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, 'Attack cancelled.');
        }
        return redirect()->route('game.attack');
    }
}
