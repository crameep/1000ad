<?php

namespace App\Services;

use App\Models\Alliance;
use App\Models\AttackNews;
use App\Models\AttackQueue;
use App\Models\Player;
use App\Models\PlayerMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Combat Service
 *
 * Processes all three attack types from the original ColdFusion game:
 *   - Standard Attack / Land Siege (doAttack.cfm)  - attack_type 0-9
 *   - Raid Attack / Steal Resources (doAttack2.cfm) - attack_type 10-19
 *   - Spy/Thief Attack (doAttack3.cfm)             - attack_type 20-29
 *
 * Original: Andrew Deren, (C) AderSoftware 2000, 2001
 */
class CombatService
{
    /**
     * Unit types that participate in combat, mapped to their DB column names.
     * Indices match the soldier definition keys in GameDataService.
     */
    protected const UNIT_COLUMNS = [
        1 => 'archers',
        2 => 'swordsman',
        3 => 'horseman',
        5 => 'catapults',
        6 => 'macemen',
        7 => 'trained_peasants',
        8 => 'thieves',
        9 => 'uunit',
    ];

    /**
     * Resource columns that can be raided.
     */
    protected const RAID_RESOURCES = ['wood', 'food', 'iron', 'gold', 'tools'];

    public function __construct(
        protected GameDataService $gameData,
        protected ScoreService $scoreService,
    ) {}

    // -------------------------------------------------------------------------
    //  Main Entry Point
    // -------------------------------------------------------------------------

    /**
     * Process a single attack from the queue.
     *
     * This is the top-level dispatcher. It determines the attack category from
     * the attack_type field and delegates to the appropriate handler.
     *
     * @param AttackQueue $attack       The queued attack record.
     * @param Player      $attacker     The attacker's Player model (fresh from DB).
     * @param array       $attackerData Working copy of attacker data (modified in place).
     * @param string      $message      Running message log (appended to).
     */
    public function processAttack(
        AttackQueue $attack,
        Player $attacker,
        array &$attackerData,
        string &$message,
    ): void {
        $attackType = (int) $attack->attack_type;

        if ($attackType >= 0 && $attackType <= 9) {
            $this->processStandardAttack($attack, $attacker, $attackerData, $message);
        } elseif ($attackType >= 10 && $attackType <= 19) {
            $this->processRaidAttack($attack, $attacker, $attackerData, $message);
        } elseif ($attackType >= 20 && $attackType <= 29) {
            $this->processSpyAttack($attack, $attacker, $attackerData, $message);
        } else {
            $message .= "Unknown attack type {$attackType}. Attack discarded.\n";
        }
    }

    // =========================================================================
    //  TYPE 0-9 : Standard Attack / Land Siege  (doAttack.cfm)
    // =========================================================================

    protected function processStandardAttack(
        AttackQueue $attack,
        Player $attacker,
        array &$attackerData,
        string &$message,
    ): void {
        // 1. Load defender
        $defender = Player::find($attack->attack_player_id);
        if (!$defender || !$defender->isAlive()) {
            $this->returnTroopsHome($attack, $attackerData, $message, 'Defender is dead or not found.');
            return;
        }

        // 2. Diminishing returns: how many times attacked this defender in 24h
        $hasAttacks = $this->countRecentAttacks($attacker->id, $defender->id);
        $diminishingFactor = $this->getDiminishingFactor($hasAttacks);

        // Soldier definitions for both sides
        $attackerSoldiers = $this->gameData->getSoldiers($attacker->civ);
        $defenderSoldiers = $this->gameData->getSoldiers($defender->civ);

        // 3. Calculate attacker army strength
        $attackStrength = $this->calculateAttackStrength(
            $attack,
            $attackerSoldiers,
            $attacker,
            $defender,
        );

        // 4. Calculate defender army strength
        $defenseStrength = $this->calculateDefenseStrength(
            $defender,
            $defenderSoldiers,
        );

        // 5. Apply randomness (+-15%)
        $attackStrength = $this->applyRandomFactor($attackStrength);
        $defenseStrength = $this->applyRandomFactor($defenseStrength);

        // Apply diminishing returns
        $attackStrength = (int) round($attackStrength * $diminishingFactor);

        // Wine bonus from the attack queue cost_wine
        $wineBonus = $this->calculateWineBonus($attack->cost_wine);
        $attackStrength = (int) round($attackStrength * (1 + $wineBonus));

        // Build details string
        $details = "Attack Strength: {$attackStrength} | Defense Strength: {$defenseStrength}";
        $details .= " | Diminishing: {$diminishingFactor} | Wine Bonus: " . round($wineBonus * 100) . "%";

        // 6. Determine winner
        $attackerWins = $attackStrength > $defenseStrength;

        if ($attackerWins) {
            $this->resolveStandardAttackWin(
                $attack, $attacker, $attackerData, $defender,
                $attackerSoldiers, $defenderSoldiers,
                $attackStrength, $defenseStrength,
                $details, $message,
            );
        } else {
            $this->resolveStandardAttackLoss(
                $attack, $attacker, $attackerData, $defender,
                $attackerSoldiers, $defenderSoldiers,
                $attackStrength, $defenseStrength,
                $details, $message,
            );
        }
    }

