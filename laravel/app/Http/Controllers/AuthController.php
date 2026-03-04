<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Authentication Controller
 *
 * Handles user authentication (login/register/logout).
 * Registration creates a User account only — empire/civ selection
 * happens when joining a game via the Lobby.
 *
 * Ported from login.cfm, createPlayer.cfm, forgotpassword.cfm
 */
class AuthController extends Controller
{
    /**
     * Show login form.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('lobby');
        }

        return view('auth.login');
    }

    /**
     * Handle login form submission.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login_name' => 'required|string|max:50',
            'password' => 'required|string',
        ]);

        $user = User::where('login_name', $request->login_name)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()
                ->withInput($request->only('login_name'))
                ->with('error', 'Invalid login name or password.');
        }

        Auth::login($user, $request->boolean('remember'));

        return redirect()->route('lobby');
    }

    /**
     * Handle logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Show registration form.
     * Registration only creates a User account — no empire/civ selection here.
     */
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('lobby');
        }

        return view('auth.register');
    }

    /**
     * Handle registration form submission.
     * Creates a User account only. Empire creation happens when joining a game.
     */
    public function register(Request $request)
    {
        $request->validate([
            'login_name' => 'required|string|max:50|unique:users,login_name',
            'password' => 'required|string|min:1|confirmed',
            'email' => 'required|email|max:50',
        ], [
            'login_name.unique' => 'That login name is already taken.',
        ]);

        User::create([
            'login_name' => $request->login_name,
            'password' => Hash::make($request->password),
            'email' => $request->email,
        ]);

        return redirect()->route('login')
            ->with('success', "Your account '{$request->login_name}' has been created. You can now login and join a game.");
    }

    /**
     * Show forgot password form.
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

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $newPassword = Str::random(10);
            $user->update([
                'password' => Hash::make($newPassword),
            ]);

            return back()->with('success', 'A new password has been generated. Check your email.');
        }

        return back()->with('error', 'No account found with that email address.');
    }
}
