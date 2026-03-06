<?php

namespace App\Http\Controllers;

use App\Http\Traits\ReturnsJson;
use App\Models\BlockMessage;
use App\Models\Player;
use App\Models\PlayerMessage;
use Illuminate\Http\Request;

/**
 * Message Controller
 *
 * Handles player-to-player messaging, blocking, and message management.
 * Ported from player_messages.cfm, eflag_player_messages.cfm
 *
 * Original: Andrew Deren, (C) AderSoftware 2000, 2001
 */
class MessageController extends Controller
{
    use ReturnsJson;
    /**
     * Show messages page with folder navigation.
     * Route: GET /game/messages/{folder?}
     * Ported from player_messages.cfm
     */
    public function index(Request $request, $folder = 'inbox')
    {
        $player = player();
        $menuPlayerID = $request->input('menuPlayerID', 0);

        $data = [
            'messageFolder' => $folder,
            'menuPlayerID' => $menuPlayerID,
        ];

        switch ($folder) {
            case 'inbox':
                // Get alliance members for quick lookup
                $allianceMembers = collect();
                $allianceList = '';
                if ($player->alliance_id > 0) {
                    $allianceMembers = Player::where('alliance_id', $player->alliance_id)
                        ->orderBy('name')
                        ->get(['id', 'name']);
                    $allianceList = $allianceMembers->pluck('id')->implode(',');
                }

                // Get inbox messages (messageType=0)
                $messages = PlayerMessage::where('to_player_id', $player->id)
                    ->where('message_type', 0)
                    ->orderBy('created_on', 'desc')
                    ->get();

                // Mark all unviewed messages as viewed
                if ($messages->isNotEmpty()) {
                    PlayerMessage::where('to_player_id', $player->id)
                        ->where('message_type', 0)
                        ->where('viewed', 0)
                        ->update(['viewed' => 1]);
                }

                // Always clear the flag when visiting inbox
                if ($player->has_new_messages) {
                    $player->update(['has_new_messages' => 0]);
                }

                $data['messages'] = $messages;
                $data['allianceMembers'] = $allianceMembers;
                $data['allianceList'] = $allianceList;
                break;

            case 'saved':
                $data['messages'] = PlayerMessage::where('to_player_id', $player->id)
                    ->where('message_type', 4)
                    ->orderBy('created_on', 'desc')
                    ->get();
                break;

            case 'sent':
                $data['messages'] = PlayerMessage::where('from_player_id', $player->id)
                    ->whereIn('message_type', [0, 2, 4])
                    ->orderBy('created_on', 'desc')
                    ->limit(250)
                    ->get(['id', 'to_player_name', 'to_player_id', 'viewed', 'created_on']);
                break;

            case 'deleted':
                $data['messages'] = PlayerMessage::where('to_player_id', $player->id)
                    ->where('message_type', 2)
                    ->orderBy('created_on', 'desc')
                    ->limit(250)
                    ->get(['id', 'from_player_name', 'from_player_id', 'viewed', 'created_on']);
                break;

            case 'options':
                // Get blocked players
                $blockedPlayers = BlockMessage::where('block_messages.player_id', $player->id)
                    ->join('players', 'players.id', '=', 'block_messages.block_player_id')
                    ->orderBy('players.name')
                    ->get([
                        'block_messages.id',
                        'players.id as player_id',
                        'players.name',
                    ]);
                $data['blockedPlayers'] = $blockedPlayers;
                break;
        }

        return view('pages.messages', $data);
    }

    /**
     * View a single message.
     * Route: GET /game/messages/view/{id}
     */
    public function viewMessage($id)
    {
        $player = player();

        $message = PlayerMessage::where(function ($q) use ($player) {
                $q->where('from_player_id', $player->id)
                  ->orWhere('to_player_id', $player->id);
            })
            ->where('id', (int) $id)
            ->first();

        return view('pages.messages', [
            'messageFolder' => 'viewMessage',
            'menuPlayerID' => 0,
            'message' => $message,
        ]);
    }

