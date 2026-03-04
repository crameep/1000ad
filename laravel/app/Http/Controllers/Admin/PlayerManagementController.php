<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Admin Player Management Controller
 *
 * List users, view their game memberships, edit player state, grant turns.
 */
class PlayerManagementController extends Controller
{
    /**
     * List all users with their game memberships.
     */
    public function index()
    {
        $users = User::orderBy('login_name')->get()->map(function ($user) {
            $user->player_count = Player::withoutGlobalScope('game')
                ->where('user_id', $user->id)
                ->count();
            return $user;
        });

        return view('admin.players.index', ['users' => $users]);
    }

    /**
     * Show a user's details and all their player records.
     */
    public function show(User $user)
    {
        $players = Player::withoutGlobalScope('game')
            ->where('user_id', $user->id)
            ->with('game')
            ->get();

        return view('admin.players.show', [
            'user' => $user,
            'players' => $players,
        ]);
    }

    /**
     * Edit a specific player record (admin tools).
     */
    public function editPlayer(Player $player)
    {
        $player = Player::withoutGlobalScope('game')->findOrFail($player->id);
        $player->load('game');

        $empireName = config('game.empires')[$player->civ] ?? 'Unknown';

        return view('admin.players.edit', [
            'player' => $player,
            'empireName' => $empireName,
        ]);
    }

    /**
     * Update a player record (admin edit).
     */
    public function updatePlayer(Request $request, Player $player)
    {
        $player = Player::withoutGlobalScope('game')->findOrFail($player->id);

        $request->validate([
            'gold' => 'nullable|integer|min:0',
            'wood' => 'nullable|integer|min:0',
            'food' => 'nullable|integer|min:0',
            'iron' => 'nullable|integer|min:0',
            'tools' => 'nullable|integer|min:0',
            'people' => 'nullable|integer|min:0',
            'turns_free' => 'nullable|integer|min:0',
            'killed_by' => 'nullable|integer|min:0',
        ]);

        $fields = ['gold', 'wood', 'food', 'iron', 'tools', 'people', 'turns_free', 'killed_by'];
        $updates = [];
        foreach ($fields as $field) {
            if ($request->has($field) && $request->$field !== null) {
                $updates[$field] = $request->$field;
            }
        }

        if (!empty($updates)) {
            $player->update($updates);
        }

        return redirect()->route('admin.players.edit', $player)
            ->with('success', "Player '{$player->name}' updated successfully.");
    }

    /**
     * Grant bonus turns to a player.
     */
    public function grantTurns(Request $request, Player $player)
    {
        $player = Player::withoutGlobalScope('game')->findOrFail($player->id);

        $request->validate([
            'turns' => 'required|integer|min:1|max:9999',
        ]);

        $player->update([
            'turns_free' => $player->turns_free + $request->turns,
        ]);

        return redirect()->route('admin.players.edit', $player)
            ->with('success', "Granted {$request->turns} turns to '{$player->name}'.");
    }
}
