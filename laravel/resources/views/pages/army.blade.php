{{-- Army page - modernized card-based UI --}}
@extends('layouts.game')

@section('content')
<div class="page-title-bar">
    <h2>Army</h2>
    <a href="javascript:openHelp('army')" class="help-link">Help</a>
</div>

<x-advisor-panel :tips="$advisorTips" />

{{-- Training Queue (collapsible) --}}
@if($trainQueue->count() > 0)
<div class="bq-section" id="trainQueueSection">
    <div class="bq-header" onclick="toggleTrainQueue(event)">
        <span class="bq-toggle" id="tqToggleArrow">&#9660;</span>
        <span class="bq-title">Training Queue ({{ $trainQueue->count() }})</span>
        @if($trainQueue->count() > 1)
            <button type="button" class="bq-cancel-all" id="tqCancelAll" title="Cancel all" onclick="event.stopPropagation()">Cancel All</button>
        @endif
    </div>
    <div class="bq-list" id="trainQueueList">
        @foreach($trainQueue as $idx => $tq)
            @php $tqSoldier = $soldiers[$tq->soldier_type] ?? null; @endphp
            @if($tqSoldier)
            <div class="bq-item" data-qid="{{ $tq->id }}">
                <div class="bq-item-rank">{{ $idx + 1 }}</div>
                <img src="{{ soldierIcon($tqSoldier, $tq->soldier_type, $player->civ) }}" alt="{{ $tqSoldier['name'] }}" class="bq-item-icon" onerror="this.style.display='none'">
                <div class="bq-item-info">
                    <div class="bq-item-name">{{ $tqSoldier['name'] }}</div>
                    <div class="bq-item-detail">x{{ number_format($tq->qty) }} &middot; {{ $tq->turns_remaining }} turn{{ $tq->turns_remaining != 1 ? 's' : '' }}</div>
                </div>
                <div class="bq-item-actions">
                    @if($trainQueue->count() > 1)
                    <button type="button" class="bq-btn bq-btn-move" data-action="top" title="Move to top">&#9650;</button>
                    <button type="button" class="bq-btn bq-btn-move" data-action="bottom" title="Move to bottom">&#9660;</button>
                    @endif
                    <button type="button" class="bq-btn bq-btn-cancel" data-action="cancel" title="Cancel">&times;</button>
                </div>
            </div>
            @endif
        @endforeach
    </div>
</div>
<script>
var Prefs = (window.Game && Game.Prefs) || { get: function() { return arguments[1]; }, set: function() {} };
function toggleTrainQueue(e) {
    var list = document.getElementById('trainQueueList');
    var arrow = document.getElementById('tqToggleArrow');
    var isOpen = list.style.display !== 'none';
    list.style.display = isOpen ? 'none' : '';
    arrow.innerHTML = isOpen ? '&#9654;' : '&#9660;';
    Prefs.set('armyQueueOpen', !isOpen);
}
(function() {
    var open = Prefs.get('armyQueueOpen', true);
    if (!open) {
        var list = document.getElementById('trainQueueList');
        var arrow = document.getElementById('tqToggleArrow');
        if (list) { list.style.display = 'none'; }
        if (arrow) { arrow.innerHTML = '&#9654;'; }
    }
})();

(function() {
    var queueSection = document.getElementById('trainQueueSection');
    if (!queueSection) return;

    queueSection.addEventListener('click', function(e) {
        var btn = e.target.closest('.bq-btn');
        if (!btn) return;

        var item = btn.closest('.bq-item');
        var qid = item ? item.dataset.qid : null;
        var action = btn.dataset.action;
        if (!qid || !action) return;

        var urls = { cancel: '/game/army/cancel', top: '/game/army/move-top', bottom: '/game/army/move-bottom' };
        var url = urls[action];
        if (!url) return;

        if (action === 'cancel') {
            item.style.opacity = '0.4';
        } else {
            btn.style.color = 'var(--border-accent)';
        }

        Game.Ajax.post(url, { q_id: qid }, { silent: action !== 'cancel' })
            .then(function(data) {
                if (data.success) {
                    if (action === 'cancel') {
                        item.style.transition = 'opacity 0.2s, max-height 0.3s';
                        item.style.maxHeight = item.offsetHeight + 'px';
                        requestAnimationFrame(function() {
                            item.style.opacity = '0';
                            item.style.maxHeight = '0';
                            item.style.overflow = 'hidden';
                            item.style.padding = '0';
                            item.style.margin = '0';
                        });
                        setTimeout(function() {
                            item.remove();
                            document.querySelectorAll('#trainQueueList .bq-item .bq-item-rank').forEach(function(el, i) {
                                el.textContent = i + 1;
                            });
                            if (!document.querySelectorAll('#trainQueueList .bq-item').length) {
                                queueSection.style.display = 'none';
                            }
                        }, 350);
                    } else {
                        window.location.reload();
                    }
                }
            })
            .catch(function() {
                item.style.opacity = '1';
                btn.style.color = '';
            });
    });

    var cancelAllBtn = document.getElementById('tqCancelAll');
    if (cancelAllBtn) {
        cancelAllBtn.addEventListener('click', function() {
            var items = document.querySelectorAll('#trainQueueList .bq-item');
            items.forEach(function(item) { item.style.opacity = '0.4'; });

            Game.Ajax.post('/game/army/cancel-all', {})
                .then(function(data) {
                    if (data.success) {
                        queueSection.style.display = 'none';
                    }
                })
                .catch(function() {
                    items.forEach(function(item) { item.style.opacity = '1'; });
                });
        });
    }
})();
</script>
@endif

