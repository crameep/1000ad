<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Player;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Admin Game Management Controller
 *
 * CRUD operations for games: create, edit, duplicate, end.
 */
class GameManagementController extends Controller
{
    public function index()
    {
        $games = Game::orderBy('created_at', 'desc')->get()->map(function ($game) {
            $game->player_count = Player::withoutGlobalScope('game')
                ->where('game_id', $game->id)
                ->where('killed_by', 0)
                ->count();
            return $game;
        });

        return view('admin.games.index', ['games' => $games]);
    }

    public function create()
    {
        $presets = config('game.presets');
        return view('admin.games.create', ['presets' => $presets]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'preset' => 'required|string|in:standard,blitz,tournament,custom',
            'minutes_per_turn' => 'required|integer|min:1|max:60',
            'max_turns_stored' => 'required|integer|min:10|max:9999',
            'start_turns' => 'required|integer|min:0|max:9999',
            'max_attacks' => 'required|integer|min:1|max:50',
            'max_builds' => 'required|integer|min:1|max:200',
            'alliance_max_members' => 'required|integer|min:0|max:50',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string|max:500',
        ]);

        $slug = Str::slug($request->name);
        $originalSlug = $slug;
        $counter = 1;
        while (Game::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        $game = Game::create([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'preset' => $request->preset,
            'status' => 'active',
            'minutes_per_turn' => $request->minutes_per_turn,
            'max_turns_stored' => $request->max_turns_stored,
            'start_turns' => $request->start_turns,
            'max_attacks' => $request->max_attacks,
            'max_builds' => $request->max_builds,
            'alliance_max_members' => $request->alliance_max_members,
            'start_date' => $request->start_date ? Carbon::parse($request->start_date) : now(),
            'end_date' => $request->end_date ? Carbon::parse($request->end_date) : null,
            'deathmatch_mode' => $request->boolean('deathmatch_mode'),
            'deathmatch_start' => $request->deathmatch_start ? Carbon::parse($request->deathmatch_start) : null,
            'settings' => [
                'trade_prices' => config('game.trade_prices'),
                'local_prices' => config('game.local_prices'),
                'wall' => config('game.wall'),
                'new_player' => config('game.new_player'),
                'local_trade_multiplier' => config('game.local_trade_multiplier'),
                'people_eat_one_food' => config('game.people_eat_one_food'),
                'soldiers_eat_one_food' => config('game.soldiers_eat_one_food'),
                'extra_food_per_land' => config('game.extra_food_per_land'),
                'people_burn_one_wood' => config('game.people_burn_one_wood'),
            ],
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.games.index')
            ->with('success', "Game '{$game->name}' created successfully.");
    }

    public function show(Game $game)
    {
        return redirect()->route('admin.games.edit', $game);
    }

    public function edit(Game $game)
    {
        $playerCount = Player::withoutGlobalScope('game')
            ->where('game_id', $game->id)
            ->where('killed_by', 0)
            ->count();

        $presets = config('game.presets');

        return view('admin.games.edit', [
            'game' => $game,
            'playerCount' => $playerCount,
            'presets' => $presets,
        ]);
    }

    public function update(Request $request, Game $game)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'status' => 'required|string|in:setup,active,paused,ended',
            'minutes_per_turn' => 'required|integer|min:1|max:60',
            'max_turns_stored' => 'required|integer|min:10|max:9999',
            'start_turns' => 'required|integer|min:0|max:9999',
            'max_attacks' => 'required|integer|min:1|max:50',
            'max_builds' => 'required|integer|min:1|max:200',
            'alliance_max_members' => 'required|integer|min:0|max:50',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'description' => 'nullable|string|max:500',
        ]);

        $game->update([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
            'minutes_per_turn' => $request->minutes_per_turn,
            'max_turns_stored' => $request->max_turns_stored,
            'start_turns' => $request->start_turns,
            'max_attacks' => $request->max_attacks,
            'max_builds' => $request->max_builds,
            'alliance_max_members' => $request->alliance_max_members,
            'start_date' => $request->start_date ? Carbon::parse($request->start_date) : $game->start_date,
            'end_date' => $request->end_date ? Carbon::parse($request->end_date) : null,
            'deathmatch_mode' => $request->boolean('deathmatch_mode'),
            'deathmatch_start' => $request->deathmatch_start ? Carbon::parse($request->deathmatch_start) : null,
        ]);

        return redirect()->route('admin.games.edit', $game)
            ->with('success', "Game '{$game->name}' updated successfully.");
    }

    public function destroy(Game $game)
    {
        // Soft end — don't actually delete, just mark as ended
        $game->update(['status' => 'ended']);

        return redirect()->route('admin.games.index')
            ->with('success', "Game '{$game->name}' has been ended.");
    }

    /**
     * Duplicate a game with new dates.
     */
    public function duplicate(Game $game)
    {
        $slug = Str::slug($game->name . ' copy');
        $originalSlug = $slug;
        $counter = 1;
        while (Game::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        $newGame = $game->replicate();
        $newGame->name = $game->name . ' (Copy)';
        $newGame->slug = $slug;
        $newGame->status = 'setup';
        $newGame->start_date = now();
        $newGame->end_date = now()->addMonths(3);
        $newGame->created_by = auth()->id();
        $newGame->save();

        return redirect()->route('admin.games.edit', $newGame)
            ->with('success', "Game duplicated. Edit the settings and set status to 'Active' when ready.");
    }
}
