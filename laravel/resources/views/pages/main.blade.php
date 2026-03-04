{{-- Main page - ported from main.cfm --}}
@extends('layouts.game')

@section('content')
<div class="page-title-bar">
    <h2>Main</h2>
    <a href="javascript:openHelp('home')" class="help-link">Game Help</a>
</div>

{{-- Royal Advisor --}}
@if(!empty($advisorTips))
<div class="advisor-panel" id="advisor-panel">
    <div class="advisor-header" onclick="toggleAdvisor()">
        <span class="advisor-title">Royal Advisor</span>
        <span class="advisor-toggle" id="advisor-toggle">&#9660;</span>
    </div>
    <div class="advisor-body" id="advisor-body">
        @foreach($advisorTips as $tip)
            <div class="advisor-tip advisor-tip-{{ $tip['type'] }}">
                @if($tip['type'] === 'danger')
                    <span class="advisor-icon">!</span>
                @elseif($tip['type'] === 'warning')
                    <span class="advisor-icon">?</span>
                @else
                    <span class="advisor-icon">i</span>
                @endif
                {{ $tip['message'] }}
            </div>
        @endforeach
    </div>
</div>
<script>
function toggleAdvisor() {
    var body = document.getElementById('advisor-body');
    var toggle = document.getElementById('advisor-toggle');
    if (body.style.display === 'none') {
        body.style.display = '';
        toggle.innerHTML = '&#9660;';
    } else {
        body.style.display = 'none';
        toggle.innerHTML = '&#9654;';
    }
}
</script>
@endif

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
