@extends('layouts.game')

@section('content')
<div class="page-title-bar">
    <h2>Chat</h2>
</div>
<br>

<div class="chat-box" id="chatBox">
    @forelse($messages as $msg)
        <div class="chat-message">
            <span class="chat-name">{{ $msg->player_name }} (#{{ $msg->player_id }})</span>
            <span class="chat-time">{{ $msg->created_at->format('H:i') }}</span><br>
            <span class="chat-text">{{ $msg->message }}</span>
        </div>
    @empty
        <span class="chat-empty">No messages yet. Be the first to say something!</span>
    @endforelse
</div>

<br>
<form action="{{ route('game.chat.post') }}" method="POST" class="chat-form">
    @csrf
    <input type="text" name="message" maxlength="500" placeholder="Type your message..." autofocus class="chat-input">
    <input type="submit" value="Send">
</form>

<script>
    // Auto-scroll to bottom
    var chatBox = document.getElementById('chatBox');
    chatBox.scrollTop = chatBox.scrollHeight;
</script>
@endsection
