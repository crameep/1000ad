@extends('layouts.auth')

@section('sidebar')
<div class="panel">
    <div class="panel-header">News</div>
    <div class="panel-body">
        <span style="color:yellow;">2025:</span> 1000 A.D. has been rewritten with modern technology.<br>
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
    <div class="panel-body" style="text-align:center;">

        @if($deathmatchStarted)
            This deathmatch game is already in progress.<br>
            You can join this game next time.<br>
        @else
            If you have your account created, login below.<br>
            If not, <a href="{{ route('register') }}"><b>Click here</b></a> to get your FREE account.
        @endif
        <br><br>

        @if($gameEnded)
            <span class="error"><b>This game has ended.</b></span>
        @else
            <table style="border:1px outset; background: darkslategray url('/images/map.jpg'); padding:10px; margin:0 auto;">
            <tr>
                <td colspan="2" style="text-align:center;">
                    <span style="font-size:24px; font-weight:bold;">{{ $gameName }}</span>
                    @if($deathmatchMode)
                        <br><a href="#">View Rules of Deathmatch</a>
                    @endif
                    <br><span style="font-size:10px;">
                        Game started: {{ $startDate->format('M. d, Y') }}<br>
                        Game Ends:
                        @if($deathmatchMode)
                            Until there is only one.
                        @else
                            {{ $endDate->format('M. d, Y') }}
                        @endif
                    </span>
                    <br><br>
                    <span style="color:yellow;">1 Turn every {{ $minutesPerTurn }} minutes.<br>Max turns stored: {{ $maxTurnsStored }}</span>
                </td>
            </tr>
            <form action="{{ route('login.submit') }}" method="POST">
                @csrf
                <tr>
                    <td>Login Name:</td>
                    <td><input type="text" name="login_name" value="{{ old('login_name') }}" autofocus></td>
                </tr>
                <tr>
                    <td>Password:</td>
                    <td><input type="password" name="password"></td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align:center;">
                        <input type="submit" value="Login">
                        @if(!$deathmatchStarted)
                            <br><a href="{{ route('register') }}"><b>or get your FREE account</b></a>
                        @endif
                    </td>
                </tr>
            </form>
            </table>
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
    <div class="panel-body" style="text-align:center;">
        <a href="{{ route('rankings', 'top10') }}">Top 10 Empires</a><br>
        @if(!$deathmatchMode && $allianceMaxMembers > 0)
            <a href="{{ route('rankings', 'alliance_by_score') }}">Top Alliances By Score</a><br>
            <a href="{{ route('rankings', 'alliance_by_avgscore') }}">Top Alliances By Avg. Score</a><br>
            <a href="{{ route('rankings', 'alliance_by_members') }}">Top Alliances By Num. Members</a><br>
        @endif
    </div>
</div>
@endsection