    /**
     * Attacker wins a standard attack: take land, proportional casualties.
     */
    protected function resolveStandardAttackWin(
        AttackQueue $attack,
        Player $attacker,
        array &$attackerData,
        Player $defender,
        array $attackerSoldiers,
        array $defenderSoldiers,
        int $attackStrength,
        int $defenseStrength,
        string &$details,
        string &$message,
    ): void {
        // Strength ratio determines casualty severity
        $ratio = $defenseStrength > 0
            ? $attackStrength / $defenseStrength
            : 10.0;
        $ratio = min($ratio, 10.0);

        // Attacker casualty percentage: lower when winning by more
        // Range: ~5% (dominant win) to ~35% (narrow win)
        $attackerLossPct = max(0.05, 0.40 - ($ratio * 0.035));

        // Defender casualty percentage: higher when losing by more
        // Range: ~25% (narrow loss) to ~60% (total rout)
        $defenderLossPct = min(0.60, 0.20 + ($ratio * 0.04));

        // Apply research4 (Military Losses) reduction
        $attackerLossPct *= (1 - $attacker->research4 / 200);
        $defenderLossPct *= (1 - $defender->research4 / 200);

        // Calculate land taken
        $landTaken = 0;
        foreach (self::UNIT_COLUMNS as $soldierId => $col) {
            $count = (int) $attack->{$col};
            if ($count > 0 && isset($attackerSoldiers[$soldierId])) {
                $landTaken += $count * $attackerSoldiers[$soldierId]['take_land'];
            }
        }
        $landTaken = (int) round($landTaken);

        // Ensure we don't take more land than defender has
        $defenderTotalLand = $defender->mland + $defender->fland + $defender->pland;
        $landTaken = min($landTaken, $defenderTotalLand);
        $landTaken = max($landTaken, 0);

        // Split taken land proportionally to defender's land distribution
        $landDistribution = $this->distributeLand($defender, $landTaken);

        // Apply casualties to attacker's troops (returning army)
        $attackerCasualties = [];
        $survivingArmy = [];
        foreach (self::UNIT_COLUMNS as $soldierId => $col) {
            $count = (int) $attack->{$col};
            $lost = (int) round($count * $attackerLossPct);
            $lost = min($lost, $count);
            $attackerCasualties[$col] = $lost;
            $survivingArmy[$col] = $count - $lost;
        }

        // Apply casualties to defender's troops
        $defenderCasualties = [];
        foreach (self::UNIT_COLUMNS as $soldierId => $col) {
            $count = (int) $defender->{$col};
            $lost = (int) round($count * $defenderLossPct);
            $lost = min($lost, $count);
            $defenderCasualties[$col] = $lost;
        }

        // Defender loses tower units proportionally too
        $towerLost = (int) round($defender->tower * $defenderLossPct * 0.25);

        // Update defender: subtract land, subtract casualties
        $defenderUpdate = [];
        foreach ($defenderCasualties as $col => $lost) {
            $defenderUpdate[$col] = DB::raw("{$col} - {$lost}");
        }
        $defenderUpdate['mland'] = DB::raw("mland - {$landDistribution['mland']}");
        $defenderUpdate['fland'] = DB::raw("fland - {$landDistribution['fland']}");
        $defenderUpdate['pland'] = DB::raw("pland - {$landDistribution['pland']}");
        if ($towerLost > 0) {
            $defenderUpdate['tower'] = DB::raw("tower - {$towerLost}");
        }
        $defender->update($defenderUpdate);
        $defender->refresh();

        // Return surviving army to attacker
        foreach ($survivingArmy as $col => $count) {
            if ($count > 0) {
                $attackerData[$col] = ($attackerData[$col] ?? 0) + $count;
            }
        }

        // Give land to attacker (as plains by default)
        $attackerData['pland'] = ($attackerData['pland'] ?? 0) + $landTaken;

        $details .= " | Land Taken: {$landTaken}";
        $details .= " | Attacker Loss%: " . round($attackerLossPct * 100, 1);
        $details .= " | Defender Loss%: " . round($defenderLossPct * 100, 1);

        $message .= "Standard attack on {$defender->name}: VICTORY! Took {$landTaken} land.\n";

        // Build news message
        $newsMessage = "{$attacker->name} attacked {$defender->name} and won! "
            . "{$landTaken} land was conquered.";

        // Create records
        $this->createAttackNews(
            $attack, $attacker, $defender,
            $attackerCasualties, $defenderCasualties,
            $newsMessage, $details, true,
        );

        $this->sendDefenderMessage(
            $attacker, $defender,
            "Your lands were attacked by {$attacker->name}. You lost {$landTaken} land "
            . "and suffered military casualties.",
        );

        // Recalculate scores
        $this->scoreService->calculateScore($defender);

        // 10. Deathmatch check
        $this->checkDeathmatch($attacker, $defender);
    }

