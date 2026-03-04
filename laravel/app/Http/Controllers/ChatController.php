<?php

namespace App\Http\Controllers;

use App\Models\ForumMessage;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index()
    {
        $player = player();

        // Get recent chat messages (last 100)
        $messages = ForumMessage::where('forum_id', 0) // 0 = global chat
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->reverse()
            ->values();

        return view('pages.chat', compact('messages'));
    }

    public function postMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $player = player();

        ForumMessage::create([
            'forum_id' => 0,
            'player_id' => $player->id,
            'player_name' => $player->name,
            'message' => strip_tags($request->input('message')),
        ]);

        return redirect()->route('game.chat');
    }
}
