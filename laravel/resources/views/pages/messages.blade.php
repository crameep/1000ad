{{-- Messages page - ported from player_messages.cfm --}}
@extends('layouts.game')

@section('content')
<div class="page-title-bar">
    <h2>Messages</h2>
</div>

{{-- Folder tabs --}}
<div class="tab-bar">
    @foreach(['inbox' => 'Inbox', 'saved' => 'Saved', 'sent' => 'Sent', 'deleted' => 'Deleted', 'options' => 'Options'] as $folder => $label)
        @if($messageFolder === $folder)
            <a href="{{ route('game.messages', ['messageFolder' => $folder]) }}" class="tab active">{{ $label }}</a>
        @else
            <a href="{{ route('game.messages', ['messageFolder' => $folder]) }}" class="tab">{{ $label }}</a>
        @endif
    @endforeach
</div>

{{-- INBOX --}}
@if($messageFolder === 'inbox')
<br>
<table class="game-table">
<tr>
    <td class="bg-header"><a name="NEWMESSAGE">Send New Message</a></td>
</tr>
<form action="{{ route('game.messages.send') }}" method="POST" name="rForm" onsubmit="return checkForm(this)">
    @csrf
<tr>
    <td>Message To (Empire #):
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
    <br>
    <span class="text-sm">(You can separate multiple numbers with commas)</span>
    </td>
</tr>
<tr>
    <td><textarea name="pmessage" rows="6" cols="70" class="w-full"></textarea></td>
</tr>
<tr>
    <td align="center"><input type="submit" value="Send" style="width:100px;"></td>
</tr>
</form>
</table>

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

<br><br>

@if($messages->isEmpty())
    <span class="text-danger">You do not have any messages.</span>
@else
    <table class="game-table w-full">
    @foreach($messages as $idx => $msg)
    <tr>
        <td class="bg-header">
            @if(!$msg->viewed)<span style="color:aqua;"><b>NEW!</b></span>&nbsp;&nbsp;@endif
            Message from {{ $msg->from_player_name }} ({{ $msg->from_player_id }})
            sent on {{ $msg->created_on->format('m/d/Y') }} at {{ $msg->created_on->format('h:i A') }}
        </td>
    </tr>
    <tr>
        <td>
            {!! nl2br(e($msg->message)) !!}
            <br>
            <span class="text-sm">
                <a href="#NEWMESSAGE" onclick="replay({{ $msg->from_player_id }}, {{ $idx }})">Reply</a> |
                <form action="{{ route('game.messages.delete', $msg->id) }}" method="POST" class="inline-form">
                    @csrf
                    <a href="#" onclick="this.closest('form').submit(); return false;">Delete This Message</a>
                </form> |
                <form action="{{ route('game.messages', ['messageFolder' => 'inbox', 'eflag' => 'save']) }}" method="POST" class="inline-form">
                    @csrf
                    <input type="hidden" name="messageID" value="{{ $msg->id }}">
                    <a href="{{ route('game.messages', ['messageFolder' => 'inbox', 'eflag' => 'save_message', 'messageID' => $msg->id]) }}">Save This Message</a>
                </form> |
                <form action="{{ route('game.messages.block', $msg->from_player_id) }}" method="POST" class="inline-form">
                    @csrf
                    <a href="#" onclick="this.closest('form').submit(); return false;">Block Messages from {{ $msg->from_player_id }}</a>
                </form>
            </span>
        </td>
    </tr>
    <tr><td height="5" class="bg-header"></td></tr>
    @endforeach
    </table>

    <form action="{{ route('game.messages', ['messageFolder' => 'inbox', 'eflag' => 'delete_all']) }}" method="POST" class="inline-form">
        @csrf
        <input type="hidden" name="deleteAll" value="1">
        <a href="#" onclick="this.closest('form').submit(); return false;">Delete All Messages</a>
    </form>
@endif

{{-- SAVED --}}
@elseif($messageFolder === 'saved')
<br>
@if($messages->isEmpty())
    <span class="text-danger">You do not have any saved messages.</span>
@else
    <table class="game-table w-full">
    @foreach($messages as $msg)
    <tr>
        <td class="bg-header">
            Message from {{ $msg->from_player_name }} ({{ $msg->from_player_id }})
            sent on {{ $msg->created_on->format('m/d/Y') }} at {{ $msg->created_on->format('h:i A') }}
        </td>
    </tr>
    <tr>
        <td>
            {!! nl2br(e($msg->message)) !!}
            <br>
            <span class="text-sm">
                <form action="{{ route('game.messages.delete', $msg->id) }}" method="POST" class="inline-form">
                    @csrf
                    <input type="hidden" name="messageFolder" value="saved">
                    <a href="#" onclick="this.closest('form').submit(); return false;">Delete This Message</a>
                </form>
            </span>
        </td>
    </tr>
    <tr><td height="5" class="bg-header"></td></tr>
    @endforeach
    </table>

    <form action="{{ route('game.messages', ['messageFolder' => 'saved', 'eflag' => 'delete_all_saved']) }}" method="POST" class="inline-form">
        @csrf
        <input type="hidden" name="deleteAllSaved" value="1">
        <a href="#" onclick="this.closest('form').submit(); return false;">Delete All Saved Messages</a>
    </form>
@endif
<br><br>

{{-- SENT --}}
@elseif($messageFolder === 'sent')
@if($messages->isEmpty())
    <span class="text-danger">You do not have any sent messages.</span>
@else
    <br>
    <table class="game-table">
    <tr>
        <td class="header">Sent To</td>
        <td class="header">Date/Time</td>
        <td class="header">Received?</td>
    </tr>
    @foreach($messages as $msg)
    <tr>
        <td><a href="{{ route('game.messages', ['messageFolder' => 'viewMessage', 'messageID' => $msg->id]) }}">{{ $msg->to_player_name }} ({{ $msg->to_player_id }})</a></td>
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
    <span class="text-danger">You do not have any deleted messages.</span>
@else
    <br>
    <table class="game-table">
    <tr>
        <td class="header">Received From</td>
        <td class="header">Date/Time</td>
    </tr>
    @foreach($messages as $msg)
    <tr>
        <td><a href="{{ route('game.messages', ['messageFolder' => 'viewMessage', 'messageID' => $msg->id]) }}">{{ $msg->from_player_name }} ({{ $msg->from_player_id }})</a></td>
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
<br>
@if(!isset($message) || !$message)
    <span class="text-danger">Invalid Message.</span>
@else
    <table class="game-table w-full">
    <tr>
        <td class="bg-header">
            Message to {{ $message->to_player_name }} ({{ $message->to_player_id }})
            sent on {{ $message->created_on->format('m/d/Y') }} at {{ $message->created_on->format('h:i A') }}
        </td>
    </tr>
    <tr>
        <td>
            {!! nl2br(e($message->message)) !!}
            <br>
        </td>
    </tr>
    <tr><td height="5" class="bg-header"></td></tr>
    </table>
    <a href="{{ route('game.messages', ['messageFolder' => 'sent']) }}">Back...</a>
@endif

{{-- OPTIONS --}}
@elseif($messageFolder === 'options')
    <b>You do not wish to receive messages from the following empires:</b>
    <br>
    @forelse($blockedPlayers as $block)
        <li>{{ $block->name }} ({{ $block->player_id }}) -
            <form action="{{ route('game.messages', ['messageFolder' => 'options', 'eflag' => 'unblock']) }}" method="POST" class="inline-form">
                @csrf
                <input type="hidden" name="blockID" value="{{ $block->id }}">
                <a href="#" onclick="this.closest('form').submit(); return false;">Unblock</a>
            </form>
        </li>
    @empty
        None<br>
    @endforelse
    <br><br>

    <form action="{{ route('game.messages.block', 0) }}" method="POST" id="blockForm">
        @csrf
        <div class="form-panel" style="max-width:250px;">
            <div class="bg-header text-center"><b>Block Messages From Player</b></div>
            <div class="form-panel-body">
                Player #:
                <input type="text" name="blockID" value="0" size="3" id="blockInput">
                <input type="submit" value="Block" onclick="this.form.action='{{ route('game.messages.block', '') }}/' + document.getElementById('blockInput').value; return true;">
            </div>
        </div>
    </form>
@endif
@endsection