    /**
     * Attacker loses a standard attack: higher attacker casualties, no land change.
     */
    protected function resolveStandardAttackLoss(
        AttackQueue $attack,
        Player $attacker,
        array &$attackerData,
        Player $defender,
        array $attackerSoldiers,
        array $defenderSoldiers,
        int $attackStrength,
        int $defenseStrength,
        string &$details,
        string &$message,
    ): void {
        $ratio = $attackStrength > 0
            ? $defenseStrength / $attackStrength
            : 10.0;
        $ratio = min($ratio, 10.0);

        // Attacker casualty percentage: high when losing
        // Range: ~35% (narrow loss) to ~70% (crushed)
        $attackerLossPct = min(0.70, 0.30 + ($ratio * 0.04));

        // Defender casualty percentage: low when winning
        // Range: ~3% (dominant win) to ~20% (narrow win)
        $defenderLossPct = max(0.03, 0.25 - ($ratio * 0.022));

        // Apply research4 (Military Losses) reduction
        $attackerLossPct *= (1 - $attacker->research4 / 200);
        $defenderLossPct *= (1 - $defender->research4 / 200);

        // Attacker casualties
        $attackerCasualties = [];
        $survivingArmy = [];
        foreach (self::UNIT_COLUMNS as $soldierId => $col) {
            $count = (int) $attack->{$col};
            $lost = (int) round($count * $attackerLossPct);
            $lost = min($lost, $count);
            $attackerCasualties[$col] = $lost;
            $survivingArmy[$col] = $count - $lost;
        }

        // Defender casualties
        $defenderCasualties = [];
        foreach (self::UNIT_COLUMNS as $soldierId => $col) {
            $count = (int) $defender->{$col};
            $lost = (int) round($count * $defenderLossPct);
            $lost = min($lost, $count);
            $defenderCasualties[$col] = $lost;
        }

        // Update defender: subtract casualties
        $defenderUpdate = [];
        foreach ($defenderCasualties as $col => $lost) {
            if ($lost > 0) {
                $defenderUpdate[$col] = DB::raw("{$col} - {$lost}");
            }
        }
        if (!empty($defenderUpdate)) {
            $defender->update($defenderUpdate);
            $defender->refresh();
        }

        // Return surviving army to attacker
        foreach ($survivingArmy as $col => $count) {
            if ($count > 0) {
                $attackerData[$col] = ($attackerData[$col] ?? 0) + $count;
            }
        }

        $details .= " | Attacker Loss%: " . round($attackerLossPct * 100, 1);
        $details .= " | Defender Loss%: " . round($defenderLossPct * 100, 1);

        $message .= "Standard attack on {$defender->name}: DEFEATED. No land taken.\n";

        $newsMessage = "{$attacker->name} attacked {$defender->name} and was defeated!";

        $this->createAttackNews(
            $attack, $attacker, $defender,
            $attackerCasualties, $defenderCasualties,
            $newsMessage, $details, false,
        );

        $this->sendDefenderMessage(
            $attacker, $defender,
            "Your defenses repelled an attack from {$attacker->name}! "
            . "Your army suffered minor casualties but held the line.",
        );

        $this->scoreService->calculateScore($defender);
    }

    // =========================================================================
    //  TYPE 10-19 : Raid Attack / Steal Resources  (doAttack2.cfm)
    // =========================================================================

    protected function processRaidAttack(
        AttackQueue $attack,
        Player $attacker,
        array &$attackerData,
        string &$message,
    ): void {
        // 1. Load defender
        $defender = Player::find($attack->attack_player_id);
        if (!$defender || !$defender->isAlive()) {
            $this->returnTroopsHome($attack, $attackerData, $message, 'Defender is dead or not found.');
            return;
        }

        // 2. Diminishing returns
        $hasAttacks = $this->countRecentAttacks($attacker->id, $defender->id);
        $diminishingFactor = $this->getDiminishingFactor($hasAttacks);

        $attackerSoldiers = $this->gameData->getSoldiers($attacker->civ);
        $defenderSoldiers = $this->gameData->getSoldiers($defender->civ);

        // 3. Calculate strengths (same formulas as standard attack)
        $attackStrength = $this->calculateAttackStrength(
            $attack, $attackerSoldiers, $attacker, $defender,
        );
        $defenseStrength = $this->calculateDefenseStrength(
            $defender, $defenderSoldiers,
        );

        // 4. Random factor and diminishing
        $attackStrength = $this->applyRandomFactor($attackStrength);
        $defenseStrength = $this->applyRandomFactor($defenseStrength);
        $attackStrength = (int) round($attackStrength * $diminishingFactor);

        // Wine bonus
        $wineBonus = $this->calculateWineBonus($attack->cost_wine);
        $attackStrength = (int) round($attackStrength * (1 + $wineBonus));

        $details = "Raid | Attack: {$attackStrength} | Defense: {$defenseStrength}";

        $attackerWins = $attackStrength > $defenseStrength;

        if ($attackerWins) {
            $this->resolveRaidWin(
                $attack, $attacker, $attackerData, $defender,
                $attackerSoldiers, $defenderSoldiers,
                $attackStrength, $defenseStrength,
                $details, $message,
            );
        } else {
            $this->resolveRaidLoss(
                $attack, $attacker, $attackerData, $defender,
                $attackerSoldiers, $defenderSoldiers,
                $attackStrength, $defenseStrength,
                $details, $message,
            );
        }
    }

