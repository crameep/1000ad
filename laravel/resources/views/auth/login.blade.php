@extends('layouts.auth')

@section('sidebar')
<div class="panel">
    <div class="panel-header">News</div>
    <div class="panel-body">
        <span class="text-warning">2025:</span> 1000 A.D. has been rewritten with modern technology.<br>
    </div>
</div>

<div class="panel">
    <div class="panel-header">Docs</div>
    <div class="panel-body">
        <a href="/game/docs" target="_blank">1000 A.D. Documentation</a>
    </div>
</div>
@endsection

@section('content')
<div class="panel">
    <div class="panel-header">Welcome to 1000 A.D.</div>
    <div class="panel-body text-center">

        @if($deathmatchStarted)
            This deathmatch game is already in progress.<br>
            You can join this game next time.<br>
        @else
            If you have your account created, login below.<br>
            If not, <a href="{{ route('register') }}"><b>Click here</b></a> to get your FREE account.
        @endif
        <br><br>

        @if($gameEnded)
            <span class="text-error"><b>This game has ended.</b></span>
        @else
            <div class="form-panel" style="margin:0 auto; background: url('/images/map.jpg') center/cover; border-color:var(--border-accent);">
                <div class="form-body text-center" style="padding:16px;">
                    <h2 style="font-size:22px; margin-bottom:8px;">{{ $gameName }}</h2>
                    @if($deathmatchMode)
                        <a href="#">View Rules of Deathmatch</a><br>
                    @endif
                    <span class="text-small text-muted">
                        Game started: {{ $startDate->format('M. d, Y') }}<br>
                        Game Ends:
                        @if($deathmatchMode)
                            Until there is only one.
                        @else
                            {{ $endDate->format('M. d, Y') }}
                        @endif
                    </span>
                    <br><br>
                    <span class="text-warning">1 Turn every {{ $minutesPerTurn }} minutes.<br>Max turns stored: {{ $maxTurnsStored }}</span>
                    <br><br>

                    <form action="{{ route('login.submit') }}" method="POST">
                        @csrf
                        <div style="margin-bottom:8px;">
                            <label>Login Name:</label><br>
                            <input type="text" name="login_name" value="{{ old('login_name') }}" autofocus style="width:200px;">
                        </div>
                        <div style="margin-bottom:8px;">
                            <label>Password:</label><br>
                            <input type="password" name="password" style="width:200px;">
                        </div>
                        <div>
                            <input type="submit" value="Login">
                            @if(!$deathmatchStarted)
                                <br><br><a href="{{ route('register') }}"><b>or get your FREE account</b></a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <br>
        Forgot your password or empire name?<br>
        <a href="{{ route('password.forgot') }}">Click here.</a>
        <br><br>
    </div>
</div>

{{-- Rankings --}}
<div class="panel">
    <div class="panel-header">Current Rankings</div>
    <div class="panel-body text-center">
        <a href="{{ route('rankings', 'top10') }}">Top 10 Empires</a><br>
        @if(!$deathmatchMode && $allianceMaxMembers > 0)
            <a href="{{ route('rankings', 'alliance_by_score') }}">Top Alliances By Score</a><br>
            <a href="{{ route('rankings', 'alliance_by_avgscore') }}">Top Alliances By Avg. Score</a><br>
            <a href="{{ route('rankings', 'alliance_by_members') }}">Top Alliances By Num. Members</a><br>
        @endif
    </div>
</div>
@endsection
