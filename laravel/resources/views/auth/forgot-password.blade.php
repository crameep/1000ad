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
    <div class="panel-body" style="text-align:center;">
        <br>
        <form action="{{ route('password.forgot.submit') }}" method="POST">
            @csrf
            <table style="margin:0 auto;">
            <tr>
                <td>E-mail address:</td>
                <td><input type="email" name="email" size="30" maxlength="50" value="{{ old('email') }}"></td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:center;">
                    <br>
                    <input type="submit" value="Send New Password">
                </td>
            </tr>
            </table>
        </form>
        <br>
    </div>
</div>
@endsection
