@extends('layouts.auth')

@section('sidebar')
<div class="panel">
    <div class="panel-header">Instructions</div>
    <div class="panel-body">
        Create your account below. You'll choose your empire name and
        civilization when you join a game from the lobby.<br>
        <br>
        Your e-mail address will stay confidential.<br>
        <br>
        <a href="{{ route('login') }}">Back to Home</a>
    </div>
</div>
@endsection

@section('content')
<div class="panel">
    <div class="panel-header">Create Your Account</div>
    <div class="panel-body text-center">

        @if($errors->any())
            <div class="text-error" style="margin-bottom:10px;">
                @foreach($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <form action="{{ route('register.submit') }}" method="POST">
            @csrf
            <table style="margin:0 auto; text-align:left;">
            <tr>
                <td>Login Name:</td>
                <td><input type="text" name="login_name" maxlength="50" value="{{ old('login_name') }}" autofocus></td>
            </tr>
            <tr>
                <td>Password:</td>
                <td><input type="password" name="password" maxlength="50"></td>
            </tr>
            <tr>
                <td class="nowrap">Verify Password:</td>
                <td><input type="password" name="password_confirmation" maxlength="50"></td>
            </tr>
            <tr>
                <td>E-mail address:</td>
                <td><input type="email" name="email" maxlength="50" value="{{ old('email') }}"></td>
            </tr>
            <tr>
                <td colspan="2" class="text-center">
                    <br>
                    <input type="submit" value="    Create Account    ">
                    <br><br>
                    <span class="text-small text-muted">
                        After creating your account, you'll choose a game to join<br>
                        and pick your civilization from the game lobby.
                    </span>
                    <br><br>
                </td>
            </tr>
            </table>
        </form>
    </div>
</div>
@endsection
