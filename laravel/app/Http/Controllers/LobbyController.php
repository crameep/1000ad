<?php

namespace App\Http\Controllers;

use App\Models\EmpireSlot;
use App\Models\Game;
use App\Models\Player;
use App\Models\PrizePayout;
use App\Models\Transaction;
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

        // Get ALL alive players for this user across all games
        $myPlayers = Player::withoutGlobalScope('game')
            ->where('user_id', $user->id)
            ->where('killed_by', 0)
            ->get();

        $myGameIds = $myPlayers->pluck('game_id')->unique()->toArray();

        $myGames = [];
        if (!empty($myGameIds)) {
            $games = Game::whereIn('id', $myGameIds)->get();
            foreach ($games as $game) {
                $playersInGame = $myPlayers->where('game_id', $game->id);

                $playerCount = Player::withoutGlobalScope('game')
                    ->where('game_id', $game->id)
                    ->where('killed_by', 0)
                    ->count();

                $maxAllowed = $game->setting('max_empires_per_user') ?? 1;
                $canCreateMore = EmpireSlot::canCreateEmpire($user->id, $game->id);

                $empires = [];
                foreach ($playersInGame as $p) {
                    $empires[] = [
                        'player' => $p,
                        'empireName' => config('game.empires')[$p->civ] ?? 'Unknown',
                    ];
                }

                $extraSlots = EmpireSlot::slotsFor($user->id, $game->id);

                $myGames[] = [
                    'game' => $game,
                    'player' => $playersInGame->first(), // backward compat
                    'empireName' => config('game.empires')[$playersInGame->first()->civ] ?? 'Unknown',
                    'playerCount' => $playerCount,
                    'empires' => $empires,
                    'canCreateMore' => $canCreateMore,
                    'slotsUsed' => $playersInGame->count(),
                    'slotsTotal' => min(1 + $extraSlots, $maxAllowed),
                    'maxAllowed' => $maxAllowed,
                ];
            }
        }

        // Revenue split: 25% game prize pool, 50% tournament pool, 25% server costs
        // Compute per-game revenue and prize pools
        $allGameIds = Game::pluck('id')->toArray();
        $revenueByGame = Transaction::selectRaw('game_id, SUM(amount_cents) as total')
            ->whereNotNull('game_id')
            ->groupBy('game_id')
            ->pluck('total', 'game_id')
            ->toArray();
        $totalRevenue = Transaction::sum('amount_cents');
        $tournamentPool = (int) round($totalRevenue * 0.50);

        foreach ($myGames as &$entry) {
            $gameRevenue = $revenueByGame[$entry['game']->id] ?? 0;
            $pool = (int) round($gameRevenue * 0.25);
            $entry['gameRevenue'] = $gameRevenue;
            $entry['prizePool'] = $pool;
            $entry['prizeSplit'] = $pool > 0 ? [
                ['place' => 1, 'amount' => (int) round($pool * 0.50)],
                ['place' => 2, 'amount' => (int) round($pool * 0.30)],
                ['place' => 3, 'amount' => (int) round($pool * 0.20)],
            ] : [];
        }
        unset($entry);

        // Available games to join: active games where user can still create empires
        $availableGames = Game::where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>', now());
            })
            ->get()
            ->filter(function ($game) use ($myGameIds, $user) {
                // Show if user is not in the game at all, OR can create more empires
                if (!in_array($game->id, $myGameIds)) {
                    return true;
                }
                return EmpireSlot::canCreateEmpire($user->id, $game->id);
            })
            ->map(function ($game) use ($revenueByGame) {
                $game->player_count = Player::withoutGlobalScope('game')
                    ->where('game_id', $game->id)
                    ->where('killed_by', 0)
                    ->count();
                $gameRevenue = $revenueByGame[$game->id] ?? 0;
                $pool = (int) round($gameRevenue * 0.25);
                $game->game_revenue = $gameRevenue;
                $game->prize_pool = $pool;
                $game->prize_split = $pool > 0 ? [
                    ['place' => 1, 'amount' => (int) round($pool * 0.50)],
                    ['place' => 2, 'amount' => (int) round($pool * 0.30)],
                    ['place' => 3, 'amount' => (int) round($pool * 0.20)],
                ] : [];
                return $game;
            });

        // User earnings from prize payouts
        $userPayouts = PrizePayout::where('user_id', $user->id)
            ->with('game')
            ->orderByDesc('created_at')
            ->get();
        $totalEarnings = $userPayouts->sum('amount_cents');

        return view('pages.lobby', [
            'user' => $user,
            'myGames' => $myGames,
            'availableGames' => $availableGames,
            'empires' => config('game.empires'),
            'uniqueUnits' => config('game.unique_units'),
            'civSummaries' => config('game.civ_summaries'),
            'userPayouts' => $userPayouts,
            'totalEarnings' => $totalEarnings,
            'totalRevenue' => $totalRevenue,
            'tournamentPool' => $tournamentPool,
        ]);
    }

    /**
     * Join a game — show civ selection or process join.
     */
    public function join(Request $request, Game $game)
    {
        $user = Auth::user();

        // Check if user can create another empire in this game
        if (!EmpireSlot::canCreateEmpire($user->id, $game->id)) {
            // Has empires but no more slots — switch to first existing empire
            $existing = Player::withoutGlobalScope('game')
                ->where('user_id', $user->id)
                ->where('game_id', $game->id)
                ->where('killed_by', 0)
                ->first();

            if ($existing) {
                session([
                    'active_game_id' => $game->id,
                    'active_player_id' => $existing->id,
                ]);
                return redirect()->route('game.main');
            }

            return redirect()->route('lobby')
                ->with('error', 'You cannot create more empires in this game.');
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

        // Set this as the active game and player
        session([
            'active_game_id' => $game->id,
            'active_player_id' => $player->id,
        ]);

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
            ->where('killed_by', 0)
            ->first();

        if (!$player) {
            return redirect()->route('lobby')
                ->with('error', 'You are not in this game.');
        }

        // Clear old game session data and switch
        session()->forget(['buildings', 'soldiers', 'constants']);
        session([
            'active_game_id' => $game->id,
            'active_player_id' => $player->id,
        ]);

        return redirect()->route('game.main');
    }

    /**
     * Switch to a different empire within the same game.
     */
    public function switchEmpire(Player $player)
    {
        $user = Auth::user();

        // Verify this player belongs to the current user and is alive
        if ($player->user_id !== $user->id || $player->killed_by !== 0) {
            return redirect()->route('lobby')
                ->with('error', 'Invalid empire.');
        }

        session()->forget(['buildings', 'soldiers', 'constants']);
        session([
            'active_game_id' => $player->game_id,
            'active_player_id' => $player->id,
        ]);

        return redirect()->route('game.main');
    }

}