    /**
     * Send a message to one or more players.
     * Route: POST /game/messages/send
     * Ported from eflag_player_messages.cfm eflag=add_message
     */
    public function sendMessage(Request $request)
    {
        $player = player();

        $request->validate([
            'toPlayerID' => 'required|string|max:200',
            'pmessage' => 'required|string|max:5000',
        ]);

        $messageText = substr($request->input('pmessage'), 0, 5000);
        // Sanitize HTML
        $messageText = str_replace('<', '&lt;', $messageText);
        $messageText = str_replace('>', '&gt;', $messageText);

        $recipientIds = array_filter(array_map('intval', explode(',', $request->input('toPlayerID'))));
        $resultMessages = '';

        foreach ($recipientIds as $pId) {
            if ($pId <= 0) {
                continue;
            }

            $toPlayer = Player::find($pId, ['id', 'name']);
            if (!$toPlayer) {
                $resultMessages .= "Player #{$pId} is not valid.<br>";
                continue;
            }

            // Check if this player has blocked us
            $isBlocked = BlockMessage::where('player_id', $pId)
                ->where('block_player_id', $player->id)
                ->exists();

            if ($isBlocked) {
                $resultMessages .= "Player #{$pId} doesn't want to receive messages from you.<br>";
                continue;
            }

            PlayerMessage::create([
                'to_player_id' => $pId,
                'from_player_id' => $player->id,
                'to_player_name' => $toPlayer->name,
                'from_player_name' => $player->name,
                'created_on' => now(),
                'viewed' => 0,
                'message' => $messageText,
                'message_type' => 0,
            ]);

            Player::where('id', $pId)->update(['has_new_messages' => 1]);

            $resultMessages .= "Message sent to {$toPlayer->name}<br>";
        }

        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, $resultMessages ?: 'Message sent.');
        }

        if ($resultMessages) {
            session()->flash('game_message', $resultMessages);
        }

        return redirect()->route('game.messages');
    }

    /**
     * Delete a single message (soft delete - set messageType=2).
     * Route: POST /game/messages/delete/{id}
     * Ported from eflag_player_messages.cfm eflag=delete_message
     */
    public function deleteMessage(Request $request, $id)
    {
        $player = player();

        PlayerMessage::where('id', (int) $id)
            ->where('to_player_id', $player->id)
            ->update(['message_type' => 2]);

        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, 'Message deleted.');
        }

        return redirect()->back();
    }

    /**
     * Delete all inbox messages.
     * Route: POST /game/messages/delete-all
     * Ported from eflag_player_messages.cfm eflag=delete_all_messages
     */
    public function deleteAllMessages(Request $request)
    {
        $player = player();

        PlayerMessage::where('to_player_id', $player->id)
            ->where('message_type', 0)
            ->update(['message_type' => 2]);

        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, 'All messages deleted.');
        }

        return redirect()->route('game.messages');
    }

    /**
     * Save a message (set messageType=4).
     * Route: POST /game/messages/save/{id}
     * Ported from eflag_player_messages.cfm eflag=save_message
     */
    public function saveMessage(Request $request, $id)
    {
        $player = player();

        PlayerMessage::where('id', (int) $id)
            ->where('to_player_id', $player->id)
            ->update(['message_type' => 4]);

        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, 'Message saved.');
        }

        return redirect()->route('game.messages');
    }

    /**
     * Delete all saved messages.
     * Route: POST /game/messages/delete-all-saved
     * Ported from eflag_player_messages.cfm eflag=delete_all_saved
     */
    public function deleteAllSaved(Request $request)
    {
        $player = player();

        PlayerMessage::where('to_player_id', $player->id)
            ->where('message_type', 4)
            ->update(['message_type' => 2]);

        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, 'All saved messages deleted.');
        }

        return redirect()->route('game.messages', ['folder' => 'saved']);
    }

    /**
     * Block a player from sending messages.
     * Route: POST /game/messages/block/{id}
     * Ported from eflag_player_messages.cfm eflag=addblock
     */
    public function addBlock(Request $request, $id)
    {
        $player = player();
        $blockId = (int) $id;

        $targetPlayer = Player::find($blockId, ['id', 'name']);
        if (!$targetPlayer) {
            if ($request->expectsJson()) {
                return $this->jsonError("Player #{$blockId} not found.");
            }
            session()->flash('game_message', "Player #{$blockId} not found.");
            return redirect()->route('game.messages', ['folder' => 'options']);
        }

        $alreadyBlocked = BlockMessage::where('player_id', $player->id)
            ->where('block_player_id', $blockId)
            ->exists();

        if ($alreadyBlocked) {
            if ($request->expectsJson()) {
                return $this->jsonError("You're already blocking messages from player #{$blockId}");
            }
            session()->flash('game_message', "You're already blocking messages from player #{$blockId}");
        } else {
            BlockMessage::create([
                'player_id' => $player->id,
                'block_player_id' => $blockId,
            ]);
            if ($request->expectsJson()) {
                return $this->jsonSuccess($player, "You will no longer receive messages from {$targetPlayer->name} (#{$blockId})");
            }
            session()->flash('game_message', "You will no longer receive messages from {$targetPlayer->name} (#{$blockId})");
        }

        return redirect()->route('game.messages', ['folder' => 'options']);
    }

    /**
     * Unblock a player.
     * Route: POST /game/messages/unblock/{id}
     * Ported from eflag_player_messages.cfm eflag=unblock
     */
    public function unblock(Request $request, $id)
    {
        $player = player();

        BlockMessage::where('id', (int) $id)
            ->where('player_id', $player->id)
            ->delete();

        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, 'Player unblocked.');
        }

        return redirect()->route('game.messages', ['folder' => 'options']);
    }
}
