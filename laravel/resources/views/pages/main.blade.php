{{-- Main page - ported from main.cfm --}}
@extends('layouts.game')

@section('content')
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
    <td class="header" align="center" width="88%" style="font-size:16px;"><b>Main</b></td>
    <td class="header" align="center" width="12%"><b><a href="javascript:openHelp('home')">Game Help</a></b></td>
</tr>
</table>

<br>

{{-- Player message --}}
<div style="font-size:12px;">
    {!! $player->message !!}
</div>

{{-- Attack news --}}
@foreach($news as $item)
    <hr noshade size="1" style="border:none; border-top:1px solid #666;">
    <div>
        {!! $item->message !!}<br>
        <span style="font-size:10px;">
            <form action="{{ route('game.main.delete-news', $item->id) }}" method="POST" style="display:inline;">
                @csrf
                <a href="#" onclick="this.closest('form').submit(); return false;">Delete Message</a>
            </form>
        </span>
    </div>
@endforeach

<br>
<br>

@if($news->count() > 1)
    <span style="font-size:10px;">
        <form action="{{ route('game.main.delete-all-news') }}" method="POST" style="display:inline;">
            @csrf
            <a href="#" onclick="this.closest('form').submit(); return false;">Delete All News</a>
        </form>
    </span>
@endif
@endsection
