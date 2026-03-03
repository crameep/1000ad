@extends('layouts.auth')

@section('sidebar')
<div class="panel">
    <div class="panel-header">Help</div>
    <div class="panel-body">
        Enter your email address and we'll send you a new password.<br>
        <br>
        <a href="{{ route('login') }}">Back to Login</a>
    </div>
</div>
@endsection

@section('content')
<div class="panel">
    <div class="panel-header">Forgot Password</div>
    <div class="panel-body text-center">
        <br>
        <form action="{{ route('password.forgot.submit') }}" method="POST">
            @csrf
            <div style="margin-bottom:8px;">
                <label>E-mail address:</label><br>
                <input type="email" name="email" maxlength="50" value="{{ old('email') }}" style="width:250px;">
            </div>
            <div>
                <input type="submit" value="Send New Password">
            </div>
        </form>
        <br>
    </div>
</div>
@endsection
