@extends('layouts.auth')

@section('sidebar')
<div class="panel">
    <div class="panel-header">News</div>
    <div class="panel-body">
        <span class="text-warning">2025:</span> 1000 A.D. has been rewritten with modern technology.<br>
        <br>
        <span class="text-warning">New:</span> Multi-game support! Join multiple games with different speeds and rulesets.
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

        If you have your account created, login below.<br>
        If not, <a href="{{ route('register') }}"><b>Click here</b></a> to get your FREE account.
        <br><br>

        <div class="form-panel" style="margin:0 auto; background: url('/images/map.jpg') center/cover; border-color:var(--border-accent);">
            <div class="form-body text-center" style="padding:16px;">
                <h2 style="font-size:22px; margin-bottom:8px;">1000 A.D.</h2>
                <span class="text-small text-muted">
                    A free turn-based strategy game.<br>
                    Build your empire, raise armies, conquer lands.
                </span>
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
                    <div style="margin-bottom:8px;">
                        <label style="font-size:12px;">
                            <input type="checkbox" name="remember" value="1"> Remember me
                        </label>
                    </div>
                    <div>
                        <input type="submit" value="Login">
                        <br><br><a href="{{ route('register') }}"><b>or get your FREE account</b></a>
                    </div>
                </form>
            </div>
        </div>

        <br>
        Forgot your password?<br>
        <a href="{{ route('password.forgot') }}">Click here.</a>
        <br><br>
    </div>
</div>

{{-- Rankings --}}
<div class="panel">
    <div class="panel-header">Current Rankings</div>
    <div class="panel-body text-center">
        <a href="{{ route('rankings', 'top10') }}">Top 10 Empires</a><br>
        <a href="{{ route('rankings', 'alliance_by_score') }}">Top Alliances By Score</a><br>
    </div>
</div>
@endsection
