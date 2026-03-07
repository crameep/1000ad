{{-- Messages page - ported from player_messages.cfm --}}
@extends('layouts.game')

@section('content')
<div class="page-title-bar">
    <h2>Messages</h2>
</div>

{{-- Folder tabs --}}
<div class="tab-bar">
    @foreach(['inbox' => 'Inbox', 'saved' => 'Saved', 'sent' => 'Sent', 'deleted' => 'Deleted', 'options' => 'Options'] as $folder => $label)
        <a href="{{ route('game.messages', ['folder' => $folder]) }}" class="tab {{ $messageFolder === $folder ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
</div>

{{-- INBOX --}}
@if($messageFolder === 'inbox')

<div class="form-panel">
<form action="{{ route('game.messages.send') }}" method="POST" name="rForm" onsubmit="return checkForm(this)">
    @csrf
    <div class="form-header"><a name="NEWMESSAGE">Send New Message</a></div>
    <div class="form-body">
        Message To (Empire #):
        <input type="text" size="15" name="toPlayerID" value="{{ $menuPlayerID }}">
        @if($player->alliance_id > 0 && $allianceMembers->isNotEmpty())
        <select name="ql" onchange="quickLookup(this)">
            <option value="">--- Quick Lookup ---</option>
            <option value="{{ $allianceList }}">All Alliance Members</option>
            @foreach($allianceMembers as $member)
                <option value="{{ $member->id }}">{{ $member->name }} (#{{ $member->id }})</option>
            @endforeach
            <option value="">--------------------------------</option>
        </select>
        @endif
        <div class="text-sm">(You can separate multiple numbers with commas)</div>
        <textarea name="pmessage" rows="6" cols="70" class="w-full"></textarea>
    </div>
    <div class="form-footer"><input type="submit" value="Send"></div>
</form>
</div>

<script>
function quickLookup(s) {
    document.rForm.toPlayerID.value = s.options[s.selectedIndex].value;
}
function checkForm(form) {
    var temp = form.pmessage.value;
    if (temp.length > 5000) {
        alert("Your message exceeds allowable 5000 characters!");
        return false;
    }
    return true;
}
document.rForm.pmessage.focus();

@if($messages->isNotEmpty())
var messages = [];
@foreach($messages as $idx => $msg)
messages[{{ $idx }}] = "> {!! addslashes(str_replace(["\r\n", "\n", "\r"], '\n> ', e($msg->message))) !!}";
@endforeach
function replay(pid, mid) {
    var form = document.rForm;
    form.toPlayerID.value = pid;
    form.pmessage.value = messages[mid];
    window.location.hash = 'NEWMESSAGE';
}
@endif
</script>

@if($messages->isEmpty())
    <span class="text-error">You do not have any messages.</span>
@else
    @foreach($messages as $idx => $msg)
    <div class="msg-card">
        <div class="msg-card-header">
            @if(!$msg->viewed)<span class="msg-new-badge">NEW!</span>@endif
            <span>Message from {{ $msg->from_player_name }} ({{ $msg->from_player_id }})</span>
            <span class="text-sm">{{ $msg->created_on->format('m/d/Y') }} at {{ $msg->created_on->format('h:i A') }}</span>
        </div>
        <div class="msg-card-body">{!! nl2br(e($msg->message)) !!}</div>
        <div class="msg-card-actions">
            <a href="#NEWMESSAGE" onclick="replay({{ $msg->from_player_id }}, {{ $idx }})">Reply</a>
            <form action="{{ route('game.messages.delete', $msg->id) }}" method="POST" class="inline-form">@csrf <a href="#" onclick="this.closest('form').submit(); return false;">Delete</a></form>
            <form action="{{ route('game.messages.save', $msg->id) }}" method="POST" class="inline-form">@csrf <a href="#" onclick="this.closest('form').submit(); return false;">Save</a></form>
            <form action="{{ route('game.messages.block', $msg->from_player_id) }}" method="POST" class="inline-form">@csrf <a href="#" onclick="this.closest('form').submit(); return false;">Block {{ $msg->from_player_id }}</a></form>
        </div>
    </div>
    @endforeach

    <form action="{{ route('game.messages.delete-all') }}" method="POST" class="inline-form">
        @csrf
        <a href="#" onclick="this.closest('form').submit(); return false;">Delete All Messages</a>
    </form>
@endif

{{-- SAVED --}}
@elseif($messageFolder === 'saved')

@if($messages->isEmpty())
    <span class="text-error">You do not have any saved messages.</span>
@else
    @foreach($messages as $msg)
    <div class="msg-card">
        <div class="msg-card-header">
            <span>Message from {{ $msg->from_player_name }} ({{ $msg->from_player_id }})</span>
            <span class="text-sm">{{ $msg->created_on->format('m/d/Y') }} at {{ $msg->created_on->format('h:i A') }}</span>
        </div>
        <div class="msg-card-body">{!! nl2br(e($msg->message)) !!}</div>
        <div class="msg-card-actions">
            <form action="{{ route('game.messages.delete', $msg->id) }}" method="POST" class="inline-form">
                @csrf
                <a href="#" onclick="this.closest('form').submit(); return false;">Delete</a>
            </form>
        </div>
    </div>
    @endforeach

    <form action="{{ route('game.messages.delete-all-saved') }}" method="POST" class="inline-form">
        @csrf
        <a href="#" onclick="this.closest('form').submit(); return false;">Delete All Saved Messages</a>
    </form>
@endif

{{-- SENT --}}
@elseif($messageFolder === 'sent')
@if($messages->isEmpty())
    <span class="text-error">You do not have any sent messages.</span>
@else
    <table class="game-table">
    <tr>
        <td class="header">Sent To</td>
        <td class="header">Date/Time</td>
        <td class="header">Received?</td>
    </tr>
    @foreach($messages as $msg)
    <tr>
        <td><a href="{{ route('game.messages.view', $msg->id) }}">{{ $msg->to_player_name }} ({{ $msg->to_player_id }})</a></td>
        <td>{{ $msg->created_on->format('m/d/Y') }} at {{ $msg->created_on->format('h:i A') }}</td>
        <td>{{ $msg->viewed ? 'Yes' : 'No' }}</td>
    </tr>
    @endforeach
    </table>
    @if($messages->count() >= 250)
        Showing latest 250 sent messages...
    @endif
@endif

{{-- DELETED --}}
@elseif($messageFolder === 'deleted')
@if($messages->isEmpty())
    <span class="text-error">You do not have any deleted messages.</span>
@else
    <table class="game-table">
    <tr>
        <td class="header">Received From</td>
        <td class="header">Date/Time</td>
    </tr>
    @foreach($messages as $msg)
    <tr>
        <td><a href="{{ route('game.messages.view', $msg->id) }}">{{ $msg->from_player_name }} ({{ $msg->from_player_id }})</a></td>
        <td>{{ $msg->created_on->format('m/d/Y') }} at {{ $msg->created_on->format('h:i A') }}</td>
    </tr>
    @endforeach
    </table>
    @if($messages->count() >= 250)
        Showing latest 250 deleted messages...
    @endif
@endif

{{-- VIEW MESSAGE --}}
@elseif($messageFolder === 'viewMessage')

@if(!isset($message) || !$message)
    <span class="text-error">Invalid Message.</span>
@else
    <div class="msg-card">
        <div class="msg-card-header">
            Message from {{ $message->from_player_name }} ({{ $message->from_player_id }})
            to {{ $message->to_player_name }} ({{ $message->to_player_id }})
            <span class="text-sm">{{ $message->created_on->format('m/d/Y') }} at {{ $message->created_on->format('h:i A') }}</span>
        </div>
        <div class="msg-card-body">{!! nl2br(e($message->message)) !!}</div>
    </div>
    <a href="{{ route('game.messages', ['folder' => 'sent']) }}">Back to Sent</a>
@endif

{{-- OPTIONS --}}
@elseif($messageFolder === 'options')
    <b>You do not wish to receive messages from the following empires:</b>
    @forelse($blockedPlayers as $block)
        <li>{{ $block->name }} ({{ $block->player_id }}) -
            <form action="{{ route('game.messages.unblock', $block->id) }}" method="POST" class="inline-form">
                @csrf
                <a href="#" onclick="this.closest('form').submit(); return false;">Unblock</a>
            </form>
        </li>
    @empty
        None
    @endforelse

    <form action="{{ route('game.messages.block', 0) }}" method="POST" id="blockForm" onsubmit="this.action='/game/messages/block/' + document.getElementById('blockInput').value; return true;">
        @csrf
        <div class="form-panel">
            <div class="form-header">Block Messages From Player</div>
            <div class="form-body">
                Player #:
                <input type="text" name="blockID" value="" size="3" id="blockInput">
                <input type="submit" value="Block">
            </div>
        </div>
    </form>
@endif
@endsection