{{-- Soldier Card Grid --}}
<div class="army-section">
    <div class="army-card-grid">
        @foreach($soldierDisplay as $i)
            @php $data = $armyData[$i]; @endphp
            <div class="army-card"
                 data-soldier="{{ $i }}"
                 data-sname="{{ $data['soldier']['name'] }}"
                 data-have="{{ $data['have'] }}"
                 data-attacking="{{ $data['attacking'] }}"
                 data-training="{{ $data['training'] }}"
                 data-max-train="{{ $data['maxTrain'] }}"
                 data-needed="{{ strip_tags($data['neededToTrain']) }}"
                 data-turns="{{ $data['soldier']['turns'] }}"
                 data-attack-pt="{{ $data['soldier']['attack_pt'] }}"
                 data-defense-pt="{{ $data['soldier']['defense_pt'] }}"
                 data-gold-cost="{{ $data['goldCost'] }}"
                 data-food-cost="{{ $data['foodUsed'] }}"
                 data-help-index="{{ $data['helpIndex'] }}"
                 data-own="{{ $player->{$data['soldier']['db_name']} ?? 0 }}"
                 data-tc-capped="{{ $data['tcCapped'] ? 1 : 0 }}"
                 data-tc-max="{{ $data['tcMax'] }}">
                <img src="{{ soldierIcon($data['soldier'], $i, $player->civ) }}" alt="{{ $data['soldier']['name'] }}" class="army-card-icon"
                     onerror="this.style.display='none'" onload="this.style.display=''">
                <div class="army-card-info">
                    <div class="army-card-top">
                        <a href="javascript:openHelp('army#UNIT{{ $data['helpIndex'] }}')" class="army-card-name" style="color: var(--border-accent)">{{ $data['soldier']['name'] }}</a>
                        <span class="army-card-count" style="color: var(--border-accent)">{{ number_format($data['have']) }}</span>
                    </div>
                    <div class="army-card-mid">
                        <span class="army-card-cost">
                            <span>ATK {{ $data['soldier']['attack_pt'] }}</span>
                            <span>DEF {{ $data['soldier']['defense_pt'] }}</span>
                            @if($data['soldier']['gold_per_turn'] > 0)
                                <span>{{ $data['soldier']['gold_per_turn'] }}g/t</span>
                            @endif
                        </span>
                    </div>
                    @php
                        $hasStatus = $data['attacking'] > 0 || $data['training'] > 0;
                    @endphp
                    <div class="army-card-stats" {!! $hasStatus ? '' : 'style="display:none"' !!}>
                        @if($data['attacking'] > 0)
                            <span style="color: #cc8855">Attacking: {{ number_format($data['attacking']) }}</span>
                        @endif
                        @if($data['training'] > 0)
                            <span style="color: var(--text-success)">Training: {{ number_format($data['training']) }}</span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Action panel — lives inside grid, moves below selected card via JS --}}
        <div class="army-action-panel" id="armyActionPanel" style="display:none;">
            <div class="army-action-head">
                <div class="army-action-left">
                    <span class="army-action-info" id="armyInfo"></span>
                    <span class="army-action-desc" id="armyDesc"></span>
                </div>
                <div class="army-action-suggest" id="armySuggest" style="display:none;"></div>
            </div>
            <div class="army-action-row">
                <div class="army-action-controls">
                    <label for="armyQty" class="army-qty-label">Qty:</label>
                    <div class="army-qty-stepper">
                        <button type="button" class="army-qty-btn" id="armyQtyMinus">&minus;</button>
                        <input type="number" id="armyQty" value="1" min="1" max="10000000" class="army-qty-input">
                        <button type="button" class="army-qty-btn" id="armyQtyPlus">+</button>
                    </div>
                    <button type="button" class="army-max-btn" id="armyHalfBtn" title="Set to half of max">&frac12;</button>
                    <button type="button" class="army-max-btn" id="armyMaxBtn" title="Set to max you can train">Max</button>
                    <button type="button" class="army-go-btn" id="armyTrainBtn">Train</button>
                </div>
                <button type="button" class="army-disband-btn" id="armyDisbandBtn">Disband</button>
            </div>
        </div>
    </div>

    <div class="army-status-bar">
        <span class="army-totals">
            {{ number_format($totalHave) }} soldiers &middot;
            {{ number_format($totalCost) }}g + {{ number_format($totalFood) }}f upkeep &middot;
            {{ number_format($capacityPercent, 1) }}% capacity
            @if($canHold > 0)
                (room for {{ number_format($canHold) }})
            @elseif($canHold == 0)
                (full)
            @endif
        </span>
    </div>
