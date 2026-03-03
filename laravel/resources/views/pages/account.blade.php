{{-- Account Options page - ported from account.cfm --}}
@extends('layouts.game')

@section('content')
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
    <td class="header" align="center" width="100%" style="font-size:16px;"><b>Account Options</b></td>
</tr>
</table>
<br>

{{-- Change Login Name --}}
<table border="0" cellspacing="0" cellpadding="2" style="border:thin outset;">
<form action="{{ route('game.account.login') }}" method="POST">
    @csrf
<tr>
    <td colspan="2" style="background-color:darkslategray;">Change Login Name</td>
</tr>
<tr><td colspan="2"><span style="font-size:10px;">Changing your login name does not change your Empire name</span></td></tr>
<tr>
    <td>Login Name:</td>
    <td><input type="text" name="newLogin" size="30" maxlength="30" value="{{ $player->login_name }}"></td>
</tr>
<tr><td colspan="2" align="center"><input type="submit" value="Change Login"></td></tr>
</form>
</table>

<br><br>

{{-- Change Password --}}
<table border="0" cellspacing="0" cellpadding="2" style="border:thin outset;">
<form action="{{ route('game.account.password') }}" method="POST">
    @csrf
<tr>
    <td colspan="2" style="background-color:darkslategray;">Change Password</td>
</tr>
<tr>
    <td>Current Password:</td>
    <td><input type="password" name="curPassword" size="20" maxlength="30"></td>
</tr>
<tr>
    <td>New Password:</td>
    <td><input type="password" name="newPassword" size="20" maxlength="30"></td>
</tr>
<tr>
    <td>New Password (verify):</td>
    <td><input type="password" name="newPassword2" size="20" maxlength="30"></td>
</tr>
<tr><td colspan="2" align="center"><input type="submit" value="Change Password"></td></tr>
</form>
</table>

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

<table border="0" cellspacing="0" cellpadding="2" style="border:thin outset;">
<form action="{{ route('game.account.delete') }}" method="POST" onsubmit="return confirmDelete(this)">
    @csrf
<tr>
    <td colspan="2" style="background-color:darkslategray;">Delete My Empire</td>
</tr>
<tr>
    <td>Login Name:</td>
    <td><input type="text" name="lName" size="20" maxlength="30"></td>
</tr>
<tr>
    <td>Current Password:</td>
    <td><input type="password" name="curPassword" size="20" maxlength="30"></td>
</tr>
<tr><td colspan="2" align="center"><input type="submit" value="Delete Empire"></td></tr>
</form>
</table>
@endsection