    /**
     * Attacker wins a raid: steal resources, lower casualties.
     */
    protected function resolveRaidWin(
        AttackQueue $attack,
        Player $attacker,
        array &$attackerData,
        Player $defender,
        array $attackerSoldiers,
        array $defenderSoldiers,
        int $attackStrength,
        int $defenseStrength,
        string &$details,
        string &$message,
    ): void {
        $ratio = $defenseStrength > 0
            ? $attackStrength / $defenseStrength
            : 10.0;
        $ratio = min($ratio, 10.0);

        // Raid has lower casualties than standard attack
        $attackerLossPct = max(0.02, 0.20 - ($ratio * 0.02));
        $defenderLossPct = min(0.30, 0.10 + ($ratio * 0.02));

        $attackerLossPct *= (1 - $attacker->research4 / 200);
        $defenderLossPct *= (1 - $defender->research4 / 200);

        // Calculate steal percentage based on strength ratio
        // Stronger win = steal more. Range: 10%-40% of defender's resources
        $stealPct = min(0.40, 0.05 + ($ratio * 0.035));

        // Calculate total army size for carry capacity
        $totalTroops = 0;
        foreach (self::UNIT_COLUMNS as $soldierId => $col) {
            $totalTroops += (int) $attack->{$col};
        }

        // Steal resources
        $stolen = [];
        $defenderUpdate = [];
        foreach (self::RAID_RESOURCES as $resource) {
            $available = (int) $defender->{$resource};
            $amount = (int) round($available * $stealPct);

            // Carry capacity limit: roughly 10 units per soldier for bulk goods
            $carryLimit = $totalTroops * $this->getCarryCapacity($resource);
            $amount = min($amount, $carryLimit);
            $amount = max($amount, 0);

            if ($amount > 0) {
                $stolen[$resource] = $amount;
                $attackerData[$resource] = ($attackerData[$resource] ?? 0) + $amount;
                $defenderUpdate[$resource] = DB::raw("{$resource} - {$amount}");
            }
        }

        // Apply casualties
        $attackerCasualties = [];
        $survivingArmy = [];
        foreach (self::UNIT_COLUMNS as $soldierId => $col) {
            $count = (int) $attack->{$col};
            $lost = (int) round($count * $attackerLossPct);
            $lost = min($lost, $count);
            $attackerCasualties[$col] = $lost;
            $survivingArmy[$col] = $count - $lost;
        }

        $defenderCasualties = [];
        foreach (self::UNIT_COLUMNS as $soldierId => $col) {
            $count = (int) $defender->{$col};
            $lost = (int) round($count * $defenderLossPct);
            $lost = min($lost, $count);
            $defenderCasualties[$col] = $lost;
            if ($lost > 0) {
                $defenderUpdate[$col] = DB::raw("{$col} - {$lost}");
            }
        }

        if (!empty($defenderUpdate)) {
            $defender->update($defenderUpdate);
            $defender->refresh();
        }

        // Return surviving army
        foreach ($survivingArmy as $col => $count) {
            if ($count > 0) {
                $attackerData[$col] = ($attackerData[$col] ?? 0) + $count;
            }
        }

        // Build stolen summary
        $stolenParts = [];
        foreach ($stolen as $resource => $amount) {
            $stolenParts[] = number_format($amount) . " {$resource}";
        }
        $stolenSummary = implode(', ', $stolenParts);

        $details .= " | Stolen: {$stolenSummary}";
        $details .= " | Steal%: " . round($stealPct * 100, 1);

        $message .= "Raid on {$defender->name}: SUCCESS! Stole {$stolenSummary}.\n";

        $newsMessage = "{$attacker->name} raided {$defender->name} and stole resources: {$stolenSummary}.";

        $this->createAttackNews(
            $attack, $attacker, $defender,
            $attackerCasualties, $defenderCasualties,
            $newsMessage, $details, true,
        );

        $this->sendDefenderMessage(
            $attacker, $defender,
            "You were raided by {$attacker->name}! They stole: {$stolenSummary}.",
        );

        $this->scoreService->calculateScore($defender);
    }

    /**
     * Attacker loses a raid: no resources stolen, moderate casualties.
     */
    protected function resolveRaidLoss(
        AttackQueue $attack,
        Player $attacker,
        array &$attackerData,
        Player $defender,
        array $attackerSoldiers,
        array $defenderSoldiers,
        int $attackStrength,
        int $defenseStrength,
        string &$details,
        string &$message,
    ): void {
        $ratio = $attackStrength > 0
            ? $defenseStrength / $attackStrength
            : 10.0;
        $ratio = min($ratio, 10.0);

        // Failed raid: moderate attacker losses, low defender losses
        $attackerLossPct = min(0.50, 0.25 + ($ratio * 0.025));
        $defenderLossPct = max(0.02, 0.15 - ($ratio * 0.013));

        $attackerLossPct *= (1 - $attacker->research4 / 200);
        $defenderLossPct *= (1 - $defender->research4 / 200);

        $attackerCasualties = [];
        $survivingArmy = [];
        foreach (self::UNIT_COLUMNS as $soldierId => $col) {
            $count = (int) $attack->{$col};
            $lost = (int) round($count * $attackerLossPct);
            $lost = min($lost, $count);
            $attackerCasualties[$col] = $lost;
            $survivingArmy[$col] = $count - $lost;
        }

        $defenderCasualties = [];
        $defenderUpdate = [];
        foreach (self::UNIT_COLUMNS as $soldierId => $col) {
            $count = (int) $defender->{$col};
            $lost = (int) round($count * $defenderLossPct);
            $lost = min($lost, $count);
            $defenderCasualties[$col] = $lost;
            if ($lost > 0) {
                $defenderUpdate[$col] = DB::raw("{$col} - {$lost}");
            }
        }

        if (!empty($defenderUpdate)) {
            $defender->update($defenderUpdate);
            $defender->refresh();
        }

        foreach ($survivingArmy as $col => $count) {
            if ($count > 0) {
                $attackerData[$col] = ($attackerData[$col] ?? 0) + $count;
            }
        }

        $message .= "Raid on {$defender->name}: FAILED. No resources stolen.\n";

        $newsMessage = "{$attacker->name} attempted to raid {$defender->name} but was repelled!";

        $this->createAttackNews(
            $attack, $attacker, $defender,
            $attackerCasualties, $defenderCasualties,
            $newsMessage, $details, false,
        );

        $this->sendDefenderMessage(
            $attacker, $defender,
            "Your defenses repelled a raid attempt from {$attacker->name}!",
        );

        $this->scoreService->calculateScore($defender);
    }

    // =========================================================================
    //  TYPE 20-29 : Spy / Thief Attack  (doAttack3.cfm)
    // =========================================================================