</div>

{{-- Military Strength --}}
<div class="bq-pop-summary">
    <div class="bq-pop-title">Military Strength</div>
    <div class="bq-pop-grid">
        <span class="bq-pop-label">Army:</span><span>{{ number_format($attackPower) }} ATK / {{ number_format($defensePower) }} DEF</span>
        <span class="bq-pop-label">Catapults:</span><span>{{ number_format($cAttackPower) }} ATK / {{ number_format($cDefensePower) }} DEF</span>
        <span class="bq-pop-label">Thieves:</span><span>{{ number_format($tAttackPower) }} ATK / {{ number_format($tDefensePower) }} DEF</span>
    </div>
</div>

{{-- Weapons & Capacity --}}
<div class="bq-pop-summary">
    <div class="bq-pop-title">Weapons & Capacity</div>
    <div class="bq-pop-grid">
        <span class="bq-pop-label">Capacity:</span><span>{{ number_format($maxSoldiers) }} max &middot; train {{ number_format($maxTrain) }} at a time &middot; {{ number_format($canTrain) }} slots free</span>
        <span class="bq-pop-label">Weapons:</span><span>{{ number_format($player->swords) }} swords &middot; {{ number_format($player->bows) }} bows &middot; {{ number_format($player->horses) }} horses &middot; {{ number_format($player->maces) }} maces</span>
    </div>
</div>

