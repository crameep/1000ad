{{-- Alliance page - ported from alliance.cfm --}}
@extends('layouts.game')

@section('content')
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
    <td class="header" align="center" width="92%"><b>Alliance</b></td>
    <td class="header" align="center" width="8%"><b><a href="javascript:openHelp('alliance')">Help</a></b></td>
</tr>
</table>

@if(!$hasAlliance)
    {{-- ============================================ --}}
    {{-- NO ALLIANCE: Show Join / Create forms --}}
    {{-- ============================================ --}}
    <br>
    <table border="1" cellpadding="1" cellspacing="1" bordercolor="darkslategray" width="250">
    <form action="{{ route('game.alliance.join') }}" method="POST">
        @csrf
        <tr>
            <td class="header">Join Alliance</td>
        </tr>
        <tr>
            <td nowrap>Alliance Tag:
                <select name="join_alliance_id">
                    <option value="0">--- Select One ---</option>
                    @foreach($alliances as $a)
                        <option value="{{ $a->id }}">{{ $a->tag }}</option>
                    @endforeach
                </select>
                <br>
                Password: &nbsp;&nbsp; <input type="text" name="password" size="20" maxlength="20">
                <center>
                <input type="submit" value="Join" style="width:100px">
            </td>
        </tr>
    </form>
    </table>

    <br><br>

    <table border="1" cellpadding="1" cellspacing="1" bordercolor="darkslategray" width="250">
    <form action="{{ route('game.alliance.create') }}" method="POST">
        @csrf
        <tr>
            <td class="header">Create New Alliance</td>
        </tr>
        <tr>
            <td nowrap>Alliance Tag:
                <input type="text" name="tag" size="20" maxlength="15">
                <br>
                Password: &nbsp;&nbsp; <input type="text" name="password" size="20" maxlength="20">
                <center>
                <input type="submit" value="Create Alliance" style="width:100px">
            </td>
        </tr>
    </form>
    </table>
    <br>

