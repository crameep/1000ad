{{-- Account Options page - ported from account.cfm --}}
@extends('layouts.game')

@section('content')
<div class="page-title-bar">
    <h2>Account Options</h2>
</div>
<br>

{{-- Change Login Name --}}
<div class="form-panel">
<form action="{{ route('game.account.login') }}" method="POST">
    @csrf
<div class="bg-header" style="padding:4px;">Change Login Name</div>
<div style="padding:4px;">
    <span class="text-small">Changing your login name does not change your Empire name</span><br><br>
    <label>Login Name:</label>
    <input type="text" name="newLogin" size="30" maxlength="30" value="{{ $player->login_name }}">
    <br><br>
    <div class="text-center"><input type="submit" value="Change Login"></div>
</div>
</form>
</div>

<br><br>

{{-- Change Password --}}
<div class="form-panel">
<form action="{{ route('game.account.password') }}" method="POST">
    @csrf
<div class="bg-header" style="padding:4px;">Change Password</div>
<div style="padding:4px;">
    <label>Current Password:</label>
    <input type="password" name="curPassword" size="20" maxlength="30"><br>
    <label>New Password:</label>
    <input type="password" name="newPassword" size="20" maxlength="30"><br>
    <label>New Password (verify):</label>
    <input type="password" name="newPassword2" size="20" maxlength="30">
    <br><br>
    <div class="text-center"><input type="submit" value="Change Password"></div>
</div>
</form>
</div>

<br><br>

{{-- Delete Empire --}}
<script>
function confirmDelete(form) {
    if (confirm("Are you sure you want to delete your empire?")) {
        if (confirm("Are you 100% sure you want to do this?")) {
            if (confirm("Last warning. You are about to delete your empire. Are you sure?")) {
                return true;
            }
        }
    }
    return false;
}
</script>

<div class="form-panel">
<form action="{{ route('game.account.delete') }}" method="POST" onsubmit="return confirmDelete(this)">
    @csrf
<div class="bg-header" style="padding:4px;">Delete My Empire</div>
<div style="padding:4px;">
    <label>Login Name:</label>
    <input type="text" name="lName" size="20" maxlength="30"><br>
    <label>Current Password:</label>
    <input type="password" name="curPassword" size="20" maxlength="30">
    <br><br>
    <div class="text-center"><input type="submit" value="Delete Empire"></div>
</div>
</form>
</div>
@endsection
