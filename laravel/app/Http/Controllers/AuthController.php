<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Services\ScoreService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Authentication Controller
 *
 * Ported from login.cfm, createPlayer.cfm, forgotpassword.cfm
 */
class AuthController extends Controller
{
    /**
     * Show login form.
     * Ported from login.cfm
     */
    public function showLogin()
    {
        $endDate = Carbon::parse(config('game.end_date'));
        $startDate = Carbon::parse(config('game.start_date'));
        $deathmatchMode = config('game.deathmatch_mode');
        $deathmatchStart = config('game.deathmatch_start')
            ? Carbon::parse(config('game.deathmatch_start'))
            : null;
        $deathmatchStarted = $deathmatchMode && $deathmatchStart && $deathmatchStart->isPast();

        return view('auth.login', [
            'gameName' => config('game.name'),
            'gameEnded' => $endDate->isPast(),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'deathmatchMode' => $deathmatchMode,
            'deathmatchStarted' => $deathmatchStarted,
            'deathmatchStart' => $deathmatchStart,
            'minutesPerTurn' => config('game.minutes_per_turn'),
            'maxTurnsStored' => config('game.max_turns_stored'),
            'allianceMaxMembers' => config('game.alliance_max_members'),
        ]);
    }

    /**
     * Handle login form submission.
     * Ported from login.cfm eflag=login
     */
    public function login(Request $request)
    {
        $request->validate([
            'login_name' => 'required|string|max:50',
            'password' => 'required|string',
        ]);

        $endDate = Carbon::parse(config('game.end_date'));
        if ($endDate->isPast()) {
            return back()->with('error', 'Sorry but this game has ended.');
        }

        $player = Player::where('login_name', $request->login_name)->first();

        if (!$player || !Hash::check($request->password, $player->password)) {
            return back()->with('error', 'Invalid login name or password.');
        }

        Auth::login($player);

        // Log the login
        $player->loginEntries()->create([
            'created_on' => now(),
            'ip_address' => substr($request->ip(), 0, 20),
            'http_referer' => substr($request->header('referer', ''), 0, 50),
            'http_user_agent' => substr($request->userAgent() ?? '', 0, 50),
        ]);

        return redirect()->route('game.main');
    }

    /**
     * Handle logout.
     * Ported from login.cfm eflag=logout
     */
    public function logout(Request $request)
    {
        if (Auth::check()) {
            $player = Auth::user();
            $player->update([
                'last_load' => now()->subMinutes(10),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Show registration form.
     * Ported from createPlayer.cfm
     */
    public function showRegister()
    {
        $deathmatchMode = config('game.deathmatch_mode');
        $deathmatchStart = config('game.deathmatch_start')
            ? Carbon::parse(config('game.deathmatch_start'))
            : null;
        $deathmatchStarted = $deathmatchMode && $deathmatchStart && $deathmatchStart->isPast();

        return view('auth.register', [
            'empires' => config('game.empires'),
            'uniqueUnits' => config('game.unique_units'),
            'deathmatchStarted' => $deathmatchStarted,
        ]);
    }

    /**
     * Handle registration form submission.
     * Ported from createPlayer.cfm eflag=createPlayer
     */
    public function register(Request $request, ScoreService $scoreService)
    {
        $deathmatchMode = config('game.deathmatch_mode');
        $deathmatchStart = config('game.deathmatch_start')
            ? Carbon::parse(config('game.deathmatch_start'))
            : null;
        $deathmatchStarted = $deathmatchMode && $deathmatchStart && $deathmatchStart->isPast();

        if ($deathmatchStarted) {
            return back()->with('error', 'This deathmatch game is already in progress.');
        }

        $request->validate([
            'empire_name' => ['required', 'string', 'max:20', 'regex:/^[a-zA-Z0-9 _]+$/'],
            'login_name' => 'required|string|max:50|unique:players,login_name',
            'password' => 'required|string|min:1|confirmed',
            'email' => 'required|email|max:50',
            'civ' => 'required|integer|between:1,6',
        ], [
            'empire_name.regex' => 'Empire name can only contain spaces and alpha-numeric characters.',
            'login_name.unique' => 'Login name already exists.',
        ]);

        // Check empire name uniqueness
        if (Player::where('name', $request->empire_name)->exists()) {
            return back()
                ->withInput()
                ->with('error', 'Empire with that name already exists.');
        }

        // Calculate starting turns
        $startDate = Carbon::parse(config('game.start_date'));
        $minutesPerTurn = config('game.minutes_per_turn');
        $maxTurnsStored = config('game.max_turns_stored');
        $startTurns = config('game.start_turns');

        $extraMinutes = $startDate->diffInMinutes(now());
        $extraTurns = intdiv((int) $extraMinutes, $minutesPerTurn);
        $numTurns = min($startTurns + $extraTurns, $maxTurnsStored);

        $defaults = config('game.new_player');
        $validationCode = Str::uuid()->toString();

        $player = Player::create([
            'name' => $request->empire_name,
            'login_name' => $request->login_name,
            'password' => Hash::make($request->password),
            'email' => $request->email,
            'civ' => $request->civ,
            'food_ratio' => $defaults['food_ratio'],

            // Buildings
            'tool_maker' => $defaults['tool_maker'],
            'wood_cutter' => $defaults['wood_cutter'],
            'gold_mine' => $defaults['gold_mine'],
            'hunter' => $defaults['hunter'],
            'tower' => $defaults['tower'],
            'town_center' => $defaults['town_center'],
            'market' => $defaults['market'],
            'iron_mine' => $defaults['iron_mine'],
            'house' => $defaults['house'],
            'farmer' => $defaults['farmer'],

            // Land
            'fland' => $defaults['fland'],
            'mland' => $defaults['mland'],
            'pland' => $defaults['pland'],

            // Military
            'swordsman' => $defaults['swordsman'],
            'archers' => $defaults['archers'],
            'horseman' => $defaults['horseman'],

            // Resources
            'people' => $defaults['people'],
            'wood' => $defaults['wood'],
            'food' => $defaults['food'],
            'iron' => $defaults['iron'],
            'gold' => $defaults['gold'],
            'tools' => $defaults['tools'],

            // Building statuses (all 100% operational)
            'hunter_status' => 100,
            'farmer_status' => 100,
            'iron_mine_status' => 100,
            'gold_mine_status' => 100,
            'tool_maker_status' => 100,
            'weapon_smith_status' => 100,
            'stable_status' => 100,
            'wood_cutter_status' => 100,
            'mage_tower_status' => 100,
            'winery_status' => 100,

            // Turn tracking
            'turn' => 0,
            'last_turn' => now(),
            'turns_free' => $numTurns,
            'created_on' => now(),
            'validation_code' => $validationCode,

            'message' => 'Thank you for playing 1000 A.D.<br>View Help / Docs section for information on how to play this game.',
        ]);

        // Calculate initial score
        $scoreService->calculateScore($player);

        return redirect()->route('login')
            ->with('success', "Your empire '{$request->empire_name}' (#{$player->id}) has been created. You can now login.");
    }

    /**
     * Show forgot password form.
     * Ported from forgotpassword.cfm
     */
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle forgot password.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $player = Player::where('email', $request->email)->first();

        if ($player) {
            $newPassword = Str::random(10);
            $player->update([
                'password' => Hash::make($newPassword),
            ]);

            // In production, send email with new password
            // For now, flash it as a message
            return back()->with('success', "A new password has been generated. Check your email.");
        }

        return back()->with('error', 'No account found with that email address.');
    }
}
