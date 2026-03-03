<?php

namespace App\Http\Controllers;

use App\Models\Alliance;
use App\Models\AttackNews;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Battle Controller
 *
 * Handles recent battles search/display, battle scores, and alliance scores.
 * Ported from recent_battles.cfm, battle_scores.cfm, alliance_scores.cfm
 *
 * Original: Andrew Deren, (C) AderSoftware 2000, 2001
 */
class BattleController extends Controller
{
    /**
     * Show recent battles page with search form.
     * Route: GET /game/recent-battles
     * Ported from recent_battles.cfm
     */
    public function index(Request $request)
    {
        $player = Auth::user();
        $deathmatchMode = config('game.deathmatch_mode');

        if ($deathmatchMode) {
            session()->flash('game_message', 'Cannot view this page in deathmatch game.');
            return redirect()->route('game.main');
        }

        $alliances = Alliance::orderBy('tag')->get(['id', 'tag']);

        // Handle search via GET (form submits with pageFlag=view_battles)
        if ($request->input('pageFlag') === 'view_battles') {
            return $this->search($request);
        }

        return view('pages.recent-battles', [
            'pageFlag' => '',
            'battles' => collect(),
            'battleDetail' => null,
            'alliances' => $alliances,
        ]);
    }

    /**
     * Search recent battles.
     * Route: POST /game/recent-battles
     * Ported from recent_battles.cfm pageFlag=view_battles
     */
    public function search(Request $request)
    {
        $player = Auth::user();
        $deathmatchMode = config('game.deathmatch_mode');

        if ($deathmatchMode) {
            session()->flash('game_message', 'Cannot view this page in deathmatch game.');
            return redirect()->route('game.main');
        }

        $alliances = Alliance::orderBy('tag')->get(['id', 'tag']);

        $numHours = (int) $request->input('numHours', 24);
        $empireNo = (int) $request->input('viewPlayer', 0);
        $searchType = $request->input('searchType', 'empireNo');
        $defenderOrAttacker = (int) $request->input('defenderOrAttacker', 2);
        $attackType = (int) $request->input('attackType', 0);
        $allianceName = $request->input('allianceName', '');
        $battles = collect();

        if ($numHours <= 0) {
            session()->flash('game_message', 'Invalid number of hours.');
        } elseif ($searchType === 'empireNo' && ($empireNo <= 0 || $empireNo > 1000000)) {
            session()->flash('game_message', 'Invalid empire #');
        } else {
            $dateStart = now()->subHours($numHours);

            $query = AttackNews::leftJoin('players as attacker', 'attack_news.attack_id', '=', 'attacker.id')
                ->leftJoin('players as defender', 'attack_news.defense_id', '=', 'defender.id')
                ->select([
                    'attack_news.*',
                    'attacker.name as attacker_name',
                    'defender.name as defender_name',
                ])
                ->where('attack_news.created_on', '>', $dateStart);

            if ($searchType === 'empireNo') {
                if ($defenderOrAttacker === 0) {
                    $query->where('attack_news.defense_id', $empireNo);
                } elseif ($defenderOrAttacker === 1) {
                    $query->where('attack_news.attack_id', $empireNo);
                } else {
                    $query->where(function ($q) use ($empireNo) {
                        $q->where('attack_news.attack_id', $empireNo)
                          ->orWhere('attack_news.defense_id', $empireNo);
                    });
                }
            } else {
                // Alliance search
                if ($allianceName && $allianceName !== '___ANY___') {
                    if ($defenderOrAttacker === 0) {
                        $query->where('attack_news.defense_alliance', $allianceName);
                    } elseif ($defenderOrAttacker === 1) {
                        $query->where('attack_news.attack_alliance', $allianceName);
                    } else {
                        $query->where(function ($q) use ($allianceName) {
                            $q->where('attack_news.defense_alliance', $allianceName)
                              ->orWhere('attack_news.attack_alliance', $allianceName);
                        });
                    }
                }
            }

            if ($attackType > 0) {
                $query->where('attack_news.attack_type', $attackType);
            }

            $battles = $query->orderBy('attack_news.id', 'desc')->get();
        }

        return view('pages.recent-battles', [
            'pageFlag' => 'view_battles',
            'battles' => $battles,
            'battleDetail' => null,
            'alliances' => $alliances,
        ]);
    }

