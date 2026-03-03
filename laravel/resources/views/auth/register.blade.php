@extends('layouts.auth')

@section('sidebar')
<div class="panel">
    <div class="panel-header">Instructions</div>
    <div class="panel-body">
        All fields are required. You are allowed only 1 account per game. If you are found using
        multiple accounts, all of them could be deleted.
        Your e-mail address will stay confidential.<br>
        <br>
        <a href="{{ route('login') }}">Back to Home</a>
    </div>
</div>
@endsection

@section('content')
<div class="panel">
    <div class="panel-header">Create Your Account</div>
    <div class="panel-body" style="text-align:center;">

        @if($deathmatchStarted)
            <span class="error">This deathmatch game is already in progress. You can join next time.</span>
        @else
            @if($errors->any())
                <div style="color:red; margin-bottom:10px;">
                    @foreach($errors->all() as $error)
                        {{ $error }}<br>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('register.submit') }}" method="POST">
                @csrf
                <table style="margin:0 auto;">
                <tr>
                    <td>Login Name:</td>
                    <td><input type="text" name="login_name" size="30" maxlength="50" value="{{ old('login_name') }}"></td>
                </tr>
                <tr>
                    <td>Password:</td>
                    <td><input type="password" name="password" size="30" maxlength="50"></td>
                </tr>
                <tr>
                    <td style="white-space:nowrap;">Verify Password:</td>
                    <td><input type="password" name="password_confirmation" size="30" maxlength="50"></td>
                </tr>
                <tr>
                    <td>E-mail address:</td>
                    <td><input type="email" name="email" size="30" maxlength="50" value="{{ old('email') }}"></td>
                </tr>
                <tr>
                    <td>Empire Name:</td>
                    <td><input type="text" name="empire_name" size="30" maxlength="20" value="{{ old('empire_name') }}"></td>
                </tr>
                <tr>
                    <td valign="top">Civilization:</td>
                    <td style="text-align:left;">
                        @foreach($empires as $id => $name)
                            <input type="radio" name="civ" value="{{ $id }}" {{ old('civ', 1) == $id ? 'checked' : '' }}>
                            <b>{{ $name }}</b> - Unique Unit: {{ $uniqueUnits[$id] }}<br>

                            @if($id === 1)
                                <span style="font-size:10px;">
                                <b>Pluses:</b> Woodcutter: -2 land, +2 wood. Hunter: +2 food. Iron Mine: +1 iron.<br>
                                <b>Minuses:</b> Stable: +2 land, +50 food. Warehouse: half storage. House: -25 people. Farm: -2 food.<br>
                                </span>
                            @elseif($id === 2)
                                <span style="font-size:10px;">
                                <b>Pluses:</b> Farm: -2 land. Tool maker: +4 builders. Tower: -1 land, cheaper, +15 def. Town center: +1 explorer.<br>
                                <b>Minuses:</b> Town center: +10 land. Fort: -3 army. Mage Tower: +2 land.<br>
                                </span>
                            @elseif($id === 3)
                                <span style="font-size:10px;">
                                <b>Pluses:</b> Farm: +2 food. House: +20 people. Town Center: -5 land. Mage Tower: +0.5 research.<br>
                                <b>Minuses:</b> Hunter: -1 food. Woodcutter: +1 land. Market: -10 trades. Stable: +4 land, +25 food.<br>
                                </span>
                            @elseif($id === 4)
                                <span style="font-size:10px;">
                                <b>Pluses:</b> Gold Mine: -4 land, +150 gold. Market: +50 trades. Warehouse: 2x storage. Mage Tower: -2 land.<br>
                                <b>Minuses:</b> Iron Mine: +1 land. Tool Maker: -1 builder. People eat 20% more food.<br>
                                </span>
                            @elseif($id === 5)
                                <span style="font-size:10px;">
                                <b>Pluses:</b> Fort: -4 land, +5 army. Weaponsmith/ToolMaker: +1 production. Stable: +1 horse. Hunter: +1 food.<br>
                                <b>Minuses:</b> Town Center: -1 explorer. Farms: -2 food. Mage Tower: +100 gold needed.<br>
                                </span>
                            @elseif($id === 6)
                                <span style="font-size:10px;">
                                <b>Pluses:</b> Mage Tower: -60 gold. Town Center: +2500 storage. Market: +50 trades. Thieves: +25 def.<br>
                                <b>Minuses:</b> Town Center: +5 land. Iron Mine: +1 land. Catapults weaker. Horseman useless.<br>
                                </span>
                            @endif
                            <br>
                        @endforeach
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align:center;">
                        <br>
                        <input type="submit" value="    Create My Empire    ">
                        <br><br>
                    </td>
                </tr>
                </table>
            </form>
        @endif
    </div>
</div>
@endsection
