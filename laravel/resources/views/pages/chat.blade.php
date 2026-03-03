@extends('layouts.game')

@section('content')
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
    <td class="HEADER" align="center" width="100%"><b>Chat</b></td>
</tr>
</table>
<br>

<div style="border:1px solid darkslategray; padding:5px; height:400px; overflow-y:auto; background:#1a1a2e;" id="chatBox">
    @forelse($messages as $msg)
        <div style="margin-bottom:4px;">
            <span style="color:yellow; font-size:11px;">{{ $msg->player_name }} (#{{ $msg->player_id }})</span>
            <span style="color:#666; font-size:10px;">{{ $msg->created_at->format('H:i') }}</span><br>
            <span style="color:white; font-size:12px;">{{ $msg->message }}</span>
        </div>
    @empty
        <span style="color:gray;">No messages yet. Be the first to say something!</span>
    @endforelse
</div>

<br>
<form action="{{ route('game.chat.post') }}" method="POST">
    @csrf
    <table>
    <tr>
        <td>
            <input type="text" name="message" size="70" maxlength="500" placeholder="Type your message..." autofocus>
            <input type="submit" value="Send">
        </td>
    </tr>
    </table>
</form>

<script>
    // Auto-scroll to bottom
    var chatBox = document.getElementById('chatBox');
    chatBox.scrollTop = chatBox.scrollHeight;
</script>
@endsection
