{{-- Local Trade page - modernized card-based UI --}}
@extends('layouts.game')

@section('content')
<div class="page-title-bar">
    <h2>Local Trade</h2>
    <a href="javascript:openHelp('trade')" class="help-link">Help</a>
</div>

<x-advisor-panel :tips="$advisorTips" />

{{-- Trade Summary --}}
<div class="trade-summary">
    <div class="trade-summary-left">
        <span class="trade-summary-label">Traded this turn:</span>
        <span class="trade-summary-value" id="trades-used">{{ number_format($player->trades_this_turn) }}</span>
        <span class="trade-summary-sep">/</span>
        <span class="trade-summary-max">{{ number_format($maxTrades) }}</span>
    </div>
    <div class="trade-summary-right">
        <span class="trade-summary-label">Remaining:</span>
        <span class="trade-summary-value text-success" id="trades-remaining">{{ number_format($tradesRemaining) }}</span>
    </div>
</div>

{{-- Resource Trade Cards --}}
@if($tradesRemaining > 0 || $player->trades_this_turn > 0)
<div class="trade-grid" id="trade-grid"
     data-max-trades="{{ $maxTrades }}"
     data-trades-remaining="{{ $tradesRemaining }}">
    @php
        $resources = [
            ['key' => 'wood', 'name' => 'Wood', 'have' => $player->wood, 'buyPrice' => $woodBuyPrice, 'sellPrice' => $woodSellPrice],
            ['key' => 'food', 'name' => 'Food', 'have' => $player->food, 'buyPrice' => $foodBuyPrice, 'sellPrice' => $foodSellPrice],
            ['key' => 'iron', 'name' => 'Iron', 'have' => $player->iron, 'buyPrice' => $ironBuyPrice, 'sellPrice' => $ironSellPrice],
            ['key' => 'tools', 'name' => 'Tools', 'have' => $player->tools, 'buyPrice' => $toolBuyPrice, 'sellPrice' => $toolSellPrice],
        ];
    @endphp

    @foreach($resources as $res)
    <div class="trade-card" data-resource="{{ $res['key'] }}" data-buy-price="{{ $res['buyPrice'] }}" data-sell-price="{{ $res['sellPrice'] }}">
        <div class="trade-card-header">
            <img class="trade-card-icon" src="{{ resourceIcon($res['key']) }}" alt="{{ $res['name'] }}">
            <span class="trade-card-name">{{ $res['name'] }}</span>
            <span class="trade-card-have">You have: <b data-resource="{{ $res['key'] }}">{{ number_format($res['have']) }}</b></span>
        </div>
        <div class="trade-card-body">
            <div class="trade-card-row">
                <input type="number" class="trade-input" id="buy-{{ $res['key'] }}" value="0" min="0" placeholder="0">
                <button type="button" class="trade-btn trade-btn-buy" data-action="buy" data-res="{{ $res['key'] }}">
                    Buy <span class="trade-price">{{ number_format($res['buyPrice']) }}g ea</span>
                </button>
            </div>
            <div class="trade-card-row">
                <input type="number" class="trade-input" id="sell-{{ $res['key'] }}" value="0" min="0" placeholder="0">
                <button type="button" class="trade-btn trade-btn-sell" data-action="sell" data-res="{{ $res['key'] }}">
                    Sell <span class="trade-price">{{ number_format($res['sellPrice']) }}g ea</span>
                </button>
            </div>
        </div>
    </div>
    @endforeach
</div>
@else
<div class="trade-exhausted">
    You've traded the maximum amount this turn. End a turn to trade again.
</div>
@endif

{{-- Auto Trade Section --}}
<div class="auto-trade-section" id="auto-trade-section">
    <div class="auto-trade-header" onclick="toggleAutoTrade()">
        <span class="bq-toggle" id="atToggleArrow">&#9660;</span>
        <span class="bq-title">Automatic Trade</span>
        <span class="auto-trade-hint">runs each turn &middot; limit {{ number_format($maxTrades) }}</span>
    </div>
    <div class="auto-trade-body" id="auto-trade-body">
        <div class="auto-trade-status">
            Auto trading <b>{{ number_format($totalAutoTrade) }}</b> / {{ number_format($maxTrades) }} goods
            &middot; <span id="at-remaining">{{ number_format($remAutoTrade) }}</span> remaining
        </div>
        <div class="auto-trade-grid">
            <div class="auto-trade-grid-header">
                <span></span>
                <span><img class="trade-card-icon" src="{{ resourceIcon('wood') }}" alt="Wood" title="Wood"></span>
                <span><img class="trade-card-icon" src="{{ resourceIcon('food') }}" alt="Food" title="Food"></span>
                <span><img class="trade-card-icon" src="{{ resourceIcon('iron') }}" alt="Iron" title="Iron"></span>
                <span><img class="trade-card-icon" src="{{ resourceIcon('tools') }}" alt="Tools" title="Tools"></span>
                <span title="Gold per turn">Gold/turn</span>
            </div>
            <div class="auto-trade-grid-row">
                <span class="auto-trade-label">Auto Buy</span>
                <input type="number" class="trade-input at-input" name="auto_buy_wood" value="{{ $player->auto_buy_wood }}" min="0">
                <input type="number" class="trade-input at-input" name="auto_buy_food" value="{{ $player->auto_buy_food }}" min="0">
                <input type="number" class="trade-input at-input" name="auto_buy_iron" value="{{ $player->auto_buy_iron }}" min="0">
                <input type="number" class="trade-input at-input" name="auto_buy_tools" value="{{ $player->auto_buy_tools }}" min="0">
                <span class="auto-trade-gold text-error" id="at-gold-buy">-{{ number_format($autoTradeGoldUsed) }}</span>
            </div>
            <div class="auto-trade-grid-row">
                <span class="auto-trade-label">Auto Sell</span>
                <input type="number" class="trade-input at-input" name="auto_sell_wood" value="{{ $player->auto_sell_wood }}" min="0">
                <input type="number" class="trade-input at-input" name="auto_sell_food" value="{{ $player->auto_sell_food }}" min="0">
                <input type="number" class="trade-input at-input" name="auto_sell_iron" value="{{ $player->auto_sell_iron }}" min="0">
                <input type="number" class="trade-input at-input" name="auto_sell_tools" value="{{ $player->auto_sell_tools }}" min="0">
                <span class="auto-trade-gold text-success" id="at-gold-sell">+{{ number_format($autoTradeGoldEarned) }}</span>
            </div>
            <div class="auto-trade-grid-footer">
                <button type="button" class="turn-btn" id="at-save">Save Auto-Trade</button>
                <span class="auto-trade-net">
                    Net: <b id="at-gold-net">{{ number_format($autoTradeGoldEarned - $autoTradeGoldUsed) }}</b> gold/turn
                </span>
            </div>
        </div>
    </div>
