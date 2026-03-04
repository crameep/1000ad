<?php

namespace App\Http\Middleware;

use App\Models\Game;
use App\Models\Player;
use App\Services\GameDataService;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Game Session Middleware (GameContext)
 *
 * Resolves the active game and player, binds them to the container,
 * loads game data, calculates free turns, and shares player data with views.
 *
 * Replaces the original single-game middleware with multi-game support.
 */
class GameSession
{
    public function __construct(
        protected GameDataService $gameData
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // --- Resolve active game ---
        $gameId = session('active_game_id');

        if (!$gameId) {
            // No game selected — redirect to lobby
            return redirect()->route('lobby');
        }

        $game = Game::find($gameId);
        if (!$game || !$game->isPlayable()) {
            session()->forget('active_game_id');
            return redirect()->route('lobby')->with('error', 'That game is no longer available.');
        }

        // Bind game to the service container (used by BelongsToGame trait)
        app()->instance('current_game_id', $game->id);
        app()->instance('current_game', $game);

        // --- Resolve player for this user in this game ---
        $player = Player::withoutGlobalScope('game')
            ->where('user_id', $user->id)
            ->where('game_id', $game->id)
            ->first();

        if (!$player) {
            session()->forget('active_game_id');
            return redirect()->route('lobby')->with('error', 'You are not in this game.');
        }

        // Bind player to the service container (used by player() helper)
        app()->instance('current_player', $player);

        // --- Load game data based on civilization ---
        $buildings = $this->gameData->getBuildings($player->civ);
        $soldiers = $this->gameData->getSoldiers($player->civ);
        $constants = $this->gameData->getConstants($player->civ);

        // Store in session for use by services
        session([
            'buildings' => $buildings,
            'soldiers' => $soldiers,
            'constants' => $constants,
        ]);

        // --- Calculate free turns (using game-specific settings) ---
        $minutesPerTurn = $game->minutes_per_turn;
        $maxTurnsStored = $game->max_turns_stored;
        $playerTurns = $player->turns_free;
        $now = Carbon::now();

        // Determine the base date for turn calculation
        $deathmatchMode = $game->deathmatch_mode;
        if ($deathmatchMode) {
            $deathmatchStart = $game->deathmatch_start;
            if (!$player->last_turn) {
                $playerDate = $deathmatchStart;
            } elseif ($player->last_turn->lt($deathmatchStart)) {
                $playerDate = $deathmatchStart;
            } else {
                $playerDate = $player->last_turn;
            }
        } else {
            $playerDate = $player->last_turn ?? $now;
        }

        // Calculate new turns earned since last visit
        $minutes = $playerDate->diffInMinutes($now);
        $newTurns = intdiv((int) $minutes, $minutesPerTurn);

        if ($newTurns > 0) {
            $addMinutes = $newTurns * $minutesPerTurn;
            $newDate = $playerDate->copy()->addMinutes($addMinutes);
            $playerTurns += $newTurns;
            if ($playerTurns > $maxTurnsStored) {
                $playerTurns = $maxTurnsStored;
            }

            $player->update([
                'last_turn' => $newDate,
                'turns_free' => $playerTurns,
            ]);
            $player->refresh();

            // Re-bind the refreshed player
            app()->instance('current_player', $player);
        }

        // Calculate time until next turn
        $secondsSinceLastTurn = $player->last_turn
            ? $player->last_turn->diffInSeconds($now)
            : 0;
        $nextTurnSeconds = ($minutesPerTurn * 60) - $secondsSinceLastTurn;
        if ($nextTurnSeconds < 0) {
            $nextTurnSeconds = 0;
        }

        // Update last load timestamp
        $player->update(['last_load' => $now]);

        // --- Calculate used/free land for resource bar ---
        $usedM = ($player->iron_mine * $buildings[5]['sq'])
            + ($player->gold_mine * $buildings[6]['sq']);
        $usedF = ($player->hunter * $buildings[2]['sq'])
            + ($player->wood_cutter * $buildings[1]['sq']);
        $usedP = ($player->farmer * $buildings[3]['sq'])
            + ($player->house * $buildings[4]['sq'])
            + ($player->tool_maker * $buildings[7]['sq'])
            + ($player->weapon_smith * $buildings[8]['sq'])
            + ($player->fort * $buildings[9]['sq'])
            + ($player->tower * $buildings[10]['sq'])
            + ($player->town_center * $buildings[11]['sq'])
            + ($player->market * $buildings[12]['sq'])
            + ($player->warehouse * $buildings[13]['sq'])
            + ($player->stable * $buildings[14]['sq'])
            + ($player->mage_tower * $buildings[15]['sq'])
            + ($player->winery * $buildings[16]['sq']);

        $freeM = $player->mland - $usedM;
        $freeF = $player->fland - $usedF;
        $freeP = $player->pland - $usedP;

        // Calculate in-game date
        $month = ($player->turn % 12) + 1;
        $year = intdiv($player->turn, 12) + 1000;
        $gameDate = date('F', mktime(0, 0, 0, $month, 1)) . ' ' . $year;

        // Empire name (static — doesn't change per game)
        $empireName = config('game.empires')[$player->civ] ?? 'Unknown';

        // Share data with all views
        View::share([
            'player' => $player,
            'currentUser' => $user,
            'currentGame' => $game,
            'buildings' => $buildings,
            'soldiers' => $soldiers,
            'constants' => $constants,
            'playerTurns' => $playerTurns,
            'nextTurnSeconds' => $nextTurnSeconds,
            'freeM' => $freeM,
            'freeF' => $freeF,
            'freeP' => $freeP,
            'usedM' => $usedM,
            'usedF' => $usedF,
            'usedP' => $usedP,
            'gameDate' => $gameDate,
            'empireName' => $empireName,
            'deathmatchMode' => $deathmatchMode,
            'minutesPerTurn' => $minutesPerTurn,
            'maxTurnsStored' => $maxTurnsStored,
        ]);

        return $next($request);
    }
}
