<?php

namespace App\Http\Controllers;

use App\Models\AidLog;
use App\Models\Player;
use App\Models\TransferQueue;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Aid Controller
 *
 * Handles sending aid (resources) to other players.
 * Ported from aid.cfm and eflag_aid.cfm
 *
 * Original: Andrew Deren, (C) AderSoftware 2000, 2001
 */
class AidController extends Controller
{
    /**
     * Show the aid page.
     * Ported from aid.cfm
     */
    public function index()
    {
        $player = player();
        $buildings = session('buildings');

        // Block in deathmatch mode
        if (gameConfig('deathmatch_mode') || gameConfig('alliance_max_members') == 0) {
            session()->flash('game_message', 'Cannot view this page in deathmatch game.');
            return redirect()->route('game.main');
        }

        // Calculate max trades and remaining
        $maxTrades = TradeController::calculateMaxTrades($player, $buildings);
        $tradesRemaining = $maxTrades - $player->trades_this_turn;

        // Get dispatched caravans (aid transfers)
        $caravans = TransferQueue::where('from_player_id', $player->id)
            ->where('transfer_type', 1)
            ->join('players', 'transfer_queues.to_player_id', '=', 'players.id')
            ->select('transfer_queues.*', 'players.name as recipient_name')
            ->orderBy('transfer_queues.id')
            ->get();

        // Calculate cancel cutoff time (15 minutes ago)
        $cancelCutoff = Carbon::now()->subMinutes(15);

        return view('pages.aid', [
            'maxTrades' => $maxTrades,
            'tradesRemaining' => $tradesRemaining,
            'caravans' => $caravans,
            'cancelCutoff' => $cancelCutoff,
        ]);
    }

    /**
     * Send aid to another player.
     * Ported from eflag_aid.cfm eflag=startAid
     */
    public function sendAid(Request $request)
    {
        $player = player();
        $buildings = session('buildings');

        if (gameConfig('deathmatch_mode')) {
            session()->flash('game_message', 'Cannot view this page in deathmatch game.');
            return redirect()->route('game.main');
        }

        // Parse amounts (strip commas)
        $sendWood = max(0, (int) str_replace(',', '', $request->input('send_wood', 0)));
        $sendFood = max(0, (int) str_replace(',', '', $request->input('send_food', 0)));
        $sendIron = max(0, (int) str_replace(',', '', $request->input('send_iron', 0)));
        $sendGold = max(0, (int) str_replace(',', '', $request->input('send_gold', 0)));
        $sendTools = max(0, (int) str_replace(',', '', $request->input('send_tools', 0)));
        $sendMaces = max(0, (int) str_replace(',', '', $request->input('send_maces', 0)));
        $sendSwords = max(0, (int) str_replace(',', '', $request->input('send_swords', 0)));
        $sendBows = max(0, (int) str_replace(',', '', $request->input('send_bows', 0)));
        $sendHorses = max(0, (int) str_replace(',', '', $request->input('send_horses', 0)));
        $toPlayerId = (int) $request->input('send_empire_no', 0);

        $sendOK = true;
        $message = '';

        // Check if already sent aid to this person in the past hour
        $oneHourAgo = Carbon::now()->subHour();
        $recentAid = AidLog::where('from_player_id', $player->id)
            ->where('to_player_id', $toPlayerId)
            ->where('created_on', '>=', $oneHourAgo)
            ->first();

        if ($recentAid) {
            session()->flash('game_message', 'You are only allowed to send aid to the same person once every hour.');
            return redirect()->route('game.aid');
        }

        // Validate target empire exists
        $toPlayer = Player::find($toPlayerId);
        if (!$toPlayer) {
            session()->flash('game_message', "Empire #{$toPlayerId} not found.");
            return redirect()->route('game.aid');
        }

        // Validate resource amounts
        $validations = [
            'wood' => [$sendWood, $player->wood],
            'iron' => [$sendIron, $player->iron],
            'food' => [$sendFood, $player->food],
            'gold' => [$sendGold, $player->gold],
            'tools' => [$sendTools, $player->tools],
            'maces' => [$sendMaces, $player->maces],
            'swords' => [$sendSwords, $player->swords],
            'bows' => [$sendBows, $player->bows],
            'horses' => [$sendHorses, $player->horses],
        ];

        foreach ($validations as $resource => [$sendAmount, $playerAmount]) {
            if ($sendAmount < 0 || $sendAmount > $playerAmount) {
                session()->flash('game_message', "You can only send " . number_format($playerAmount) . " " . ucfirst($resource) . ".");
                return redirect()->route('game.aid');
            }
        }

        // Calculate total and check trades remaining
        $totalSend = $sendWood + $sendIron + $sendFood + $sendGold + $sendTools
            + $sendSwords + $sendBows + $sendHorses + $sendMaces;

        if ($totalSend == 0) {
            session()->flash('game_message', 'Cannot send 0 goods.');
            return redirect()->route('game.aid');
        }

        $maxTrades = TradeController::calculateMaxTrades($player, $buildings);
        $tradesRemaining = $maxTrades - $player->trades_this_turn;

        if ($totalSend > $tradesRemaining) {
            session()->flash('game_message', "You can send only {$tradesRemaining} more goods this month.");
            return redirect()->route('game.aid');
        }

        // Deduct from player
        $player->update([
            'wood' => $player->wood - $sendWood,
            'iron' => $player->iron - $sendIron,
            'gold' => $player->gold - $sendGold,
            'food' => $player->food - $sendFood,
            'tools' => $player->tools - $sendTools,
            'swords' => $player->swords - $sendSwords,
            'bows' => $player->bows - $sendBows,
            'horses' => $player->horses - $sendHorses,
            'maces' => $player->maces - $sendMaces,
            'trades_this_turn' => $player->trades_this_turn + $totalSend,
        ]);

        // Apply 5% fee
        $sendWood = (int) round($sendWood * 0.95);
        $sendIron = (int) round($sendIron * 0.95);
        $sendFood = (int) round($sendFood * 0.95);
        $sendGold = (int) round($sendGold * 0.95);
        $sendTools = (int) round($sendTools * 0.95);
        $sendMaces = (int) round($sendMaces * 0.95);
        $sendSwords = (int) round($sendSwords * 0.95);
        $sendBows = (int) round($sendBows * 0.95);
        $sendHorses = (int) round($sendHorses * 0.95);

        $createdOn = now();

        // Create transfer queue entry
        TransferQueue::create([
            'from_player_id' => $player->id,
            'to_player_id' => $toPlayerId,
            'wood' => $sendWood,
            'iron' => $sendIron,
            'food' => $sendFood,
            'gold' => $sendGold,
            'tools' => $sendTools,
            'maces' => $sendMaces,
            'swords' => $sendSwords,
            'bows' => $sendBows,
            'horses' => $sendHorses,
            'transfer_type' => 1,
            'turns_remaining' => 3,
            'created_on' => $createdOn,
        ]);

        // Create aid log entry
        AidLog::create([
            'from_player_id' => $player->id,
            'to_player_id' => $toPlayerId,
            'wood' => $sendWood,
            'iron' => $sendIron,
            'food' => $sendFood,
            'gold' => $sendGold,
            'tools' => $sendTools,
            'maces' => $sendMaces,
            'swords' => $sendSwords,
            'bows' => $sendBows,
            'horses' => $sendHorses,
            'created_on' => $createdOn,
        ]);

        $message = "Transport to {$toPlayer->name} has been dispatched.<br>"
            . "5% fee has been assessed by merchants.<br>"
            . "Caravans will reach their destination in 3 turns.";

        session()->flash('game_message', $message);
        return redirect()->route('game.aid');
    }

