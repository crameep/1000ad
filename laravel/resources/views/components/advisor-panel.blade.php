@props(['tips' => [], 'title' => 'Royal Adviser'])
@if(!empty($tips))
<div class="advisor-panel" id="advisor-panel">
    <div class="advisor-header" onclick="toggleAdvisor()">
        <span class="advisor-title">{{ $title }}</span>
        <span class="advisor-toggle" id="advisor-toggle">&#9660;</span>
    </div>
    <div class="advisor-body" id="advisor-body">
        @foreach($tips as $tip)
            <div class="advisor-tip advisor-tip-{{ $tip['type'] }}">
                <span class="advisor-icon">
                    @if($tip['type'] === 'danger')
                        !
                    @elseif($tip['type'] === 'warning')
                        ?
                    @elseif($tip['type'] === 'success')
                        &#10003;
                    @else
                        i
                    @endif
                </span>
                {!! $tip['message'] !!}
            </div>
        @endforeach
    </div>
</div>
@endif
