<?php

namespace App\Http\Controllers;

use App\Models\ExploreQueue;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Explore Controller
 *
 * Ported from explore.cfm and eflag_explore.cfm
 * Original: Andrew Deren, (C) AderSoftware 2000, 2001
 */
class ExploreController extends Controller
{
    /**
     * Show explore page.
     * Ported from explore.cfm
     */
    public function index()
    {
        $player = Auth::user();
        $buildings = session('buildings');
        $constants = session('constants');

        // Get explore queue
        $explorations = ExploreQueue::where('player_id', $player->id)
            ->orderBy('id')
            ->get();

        // Count total active explorers (only those still exploring, turn > 0)
        $totalExplorers = $explorations->where('turn', '>', 0)->sum('people');

        // Calculate max explorers from town centers
        $maxExplorers = $player->town_center * $buildings[11]['max_explorers'];

        // Calculate food per explorer
        $totalLand = $player->mland + $player->fland + $player->pland;
        $extraFood = (int) ceil($totalLand / $constants['extra_food_per_land']);
        $foodPerExplorer = $buildings[11]['food_per_explorer'] + $extraFood;

        // Calculate how many food allows
        $sendExplorers = (int) floor($player->food / max(1, $foodPerExplorer));

        // Calculate how many can actually be sent
        $canSend = $maxExplorers - $totalExplorers;
        if ($canSend > $sendExplorers) {
            $canSend = $sendExplorers;
        }
        if ($canSend < 0) {
            $canSend = 0;
        }

        // Land type labels
        $landTypes = [
            0 => 'All',
            1 => 'Mountains',
            2 => 'Forest',
            3 => 'Plains',
        ];

        // Cancel time threshold (15 minutes ago)
        $cancelTime = Carbon::now()->subMinutes(15);

        // Last horse setting from session
        $lastHorseSetting = session('lastHorseSetting', 0);

        return view('pages.explore', [
            'explorations' => $explorations,
            'totalExplorers' => $totalExplorers,
            'maxExplorers' => $maxExplorers,
            'foodPerExplorer' => $foodPerExplorer,
            'sendExplorers' => $sendExplorers,
            'canSend' => $canSend,
            'landTypes' => $landTypes,
            'cancelTime' => $cancelTime,
            'lastHorseSetting' => $lastHorseSetting,
        ]);
    }

    /**
     * Handle explore POST actions (send or cancel).
     * Ported from eflag_explore.cfm
     */
    public function sendExplorers(Request $request)
    {
        $eflag = $request->input('eflag', 'send_explorers');

        if ($eflag === 'cancelExplore') {
            return $this->cancelExplore($request);
        }

        return $this->doSendExplorers($request);
    }

    /**
     * Send explorers.
     * Ported from eflag_explore.cfm eflag=send_explorers
     */
    protected function doSendExplorers(Request $request)
    {
        $player = Auth::user();
        $buildings = session('buildings');
        $constants = session('constants');

        $qty = (int) $request->input('qty', 0);
        $withHorses = (int) $request->input('withHorses', 0);
        $seekLand = (int) $request->input('seekLand', 0);

        // Remember horse setting in session
        session(['lastHorseSetting' => $withHorses]);

        // Calculate limits
        $maxExplorers = $player->town_center * $buildings[11]['max_explorers'];
        $totalLand = $player->mland + $player->fland + $player->pland;
        $extraFood = (int) ceil($totalLand / $constants['extra_food_per_land']);
        $foodPerExplorer = $buildings[11]['food_per_explorer'] + $extraFood;
        $exploreFood = $foodPerExplorer * $qty;

        // Validation
        if ($player->people <= $qty) {
            return back()->with('game_message', "You don't have that many people.");
        } elseif ($player->food < $exploreFood) {
            return back()->with('game_message', "You don't have that much food.");
        } elseif ($seekLand < 0 || $seekLand > 3) {
            return back()->with('game_message', 'Invalid Option');
        } elseif ($qty < 4) {
            return back()->with('game_message', 'You have to send at least 4 explorers.');
        } elseif ($withHorses === 1 && $player->horses < $qty) {
            return back()->with('game_message', "You do not have enough horses to send with your explorers (You need {$qty}).");
        } elseif ($withHorses === 2 && $player->horses < $qty * 2) {
            $need = $qty * 2;
            return back()->with('game_message', "You do not have enough horses to send with your explorers (You need {$need}).");
        } elseif ($withHorses === 3 && $player->horses < $qty * 3) {
            $need = $qty * 3;
            return back()->with('game_message', "You do not have enough horses to send with your explorers (You need {$need}).");
        }

        // Check total active explorers limit
        $currentExplorers = ExploreQueue::where('player_id', $player->id)
            ->where('turn', '>', 0)
            ->sum('people');

        if ($currentExplorers + $qty > $maxExplorers) {
            return back()->with('game_message', "You can only have a total of {$maxExplorers} explorers at a time.");
        }

        // Calculate horse usage and trip length
        $useHorses = 0;
        $tripLength = 6; // Base: 6 months

        if ($withHorses >= 1 && $withHorses <= 3) {
            $useHorses = $qty * $withHorses;
            $tripLength = $tripLength + ($withHorses * 2);
        }

        // Create exploration entry
        ExploreQueue::create([
            'player_id' => $player->id,
            'turn' => $tripLength,
            'people' => $qty,
            'food' => $exploreFood,
            'seek_land' => $seekLand,
            'horses' => $useHorses,
            'created_on' => now(),
            'turns_used' => 0,
        ]);

        // Deduct resources
        $player->update([
            'people' => DB::raw("people - {$qty}"),
            'food' => DB::raw("food - {$exploreFood}"),
            'horses' => DB::raw("horses - {$useHorses}"),
        ]);

        return redirect()->route('game.explore');
    }

    /**
     * Cancel an exploration mission.
     * Ported from eflag_explore.cfm eflag=cancelExplore
     */
    public function cancelExplore(Request $request)
    {
        $player = Auth::user();

        // The cancel is submitted via the send form with a cancel eflag,
        // or via a direct link. Accept eID from either source.
        $eId = (int) $request->input('eID', 0);

        $exploration = ExploreQueue::where('player_id', $player->id)
            ->where('id', $eId)
            ->first();

        if (!$exploration) {
            return redirect()->route('game.explore');
        }

        $cancelTime = Carbon::now()->subMinutes(15);

        if ($exploration->turns_used > 0 || $exploration->created_on->lt($cancelTime)) {
            return back()->with('game_message', 'You cannot cancel those explorers anymore.');
        }

        // Return resources
        $player->update([
            'food' => DB::raw("food + {$exploration->food}"),
            'horses' => DB::raw("horses + {$exploration->horses}"),
            'people' => DB::raw("people + {$exploration->people}"),
        ]);

        $exploration->delete();

        return redirect()->route('game.explore')->with('game_message', 'Your explorers have been cancelled.');
    }
}
