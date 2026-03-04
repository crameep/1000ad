<?php

namespace App\Http\Controllers;

use App\Models\Alliance;
use App\Models\Player;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Alliance Controller
 *
 * Handles alliance management: joining, creating, leaving, relations, and member management.
 * Ported from alliance.cfm and eflag_alliance.cfm
 *
 * Original: Andrew Deren, (C) AderSoftware 2000, 2001
 */
class AllianceController extends Controller
{
    /**
     * Show the alliance page.
     * Ported from alliance.cfm
     */
    public function index()
    {
        $player = player();

        // Block in deathmatch mode or if alliances disabled
        if (gameConfig('deathmatch_mode') || gameConfig('alliance_max_members') == 0) {
            session()->flash('game_message', 'Cannot view this page in deathmatch game.');
            return redirect()->route('game.main');
        }

        // Clear hasAllianceNews flag
        $player->update(['has_alliance_news' => false]);

        if ($player->alliance_id == 0) {
            // Player has no alliance - show join/create forms
            $alliances = Alliance::orderBy('tag')->get(['id', 'tag']);

            return view('pages.alliance', [
                'hasAlliance' => false,
                'alliances' => $alliances,
            ]);
        }

        // Player has an alliance - load full alliance data
        $alliance = Alliance::find($player->alliance_id);

        if (!$alliance) {
            // Alliance no longer exists, reset player
            $player->update(['alliance_id' => 0, 'alliance_member_type' => 0]);
            return redirect()->route('game.alliance');
        }

        // Get all other alliances for the relations dropdowns
        $otherAlliances = Alliance::where('id', '<>', $player->alliance_id)
            ->orderBy('tag')
            ->get(['id', 'tag']);

        // Find alliances that have this alliance on their ally list
        $alliedBy = Alliance::where('ally1', $player->alliance_id)
            ->orWhere('ally2', $player->alliance_id)
            ->orWhere('ally3', $player->alliance_id)
            ->orWhere('ally4', $player->alliance_id)
            ->orWhere('ally5', $player->alliance_id)
            ->orderBy('tag')
            ->pluck('tag');

        // Find alliances that have this alliance on their war list
        $warredBy = Alliance::where('war1', $player->alliance_id)
            ->orWhere('war2', $player->alliance_id)
            ->orWhere('war3', $player->alliance_id)
            ->orWhere('war4', $player->alliance_id)
            ->orWhere('war5', $player->alliance_id)
            ->orderBy('tag')
            ->pluck('tag');

        // Resolve ally/war tag names for read-only display (non-leader)
        $allyTags = [];
        $warTags = [];
        for ($i = 1; $i <= 5; $i++) {
            $allyId = $alliance->{"ally{$i}"} ?? 0;
            $warId = $alliance->{"war{$i}"} ?? 0;
            if ($allyId > 0) {
                $a = Alliance::find($allyId);
                if ($a) {
                    $allyTags[] = $a->tag;
                }
            }
            if ($warId > 0) {
                $w = Alliance::find($warId);
                if ($w) {
                    $warTags[] = $w->tag;
                }
            }
        }

        // Get alliance members with calculated fields
        $members = Player::where('alliance_id', $alliance->id)
            ->select([
                'id', 'name', 'last_load', 'score', 'alliance_member_type', 'civ',
                'swordsman', 'horseman', 'archers', 'trained_peasants',
                'thieves', 'catapults', 'macemen', 'uunit',
                DB::raw('(pland + mland + fland) as total_land'),
                DB::raw('(swordsman + horseman + archers + trained_peasants + thieves + catapults + macemen) as total_army'),
            ])
            ->orderBy('score', 'desc')
            ->get();

        // Calculate rank for each member
        foreach ($members as $member) {
            $member->rank = Player::where('score', '>', $member->score)->count() + 1;
        }

        $isLeader = ($alliance->leader_id == $player->id);

        return view('pages.alliance', [
            'hasAlliance' => true,
            'alliance' => $alliance,
            'otherAlliances' => $otherAlliances,
            'alliedBy' => $alliedBy,
            'warredBy' => $warredBy,
            'allyTags' => $allyTags,
            'warTags' => $warTags,
            'members' => $members,
            'isLeader' => $isLeader,
        ]);
    }

