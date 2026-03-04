<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Player;
use App\Models\User;

/**
 * Admin Dashboard Controller
 *
 * Shows overview stats: total users, active games, player counts.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $totalGames = Game::count();
        $activeGames = Game::where('status', 'active')->count();

        $games = Game::orderBy('created_at', 'desc')->get()->map(function ($game) {
            $game->player_count = Player::withoutGlobalScope('game')
                ->where('game_id', $game->id)
                ->where('killed_by', 0)
                ->count();
            $game->total_players = Player::withoutGlobalScope('game')
                ->where('game_id', $game->id)
                ->count();
            return $game;
        });

        return view('admin.dashboard', [
            'totalUsers' => $totalUsers,
            'totalGames' => $totalGames,
            'activeGames' => $activeGames,
            'games' => $games,
        ]);
    }
}