    protected function processSpyAttack(
        AttackQueue $attack,
        Player $attacker,
        array &$attackerData,
        string &$message,
    ): void {
        // 1. Load defender
        $defender = Player::find($attack->attack_player_id);
        if (!$defender || !$defender->isAlive()) {
            $this->returnTroopsHome($attack, $attackerData, $message, 'Defender is dead or not found.');
            return;
        }

        $attackerSoldiers = $this->gameData->getSoldiers($attacker->civ);
        $defenderSoldiers = $this->gameData->getSoldiers($defender->civ);

        // 2. Calculate thief attack strength
        //    Uses thieves primarily: thief count * thief attack_pt
        //    Plus research3 (Thieves Strength) bonus
        $thiefCount = (int) $attack->thieves;
        $thiefAttackPt = $attackerSoldiers[8]['attack_pt'] ?? 50;
        $spyStrength = $thiefCount * $thiefAttackPt;

        // Apply research3 bonus
        $spyStrength += (int) round($spyStrength * ($attacker->research3 / 100));

        // Other units contribute a small amount to spy missions
        foreach (self::UNIT_COLUMNS as $soldierId => $col) {
            if ($soldierId === 8) {
                continue; // thieves already counted
            }
            $count = (int) $attack->{$col};
            if ($count > 0 && isset($attackerSoldiers[$soldierId])) {
                // Other units contribute 10% of their attack value to spy missions
                $spyStrength += (int) round($count * $attackerSoldiers[$soldierId]['attack_pt'] * 0.10);
            }
        }

        // 3. Calculate defender's thief defense
        //    Defender's thieves * defense_pt + research3 bonus
        $defenderThieves = (int) $defender->thieves;
        $defenderThiefDef = $defenderSoldiers[8]['defense_pt'] ?? 55;
        $spyDefense = $defenderThieves * $defenderThiefDef;

        // Apply defender's research3 bonus
        $spyDefense += (int) round($spyDefense * ($defender->research3 / 100));

        // Towers add to spy defense
        $towerDef = $defenderSoldiers[4]['defense_pt'] ?? 50;
        $spyDefense += (int) $defender->tower * (int) round($towerDef * 0.25);

        // Wall adds minor spy defense
        $spyDefense += (int) round($defender->wall * 2);

        // 4. Apply randomness (+-15%)
        $spyStrength = $this->applyRandomFactor($spyStrength);
        $spyDefense = $this->applyRandomFactor($spyDefense);

        $details = "Spy | Strength: {$spyStrength} | Defense: {$spyDefense}";

        // 5. Determine outcome
        $attackerWins = $spyStrength > $spyDefense;

        if ($attackerWins) {
            $this->resolveSpyWin(
                $attack, $attacker, $attackerData, $defender,
                $attackerSoldiers, $defenderSoldiers,
                $spyStrength, $spyDefense, $thiefCount,
                $details, $message,
            );
        } else {
            $this->resolveSpyLoss(
                $attack, $attacker, $attackerData, $defender,
                $attackerSoldiers, $defenderSoldiers,
                $spyStrength, $spyDefense, $thiefCount,
                $details, $message,
            );
        }
    }

    /**
     * Successful spy: steal gold, reveal defender intel.
     */
    protected function resolveSpyWin(
        AttackQueue $attack,
        Player $attacker,
        array &$attackerData,
        Player $defender,
        array $attackerSoldiers,
        array $defenderSoldiers,
        int $spyStrength,
        int $spyDefense,
        int $thiefCount,
        string &$details,
        string &$message,
    ): void {
        $ratio = $spyDefense > 0
            ? $spyStrength / $spyDefense
            : 10.0;
        $ratio = min($ratio, 10.0);

        // Steal gold: thieves * 100 gold each, capped at 25% of defender's gold
        $goldStolen = min(
            $thiefCount * 100,
            (int) round($defender->gold * 0.25),
        );
        $goldStolen = max($goldStolen, 0);

        // Thief casualties (low on success): 5-15%
        $thiefLossPct = max(0.05, 0.20 - ($ratio * 0.015));
        $thiefLossPct *= (1 - $attacker->research4 / 200);

        $thievesLost = (int) round($thiefCount * $thiefLossPct);
        $thievesLost = min($thievesLost, $thiefCount);
        $survivingThieves = $thiefCount - $thievesLost;

        // Defender loses some thieves too
        $defThievesLost = (int) round($defender->thieves * 0.05);

        // Build intelligence report
        $intel = $this->buildIntelReport($defender, $defenderSoldiers);

        // Apply changes
        if ($goldStolen > 0 || $defThievesLost > 0) {
            $defenderUpdate = [];
            if ($goldStolen > 0) {
                $defenderUpdate['gold'] = DB::raw("gold - {$goldStolen}");
            }
            if ($defThievesLost > 0) {
                $defenderUpdate['thieves'] = DB::raw("thieves - {$defThievesLost}");
            }
            $defender->update($defenderUpdate);
            $defender->refresh();
        }

        // Return surviving army to attacker
        foreach (self::UNIT_COLUMNS as $soldierId => $col) {
            $count = (int) $attack->{$col};
            if ($col === 'thieves') {
                $count = $survivingThieves;
            }
            if ($count > 0) {
                $attackerData[$col] = ($attackerData[$col] ?? 0) + $count;
            }
        }

        // Give stolen gold
        $attackerData['gold'] = ($attackerData['gold'] ?? 0) + $goldStolen;

        $details .= " | Gold Stolen: " . number_format($goldStolen);
        $details .= " | Thieves Lost: {$thievesLost}";

        $message .= "Spy mission on {$defender->name}: SUCCESS!\n";
        $message .= "Stole " . number_format($goldStolen) . " gold.\n";
        $message .= "Intelligence Report:\n{$intel}\n";

        $newsMessage = "{$attacker->name} sent spies against {$defender->name} "
            . "and stole " . number_format($goldStolen) . " gold.";

        $attackerCasualties = array_fill_keys(array_values(self::UNIT_COLUMNS), 0);
        $attackerCasualties['thieves'] = $thievesLost;

        $defenderCasualties = array_fill_keys(array_values(self::UNIT_COLUMNS), 0);
        $defenderCasualties['thieves'] = $defThievesLost;

        $this->createAttackNews(
            $attack, $attacker, $defender,
            $attackerCasualties, $defenderCasualties,
            $newsMessage, $details, true,
        );

        $this->sendDefenderMessage(
            $attacker, $defender,
            "Spies from {$attacker->name} infiltrated your kingdom! "
            . "They stole " . number_format($goldStolen) . " gold.",
        );

        $this->scoreService->calculateScore($defender);
    }

