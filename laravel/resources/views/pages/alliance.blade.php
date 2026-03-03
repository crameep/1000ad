{{-- Alliance page - ported from alliance.cfm --}}
@extends('layouts.game')

@section('content')
<div class="page-title-bar">
    <h2>Alliance</h2>
    <a href="javascript:openHelp('alliance')" class="help-link">Help</a>
</div>

@if(!$hasAlliance)
    {{-- ============================================ --}}
    {{-- NO ALLIANCE: Show Join / Create forms --}}
    {{-- ============================================ --}}
    <br>
    <div class="form-panel" style="max-width:250px;">
    <form action="{{ route('game.alliance.join') }}" method="POST">
        @csrf
        <div class="form-panel-header">Join Alliance</div>
        <div class="form-panel-body">
            Alliance Tag:
            <select name="join_alliance_id">
                <option value="0">--- Select One ---</option>
                @foreach($alliances as $a)
                    <option value="{{ $a->id }}">{{ $a->tag }}</option>
                @endforeach
            </select>
            <br>
            Password: &nbsp;&nbsp; <input type="text" name="password" size="20" maxlength="20">
            <div class="text-center">
            <input type="submit" value="Join" style="width:100px">
            </div>
        </div>
    </form>
    </div>

    <br><br>

    <div class="form-panel" style="max-width:250px;">
    <form action="{{ route('game.alliance.create') }}" method="POST">
        @csrf
        <div class="form-panel-header">Create New Alliance</div>
        <div class="form-panel-body">
            Alliance Tag:
            <input type="text" name="tag" size="20" maxlength="15">
            <br>
            Password: &nbsp;&nbsp; <input type="text" name="password" size="20" maxlength="20">
            <div class="text-center">
            <input type="submit" value="Create Alliance" style="width:100px">
            </div>
        </div>
    </form>
    </div>
    <br>

