<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Player;
use App\Services\GameDataService;
use App\Services\ScoreService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Lobby Controller
 *
 * Handles the game lobby where users can see available games,
 * join new games, and switch between active games.
 */
class LobbyController extends Controller
{
    /**
     * Show the game lobby.
     */
    public function index()
    {
        $user = Auth::user();

        // Games the user is already playing (query without game scope)
        $myGameIds = Player::withoutGlobalScope('game')
            ->where('user_id', $user->id)
            ->where('killed_by', 0)
            ->pluck('game_id')
            ->toArray();

        $myGames = [];
        if (!empty($myGameIds)) {
            $games = Game::whereIn('id', $myGameIds)->get();
            foreach ($games as $game) {
                $playerInGame = Player::withoutGlobalScope('game')
                    ->where('user_id', $user->id)
                    ->where('game_id', $game->id)
                    ->first();

                $playerCount = Player::withoutGlobalScope('game')
                    ->where('game_id', $game->id)
                    ->where('killed_by', 0)
                    ->count();

                $empireName = config('game.empires')[$playerInGame->civ] ?? 'Unknown';

                $myGames[] = [
                    'game' => $game,
                    'player' => $playerInGame,
                    'empireName' => $empireName,
                    'playerCount' => $playerCount,
                ];
            }
        }

        // Available games to join
        $availableGames = Game::where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>', now());
            })
            ->whereNotIn('id', $myGameIds)
            ->get()
            ->map(function ($game) {
                $game->player_count = Player::withoutGlobalScope('game')
                    ->where('game_id', $game->id)
                    ->where('killed_by', 0)
                    ->count();
                return $game;
            });

        return view('pages.lobby', [
            'user' => $user,
            'myGames' => $myGames,
            'availableGames' => $availableGames,
            'empires' => config('game.empires'),
            'uniqueUnits' => config('game.unique_units'),
        ]);
    }

    /**
     * Join a game — show civ selection or process join.
     */
    public function join(Request $request, Game $game)
    {
        $user = Auth::user();

        // Check if already in this game
        $existing = Player::withoutGlobalScope('game')
            ->where('user_id', $user->id)
            ->where('game_id', $game->id)
            ->first();

        if ($existing) {
            session(['active_game_id' => $game->id]);
            return redirect()->route('game.main');
        }

        // Validate
        $request->validate([
            'empire_name' => ['required', 'string', 'max:20', 'regex:/^[a-zA-Z0-9 _]+$/'],
            'civ' => 'required|integer|between:1,6',
        ], [
            'empire_name.regex' => 'Empire name can only contain spaces and alpha-numeric characters.',
        ]);

        // Check empire name uniqueness within the game
        $nameExists = Player::withoutGlobalScope('game')
            ->where('game_id', $game->id)
            ->where('name', $request->empire_name)
            ->exists();

        if ($nameExists) {
            return back()->withInput()
                ->with('error', 'Empire with that name already exists in this game.');
        }

        // Calculate starting turns
        $startDate = $game->start_date ?? Carbon::now();
        $minutesPerTurn = $game->minutes_per_turn;
        $maxTurnsStored = $game->max_turns_stored;
        $startTurns = $game->start_turns;

        $extraMinutes = $startDate->diffInMinutes(now());
        $extraTurns = intdiv((int) $extraMinutes, $minutesPerTurn);
        $numTurns = min($startTurns + $extraTurns, $maxTurnsStored);

        // Use game-specific new player defaults, falling back to config
        $defaults = $game->setting('new_player') ?? config('game.new_player');

        $player = Player::withoutGlobalScope('game')->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'name' => $request->empire_name,
            'login_name' => $user->login_name,
            'password' => $user->password,
            'email' => $user->email,
            'civ' => $request->civ,
            'food_ratio' => $defaults['food_ratio'] ?? 1,

            // Buildings
            'tool_maker' => $defaults['tool_maker'] ?? 10,
            'wood_cutter' => $defaults['wood_cutter'] ?? 20,
            'gold_mine' => $defaults['gold_mine'] ?? 10,
            'hunter' => $defaults['hunter'] ?? 50,
            'tower' => $defaults['tower'] ?? 10,
            'town_center' => $defaults['town_center'] ?? 10,
            'market' => $defaults['market'] ?? 10,
            'iron_mine' => $defaults['iron_mine'] ?? 20,
            'house' => $defaults['house'] ?? 50,
            'farmer' => $defaults['farmer'] ?? 20,

            // Land
            'fland' => $defaults['fland'] ?? 1000,
            'mland' => $defaults['mland'] ?? 500,
            'pland' => $defaults['pland'] ?? 2500,

            // Military
            'swordsman' => $defaults['swordsman'] ?? 3,
            'archers' => $defaults['archers'] ?? 3,
            'horseman' => $defaults['horseman'] ?? 3,

            // Resources
            'people' => $defaults['people'] ?? 3000,
            'wood' => $defaults['wood'] ?? 1000,
            'food' => $defaults['food'] ?? 2500,
            'iron' => $defaults['iron'] ?? 1000,
            'gold' => $defaults['gold'] ?? 100000,
            'tools' => $defaults['tools'] ?? 250,

            // Building statuses (all 100% operational)
            'hunter_status' => 100,
            'farmer_status' => 100,
            'iron_mine_status' => 100,
            'gold_mine_status' => 100,
            'tool_maker_status' => 100,
            'weapon_smith_status' => 100,
            'stable_status' => 100,
            'wood_cutter_status' => 100,
            'mage_tower_status' => 100,
            'winery_status' => 100,

            // Turn tracking
            'turn' => 0,
            'last_turn' => now(),
            'turns_free' => $numTurns,
            'created_on' => now(),

            'message' => 'Welcome to ' . $game->name . '! View Help / Docs section for information on how to play.',
        ]);

        // Calculate initial score
        app(ScoreService::class)->calculateScore($player);

        // Set this as the active game
        session(['active_game_id' => $game->id]);

        return redirect()->route('game.main')
            ->with('success', "Welcome to {$game->name}! Your empire '{$request->empire_name}' has been created.");
    }

    /**
     * Switch to a different game.
     */
    public function switchGame(Game $game)
    {
        $user = Auth::user();

        // Verify user has a player in this game
        $player = Player::withoutGlobalScope('game')
            ->where('user_id', $user->id)
            ->where('game_id', $game->id)
            ->first();

        if (!$player) {
            return redirect()->route('lobby')
                ->with('error', 'You are not in this game.');
        }

        // Clear old game session data and switch
        session()->forget(['buildings', 'soldiers', 'constants']);
        session(['active_game_id' => $game->id]);

        return redirect()->route('game.main');
    }
}