<script>
(function() {
    var selectedCard = null;
    var panel = document.getElementById('armyActionPanel');
    var infoEl = document.getElementById('armyInfo');
    var descEl = document.getElementById('armyDesc');
    var suggestEl = document.getElementById('armySuggest');
    var qtyInput = document.getElementById('armyQty');
    var totalHave = {{ $totalHave }};
    var canHold = {{ $canHold }};

    // Qty stepper
    document.getElementById('armyQtyMinus').addEventListener('click', function() {
        var v = parseInt(qtyInput.value, 10) || 1;
        if (v > 1) qtyInput.value = v - 1;
    });
    document.getElementById('armyQtyPlus').addEventListener('click', function() {
        var v = parseInt(qtyInput.value, 10) || 0;
        qtyInput.value = v + 1;
    });

    // Half / Max buttons
    document.getElementById('armyHalfBtn').addEventListener('click', function() {
        if (!selectedCard) return;
        var half = Math.floor(parseInt(selectedCard.dataset.maxTrain, 10) / 2);
        if (half > 0) qtyInput.value = half;
    });
    document.getElementById('armyMaxBtn').addEventListener('click', function() {
        if (!selectedCard) return;
        var max = parseInt(selectedCard.dataset.maxTrain, 10);
        if (max > 0) qtyInput.value = max;
    });

    // Card selection
    var cards = document.querySelectorAll('.army-card');
    for (var i = 0; i < cards.length; i++) {
        cards[i].addEventListener('click', function() {
            var card = this;
            if (selectedCard) selectedCard.classList.remove('army-card-selected');
            selectedCard = card;
            card.classList.add('army-card-selected');

            // Move panel right after selected card
            card.insertAdjacentElement('afterend', panel);
            panel.style.display = '';

            updatePanel(card);

            setTimeout(function() {
                panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 50);
        });
    }

    function calcSuggestion(card) {
        var maxTrain = parseInt(card.dataset.maxTrain, 10);
        var have = parseInt(card.dataset.have, 10);
        var soldierIdx = parseInt(card.dataset.soldier, 10);
        var roomCap = Math.min(maxTrain, Math.max(canHold, 0));

        if (maxTrain <= 0) return { qty: 0, reason: 'cannot train' };
        if (roomCap <= 0) return { qty: 0, reason: 'at capacity' };

        // Trained peasants (7): free, weak
        if (soldierIdx === 7) {
            if (totalHave === 0) return { qty: Math.min(10, roomCap), reason: 'bootstrap your army' };
            return { qty: Math.min(5, roomCap), reason: 'cheap filler' };
        }

        // Catapults (5): expensive resources, limited by town centers
        if (soldierIdx === 5) {
            return { qty: Math.min(3, roomCap), reason: 'siege power' };
        }

        // Thieves (8): expensive gold, limited by town centers
        if (soldierIdx === 8) {
            return { qty: Math.min(3, roomCap), reason: 'covert ops' };
        }

        // Combat units (1=archer, 2=swordsman, 3=horseman, 6=macemen, 9=unique)
        var growth = Math.max(5, Math.ceil(have * 0.25));
        var ratio = totalHave > 0 ? have / totalHave : 0;
        var reason = '';

        if (have === 0) {
            growth = Math.min(10, roomCap);
            reason = 'diversify army';
        } else if (ratio < 0.1 && totalHave > 20) {
            growth = Math.max(growth, Math.ceil(totalHave * 0.1));
            reason = 'underrepresented (' + Math.round(ratio * 100) + '%)';
        } else {
            reason = 'steady growth';
        }

        if (soldierIdx === 9) reason = 'elite unit';
        else if (soldierIdx === 3 && reason === 'steady growth') reason = 'cavalry power';

        return { qty: Math.min(growth, roomCap), reason: reason };
    }

    function updatePanel(card) {
        var maxTrain = parseInt(card.dataset.maxTrain, 10);
        var have = parseInt(card.dataset.have, 10);
        var needed = card.dataset.needed;
        var turns = card.dataset.turns;
        var own = parseInt(card.dataset.own, 10);

        var tcCapped = card.dataset.tcCapped === '1';
        var tcMax = parseInt(card.dataset.tcMax, 10);

        infoEl.textContent = 'Can train ' + maxTrain.toLocaleString() + ' \u00b7 Have ' + have.toLocaleString()
            + (own !== have ? ' (' + own.toLocaleString() + ' home)' : '');

        var descParts = [];
        if (needed) descParts.push('Needs: ' + needed);
        descParts.push('Trains in ' + turns + ' turn' + (turns != 1 ? 's' : ''));
        if (tcCapped) descParts.push('Limit: ' + tcMax + ' (1 per town center)');
        descEl.textContent = descParts.join(' \u00b7 ');
        descEl.style.display = '';

        // Suggestion chip
        var s = calcSuggestion(card);
        if (s.qty > 0) {
            suggestEl.style.display = '';
            var chipClass = maxTrain > 0 ? 'army-suggest-chip' : 'army-suggest-chip army-suggest-chip-disabled';
            suggestEl.innerHTML = 'Suggested: <span class="' + chipClass + '" title="' + (maxTrain > 0 ? 'Click to set qty' : 'Not enough resources') + '">'
                + s.qty.toLocaleString() + '</span> <span class="army-suggest-reason">(' + s.reason + ')</span>';
            if (maxTrain > 0) {
                suggestEl.querySelector('.army-suggest-chip').addEventListener('click', function() {
                    qtyInput.value = s.qty;
                });
            }
        } else {
            suggestEl.style.display = 'none';
        }

        qtyInput.value = 1;
    }

    // Train button
    document.getElementById('armyTrainBtn').addEventListener('click', function() {
        if (!selectedCard) return;
        var soldierIdx = selectedCard.dataset.soldier;
        var qty = parseInt(qtyInput.value, 10) || 0;
        if (qty <= 0) return;

        var data = {};
        data['qty' + soldierIdx] = qty;

        Game.Ajax.post('/game/army/train', data)
            .then(function(json) {
                if (json.success) {
                    setTimeout(function() { window.location.reload(); }, 800);
                }
            });
    });

    // Disband button
    document.getElementById('armyDisbandBtn').addEventListener('click', function() {
        if (!selectedCard) return;
        var soldierIdx = selectedCard.dataset.soldier;
        var qty = parseInt(qtyInput.value, 10) || 0;
        var name = selectedCard.dataset.sname;
        if (qty <= 0) return;

        if (!confirm('Are you sure you want to disband ' + qty + ' ' + name + '?')) return;

        var data = {};
        data['qty' + soldierIdx] = qty;

        Game.Ajax.post('/game/army/disband', data)
            .then(function(json) {
                if (json.success) {
                    setTimeout(function() { window.location.reload(); }, 800);
                }
            });
    });
})();
</script>
@endsection
