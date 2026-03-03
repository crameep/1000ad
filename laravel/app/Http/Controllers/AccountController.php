<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Account Controller
 *
 * Handles account settings: change login name, change password, delete empire.
 * Ported from account.cfm, eflag_account.cfm
 *
 * Original: Andrew Deren, (C) AderSoftware 2000, 2001
 */
class AccountController extends Controller
{
    /**
     * Show the account options page.
     * Route: GET /game/account
     * Ported from account.cfm
     */
    public function index()
    {
        return view('pages.account');
    }

    /**
     * Change login name.
     * Route: POST /game/account/login
     * Ported from eflag_account.cfm eflag=change_login
     */
    public function changeLogin(Request $request)
    {
        $player = Auth::user();
        $newLogin = trim($request->input('newLogin', ''));

        if (empty($newLogin)) {
            session()->flash('game_message', 'Login name cannot be empty.');
            return redirect()->route('game.account');
        }

        // Check if another player has the same login name
        $exists = Player::where('login_name', $newLogin)
            ->where('id', '!=', $player->id)
            ->exists();

        if ($exists) {
            session()->flash('game_message', "Cannot change login name. <br>Another player is using '{$newLogin}'");
            return redirect()->route('game.account');
        }

        $player->update(['login_name' => $newLogin]);

        session()->flash('game_message', 'Login name change successful.');
        return redirect()->route('game.account');
    }

    /**
     * Change password.
     * Route: POST /game/account/password
     * Ported from eflag_account.cfm eflag=change_pw
     */
    public function changePassword(Request $request)
    {
        $player = Auth::user();

        $curPassword = $request->input('curPassword', '');
        $newPassword = $request->input('newPassword', '');
        $newPassword2 = $request->input('newPassword2', '');

        if (!Hash::check($curPassword, $player->password)) {
            session()->flash('game_message', 'Invalid current password entered.');
            return redirect()->route('game.account');
        }

        if ($newPassword !== $newPassword2) {
            session()->flash('game_message', 'Your verify password does not match your new password.');
            return redirect()->route('game.account');
        }

        if (empty($newPassword)) {
            session()->flash('game_message', 'New password cannot be empty.');
            return redirect()->route('game.account');
        }

        $player->update([
            'password' => Hash::make($newPassword),
        ]);

        session()->flash('game_message', 'Password change successful.');
        return redirect()->route('game.account');
    }

    /**
     * Delete empire.
     * Route: POST /game/account/delete
     * Ported from eflag_account.cfm eflag=delete_empire
     */
    public function deleteEmpire(Request $request)
    {
        $player = Auth::user();

        $lName = $request->input('lName', '');
        $curPassword = $request->input('curPassword', '');

        // Verify credentials
        if ($player->login_name !== $lName || !Hash::check($curPassword, $player->password)) {
            session()->flash('game_message', 'Invalid login name or password. <br>Account not deleted.');
            return redirect()->route('game.account');
        }

        // Check deathmatch
        $deathmatchMode = config('game.deathmatch_mode');
        $deathmatchStart = config('game.deathmatch_start')
            ? Carbon::parse(config('game.deathmatch_start'))
            : null;
        $deathmatchStarted = $deathmatchMode && $deathmatchStart && $deathmatchStart->isPast();

        if ($deathmatchStarted) {
            session()->flash('game_message', 'Cannot delete empires once deathmatch started.');
            return redirect()->route('game.account');
        }

        $playerId = $player->id;

        // Delete all related data
        $player->receivedMessages()->delete();
        $player->sentMessages()->delete();
        $player->blockedPlayers()->delete();
        $player->buildQueues()->delete();
        $player->trainQueues()->delete();
        $player->exploreQueues()->delete();
        $player->attackQueues()->delete();
        $player->autoLocalTrades()->delete();
        $player->loginEntries()->delete();

        // Logout first
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Delete the player
        Player::destroy($playerId);

        return redirect()->route('login')
            ->with('success', 'Your empire has been deleted.');
    }
}
