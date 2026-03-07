{{-- Resource strip --}}
<div class="resource-bar">
    {{-- Core stats --}}
    <span class="res-cell" title="Score">
        <span class="res-symbol text-gold">&#x2B50;</span>
        <span data-resource="score">{{ number_format($player->score) }}</span>
    </span>
    <span class="res-cell" title="Population">
        <span class="res-symbol">&#x1F464;</span>
        <span data-resource="people">{{ number_format($player->people) }}</span>
    </span>
    <span class="res-cell" title="Gold">
        <span class="res-symbol text-gold">&#x1FA99;</span>
        <span data-resource="gold">{{ number_format($player->gold) }}</span>
    </span>

    <span class="res-divider"></span>

    {{-- Goods --}}
    <span class="res-cell" title="Wood">
        <img class="res-icon" src="{{ resourceIcon('wood') }}" alt="Wood">
        <span data-resource="wood">{{ number_format($player->wood) }}</span>
    </span>
    <span class="res-cell" title="Iron">
        <img class="res-icon" src="{{ resourceIcon('iron') }}" alt="Iron">
        <span data-resource="iron">{{ number_format($player->iron) }}</span>
    </span>
    <span class="res-cell" title="Food">
        <img class="res-icon" src="{{ resourceIcon('food') }}" alt="Food">
        <span data-resource="food">{{ number_format($player->food) }}</span>
    </span>
    <span class="res-cell" title="Tools">
        <img class="res-icon" src="{{ resourceIcon('tools') }}" alt="Tools">
        <span data-resource="tools">{{ number_format($player->tools) }}</span>
    </span>
    <span class="res-cell" title="Horses">
        <img class="res-icon" src="{{ resourceIcon('horses') }}" alt="Horses">
        <span data-resource="horses">{{ number_format($player->horses) }}</span>
    </span>
    <span class="res-cell" title="Wine">
        <img class="res-icon" src="{{ resourceIcon('wine') }}" alt="Wine">
        <span data-resource="wine">{{ number_format($player->wine) }}</span>
    </span>
    <span class="res-cell" title="Swords">
        <img class="res-icon" src="{{ resourceIcon('swords') }}" alt="Swords">
        <span data-resource="swords">{{ number_format($player->swords) }}</span>
    </span>
    <span class="res-cell" title="Bows">
        <img class="res-icon" src="{{ resourceIcon('bows') }}" alt="Bows">
        <span data-resource="bows">{{ number_format($player->bows) }}</span>
    </span>
    <span class="res-cell" title="Maces">
        <img class="res-icon" src="{{ resourceIcon('maces') }}" alt="Maces">
        <span data-resource="maces">{{ number_format($player->maces) }}</span>
    </span>

    <noscript>
        <form action="{{ route('game.end-turn') }}" method="POST" class="inline-form">
            @csrf
            <a href="#" onclick="this.closest('form').submit(); return false;"><b>END TURN</b></a>
        </form>
    </noscript>
</div>

{{-- Land panel (Total / Free) --}}
<div class="land-panel">
    <div class="land-row" style="margin-bottom:2px;">
        <span class="land-total-cell land-label"><b>Total:</b></span>
        <span class="land-total-cell"><img class="land-icon" src="{{ landIcon('mountain') }}" alt="M"><span data-resource="mland">{{ number_format($player->mland) }}</span></span>
        <span class="land-total-cell"><img class="land-icon" src="{{ landIcon('forest') }}" alt="F"><span data-resource="fland">{{ number_format($player->fland) }}</span></span>
        <span class="land-total-cell"><img class="land-icon" src="{{ landIcon('plains') }}" alt="P"><span data-resource="pland">{{ number_format($player->pland) }}</span></span>
    </div>
    <div class="land-row">
        <span class="land-free-cell land-label"><b>Free:</b></span>
        <span class="land-free-cell"><img class="land-icon" src="{{ landIcon('mountain') }}" alt="M"><span data-resource="free_mland">{{ number_format($freeM) }}</span></span>
        <span class="land-free-cell"><img class="land-icon" src="{{ landIcon('forest') }}" alt="F"><span data-resource="free_fland">{{ number_format($freeF) }}</span></span>
        <span class="land-free-cell"><img class="land-icon" src="{{ landIcon('plains') }}" alt="P"><span data-resource="free_pland">{{ number_format($freeP) }}</span></span>
    </div>
</div>

{{-- Progress indicators --}}
<div class="progress-indicators">
    <div class="progress-ind" title="Great Wall: {{ number_format($wallCurrent) }} / {{ number_format($wallMax) }} ({{ $wallPercent }}% protection)">
        <span class="progress-ind-label">Wall</span>
        <div class="progress-ind-track progress-ind-track--wall">
            <div class="progress-ind-fill progress-ind-fill--wall" data-progress="wall" style="width: {{ $wallPercent }}%"></div>
        </div>
        <span class="progress-ind-pct" data-progress-pct="wall">{{ $wallPercent }}%</span>
    </div>
    <div class="progress-ind" title="Research: {{ $currentResearchName }} Lv.{{ $currentResearchLevel }} — {{ number_format($researchPoints) }} / {{ number_format($researchNextLevel) }} points ({{ $researchPercent }}%)">
        <span class="progress-ind-label" data-progress-label="research">{{ $currentResearchName }}&nbsp;{{ $currentResearchLevel }}</span>
        <div class="progress-ind-track progress-ind-track--research">
            <div class="progress-ind-fill progress-ind-fill--research-level" data-progress="research_level" style="width: {{ $researchLevelPercent }}%"></div>
            <div class="progress-ind-fill progress-ind-fill--research-progress" data-progress="research_progress" style="width: {{ $researchProgressPercent }}%"></div>
        </div>
        <span class="progress-ind-pct" data-progress-pct="research">{{ $researchPercent }}%</span>
    </div>
    <div class="progress-ind" title="Warehouse: {{ number_format($warehouseCurrent) }} / {{ number_format($warehouseMax) }} goods stored">
        <span class="progress-ind-label">Storage</span>
        <div class="progress-ind-track progress-ind-track--warehouse">
            <div class="progress-ind-fill progress-ind-fill--warehouse" data-progress="warehouse" style="width: {{ $warehousePercent }}%"></div>
        </div>
        <span class="progress-ind-pct" data-progress-pct="warehouse">{{ $warehousePercent }}%</span>
    </div>
</div>
