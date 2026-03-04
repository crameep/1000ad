{{-- Resource bar --}}
<div class="resource-bar">
    <span class="text-gold"><b>Score:</b> <span data-resource="score">{{ number_format($player->score) }}</span></span>
    <span><b>Pop:</b> <span data-resource="people">{{ number_format($player->people) }}</span></span>
    <span><b>Gold:</b> <span data-resource="gold">{{ number_format($player->gold) }}</span></span>
    <span>
        <noscript>
            <form action="{{ route('game.end-turn') }}" method="POST" class="inline-form">
                @csrf
                <a href="#" onclick="this.closest('form').submit(); return false;"><b>END TURN</b></a>
            </form>
        </noscript>
    </span>
</div>

{{-- Land and resources --}}
<div class="resource-panels">
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
    <div class="goods-panel">
        <div class="goods-grid">
            <span class="resource-item"><img class="res-icon" src="{{ resourceIcon('wood') }}" alt="Wood"> <span data-resource="wood">{{ number_format($player->wood) }}</span></span>
            <span class="resource-item"><img class="res-icon" src="{{ resourceIcon('iron') }}" alt="Iron"> <span data-resource="iron">{{ number_format($player->iron) }}</span></span>
            <span class="resource-item"><img class="res-icon" src="{{ resourceIcon('food') }}" alt="Food"> <span data-resource="food">{{ number_format($player->food) }}</span></span>
            <span class="resource-item"><img class="res-icon" src="{{ resourceIcon('tools') }}" alt="Tools"> <span data-resource="tools">{{ number_format($player->tools) }}</span></span>
            <span class="resource-item"><img class="res-icon" src="{{ resourceIcon('horses') }}" alt="Horses"> <span data-resource="horses">{{ number_format($player->horses) }}</span></span>
            <span class="resource-item"><img class="res-icon" src="{{ resourceIcon('wine') }}" alt="Wine"> <span data-resource="wine">{{ number_format($player->wine) }}</span></span>
            <span class="resource-item"><img class="res-icon" src="{{ resourceIcon('swords') }}" alt="Swords"> <span data-resource="swords">{{ number_format($player->swords) }}</span></span>
            <span class="resource-item"><img class="res-icon" src="{{ resourceIcon('bows') }}" alt="Bows"> <span data-resource="bows">{{ number_format($player->bows) }}</span></span>
            <span class="resource-item"><img class="res-icon" src="{{ resourceIcon('maces') }}" alt="Maces"> <span data-resource="maces">{{ number_format($player->maces) }}</span></span>
        </div>
    </div>
</div>