    /**
     * Join an existing alliance.
     * Ported from eflag_alliance.cfm eflag=join_alliance
     *
     * Route: POST /alliance/join -> game.alliance.join
     */
    public function joinAlliance(Request $request)
    {
        $player = player();

        if (gameConfig('deathmatch_mode')) {
            session()->flash('game_message', 'Cannot view this page in deathmatch game.');
            return redirect()->route('game.main');
        }

        $request->validate([
            'join_alliance_id' => 'required|integer',
            'password' => 'required|string',
        ]);

        $joinAllianceId = (int) $request->join_alliance_id;
        $alliance = Alliance::find($joinAllianceId);

        if (!$alliance) {
            session()->flash('game_message', 'Alliance not found.');
            return redirect()->route('game.alliance');
        }

        // Check member limit
        $memberCount = Player::where('alliance_id', $joinAllianceId)->count();
        if ($memberCount >= gameConfig('alliance_max_members')) {
            session()->flash('game_message', 'This alliance already has maximum allowable number of members.');
            return redirect()->route('game.alliance');
        }

        // Validate password
        if ($alliance->passwd !== trim($request->password)) {
            session()->flash('game_message', 'Invalid Password.');
            return redirect()->route('game.alliance');
        }

        // Join the alliance
        $player->update([
            'alliance_id' => $joinAllianceId,
            'alliance_member_type' => 0,
        ]);

        // Calculate game date for news
        $gameDate = $this->getGameDate($player);

        // Update alliance news
        $alliance->update([
            'news' => $alliance->news . "\n{$gameDate}: {$player->name} (#{$player->id}) joined your alliance",
        ]);

        // Notify alliance members
        Player::where('alliance_id', $alliance->id)
            ->update(['has_alliance_news' => true]);

        return redirect()->route('game.alliance');
    }

    /**
     * Create a new alliance.
     * Ported from eflag_alliance.cfm eflag=create_alliance
     *
     * Route: POST /alliance/create -> game.alliance.create
     */
    public function createAlliance(Request $request)
    {
        $player = player();

        if (gameConfig('deathmatch_mode')) {
            session()->flash('game_message', 'Cannot view this page in deathmatch game.');
            return redirect()->route('game.main');
        }

        $request->validate([
            'tag' => 'required|string|max:15',
            'password' => 'required|string|max:20',
        ]);

        $newTag = trim($request->tag);

        // Validate tag characters: only alphanumeric, spaces, underscores
        if (!preg_match('/^[a-zA-Z0-9 _]+$/', $newTag)) {
            session()->flash('game_message', 'Alliance name can only contain spaces and alpha-numeric characters and cannot contain two spaces by each other.');
            return redirect()->route('game.alliance');
        }

        // Check for double spaces
        if (str_contains($newTag, '  ')) {
            session()->flash('game_message', 'Alliance name can only contain spaces and alpha-numeric characters and cannot contain two spaces by each other.');
            return redirect()->route('game.alliance');
        }

        if ($newTag === '') {
            session()->flash('game_message', 'Please provide alliance tag.');
            return redirect()->route('game.alliance');
        }

        // Check if tag already exists
        if (Alliance::where('tag', $newTag)->exists()) {
            session()->flash('game_message', 'Alliance with that tag already exists.');
            return redirect()->route('game.alliance');
        }

        // Create the alliance
        $alliance = Alliance::create([
            'name' => $newTag,
            'tag' => $newTag,
            'leader_id' => $player->id,
            'passwd' => trim($request->password),
        ]);

        // Assign player to the new alliance as trusted member
        $player->update([
            'alliance_id' => $alliance->id,
            'alliance_member_type' => 1,
        ]);

        return redirect()->route('game.alliance');
    }

    /**
     * Leave the current alliance.
     * Ported from eflag_alliance.cfm eflag=leave_alliance
     *
     * Route: POST /alliance/leave -> game.alliance.leave
     */
    public function leaveAlliance()
    {
        $player = player();

        if (gameConfig('deathmatch_mode')) {
            session()->flash('game_message', 'Cannot view this page in deathmatch game.');
            return redirect()->route('game.main');
        }

        $alliance = Alliance::find($player->alliance_id);

        if (!$alliance) {
            $player->update(['alliance_id' => 0, 'alliance_member_type' => 0]);
            return redirect()->route('game.alliance');
        }

        $allianceTag = $alliance->tag;
        $gameDate = $this->getGameDate($player);

        // Remove player from alliance
        $player->update([
            'alliance_id' => 0,
            'alliance_member_type' => 0,
        ]);

        // Update alliance news
        $alliance->update([
            'news' => $alliance->news . "\n{$gameDate}: {$player->name} (#{$player->id}) left your alliance",
        ]);

        // Notify remaining members
        Player::where('alliance_id', $alliance->id)
            ->update(['has_alliance_news' => true]);

        session()->flash('game_message', "You left '{$allianceTag}' alliance.");
        return redirect()->route('game.alliance');
    }

