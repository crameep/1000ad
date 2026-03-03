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
            <span class="land-total-cell"><img src="/images/mland.gif" alt="Mountain"><span data-resource="mland">{{ number_format($player->mland) }}</span></span>
            <span class="land-total-cell"><img src="/images/fland.gif" alt="Forest"><span data-resource="fland">{{ number_format($player->fland) }}</span></span>
            <span class="land-total-cell"><img src="/images/pland.gif" alt="Plains"><span data-resource="pland">{{ number_format($player->pland) }}</span></span>
        </div>
        <div class="land-row">
            <span class="land-free-cell land-label"><b>Free:</b></span>
            <span class="land-free-cell"><img src="/images/mland_free.gif" alt="Free Mountain">{{ number_format($freeM) }}</span>
            <span class="land-free-cell"><img src="/images/fland_free.gif" alt="Free Forest">{{ number_format($freeF) }}</span>
            <span class="land-free-cell"><img src="/images/pland_free.gif" alt="Free Plains">{{ number_format($freeP) }}</span>
        </div>
    </div>
    <div class="goods-panel">
        <div class="goods-grid">
            <span class="resource-item" data-tooltip="Wood: {{ number_format($player->wood) }}">
                <img src="/images/wood.gif" alt="Wood"> <span data-resource="wood">{{ number_format($player->wood) }}</span>
            </span>
            <span class="resource-item" data-tooltip="Iron: {{ number_format($player->iron) }}">
                <img src="/images/iron.gif" alt="Iron"> <span data-resource="iron">{{ number_format($player->iron) }}</span>
            </span>
            <span class="resource-item" data-tooltip="Food: {{ number_format($player->food) }}">
                <img src="/images/food.gif" alt="Food"> <span data-resource="food">{{ number_format($player->food) }}</span>
            </span>
            <span class="resource-item" data-tooltip="Tools: {{ number_format($player->tools) }}">
                <img src="/images/tools.gif" alt="Tools"> <span data-resource="tools">{{ number_format($player->tools) }}</span>
            </span>
        </div>
    </div>
</div>