    /**
     * Cancel a pending aid transfer.
     * Ported from eflag_aid.cfm eflag=cancelAid
     */
    public function cancelAid(Request $request)
    {
        $player = player();

        if (gameConfig('deathmatch_mode')) {
            session()->flash('game_message', 'Cannot view this page in deathmatch game.');
            return redirect()->route('game.main');
        }

        $aidId = (int) $request->input('aid_id', 0);

        $aid = TransferQueue::where('id', $aidId)
            ->where('from_player_id', $player->id)
            ->where('transfer_type', 1)
            ->first();

        if (!$aid) {
            return redirect()->route('game.aid');
        }

        // Check if cancel is still allowed (turnsRemaining=3 and created within 15 minutes)
        $cancelCutoff = Carbon::now()->subMinutes(15);

        if ($aid->turns_remaining != 3 || ($aid->created_on && $aid->created_on->lt($cancelCutoff))) {
            session()->flash('game_message', 'This aid cannot be cancelled anymore.');
            return redirect()->route('game.aid');
        }

        // Return goods to player (these are already after the 5% fee)
        $player->update([
            'wood' => $player->wood + $aid->wood,
            'iron' => $player->iron + $aid->iron,
            'food' => $player->food + $aid->food,
            'gold' => $player->gold + $aid->gold,
            'tools' => $player->tools + $aid->tools,
            'maces' => $player->maces + $aid->maces,
            'swords' => $player->swords + $aid->swords,
            'bows' => $player->bows + $aid->bows,
            'horses' => $player->horses + $aid->horses,
        ]);

        // Delete the aid log entry
        AidLog::where('from_player_id', $aid->from_player_id)
            ->where('to_player_id', $aid->to_player_id)
            ->where('created_on', $aid->created_on)
            ->delete();

        // Delete the transfer queue entry
        $aid->delete();

        session()->flash('game_message', "Aid to empire #{$aid->to_player_id} has been cancelled.");
        return redirect()->route('game.aid');
    }
}