    /**
     * View battle detail.
     * Route: GET /game/recent-battles/{id}
     * Ported from recent_battles.cfm pageFlag=viewDetail
     */
    public function viewDetail($id)
    {
        $player = Auth::user();
        $deathmatchMode = config('game.deathmatch_mode');

        if ($deathmatchMode) {
            session()->flash('game_message', 'Cannot view this page in deathmatch game.');
            return redirect()->route('game.main');
        }

        $alliances = Alliance::orderBy('tag')->get(['id', 'tag']);

        $battleDetail = AttackNews::leftJoin('players as attacker', 'attack_news.attack_id', '=', 'attacker.id')
            ->leftJoin('players as defender', 'attack_news.defense_id', '=', 'defender.id')
            ->select([
                'attack_news.*',
                'attacker.name as attacker_name',
                'defender.name as defender_name',
            ])
            ->where('attack_news.id', (int) $id)
            ->first();

        // Check if player can see details
        $showDetail = false;
        if ($battleDetail) {
            if ($battleDetail->attack_id === $player->id || $battleDetail->defense_id === $player->id) {
                $showDetail = true;
            } elseif ($player->alliance_id > 0 && $player->alliance_member_type == 1) {
                if ($battleDetail->attack_alliance_id == $player->alliance_id
                    || $battleDetail->defense_alliance_id == $player->alliance_id) {
                    $showDetail = true;
                }
            }
            $battleDetail->show_detail = $showDetail;
        }

        return view('pages.recent-battles', [
            'pageFlag' => 'viewDetail',
            'battles' => collect(),
            'battleDetail' => $battleDetail,
            'alliances' => $alliances,
        ]);
    }

    /**
     * Show battle scores.
     * Route: GET /game/battle-scores
     * Ported from battle_scores.cfm
     */
    public function battleScores(Request $request)
    {
        $player = Auth::user();
        $ostring = $request->input('ostring', 'total_battles');

        // Validate sort column
        $validSorts = [
            'total_battles', 'total_wins', 'num_attacks',
            'num_attack_wins', 'num_defenses', 'num_defense_wins',
        ];
        if (!in_array($ostring, $validSorts)) {
            $ostring = 'total_battles';
        }

        // Build order by clause
        $orderMap = [
            'total_battles' => DB::raw('(num_attacks + num_defenses)'),
            'total_wins' => DB::raw('(num_attack_wins + num_defense_wins)'),
            'num_attacks' => 'num_attacks',
            'num_attack_wins' => 'num_attack_wins',
            'num_defenses' => 'num_defenses',
            'num_defense_wins' => 'num_defense_wins',
        ];

        // Query battle_scores view/table (top 25)
        $topPlayers = DB::table('battle_scores')
            ->orderByDesc($orderMap[$ostring])
            ->limit(25)
            ->get();

        // Get current player's battle stats
        $myStats = DB::table('battle_scores')
            ->where('id', $player->id)
            ->first();

        return view('pages.battle-scores', [
            'topPlayers' => $topPlayers,
            'myStats' => $myStats,
            'ostring' => $ostring,
        ]);
    }

    /**
     * Show alliance scores.
     * Route: GET /game/alliance-scores
     * Ported from alliance_scores.cfm
     */
    public function allianceScores(Request $request)
    {
        $player = Auth::user();
        $orderString = $request->input('orderString', 'total_score');

        // Validate sort column
        $validSorts = ['tag', 'members', 'avg_score', 'total_score'];
        if (!in_array($orderString, $validSorts)) {
            $orderString = 'total_score';
        }

        // Build alliance scores from players table
        $query = DB::table('alliances')
            ->join('players', 'alliances.id', '=', 'players.alliance_id')
            ->select([
                'alliances.id',
                'alliances.tag',
                DB::raw('COUNT(players.id) as members'),
                DB::raw('ROUND(AVG(players.score)) as avg_score'),
                DB::raw('SUM(players.score) as total_score'),
            ])
            ->groupBy('alliances.id', 'alliances.tag')
            ->havingRaw('COUNT(players.id) >= 3');

        if ($orderString === 'tag') {
            $query->orderBy('alliances.tag');
        } else {
            $query->orderByDesc($orderString);
        }

        $alliances = $query->get();

        // Total alliance count
        $totalAlliances = Alliance::count();

        // Players in alliances
        $playersInAlliances = Player::where('alliance_id', '>', 0)->count();
        $totalPlayers = Player::count();

        return view('pages.alliance-scores', [
            'alliances' => $alliances,
            'orderString' => $orderString,
            'totalAlliances' => $totalAlliances,
            'playersInAlliances' => $playersInAlliances,
            'totalPlayers' => $totalPlayers,
        ]);
    }
}