</div>

<script>
var Prefs = (window.Game && Game.Prefs) || { get: function() { return arguments[1]; }, set: function() {} };

function toggleAutoTrade() {
    var body = document.getElementById('auto-trade-body');
    var arrow = document.getElementById('atToggleArrow');
    var isOpen = body.style.display !== 'none';
    body.style.display = isOpen ? 'none' : '';
    arrow.innerHTML = isOpen ? '&#9654;' : '&#9660;';
    Prefs.set('autoTradeOpen', !isOpen);
}
(function() {
    var open = Prefs.get('autoTradeOpen', true);
    if (!open) {
        var body = document.getElementById('auto-trade-body');
        var arrow = document.getElementById('atToggleArrow');
        if (body) body.style.display = 'none';
        if (arrow) arrow.innerHTML = '&#9654;';
    }
})();

document.addEventListener('DOMContentLoaded', function() {
    var Ajax = (window.Game && Game.Ajax) || null;
    var Toast = (window.Game && Game.Toast) || null;
    if (!Ajax) return;

    // --- Buy / Sell buttons ---
    var grid = document.getElementById('trade-grid');
    if (grid) {
        grid.addEventListener('click', function(e) {
            var btn = e.target.closest('.trade-btn');
            if (!btn) return;

            var action = btn.dataset.action; // 'buy' or 'sell'
            var res = btn.dataset.res;
            var input = document.getElementById(action + '-' + res);
            var qty = parseInt(input ? input.value : 0, 10) || 0;

            if (qty <= 0) {
                if (Toast) Toast.show('Enter an amount to ' + action + '.', 'warning', 3000);
                return;
            }

            var data = {};
            data[action + '_wood'] = 0;
            data[action + '_food'] = 0;
            data[action + '_iron'] = 0;
            data[action + '_tools'] = 0;
            data[action + '_' + res] = qty;

            var url = action === 'buy' ? '/game/localtrade/buy' : '/game/localtrade/sell';

            Ajax.post(url, data).then(function(json) {
                // Reset input
                if (input) input.value = 0;
                // Update trades remaining
                if (json.tradesRemaining !== undefined) {
                    var remEl = document.getElementById('trades-remaining');
                    var usedEl = document.getElementById('trades-used');
                    if (remEl) remEl.textContent = Number(json.tradesRemaining).toLocaleString();
                    if (usedEl && json.maxTrades !== undefined) {
                        usedEl.textContent = Number(json.maxTrades - json.tradesRemaining).toLocaleString();
                    }
                    grid.dataset.tradesRemaining = json.tradesRemaining;
                }
            }).catch(function() {});
        });
    }

    // --- Auto-trade save ---
    var saveBtn = document.getElementById('at-save');
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            var data = {};
            document.querySelectorAll('.at-input').forEach(function(el) {
                data[el.name] = parseInt(el.value, 10) || 0;
            });
            Ajax.post('/game/localtrade/autotrade', data).then(function() {
                // success toast handled by Ajax
            }).catch(function() {});
        });
    }

    // --- Live gold estimate for auto-trade ---
    var buyPrices = { wood: {{ $woodBuyPrice }}, food: {{ $foodBuyPrice }}, iron: {{ $ironBuyPrice }}, tools: {{ $toolBuyPrice }} };
    var sellPrices = { wood: {{ $woodSellPrice }}, food: {{ $foodSellPrice }}, iron: {{ $ironSellPrice }}, tools: {{ $toolSellPrice }} };

    function updateAutoTradeGold() {
        var buyGold = 0, sellGold = 0;
        ['wood', 'food', 'iron', 'tools'].forEach(function(r) {
            var buyEl = document.querySelector('[name="auto_buy_' + r + '"]');
            var sellEl = document.querySelector('[name="auto_sell_' + r + '"]');
            if (buyEl) buyGold += (parseInt(buyEl.value, 10) || 0) * buyPrices[r];
            if (sellEl) sellGold += (parseInt(sellEl.value, 10) || 0) * sellPrices[r];
        });
        var buySpan = document.getElementById('at-gold-buy');
        var sellSpan = document.getElementById('at-gold-sell');
        var netSpan = document.getElementById('at-gold-net');
        if (buySpan) buySpan.textContent = '-' + buyGold.toLocaleString();
        if (sellSpan) sellSpan.textContent = '+' + sellGold.toLocaleString();
        if (netSpan) netSpan.textContent = (sellGold - buyGold).toLocaleString();
    }

    document.querySelectorAll('.at-input').forEach(function(el) {
        el.addEventListener('input', updateAutoTradeGold);
    });
});
</script>
@endsection
