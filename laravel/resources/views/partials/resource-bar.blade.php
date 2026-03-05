{{-- Unified resource strip --}}
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

    {{-- Land (total / free) --}}
    <span class="res-cell" title="Mountain (total / free)">
        <img class="res-icon" src="{{ landIcon('mountain') }}" alt="Mountain">
        <span data-resource="mland">{{ number_format($player->mland) }}</span>/<span data-resource="free_mland">{{ number_format($freeM) }}</span>
    </span>
    <span class="res-cell" title="Forest (total / free)">
        <img class="res-icon" src="{{ landIcon('forest') }}" alt="Forest">
        <span data-resource="fland">{{ number_format($player->fland) }}</span>/<span data-resource="free_fland">{{ number_format($freeF) }}</span>
    </span>
    <span class="res-cell" title="Plains (total / free)">
        <img class="res-icon" src="{{ landIcon('plains') }}" alt="Plains">
        <span data-resource="pland">{{ number_format($player->pland) }}</span>/<span data-resource="free_pland">{{ number_format($freeP) }}</span>
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
