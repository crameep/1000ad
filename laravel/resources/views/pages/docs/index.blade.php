<!DOCTYPE html>
<html>
<head>
    <title>1000 A.D. - Game Help: {{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('css/game.css') }}">
</head>
<body bgcolor="#2b2b2b" text="white" link="#00ccff" vlink="#00ccff">

<table border="0" cellpadding="5" cellspacing="0" width="600" align="center">
<tr>
    <td bgcolor="darkslategray" align="center">
        <b style="font-size:16px;">1000 A.D. Game Help</b>
    </td>
</tr>
<tr>
    <td>
        {{-- Navigation --}}
        <div style="margin:10px 0; font-size:11px;">
            @foreach($pages as $key => $label)
                @if($key === $page)
                    <b style="color:yellow;">{{ $label }}</b>
                @else
                    <a href="{{ route('docs', $key) }}">{{ $label }}</a>
                @endif
                @if(!$loop->last) | @endif
            @endforeach
        </div>
        <hr noshade size="1" color="darkslategray">

        {{-- Page Content --}}
        @include('pages.docs.content.' . $page)

        <hr noshade size="1" color="darkslategray">
        <div style="text-align:center; margin:10px 0;">
            <a href="javascript:window.close()">Close Window</a>
        </div>
    </td>
</tr>
</table>

</body>
</html>
