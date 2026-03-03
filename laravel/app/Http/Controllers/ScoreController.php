<?php

namespace App\Http\Controllers;

use App\Models\Alliance;
use App\Models\Player;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Score Controller
 *
 * Handles scores, rankings, battle scores, alliance scores, and recent battles.
 * Ported from scores.cfm, scores_show.cfm, rank.cfm, battle_scores.cfm, alliance_scores.cfm, recent_battles.cfm
 *
 * Original: Andrew Deren, (C) AderSoftware 2000, 2001
 */
class ScoreController extends Controller
{
    /**
     * Show the main scores page.
     * Ported from scores.cfm
     */
    public function index()
    {
        $player = Auth::user();
        $deathmatchMode = config('game.deathmatch_mode');
        $allianceMaxMembers = config('game.alliance_max_members');

        // Load alliance war/ally data
        $wars = [0, 0, 0, 0, 0];
        $allies = [0, 0, 0, 0, 0];
        $myAllianceID = -1;

        if ($player->alliance_id > 0) {
            $myAllianceID = $player->alliance_id;
            $alliance = Alliance::find($player->alliance_id);
            if ($alliance) {
                $wars = [
                    $alliance->war1 ?? 0, $alliance->war2 ?? 0, $alliance->war3 ?? 0,
                    $alliance->war4 ?? 0, $alliance->war5 ?? 0,
                ];
                $allies = [
                    $alliance->ally1 ?? 0, $alliance->ally2 ?? 0, $alliance->ally3 ?? 0,
                    $alliance->ally4 ?? 0, $alliance->ally5 ?? 0,
                ];
            }
        }

        // Get all players ordered by score
        $players = Player::leftJoin('alliances', 'players.alliance_id', '=', 'alliances.id')
            ->select([
                'players.id', 'players.name', 'players.civ', 'players.turn', 'players.score',
                DB::raw('(players.mland + players.fland + players.pland) as total_land'),
                'players.last_load', 'players.military_score', 'players.land_score',
                'players.good_score', 'players.alliance_id', 'players.killed_by', 'players.killed_by_name',
                'alliances.tag', 'alliances.leader_id',
                DB::raw('(players.research1 + players.research2 + players.research3 + players.research4 + players.research5 + players.research6 + players.research7 + players.research8 + players.research9 + players.research10 + players.research11 + players.research12) as research_levels'),
            ])
            ->orderBy('players.killed_by')
            ->orderBy('players.score', 'desc')
            ->orderBy('players.id')
            ->get();

        // Count online players (last load within 10 minutes)
        $onlineCount = Player::where('last_load', '>=', now()->subMinutes(10))->count();

        // Find current player rank
        $rank = 0;
        foreach ($players as $idx => $p) {
            if ($p->id === $player->id) {
                $rank = $idx + 1;
                break;
            }
        }

        // Calculate display ranges
        $isAdmin = $player->isAdmin();

        $empireNames = config('game.empires');

        return view('pages.scores', [
            'players' => $players,
            'onlineCount' => $onlineCount,
            'myAllianceID' => $myAllianceID,
            'wars' => $wars,
            'allies' => $allies,
            'rank' => $rank,
            'isAdmin' => $isAdmin,
            'empireNames' => $empireNames,
            'allianceMaxMembers' => $allianceMaxMembers,
        ]);
    }

    /**
     * Public rankings page (no auth required).
     * Ported from rank.cfm
     */
    public function publicRankings($type = 'top10')
    {
        $deathmatchMode = config('game.deathmatch_mode');
        $allianceMaxMembers = config('game.alliance_max_members');
        $empireNames = config('game.empires');

        if ($type === 'top10') {
            $players = Player::leftJoin('alliances', 'players.alliance_id', '=', 'alliances.id')
                ->select([
                    'players.id', 'players.name', 'players.civ', 'players.turn', 'players.score',
                    DB::raw('(players.mland + players.fland + players.pland) as total_land'),
                    'players.last_load', 'players.alliance_id', 'players.killed_by', 'players.killed_by_name',
                    'alliances.tag', 'alliances.leader_id',
                    DB::raw('(players.research1 + players.research2 + players.research3 + players.research4 + players.research5 + players.research6 + players.research7 + players.research8 + players.research9 + players.research10 + players.research11 + players.research12) as research_levels'),
                ])
                ->orderBy('players.killed_by')
                ->orderBy('players.score', 'desc')
                ->orderBy('players.id')
                ->get();

            $totalPlayers = $players->count();

            return view('pages.rankings', [
                'type' => $type,
                'players' => $players->take(10),
                'totalPlayers' => $totalPlayers,
                'empireNames' => $empireNames,
                'deathmatchMode' => $deathmatchMode,
                'allianceMaxMembers' => $allianceMaxMembers,
                'startDate' => Carbon::parse(config('game.start_date')),
                'endDate' => Carbon::parse(config('game.end_date')),
                'gameName' => config('game.name'),
            ]);
        }

        // Alliance rankings
        $orderMap = [
            'alliance_by_score' => 'total_score desc',
            'alliance_by_avgscore' => 'avg_score desc',
            'alliance_by_members' => 'members desc',
        ];

        $orderBy = $orderMap[$type] ?? 'total_score desc';

        // Build alliance scores from players table
        $alliances = DB::table('alliances')
            ->join('players', 'alliances.id', '=', 'players.alliance_id')
            ->select([
                'alliances.id',
                'alliances.tag',
                DB::raw('COUNT(players.id) as members'),
                DB::raw('AVG(players.score) as avg_score'),
                DB::raw('SUM(players.score) as total_score'),
            ])
            ->groupBy('alliances.id', 'alliances.tag')
            ->havingRaw('COUNT(players.id) >= 3')
            ->orderByRaw($orderBy)
            ->limit(10)
            ->get();

        return view('pages.rankings', [
            'type' => $type,
            'alliances' => $alliances,
            'gameName' => config('game.name'),
            'deathmatchMode' => $deathmatchMode,
            'allianceMaxMembers' => $allianceMaxMembers,
        ]);
    }

}
