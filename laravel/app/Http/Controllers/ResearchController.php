<?php

namespace App\Http\Controllers;

use App\Http\Traits\ReturnsJson;
use App\Services\GameDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Research Controller
 *
 * Ported from research.cfm and eflag_research.cfm
 * Original: Andrew Deren, (C) AderSoftware 2000, 2001
 */
class ResearchController extends Controller
{
    use ReturnsJson;
    /**
     * Show research page.
     * Ported from research.cfm
     */
    public function index(GameDataService $gameData)
    {
        $player = Auth::user();
        $buildings = session('buildings');

        // Research names
        $researchNames = $gameData->getResearchNames();

        // Calculate total research levels
        $totalResearchLevels = 0;
        for ($i = 1; $i <= 12; $i++) {
            $totalResearchLevels += $player->{"research{$i}"};
        }

        // Calculate points needed for next level
        // Original CF: 10 + round(totalresearchLevels*totalresearchLevels*sqr(totalresearchlevels))
        // CF sqr() = square root
        $nextLevelPoints = 10 + round(
            $totalResearchLevels * $totalResearchLevels * sqrt($totalResearchLevels)
        );

        // Mage tower info
        $hasMageTowers = $player->mage_tower > 0;
        $activeMageTowers = 0;
        $researchProduced = 0;
        $goldCost = 0;
        $turnsToNextLevel = 0;
        $percent = 0;

        if ($hasMageTowers) {
            $activeMageTowers = round(($player->mage_tower_status / 100) * $player->mage_tower);
            $researchProduced = round($activeMageTowers * $buildings[15]['production']);
            $goldCost = round($activeMageTowers * $buildings[15]['gold_need']);

            if ($player->current_research > 0 && $nextLevelPoints > 0) {
                $percent = ($player->research_points / $nextLevelPoints) * 100;
            }

            if ($researchProduced > 0 && $nextLevelPoints > 0) {
                $turnsToNextLevel = $nextLevelPoints / $researchProduced;
            }
        }

        // Build research data grouped by category
        // Military Research: 1 (Attack), 2 (Defense), 3 (Thieves), 11 (Catapults), 4 (Losses)
        // Production Research: 5 (Food), 12 (Wood), 6 (Mine), 7 (Weapons/Tools)
        // Other Research: 8 (Space), 9 (Markets), 10 (Explorers)
        $researchGroups = [
            'Military Research' => [1, 2, 3, 11, 4],
            'Production Research' => [5, 12, 6, 7],
            'Other Research' => [8, 9, 10],
        ];

        // Research descriptions (dynamic based on current level)
        $researchDescriptions = [
            1 => "Your army attack points are increased by {$player->research1}%",
            2 => "Your army defense points are increased by {$player->research2}%",
            3 => "Your thieves are {$player->research3}% stronger",
            4 => "You lose {$player->research4}% less army in battles",
            5 => "Your food production is increased by {$player->research5}%",
            6 => "Your mine production is increased by {$player->research6}%",
            7 => "Your weaponsmiths and tool makers are {$player->research7}% more effective",
            8 => "Your storage and housing space is increased by {$player->research8}%",
            9 => "You can transfer/aid " . ($player->research9 * 10) . "% more goods",
            10 => "Your explorers find {$player->research10}% more land",
            11 => "Your catapults are {$player->research11}% stronger",
            12 => "Your wood production is increased by {$player->research12}%",
        ];

        return view('pages.research', [
            'researchNames' => $researchNames,
            'researchGroups' => $researchGroups,
            'researchDescriptions' => $researchDescriptions,
            'totalResearchLevels' => $totalResearchLevels,
            'nextLevelPoints' => $nextLevelPoints,
            'hasMageTowers' => $hasMageTowers,
            'activeMageTowers' => $activeMageTowers,
            'researchProduced' => $researchProduced,
            'goldCost' => $goldCost,
            'turnsToNextLevel' => $turnsToNextLevel,
            'percent' => $percent,
        ]);
    }

    /**
     * Change current research focus.
     * Ported from eflag_research.cfm eflag=changeresearch
     */
    public function setResearch(Request $request)
    {
        $player = Auth::user();

        $newResearch = (int) $request->input('newCurrentResearch', 0);

        if ($newResearch >= 0 && $newResearch <= 12) {
            $player->update([
                'current_research' => $newResearch,
            ]);
        }

        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, 'Research focus updated.');
        }

        return redirect()->route('game.research');
    }
}