    /**
     * Change alliance relations (ally/war lists). Leader only.
     * Ported from eflag_alliance.cfm eflag=change_relations
     *
     * Route: POST /alliance/relations -> game.alliance.relations
     */
    public function changeRelations(Request $request)
    {
        $player = player();
        $alliance = Alliance::find($player->alliance_id);

        if (!$alliance || $alliance->leader_id != $player->id) {
            return redirect()->route('game.alliance');
        }

        // Collect new ally/war IDs from form
        $newAllies = [];
        $newWars = [];
        for ($i = 1; $i <= 5; $i++) {
            $newAllies[$i] = (int) $request->input("n_ally{$i}", 0);
            $newWars[$i] = (int) $request->input("n_war{$i}", 0);
        }

        // Validate: cannot add own alliance to ally or war list
        $ownId = $alliance->id;
        foreach ($newAllies as $id) {
            if ($id == $ownId) {
                session()->flash('game_message', 'Cannot add your own alliance to war or ally list.');
                return redirect()->route('game.alliance');
            }
        }
        foreach ($newWars as $id) {
            if ($id == $ownId) {
                session()->flash('game_message', 'Cannot add your own alliance to war or ally list.');
                return redirect()->route('game.alliance');
            }
        }

        $gameDate = $this->getGameDate($player);
        $theNews = $alliance->news;
        $myTag = $alliance->tag;
        $changedRelations = false;

        // Save original values before update
        $origAllies = [];
        $origWars = [];
        for ($i = 1; $i <= 5; $i++) {
            $origAllies[$i] = $alliance->{"ally{$i}"} ?? 0;
            $origWars[$i] = $alliance->{"war{$i}"} ?? 0;
        }

        // Update the alliance relations in DB
        $alliance->update([
            'ally1' => $newAllies[1],
            'ally2' => $newAllies[2],
            'ally3' => $newAllies[3],
            'ally4' => $newAllies[4],
            'ally5' => $newAllies[5],
            'war1' => $newWars[1],
            'war2' => $newWars[2],
            'war3' => $newWars[3],
            'war4' => $newWars[4],
            'war5' => $newWars[5],
        ]);

        // Process ally and war changes - notify affected alliances
        for ($i = 1; $i <= 5; $i++) {
            // Process ally changes
            $oldAlly = $origAllies[$i];
            $newAlly = $newAllies[$i];

            if ($oldAlly != $newAlly) {
                $changedRelations = true;

                if ($oldAlly > 0) {
                    $oldAllyAlliance = Alliance::find($oldAlly);
                    if ($oldAllyAlliance) {
                        $oldAllyAlliance->update([
                            'news' => $oldAllyAlliance->news . "\n{$gameDate}: {$myTag} removed you from their ally list",
                        ]);
                        Player::where('alliance_id', $oldAlly)
                            ->update(['has_alliance_news' => true]);
                        $theNews .= "\n{$gameDate}: {$oldAllyAlliance->tag} has been removed from your ally list";
                    }
                }

                if ($newAlly > 0) {
                    $newAllyAlliance = Alliance::find($newAlly);
                    if ($newAllyAlliance) {
                        $newAllyAlliance->update([
                            'news' => $newAllyAlliance->news . "\n{$gameDate}: {$myTag} put you on their ally list",
                        ]);
                        Player::where('alliance_id', $newAlly)
                            ->update(['has_alliance_news' => true]);
                        $theNews .= "\n{$gameDate}: {$newAllyAlliance->tag} has been put on your ally list";
                    }
                }
            }

            // Process war changes
            $oldWar = $origWars[$i];
            $newWar = $newWars[$i];

            if ($oldWar != $newWar) {
                $changedRelations = true;

                if ($oldWar > 0) {
                    $oldWarAlliance = Alliance::find($oldWar);
                    if ($oldWarAlliance) {
                        $oldWarAlliance->update([
                            'news' => $oldWarAlliance->news . "\n{$gameDate}: {$myTag} removed you from their war list",
                        ]);
                        Player::where('alliance_id', $oldWar)
                            ->update(['has_alliance_news' => true]);
                        $theNews .= "\n{$gameDate}: {$oldWarAlliance->tag} has been removed from your war list";
                    }
                }

                if ($newWar > 0) {
                    $newWarAlliance = Alliance::find($newWar);
                    if ($newWarAlliance) {
                        $newWarAlliance->update([
                            'news' => $newWarAlliance->news . "\n{$gameDate}: {$myTag} put you on their war list",
                        ]);
                        Player::where('alliance_id', $newWar)
                            ->update(['has_alliance_news' => true]);
                        $theNews .= "\n{$gameDate}: {$newWarAlliance->tag} has been added to your war list";
                    }
                }
            }
        }

        // Update own alliance news if relations changed
        if ($changedRelations) {
            $alliance->update(['news' => $theNews]);
            Player::where('alliance_id', $alliance->id)
                ->update(['has_alliance_news' => true]);
        }

        return redirect()->route('game.alliance');
    }

