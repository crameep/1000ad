<?php

namespace App\Http\Controllers;

use App\Http\Traits\ReturnsJson;
use App\Models\PlayerMessage;
use App\Services\GameAdvisorService;
use App\Services\TurnService;
use Illuminate\Http\Request;

/**
 * Game Controller
 *
 * Handles the main game page, news management, and turn processing.
 * Ported from main.cfm, eflag_main.cfm, eflag_endturn.cfm
 *
 * Original: Andrew Deren, (C) AderSoftware 2000, 2001
 */
class GameController extends Controller
{
    use ReturnsJson;

    protected TurnService $turnService;
    protected GameAdvisorService $advisorService;

    public function __construct(TurnService $turnService, GameAdvisorService $advisorService)
    {
        $this->turnService = $turnService;
        $this->advisorService = $advisorService;
    }

    /**
     * Index route - redirect to main page.
     */
    public function index()
    {
        return redirect()->route('game.main');
    }

    /**
     * Show the main game page.
     * Ported from main.cfm
     */
    public function main()
    {
        $player = player();

        // Clear hasMainNews flag
        $player->update(['has_main_news' => false]);

        // Get attack news (messageType=1)
        $news = PlayerMessage::where('message_type', 1)
            ->where('to_player_id', $player->id)
            ->orderBy('created_on', 'desc')
            ->get();

        // Get advisor tips
        $advisorTips = $this->advisorService->getTips($player);

        return view('pages.main', compact('news', 'advisorTips'));
    }

    /**
     * Delete a single news message.
     * Ported from eflag_main.cfm eflag=delete_news
     */
    public function deleteNews($id)
    {
        $player = player();

        PlayerMessage::where('id', $id)
            ->where('to_player_id', $player->id)
            ->update(['message_type' => 3]);

        return redirect()->route('game.main');
    }

    /**
     * Delete all attack news messages.
     * Ported from eflag_main.cfm eflag=delete_allnews
     */
    public function deleteAllNews()
    {
        $player = player();

        PlayerMessage::where('to_player_id', $player->id)
            ->where('message_type', 1)
            ->update(['message_type' => 3]);

        return redirect()->route('game.main');
    }

    /**
     * End a single turn.
     * Ported from eflag_endturn.cfm eflag=end_turn
     */
    public function endTurn(Request $request)
    {
        $player = player();

        if ($player->killed_by > 0) {
            if ($request->expectsJson()) {
                return $this->jsonError('Sorry, but you\'re already dead.');
            }
            session()->flash('game_message', 'Sorry, but you\'re already dead.');
            return redirect()->route('game.main');
        }

        if ($player->turns_free > 0) {
            $message = $this->turnService->processTurn($player);
            if ($message) {
                session()->flash('game_message', $message);
            }
        } else {
            $minutesPerTurn = gameConfig('minutes_per_turn');
            $noTurnsMessage = "You do not have any months remaining (1 free month every {$minutesPerTurn} minutes)";
            if ($request->expectsJson()) {
                return $this->jsonError($noTurnsMessage);
            }
            session()->flash('game_message', $noTurnsMessage);
        }

        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, $message ?? 'Turn processed.');
        }

        return redirect()->route('game.main');
    }

    /**
     * End multiple turns at once.
     * Ported from eflag_endturn.cfm eflag=end_x_turns
     */
    public function endMultipleTurns(Request $request)
    {
        $player = player();

        if ($player->killed_by > 0) {
            if ($request->expectsJson()) {
                return $this->jsonError('Sorry, but you\'re already dead.');
            }
            session()->flash('game_message', 'Sorry, but you\'re already dead.');
            return redirect()->route('game.main');
        }

        $request->validate([
            'turns' => 'required|integer|min:1|max:12',
        ]);

        $qty = min((int) $request->turns, 12);

        if ($qty <= 0) {
            if ($request->expectsJson()) {
                return $this->jsonError('Cannot end less than 0 turns.');
            }
            session()->flash('game_message', 'Cannot end less than 0 turns.');
            return redirect()->route('game.main');
        }

        $messages = '';

        for ($i = 0; $i < $qty; $i++) {
            $player->refresh();

            if ($player->turns_free <= 0) {
                $messages .= 'No more turns left...';
                break;
            }

            $turnMessage = $this->turnService->processTurn($player);
            if ($turnMessage) {
                $messages .= $turnMessage;
            }
        }

        if (!empty($messages)) {
            session()->flash('game_message', $messages);
        }

        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, $messages ?: 'Turns processed.');
        }

        return redirect()->route('game.main');
    }
}