@else
    {{-- ============================================ --}}
    {{-- HAS ALLIANCE --}}
    {{-- ============================================ --}}
    <div class="text-center"><span class="text-lg"><b>Alliance: {{ $alliance->tag }}</b></span></div>
    <br>

    @if($isLeader)
        {{-- ============================================ --}}
        {{-- LEADER VIEW: Editable relations --}}
        {{-- ============================================ --}}
        <div class="form-panel" style="max-width:400px;">
        <div class="form-panel-header text-center">Alliance Relations</div>
        <form action="{{ route('game.alliance.relations') }}" method="POST">
            @csrf
            <div class="form-panel-body">
                <div style="display:flex; gap:1rem;">
                    <div style="flex:1;" class="text-center"><b>Allies:</b><br>
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
                    </div>
                    <div style="flex:1;" class="text-center"><b>War:</b><br>
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
                    </div>
                </div>
                <div style="display:flex; gap:1rem; margin-top:0.5rem;">
                    <div style="flex:1;">
                        <b>Alliances that have your alliance on the ally list:</b><br>
                        @forelse($alliedBy as $tag)
                            {{ $tag }}<br>
                        @empty
                            None
                        @endforelse
                    </div>
                    <div style="flex:1;">
                        <b>Alliances that have your alliance on the war list:</b><br>
                        @forelse($warredBy as $tag)
                            {{ $tag }}<br>
                        @empty
                            None
                        @endforelse
                    </div>
                </div>
                <div class="text-center" style="margin-top:0.5rem;">
                    <input type="submit" value="Change Relations">
                </div>
            </div>
        </form>
        </div>

        <br>

        {{-- Alliance News (editable) --}}
        <div class="form-panel" style="max-width:400px;">
        <form action="{{ route('game.alliance.news') }}" method="POST">
            @csrf
            <div class="form-panel-header text-center">Alliance News:</div>
            <div class="form-panel-body">
                <textarea name="news" rows="5" cols="45" class="w-full">{{ $alliance->news }}</textarea>
                <div class="text-center"><input type="submit" value="Update News"></div>
            </div>
        </form>
        </div>

        <br>

        {{-- Leader Options: Change Password --}}
        <div class="form-panel" style="max-width:400px;">
        <form action="{{ route('game.alliance.password') }}" method="POST">
            @csrf
            <div class="form-panel-header text-center">Leader Options:</div>
            <div class="form-panel-body">
                Change alliance password to
                <input type="text" value="{{ $alliance->passwd }}" name="password" size="10" maxlength="20">
                <input type="submit" value="Change">
            </div>
        </form>
        </div>

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
        <div class="form-panel" style="max-width:400px;">
        <div class="form-panel-header text-center">Alliance Relations</div>
        <div class="form-panel-body">
            <div style="display:flex; gap:1rem;">
                <div style="flex:1;"><b>Allies:</b><br>
                    @forelse($allyTags as $tag)
                        {{ $tag }}<br>
                    @empty
                        No Allies
                    @endforelse
                </div>
                <div style="flex:1;"><b>War:</b><br>
                    @forelse($warTags as $tag)
                        {{ $tag }}<br>
                    @empty
                        No War
                    @endforelse
                </div>
            </div>
            <div style="display:flex; gap:1rem; margin-top:0.5rem;">
                <div style="flex:1;">
                    <b>Alliances that have your alliance on the ally list:</b><br>
                    @forelse($alliedBy as $tag)
                        {{ $tag }}<br>
                    @empty
                        None
                    @endforelse
                </div>
                <div style="flex:1;">
                    <b>Alliances that have your alliance on the war list:</b><br>
                    @forelse($warredBy as $tag)
                        {{ $tag }}<br>
                    @empty
                        None
                    @endforelse
                </div>
            </div>
        </div>
        </div>

        <br>

        {{-- Alliance News (read-only) --}}
        <div class="form-panel" style="max-width:400px;">
        <div class="form-panel-header text-center">Alliance News:</div>
        <div class="form-panel-body">
            @if(trim($alliance->news) === '')
                No Alliance News
            @else
                {!! nl2br(e($alliance->news)) !!}
            @endif
        </div>
        </div>

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
    <table class="game-table" style="max-width:400px;">
    <tr>
        <td colspan="2" class="header" align="center">Alliance Members:</td>
    </tr>
    <tr><td align="center">
        @foreach($members as $member)
            @if($member->alliance_member_type == 1)<b><u>@endif
            {{ $member->name }} (#{{ $member->id }})
            @if($member->alliance_member_type == 1)</u></b>@endif

            @if($member->id == $alliance->leader_id)
                <span class="text-danger"><b>&nbsp;&nbsp;&nbsp;Alliance Leader</b></span>
            @endif
            <br>

            Rank: {{ $member->rank }}<br>
            Score: {{ number_format($member->score) }}<br>
            Land: {{ number_format($member->total_land) }}<br>

            @if($player->alliance_member_type == 1 || $player->id == $alliance->leader_id)
                Army: {{ number_format($member->total_army) }}<br>
                <span class="text-warning text-sm">
                @if(!$member->last_load)
                    <span class="text-danger">Never Played</span>
                @else
                    @php
                        $totalMinutes = (int) $member->last_load->diffInMinutes(now());
                        $hours = intdiv($totalMinutes, 60);
                        $minutes = $totalMinutes - ($hours * 60);
                    @endphp
                    @if($hours == 0 && $minutes <= 10)
                        <span class="text-danger">* Online Now</span>
                    @else
                        Last played: @if($hours > 0){{ $hours }} hours and @endif {{ $minutes }} minutes ago.
                    @endif
                    <br>
                @endif
                </span>

                @if($member->id != $player->id && $player->id == $alliance->leader_id)
                    <span class="text-sm">
                    <a href="{{ route('game.search') }}?empireNo={{ $member->id }}&searchType=empireNo">View Army</a>
                    <br>
                    <form action="{{ route('game.alliance.toggle-status', $member->id) }}" method="POST" class="inline-form">
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
                    <form action="{{ route('game.alliance.remove', $member->id) }}" method="POST" class="inline-form" onsubmit="return confirm('Remove {{ e($member->name) }} from the alliance?')">
                        @csrf
                        <a href="#" onclick="this.closest('form').submit(); return false;">Remove From Alliance</a>
                    </form>
                    <br>
                    <form action="{{ route('game.alliance.give-leadership', $member->id) }}" method="POST" class="inline-form" onsubmit="return confirm('Give leadership to {{ e($member->name) }}?')">
                        @csrf
                        <a href="#" onclick="this.closest('form').submit(); return false;">Give Leadership</a>
                    </form>
                    <br>
                    </span>
                @endif
            @endif

            @if(!$loop->last)
                <hr>
            @endif
        @endforeach
    </td></tr>
    </table>
    <br>
@endif
@endsection
