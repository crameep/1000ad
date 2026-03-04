{{-- Main page - ported from main.cfm --}}
@extends('layouts.game')

@section('content')
<div class="page-title-bar">
    <h2>Main</h2>
    <a href="javascript:openHelp('home')" class="help-link">Game Help</a>
</div>

<x-advisor-panel :tips="$advisorTips" />

<br>

{{-- Player message --}}
<div style="font-size:12px;">
    {!! $player->message !!}
</div>

{{-- Attack news --}}
@foreach($news as $item)
    <hr>
    <div>
        {!! $item->message !!}<br>
        <span style="font-size:10px;">
            <form action="{{ route('game.main.delete-news', $item->id) }}" method="POST" class="inline-form">
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
        <form action="{{ route('game.main.delete-all-news') }}" method="POST" class="inline-form">
            @csrf
            <a href="#" onclick="this.closest('form').submit(); return false;">Delete All News</a>
        </form>
    </span>
@endif
@endsection