    /**
     * Failed spy: thieves captured/killed, no intel gained.
     */
    protected function resolveSpyLoss(
        AttackQueue $attack,
        Player $attacker,
        array &$attackerData,
        Player $defender,
        array $attackerSoldiers,
        array $defenderSoldiers,
        int $spyStrength,
        int $spyDefense,
        int $thiefCount,
        string &$details,
        string &$message,
    ): void {
        $ratio = $spyStrength > 0
            ? $spyDefense / $spyStrength
            : 10.0;
        $ratio = min($ratio, 10.0);

        // Heavy thief losses on failure: 40-80%
        $thiefLossPct = min(0.80, 0.35 + ($ratio * 0.045));
        $thiefLossPct *= (1 - $attacker->research4 / 200);

        $thievesLost = (int) round($thiefCount * $thiefLossPct);
        $thievesLost = min($thievesLost, $thiefCount);
        $survivingThieves = $thiefCount - $thievesLost;

        // Return surviving army to attacker
        foreach (self::UNIT_COLUMNS as $soldierId => $col) {
            $count = (int) $attack->{$col};
            if ($col === 'thieves') {
                $count = $survivingThieves;
            }
            if ($count > 0) {
                $attackerData[$col] = ($attackerData[$col] ?? 0) + $count;
            }
        }

        $details .= " | Thieves Lost: {$thievesLost}";

        $message .= "Spy mission on {$defender->name}: FAILED! "
            . "{$thievesLost} thieves were captured or killed.\n";

        $newsMessage = "{$attacker->name} sent spies against {$defender->name} "
            . "but they were caught! {$thievesLost} thieves were captured.";

        $attackerCasualties = array_fill_keys(array_values(self::UNIT_COLUMNS), 0);
        $attackerCasualties['thieves'] = $thievesLost;

        $defenderCasualties = array_fill_keys(array_values(self::UNIT_COLUMNS), 0);

        $this->createAttackNews(
            $attack, $attacker, $defender,
            $attackerCasualties, $defenderCasualties,
            $newsMessage, $details, false,
        );

        $this->sendDefenderMessage(
            $attacker, $defender,
            "Your guards captured spies sent by {$attacker->name}! "
            . "{$thievesLost} enemy thieves were killed or imprisoned.",
        );
    }

    // =========================================================================
    //  Strength Calculation Helpers
    // =========================================================================

    /**
     * Calculate attacker army strength for standard/raid attacks.
     *
     * For each unit type: count * attack_pt
     * Then apply research1 (Attack Points) bonus
     * Then check alliance war bonus (+10%)
     */
    protected function calculateAttackStrength(
        AttackQueue $attack,
        array $soldiers,
        Player $attacker,
        Player $defender,
    ): int {
        $strength = 0;

        foreach (self::UNIT_COLUMNS as $soldierId => $col) {
            $count = (int) $attack->{$col};
            if ($count > 0 && isset($soldiers[$soldierId])) {
                $strength += $count * $soldiers[$soldierId]['attack_pt'];
            }
        }

        // Apply research1 (Attack Points) bonus
        $strength += (int) round($strength * ($attacker->research1 / 100));

        // Apply research11 (Catapults Strength) bonus specifically to catapults
        $catapultCount = (int) $attack->catapults;
        if ($catapultCount > 0 && isset($soldiers[5])) {
            $catapultBase = $catapultCount * $soldiers[5]['attack_pt'];
            $strength += (int) round($catapultBase * ($attacker->research11 / 100));
        }

        // Alliance war bonus: if attacker's alliance is at war with defender's alliance, +10%
        if ($this->isAtWar($attacker, $defender)) {
            $strength = (int) round($strength * 1.10);
        }

        return max($strength, 0);
    }