@else
    {{-- ============================================ --}}
    {{-- HAS ALLIANCE --}}
    {{-- ============================================ --}}
    <center><font size="4"><b>Alliance: {{ $alliance->tag }}</b></font></center>
    <br>

    @if($isLeader)
        {{-- ============================================ --}}
        {{-- LEADER VIEW: Editable relations --}}
        {{-- ============================================ --}}
        <table border="1" cellpadding="1" cellspacing="1" width="400" bordercolor="darkslategray">
        <tr>
            <td colspan="2" class="header" align="center">Alliance Relations</td>
        </tr>
        <form action="{{ route('game.alliance.relations') }}" method="POST">
            @csrf
            <tr>
                <td valign="top" width="50%" align="center"><b>Allies:</b><br>
                    @for($i = 1; $i <= 5; $i++)
                        @php $aID = $alliance->{"ally{$i}"} ?? 0; @endphp
                        <select name="n_ally{{ $i }}">
                            <option value="0">--- None ---</option>
                            @foreach($otherAlliances as $oa)
                                <option value="{{ $oa->id }}" @if($oa->id == $aID) selected @endif>{{ $oa->tag }}</option>
                            @endforeach
                        </select>
                        <br>
                    @endfor
                </td>
                <td valign="top" width="50%" align="center"><b>War:</b><br>
                    @for($i = 1; $i <= 5; $i++)
                        @php $wID = $alliance->{"war{$i}"} ?? 0; @endphp
                        <select name="n_war{{ $i }}">
                            <option value="0">--- None ---</option>
                            @foreach($otherAlliances as $oa)
                                <option value="{{ $oa->id }}" @if($oa->id == $wID) selected @endif>{{ $oa->tag }}</option>
                            @endforeach
                        </select>
                        <br>
                    @endfor
                </td>
            </tr>
            <tr>
                <td valign="top">
                    <b>Alliances that have your alliance on the ally list:</b><br>
                    @forelse($alliedBy as $tag)
                        {{ $tag }}<br>
                    @empty
                        None
                    @endforelse
                </td>
                <td valign="top">
                    <b>Alliances that have your alliance on the war list:</b><br>
                    @forelse($warredBy as $tag)
                        {{ $tag }}<br>
                    @empty
                        None
                    @endforelse
                </td>
            </tr>
            <tr>
                <td colspan="2" align="center"><input type="submit" value="Change Relations"></td>
            </tr>
        </form>
        </table>

        <br>

        {{-- Alliance News (editable) --}}
        <table border="1" cellpadding="1" cellspacing="1" width="400" bordercolor="darkslategray">
        <form action="{{ route('game.alliance.news') }}" method="POST">
            @csrf
            <tr>
                <td class="header" align="center">Alliance News:</td>
            </tr>
            <tr>
                <td><textarea name="news" rows="5" cols="45">{{ $alliance->news }}</textarea></td>
            </tr>
            <tr>
                <td align="center"><input type="submit" value="Update News"></td>
            </tr>
        </form>
        </table>

        <br>

        {{-- Leader Options: Change Password --}}
        <table border="1" cellpadding="1" cellspacing="1" width="400" bordercolor="darkslategray">
        <form action="{{ route('game.alliance.password') }}" method="POST">
            @csrf
            <tr>
                <td class="header" align="center">Leader Options:</td>
            </tr>
            <tr>
                <td>
                    Change alliance password to
                    <input type="text" value="{{ $alliance->passwd }}" name="password" size="10" maxlength="20">
                    <input type="submit" value="Change">
                </td>
            </tr>
        </form>
        </table>

        <br>

        {{-- Disband Alliance --}}
        <form action="{{ route('game.alliance.disband') }}" method="POST" onsubmit="return confirm('Are you sure you want to disband this alliance?')">
            @csrf
            <input type="submit" value="Disband Alliance">
        </form>

    @else
        {{-- ============================================ --}}
        {{-- MEMBER VIEW: Read-only relations --}}
        {{-- ============================================ --}}
        <table border="1" cellpadding="1" cellspacing="1" width="400" bordercolor="darkslategray">
        <tr>
            <td colspan="2" class="header" align="center">Alliance Relations</td>
        </tr>
        <tr>
            <td valign="top" width="50%"><b>Allies:</b><br>
                @forelse($allyTags as $tag)
                    {{ $tag }}<br>
                @empty
                    No Allies
                @endforelse
            </td>
            <td valign="top" width="50%"><b>War:</b><br>
                @forelse($warTags as $tag)
                    {{ $tag }}<br>
                @empty
                    No War
                @endforelse
            </td>
        </tr>
        <tr>
            <td valign="top">
                <b>Alliances that have your alliance on the ally list:</b><br>
                @forelse($alliedBy as $tag)
                    {{ $tag }}<br>
                @empty
                    None
                @endforelse
            </td>
            <td valign="top">
                <b>Alliances that have your alliance on the war list:</b><br>
                @forelse($warredBy as $tag)
                    {{ $tag }}<br>
                @empty
                    None
                @endforelse
            </td>
        </tr>
        </table>

        <br>

        {{-- Alliance News (read-only) --}}
        <table border="1" cellpadding="1" cellspacing="1" width="400" bordercolor="darkslategray">
        <tr>
            <td colspan="2" class="header" align="center">Alliance News:</td>
        </tr>
        <tr>
            <td>
                @if(trim($alliance->news) === '')
                    No Alliance News
                @else
                    {!! nl2br(e($alliance->news)) !!}
                @endif
            </td>
        </tr>
        </table>

        <br><br>

        {{-- Leave Alliance --}}
        <form action="{{ route('game.alliance.leave') }}" method="POST" onsubmit="return confirm('Are you sure you want to leave this alliance?')">
            @csrf
            <input type="submit" value="Leave This Alliance">
        </form>
    @endif

    {{-- ============================================ --}}
    {{-- MEMBER LIST (shown for both leader and member) --}}
    {{-- ============================================ --}}
    <br>
    <table border="1" cellpadding="1" cellspacing="1" width="400" bordercolor="darkslategray">
    <tr>
        <td colspan="2" class="header" align="center">Alliance Members:</td>
    </tr>
    <tr><td align="center">
        @foreach($members as $member)
            @if($member->alliance_member_type == 1)<b><u>@endif
            {{ $member->name }} (#{{ $member->id }})
            @if($member->alliance_member_type == 1)</u></b>@endif

            @if($member->id == $alliance->leader_id)
                <font color="red"><b>&nbsp;&nbsp;&nbsp;Alliance Leader</b></font>
            @endif
            <br>

            Rank: {{ $member->rank }}<br>
            Score: {{ number_format($member->score) }}<br>
            Land: {{ number_format($member->total_land) }}<br>

            @if($player->alliance_member_type == 1 || $player->id == $alliance->leader_id)
                Army: {{ number_format($member->total_army) }}<br>
                <font color="yellow" size="1">
                @if(!$member->last_load)
                    <font color="red">Never Played</font>
                @else
                    @php
                        $totalMinutes = (int) $member->last_load->diffInMinutes(now());
                        $hours = intdiv($totalMinutes, 60);
                        $minutes = $totalMinutes - ($hours * 60);
                    @endphp
                    @if($hours == 0 && $minutes <= 10)
                        <font color="red">* Online Now</font>
                    @else
                        Last played: @if($hours > 0){{ $hours }} hours and @endif {{ $minutes }} minutes ago.
                    @endif
                    <br>
                @endif
                </font>

                @if($member->id != $player->id && $player->id == $alliance->leader_id)
                    <font size="1">
                    <a href="{{ route('game.search') }}?empireNo={{ $member->id }}&searchType=empireNo">View Army</a>
                    <br>
                    <form action="{{ route('game.alliance.toggle-status', $member->id) }}" method="POST" style="display:inline;">
                        @csrf
                        <a href="#" onclick="this.closest('form').submit(); return false;">
                            @if($member->alliance_member_type == 1)
                                Change to Starting Member
                            @else
                                Change to Trusted Member
                            @endif
                        </a>
                    </form>
                    <br>
                    <form action="{{ route('game.alliance.remove', $member->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Remove {{ e($member->name) }} from the alliance?')">
                        @csrf
                        <a href="#" onclick="this.closest('form').submit(); return false;">Remove From Alliance</a>
                    </form>
                    <br>
                    <form action="{{ route('game.alliance.give-leadership', $member->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Give leadership to {{ e($member->name) }}?')">
                        @csrf
                        <a href="#" onclick="this.closest('form').submit(); return false;">Give Leadership</a>
                    </form>
                    <br>
                    </font>
                @endif
            @endif

            @if(!$loop->last)
                <hr noshade size="2" color="darkslategray">
            @endif
        @endforeach
    </td></tr>
    </table>
    <br>
@endif
@endsection