    /**
     * Update alliance news. Leader only.
     * Ported from eflag_alliance.cfm eflag=change_news
     *
     * Route: POST /alliance/news -> game.alliance.news
     */
    public function changeNews(Request $request)
    {
        $player = player();
        $alliance = Alliance::find($player->alliance_id);

        if (!$alliance || $alliance->leader_id != $player->id) {
            return redirect()->route('game.alliance');
        }

        $alliance->update([
            'news' => $request->input('news', ''),
        ]);

        // Notify members
        Player::where('alliance_id', $player->alliance_id)
            ->where('id', '<>', $player->id)
            ->update(['has_alliance_news' => true]);

        return redirect()->route('game.alliance');
    }

    /**
     * Change alliance password. Leader only.
     * Ported from eflag_alliance.cfm eflag=change_password
     *
     * Route: POST /alliance/password -> game.alliance.password
     */
    public function changePassword(Request $request)
    {
        $player = player();
        $alliance = Alliance::find($player->alliance_id);

        if (!$alliance || $alliance->leader_id != $player->id) {
            return redirect()->route('game.alliance');
        }

        $alliance->update([
            'passwd' => $request->input('password', ''),
        ]);

        return redirect()->route('game.alliance');
    }

    /**
     * Disband the alliance. Leader only.
     * Ported from eflag_alliance.cfm eflag=finish_alliance
     *
     * Route: POST /alliance/disband -> game.alliance.disband
     */
    public function disbandAlliance()
    {
        $player = player();
        $alliance = Alliance::find($player->alliance_id);

        if (!$alliance || $alliance->leader_id != $player->id) {
            return redirect()->route('game.alliance');
        }

        $allianceId = $alliance->id;

        // Remove all members from the alliance
        Player::where('alliance_id', $allianceId)
            ->update(['alliance_id' => 0, 'alliance_member_type' => 0]);

        // Clear references from other alliances' ally/war lists
        foreach (['ally1', 'ally2', 'ally3', 'ally4', 'ally5', 'war1', 'war2', 'war3', 'war4', 'war5'] as $field) {
            Alliance::where($field, $allianceId)->update([$field => 0]);
        }

        // Delete the alliance
        $alliance->delete();

        session()->flash('game_message', 'Alliance has been disbanded.');
        return redirect()->route('game.alliance');
    }

    /**
     * Remove a member from the alliance. Leader only.
     * Ported from eflag_alliance.cfm eflag=remove_from_alliance
     *
     * Route: POST /alliance/remove/{id} -> game.alliance.remove
     */
    public function removeMember(int $id)
    {
        $player = player();
        $alliance = Alliance::find($player->alliance_id);

        if (!$alliance || $alliance->leader_id != $player->id) {
            return redirect()->route('game.alliance');
        }

        $removedPlayer = Player::find($id);

        if (!$removedPlayer || $removedPlayer->alliance_id != $player->alliance_id) {
            return redirect()->route('game.alliance');
        }

        $gameDate = $this->getGameDate($player);

        // Update alliance news
        $alliance->update([
            'news' => $alliance->news . "\n{$gameDate}: {$removedPlayer->name} (#{$id}) has been removed from your alliance",
        ]);

        // Notify members
        Player::where('alliance_id', $player->alliance_id)
            ->where('id', '<>', $player->id)
            ->update(['has_alliance_news' => true]);

        // Remove the player from the alliance
        $removedPlayer->update([
            'alliance_id' => 0,
            'alliance_member_type' => 0,
        ]);

        return redirect()->route('game.alliance');
    }