    /**
     * Calculate defender army strength.
     *
     * For each unit type: count * defense_pt
     * Towers: tower count * tower defense_pt
     * Apply research2 (Defense Points) bonus
     * Wall bonus: wall * 0.5 of total defense
     * Fort bonus: small bonus per fort
     */
    protected function calculateDefenseStrength(
        Player $defender,
        array $soldiers,
    ): int {
        $strength = 0;

        // Unit defense
        foreach (self::UNIT_COLUMNS as $soldierId => $col) {
            if ($soldierId === 4) {
                continue; // towers handled separately
            }
            $count = (int) $defender->{$col};
            if ($count > 0 && isset($soldiers[$soldierId])) {
                $strength += $count * $soldiers[$soldierId]['defense_pt'];
            }
        }

        // Tower defense
        $towerCount = (int) $defender->tower;
        if ($towerCount > 0 && isset($soldiers[4])) {
            $strength += $towerCount * $soldiers[4]['defense_pt'];
        }

        // Apply research2 (Defense Points) bonus
        $strength += (int) round($strength * ($defender->research2 / 100));

        // Apply research11 (Catapults Strength) bonus to defender's catapults
        $catapultCount = (int) $defender->catapults;
        if ($catapultCount > 0 && isset($soldiers[5])) {
            $catapultBase = $catapultCount * $soldiers[5]['defense_pt'];
            $strength += (int) round($catapultBase * ($defender->research11 / 100));
        }

        // Wall bonus: each wall point adds 0.5% to total defense
        $wallBonus = $defender->wall * 0.005;
        $strength += (int) round($strength * $wallBonus);

        // Fort bonus: each fort adds a flat defense bonus
        $fortBonus = (int) $defender->fort * 50;
        $strength += $fortBonus;

        return max($strength, 0);
    }

    /**
     * Apply a random factor of +-15% to a strength value.
     * Uses mt_rand as the PHP equivalent of CF's randRange.
     */
    protected function applyRandomFactor(int $strength): int
    {
        // Random factor between 85% and 115%
        $factor = mt_rand(85, 115) / 100.0;
        return (int) round($strength * $factor);
    }

    /**
     * Calculate wine attack bonus.
     * Wine gives an attack bonus; each wine unit provides a percentage boost.
     */
    protected function calculateWineBonus(int $wineUsed): float
    {
        if ($wineUsed <= 0) {
            return 0.0;
        }

        // Each wine gives approximately 0.5% bonus, capped at 25%
        return min($wineUsed * 0.005, 0.25);
    }

    // =========================================================================
    //  Diminishing Returns
    // =========================================================================

    /**
     * Count how many attacks this attacker has made on this defender in the last 24 hours.
     */
    protected function countRecentAttacks(int $attackerId, int $defenderId): int
    {
        return AttackNews::where('attack_id', $attackerId)
            ->where('defense_id', $defenderId)
            ->where('created_on', '>=', now()->subHours(24))
            ->count();
    }

    /**
     * Calculate diminishing returns factor based on number of recent attacks.
     *
     * 0 prior attacks: 100% effectiveness
     * 1 prior attack:   90%
     * 2 prior attacks:  75%
     * 3 prior attacks:  55%
     * 4 prior attacks:  35%
     * 5+ prior attacks: 20%
     */
    protected function getDiminishingFactor(int $attackCount): float
    {
        return match (true) {
            $attackCount <= 0 => 1.00,
            $attackCount === 1 => 0.90,
            $attackCount === 2 => 0.75,
            $attackCount === 3 => 0.55,
            $attackCount === 4 => 0.35,
            default => 0.20,
        };
    }

    // =========================================================================
    //  Alliance War Check
    // =========================================================================

    /**
     * Check if attacker's alliance is at war with defender's alliance.
     */
    protected function isAtWar(Player $attacker, Player $defender): bool
    {
        $attackerAllianceId = (int) $attacker->alliance_id;
        $defenderAllianceId = (int) $defender->alliance_id;

        if ($attackerAllianceId <= 0 || $defenderAllianceId <= 0) {
            return false;
        }

        $attackerAlliance = Alliance::find($attackerAllianceId);
        if (!$attackerAlliance) {
            return false;
        }

        // Check all 5 war slots
        for ($i = 1; $i <= 5; $i++) {
            $warField = "war{$i}";
            if ((int) $attackerAlliance->{$warField} === $defenderAllianceId) {
                return true;
            }
        }

        return false;
    }

    // =========================================================================
    //  Land Distribution
    // =========================================================================

    /**
     * Distribute taken land proportionally based on defender's land composition.
     *
     * @return array{mland: int, fland: int, pland: int}
     */
    protected function distributeLand(Player $defender, int $landTaken): array
    {
        $totalLand = $defender->mland + $defender->fland + $defender->pland;

        if ($totalLand <= 0 || $landTaken <= 0) {
            return ['mland' => 0, 'fland' => 0, 'pland' => 0];
        }

        $mlandTaken = (int) round($landTaken * ($defender->mland / $totalLand));
        $flandTaken = (int) round($landTaken * ($defender->fland / $totalLand));
        $plandTaken = $landTaken - $mlandTaken - $flandTaken; // remainder goes to pland

        // Clamp to what the defender actually has
        $mlandTaken = min($mlandTaken, $defender->mland);
        $flandTaken = min($flandTaken, $defender->fland);
        $plandTaken = min($plandTaken, $defender->pland);

        return [
            'mland' => max($mlandTaken, 0),
            'fland' => max($flandTaken, 0),
            'pland' => max($plandTaken, 0),
        ];
    }

    // =========================================================================
    //  Carry Capacity (Raids)
    // =========================================================================

    /**
     * Get carry capacity per soldier for a given resource type.
     * Bulk goods (wood, food) have higher capacity per soldier.
     */
    protected function getCarryCapacity(string $resource): int
    {
        return match ($resource) {
            'wood' => 10,
            'food' => 15,
            'iron' => 5,
            'gold' => 50,
            'tools' => 3,
            default => 5,
        };
    }

    // =========================================================================
    //  Intelligence Report (Spy)
    // =========================================================================

