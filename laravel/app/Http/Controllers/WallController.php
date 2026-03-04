<?php

namespace App\Http\Controllers;

use App\Http\Traits\ReturnsJson;
use Illuminate\Http\Request;

/**
 * Wall Controller
 *
 * Handles wall construction management.
 * Ported from wall.cfm, eflag_wall.cfm
 *
 * Original: Andrew Deren, (C) AderSoftware 2000, 2001
 */
class WallController extends Controller
{
    use ReturnsJson;
    /**
     * Show the wall management page.
     * Ported from wall.cfm
     */
    public function index()
    {
        $player = player();
        $buildings = session('buildings');

        $totalLand = $player->mland + $player->fland + $player->pland;
        $totalWall = round($totalLand * 0.05);

        $protection = 0;
        if ($totalWall > 0 && $totalLand > 0) {
            $protection = round(($player->wall / $totalWall) * 100);
        }

        $needWall = $totalWall - $player->wall;

        // Builder calculations
        $toolMakerB = $buildings[7];
        $builders = $toolMakerB['num_builders'] * $player->tool_maker + 3;
        $bPercent = $player->wall_build_per_turn / 100;
        $wallBuilders = round($builders * $bPercent);
        $wallBuild = intdiv($wallBuilders, 10);

        // Wall costs from config
        $wallCosts = gameConfig('wall');

        return view('pages.wall', [
            'totalLand' => $totalLand,
            'totalWall' => $totalWall,
            'protection' => $protection,
            'needWall' => $needWall,
            'builders' => $builders,
            'wallBuilders' => $wallBuilders,
            'wallBuild' => $wallBuild,
            'wallCosts' => $wallCosts,
        ]);
    }

    /**
     * Update wall build percentage.
     * Ported from eflag_wall.cfm eflag=updateWall
     */
    public function updateWall(Request $request)
    {
        $player = player();

        $wallBuildPerTurn = round((int) $request->input('wallBuildPerTurn', 0));

        if ($wallBuildPerTurn < 0 || $wallBuildPerTurn > 100) {
            if ($request->expectsJson()) {
                return $this->jsonError('Percentage of builders have to be between 0 and 100.');
            }
            session()->flash('game_message', 'Percentage of builders have to be between 0 and 100.');
            return redirect()->route('game.wall');
        }

        $player->update([
            'wall_build_per_turn' => $wallBuildPerTurn,
        ]);

        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, 'Wall build percentage updated.');
        }

        return redirect()->route('game.wall');
    }
}
