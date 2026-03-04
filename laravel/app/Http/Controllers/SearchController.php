<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Search Controller
 *
 * Handles player search functionality.
 * Ported from search.cfm
 *
 * Original: Andrew Deren, (C) AderSoftware 2000, 2001
 */
class SearchController extends Controller
{
    /**
     * Show the search form.
     * Ported from search.cfm
     */
    public function index(Request $request)
    {
        return view('pages.search', [
            'searchType' => '',
            'searchString' => '',
            'results' => null,
        ]);
    }

    /**
     * Perform player search.
     * Ported from search.cfm
     */
    public function search(Request $request)
    {
        $searchType = $request->input('searchType', '');
        $searchString = $request->input('searchString', '');
        $empireNames = gameConfig('empires');

        $results = null;

        if ($searchType !== '') {
            $query = Player::leftJoin('alliances', 'players.alliance_id', '=', 'alliances.id')
                ->select([
                    'players.id', 'players.name', 'players.civ', 'players.score',
                    DB::raw('(players.pland + players.mland + players.fland) as total_land'),
                    'players.last_load',
                    'alliances.tag', 'alliances.leader_id',
                ]);

            switch ($searchType) {
                case 'playerNumber':
                    $query->where('players.id', (int) $searchString);
                    break;
                case 'playerName':
                    $query->where('players.name', 'like', '%' . $searchString . '%');
                    break;
                case 'allianceName':
                    $query->where('alliances.tag', 'like', '%' . $searchString . '%');
                    break;
                case 'online':
                    $query->where('players.last_load', '>', now()->subMinutes(10));
                    break;
            }

            $results = $query->orderBy('players.score', 'desc')
                ->limit(100)
                ->get();

            // Add rank for each result
            foreach ($results as $member) {
                $member->rank = Player::where('score', '>', $member->score)->count() + 1;
                $member->is_online = $member->last_load && abs(now()->diffInMinutes($member->last_load)) < 10;
            }
        }

        return view('pages.search', [
            'searchType' => $searchType,
            'searchString' => $searchString,
            'results' => $results,
            'empireNames' => $empireNames ?? gameConfig('empires'),
        ]);
    }
}