    /**
     * Build an intelligence report on the defender's kingdom.
     */
    protected function buildIntelReport(Player $defender, array $soldiers): string
    {
        $lines = [];
        $lines[] = "=== Intelligence Report: {$defender->name} ===";

        // Army
        $lines[] = "Military Forces:";
        foreach (self::UNIT_COLUMNS as $soldierId => $col) {
            $count = (int) $defender->{$col};
            if ($count > 0 && isset($soldiers[$soldierId])) {
                $lines[] = "  {$soldiers[$soldierId]['name']}: {$count}";
            }
        }
        $towerCount = (int) $defender->tower;
        if ($towerCount > 0) {
            $lines[] = "  Towers: {$towerCount}";
        }

        // Resources
        $lines[] = "Resources:";
        $lines[] = "  Wood: " . number_format($defender->wood);
        $lines[] = "  Food: " . number_format($defender->food);
        $lines[] = "  Iron: " . number_format($defender->iron);
        $lines[] = "  Gold: " . number_format($defender->gold);
        $lines[] = "  Tools: " . number_format($defender->tools);

        // Land
        $lines[] = "Land:";
        $lines[] = "  Mountain: {$defender->mland}";
        $lines[] = "  Forest: {$defender->fland}";
        $lines[] = "  Plains: {$defender->pland}";

        // Wall
        if ($defender->wall > 0) {
            $lines[] = "Wall: {$defender->wall}";
        }

        return implode("\n", $lines);
    }

    // =========================================================================
    //  Return Troops (Abort / Invalid Target)
    // =========================================================================

    /**
     * Return all troops from an attack back to the attacker with no combat.
     */
    protected function returnTroopsHome(
        AttackQueue $attack,
        array &$attackerData,
        string &$message,
        string $reason,
    ): void {
        foreach (self::UNIT_COLUMNS as $soldierId => $col) {
            $count = (int) $attack->{$col};
            if ($count > 0) {
                $attackerData[$col] = ($attackerData[$col] ?? 0) + $count;
            }
        }

        $message .= "Attack aborted: {$reason} Troops returned home.\n";
    }

    // =========================================================================
    //  Record Creation
    // =========================================================================

    /**
     * Create an AttackNews record documenting the battle.
     */
    protected function createAttackNews(
        AttackQueue $attack,
        Player $attacker,
        Player $defender,
        array $attackerCasualties,
        array $defenderCasualties,
        string $newsMessage,
        string $details,
        bool $attackerWins,
    ): void {
        $attackerAlliance = $attacker->alliance_id > 0
            ? Alliance::find($attacker->alliance_id)
            : null;
        $defenderAlliance = $defender->alliance_id > 0
            ? Alliance::find($defender->alliance_id)
            : null;

        AttackNews::create([
            'attack_id' => $attacker->id,
            'defense_id' => $defender->id,
            'attack_swordsman' => (int) $attack->swordsman,
            'attack_horseman' => (int) $attack->horseman,
            'attack_archers' => (int) $attack->archers,
            'attack_macemen' => (int) $attack->macemen,
            'attack_catapults' => (int) $attack->catapults,
            'attack_peasants' => (int) $attack->trained_peasants,
            'attack_thieves' => (int) $attack->thieves,
            'attack_uunit' => (int) $attack->uunit,
            'defense_swordsman' => (int) $defender->swordsman,
            'defense_horseman' => (int) $defender->horseman,
            'defense_archers' => (int) $defender->archers,
            'defense_macemen' => (int) $defender->macemen,
            'defense_catapults' => (int) $defender->catapults,
            'defense_peasants' => (int) $defender->trained_peasants,
            'defense_thieves' => (int) $defender->thieves,
            'defense_uunit' => (int) $defender->uunit,
            'message' => $newsMessage,
            'created_on' => now(),
            'attacker_wins' => $attackerWins ? 1 : 0,
            'deleted' => 0,
            'attack_alliance' => $attackerAlliance?->name,
            'defense_alliance' => $defenderAlliance?->name,
            'attack_alliance_id' => $attacker->alliance_id ?? 0,
            'defense_alliance_id' => $defender->alliance_id ?? 0,
            'attack_type' => $attack->attack_type,
            'battle_details' => $details,
        ]);
    }

    /**
     * Send a system message to the defender about the attack.
     */
    protected function sendDefenderMessage(
        Player $attacker,
        Player $defender,
        string $messageText,
    ): void {
        PlayerMessage::create([
            'from_player_id' => $attacker->id,
            'to_player_id' => $defender->id,
            'from_player_name' => $attacker->name,
            'to_player_name' => $defender->name,
            'message' => $messageText,
            'viewed' => 0,
            'created_on' => now(),
            'message_type' => 1, // system message
        ]);

        // Flag that defender has new messages
        $defender->update(['has_new_messages' => true]);
    }

    // =========================================================================
    //  Deathmatch
    // =========================================================================

    /**
     * Check if defender has been killed (total land <= 0).
     * If so, mark them as killed.
     */
    protected function checkDeathmatch(Player $attacker, Player $defender): void
    {
        $defender->refresh();

        $totalLand = $defender->mland + $defender->fland + $defender->pland;

        if ($totalLand <= 0) {
            $defender->update([
                'killed_by' => $attacker->id,
                'killed_by_name' => $attacker->name,
            ]);

            Log::info("Player {$defender->name} (ID: {$defender->id}) was killed by {$attacker->name} (ID: {$attacker->id}).");

            // Notify the defeated player
            PlayerMessage::create([
                'from_player_id' => 0,
                'to_player_id' => $defender->id,
                'from_player_name' => 'System',
                'to_player_name' => $defender->name,
                'message' => "Your kingdom has fallen! You were defeated by {$attacker->name}. "
                    . "All your lands have been lost.",
                'viewed' => 0,
                'created_on' => now(),
                'message_type' => 1,
            ]);
        }
    }
}
