<?php

namespace App\Http\Controllers;

use App\Http\Traits\ReturnsJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Manage Controller
 *
 * Handles empire management: weapon production, food rationing, and land conversion.
 * Ported from manage.cfm, eflag_manage.cfm
 *
 * Original: Andrew Deren, (C) AderSoftware 2000, 2001
 */
class ManageController extends Controller
{
    use ReturnsJson;
    /**
     * Show the empire management page.
     * Route: GET /game/manage
     * Ported from manage.cfm
     */
    public function index()
    {
        $player = Auth::user();
        $buildings = session('buildings');

        // Weapon production calculations
        $weaponSmithB = $buildings[8];
        $freeWeaponsmiths = $player->weapon_smith
            - $player->bow_weapon_smith
            - $player->sword_weapon_smith
            - $player->mace_weapon_smith;

        $woodUsed = $player->bow_weapon_smith * $weaponSmithB['wood_need']
            + $player->mace_weapon_smith * $weaponSmithB['mace_wood'];
        $ironUsed = $player->sword_weapon_smith * $weaponSmithB['iron_need']
            + $player->mace_weapon_smith * $weaponSmithB['mace_iron'];

        return view('pages.manage', [
            'freeWeaponsmiths' => $freeWeaponsmiths,
            'woodUsed' => $woodUsed,
            'ironUsed' => $ironUsed,
        ]);
    }

    /**
     * Change weapon production allocation.
     * Route: POST /game/manage/weapons
     * Ported from eflag_manage.cfm eflag=changeWeaponProduction
     */
    public function changeWeaponProduction(Request $request)
    {
        $player = Auth::user();

        $bowProduction = max(0, (int) $request->input('bowProduction', 0));
        $swordProduction = max(0, (int) $request->input('swordProduction', 0));
        $maceProduction = max(0, (int) $request->input('maceProduction', 0));

        if ($bowProduction < 0 || $swordProduction < 0 || $maceProduction < 0) {
            if ($request->expectsJson()) {
                return $this->jsonError('Cannot set negative production');
            }
            session()->flash('game_message', 'Cannot set negative production');
            return redirect()->route('game.manage');
        }

        if ($bowProduction + $swordProduction + $maceProduction > $player->weapon_smith) {
            if ($request->expectsJson()) {
                return $this->jsonError("You can have a maximum of {$player->weapon_smith} units produced.");
            }
            session()->flash('game_message', "You can have a maximum of {$player->weapon_smith} units produced.");
            return redirect()->route('game.manage');
        }

        $player->update([
            'bow_weapon_smith' => $bowProduction,
            'sword_weapon_smith' => $swordProduction,
            'mace_weapon_smith' => $maceProduction,
        ]);

        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, 'Weapon production updated.');
        }

        return redirect()->route('game.manage');
    }

    /**
     * Change food rationing level.
     * Route: POST /game/manage/food-ratio
     * Ported from eflag_manage.cfm eflag=changeFoodRatio
     */
    public function changeFoodRatio(Request $request)
    {
        $player = Auth::user();

        $foodRatio = (int) $request->input('foodRatio', 0);

        if ($foodRatio >= -3 && $foodRatio <= 3) {
            $player->update(['food_ratio' => $foodRatio]);
        }

        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, 'Food ratio updated.');
        }

        return redirect()->route('game.manage');
    }

    /**
     * Convert land types (mountain -> forest, forest -> plains).
     * Route: POST /game/manage/land
     * Ported from eflag_manage.cfm eflag=changeLand
     */
    public function changeLand(Request $request)
    {
        $player = Auth::user();
        $buildings = session('buildings');
        $messages = '';

        // Mountain to Forest conversion
        $mLandChange = abs((int) $request->input('mLandChange', 0));
        if ($mLandChange > 0) {
            // Calculate free mountain land
            $usedM = $player->iron_mine * $buildings[5]['sq']
                + $player->gold_mine * $buildings[6]['sq'];
            $freeM = $player->mland - $usedM;

            $needGold = $mLandChange * 100;

            if ($mLandChange > $freeM) {
                $messages .= 'You do not have that much free mountain land.<br>';
            } elseif ($needGold > $player->gold) {
                $messages .= "You do not have that much gold (need {$needGold})<br>";
            } else {
                $player->update([
                    'mland' => $player->mland - $mLandChange,
                    'fland' => $player->fland + $mLandChange,
                    'gold' => $player->gold - $needGold,
                ]);
                $player->refresh();
            }
        }

        // Forest to Plains conversion
        $fLandChange = abs((int) $request->input('fLandChange', 0));
        if ($fLandChange > 0) {
            // Calculate free forest land
            $usedF = $player->hunter * $buildings[2]['sq']
                + $player->wood_cutter * $buildings[1]['sq'];
            $freeF = $player->fland - $usedF;

            $needGold = $fLandChange * 25;

            if ($fLandChange > $freeF) {
                $messages .= 'You do not have that much free forest land.<br>';
            } elseif ($needGold > $player->gold) {
                $messages .= "You do not have that much gold (need {$needGold})<br>";
            } else {
                $player->update([
                    'fland' => $player->fland - $fLandChange,
                    'pland' => $player->pland + $fLandChange,
                    'gold' => $player->gold - $needGold,
                ]);
            }
        }

        if ($messages) {
            if ($request->expectsJson()) {
                return $this->jsonError($messages);
            }
            session()->flash('game_message', $messages);
        }

        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, 'Land conversion completed.');
        }

        return redirect()->route('game.manage');
    }
}
