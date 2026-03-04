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
            <span class="land-total-cell"><span class="land-icon land-mountain">&#9968;</span><span data-resource="mland">{{ number_format($player->mland) }}</span></span>
            <span class="land-total-cell"><span class="land-icon land-forest">&#127794;</span><span data-resource="fland">{{ number_format($player->fland) }}</span></span>
            <span class="land-total-cell"><span class="land-icon land-plains">&#127806;</span><span data-resource="pland">{{ number_format($player->pland) }}</span></span>
        </div>
        <div class="land-row">
            <span class="land-free-cell land-label"><b>Free:</b></span>
            <span class="land-free-cell"><span class="land-icon land-mountain">&#9968;</span><span data-resource="free_mland">{{ number_format($freeM) }}</span></span>
            <span class="land-free-cell"><span class="land-icon land-forest">&#127794;</span><span data-resource="free_fland">{{ number_format($freeF) }}</span></span>
            <span class="land-free-cell"><span class="land-icon land-plains">&#127806;</span><span data-resource="free_pland">{{ number_format($freeP) }}</span></span>
        </div>
    </div>
    <div class="goods-panel">
        <div class="goods-grid">
            <span class="resource-item"><span class="res-icon">&#127795;</span> <span data-resource="wood">{{ number_format($player->wood) }}</span></span>
            <span class="resource-item"><span class="res-icon">&#9935;</span> <span data-resource="iron">{{ number_format($player->iron) }}</span></span>
            <span class="resource-item"><span class="res-icon">&#127830;</span> <span data-resource="food">{{ number_format($player->food) }}</span></span>
            <span class="resource-item"><span class="res-icon">&#128296;</span> <span data-resource="tools">{{ number_format($player->tools) }}</span></span>
            <span class="resource-item"><span class="res-icon">&#128052;</span> <span data-resource="horses">{{ number_format($player->horses) }}</span></span>
            <span class="resource-item"><span class="res-icon">&#127863;</span> <span data-resource="wine">{{ number_format($player->wine) }}</span></span>
            <span class="resource-item"><span class="res-icon">&#9876;</span> <span data-resource="swords">{{ number_format($player->swords) }}</span></span>
            <span class="resource-item"><span class="res-icon">&#127993;</span> <span data-resource="bows">{{ number_format($player->bows) }}</span></span>
            <span class="resource-item"><span class="res-icon">&#127951;</span> <span data-resource="maces">{{ number_format($player->maces) }}</span></span>
        </div>
    </div>
</div>