    /**
     * Toggle member trusted/starting status. Leader only.
     * Ported from eflag_alliance.cfm eflag=changeStatus
     *
     * Route: POST /alliance/toggle-status/{id} -> game.alliance.toggle-status
     */
    public function toggleMemberStatus(int $id)
    {
        $player = player();
        $alliance = Alliance::find($player->alliance_id);

        if (!$alliance || $alliance->leader_id != $player->id) {
            return redirect()->route('game.alliance');
        }

        $member = Player::find($id);

        if (!$member || $member->alliance_id != $player->alliance_id) {
            return redirect()->route('game.alliance');
        }

        // Toggle status: 1 -> 0, 0 -> 1
        $newStatus = $member->alliance_member_type == 1 ? 0 : 1;
        $member->update(['alliance_member_type' => $newStatus]);

        return redirect()->route('game.alliance');
    }

    /**
     * Transfer leadership to another member. Leader only.
     * Ported from eflag_alliance.cfm eflag=give_leadership
     *
     * Route: POST /alliance/give-leadership/{id} -> game.alliance.give-leadership
     */
    public function giveLeadership(int $id)
    {
        $player = player();
        $alliance = Alliance::find($player->alliance_id);

        if (!$alliance || $alliance->leader_id != $player->id) {
            return redirect()->route('game.alliance');
        }

        $newLeader = Player::find($id);

        if (!$newLeader || $newLeader->alliance_id != $player->alliance_id) {
            return redirect()->route('game.alliance');
        }

        $gameDate = $this->getGameDate($player);

        // Transfer leadership
        $alliance->update([
            'leader_id' => $id,
            'news' => $alliance->news . "\n{$gameDate}: {$newLeader->name} (#{$id}) is a new alliance leader",
        ]);

        // Make new leader trusted
        $newLeader->update(['alliance_member_type' => 1]);

        // Notify members
        Player::where('alliance_id', $alliance->id)
            ->update(['has_alliance_news' => true]);

        return redirect()->route('game.alliance');
    }

    /**
     * View a member's army. Leader only.
     * Ported from eflag_alliance.cfm eflag=viewArmy
     *
     * This is invoked via a form POST to one of the leader action routes.
     * Since the routes don't have a dedicated viewArmy route, we handle it
     * as a query parameter on the index GET: ?view_army={id}
     */
    public function viewArmy(int $memberId)
    {
        $player = player();
        $alliance = Alliance::find($player->alliance_id);

        if (!$alliance || $alliance->leader_id != $player->id) {
            return redirect()->route('game.alliance');
        }

        $member = Player::where('id', $memberId)
            ->where('alliance_id', $player->alliance_id)
            ->first();

        if (!$member) {
            return redirect()->route('game.alliance');
        }

        $uunitName = gameConfig('unique_units')[$member->civ] ?? 'Unique Unit';

        $message = "<b>{$member->name} (#{$member->id})</b><br>"
            . number_format($member->uunit) . " {$uunitName}<br>"
            . number_format($member->archers) . " archers<br>"
            . number_format($member->swordsman) . " swordsman<br>"
            . number_format($member->horseman) . " horseman<br>"
            . number_format($member->macemen) . " macemen<br>"
            . number_format($member->trained_peasants) . " trained peasants<br>"
            . number_format($member->tower) . " towers<br>"
            . number_format($member->catapults) . " catapults<br>"
            . number_format($member->thieves) . " thieves<br>";

        session()->flash('game_message', $message);
        return redirect()->route('game.alliance');
    }

    /**
     * Get the in-game date string for news entries.
     */
    protected function getGameDate(Player $player): string
    {
        $month = ($player->turn % 12) + 1;
        $year = intdiv($player->turn, 12) + 1000;
        return date('F', mktime(0, 0, 0, $month, 1)) . ' ' . $year;
    }
}
