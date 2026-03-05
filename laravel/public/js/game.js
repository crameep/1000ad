/* =================================================================
   1000 A.D. — Core Game JavaScript
   AJAX, Toasts, Turn Timer, Resource Bar, Modals, Preferences
   ================================================================= */
(function () {
    'use strict';

    // ─── Preferences (localStorage) ───────────────────────────────
    const Prefs = {
        _p: '1000ad_',
        get(key, def) {
            try {
                const v = localStorage.getItem(this._p + key);
                if (v === null) return def;
                return JSON.parse(v);
            } catch { return def; }
        },
        set(key, val) {
            try { localStorage.setItem(this._p + key, JSON.stringify(val)); } catch {}
        },
        remove(key) {
            try { localStorage.removeItem(this._p + key); } catch {}
        }
    };

    // ─── Toast Notifications ──────────────────────────────────────
    const Toast = {
        container: null,

        init() {
            this.container = document.getElementById('toast-container');
            if (!this.container) {
                this.container = document.createElement('div');
                this.container.id = 'toast-container';
                this.container.setAttribute('aria-live', 'polite');
                document.body.appendChild(this.container);
            }
        },

        /** Whether toasts are muted (errors always show) */
        muted: false,

        /**
         * @param {string} message - HTML message text
         * @param {string} type - 'success' | 'error' | 'warning' | 'info'
         * @param {number} duration - ms before auto-dismiss (0 = manual)
         */
        show(message, type, duration) {
            if (!this.container) this.init();
            type = type || 'info';
            duration = duration !== undefined ? duration : 5000;

            // Skip non-error toasts when muted
            if (this.muted && type !== 'error') return null;

            var toast = document.createElement('div');
            toast.className = 'toast toast-' + type + ' toast-enter';
            toast.innerHTML =
                '<div class="toast-content">' + message + '</div>' +
                '<button class="toast-close" aria-label="Close">&times;</button>';

            toast.querySelector('.toast-close').addEventListener('click', function () {
                Toast._dismiss(toast);
            });

            this.container.appendChild(toast);

            if (duration > 0) {
                setTimeout(function () { Toast._dismiss(toast); }, duration);
            }

            return toast;
        },

        _dismiss(toast) {
            if (toast._dismissed) return;
            toast._dismissed = true;
            toast.classList.remove('toast-enter');
            toast.classList.add('toast-exit');
            setTimeout(function () { toast.remove(); }, 300);
        }
    };

    // ─── AJAX Helper ──────────────────────────────────────────────
    var csrfToken = '';
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta) csrfToken = csrfMeta.getAttribute('content');

    const Ajax = {
        /**
         * POST JSON to a URL and return parsed response.
         * Automatically shows toast and updates resource bar on success.
         */
        async post(url, data, opts) {
            opts = opts || {};
            try {
                var body = new FormData();
                body.append('_token', csrfToken);
                if (data) {
                    Object.keys(data).forEach(function (k) {
                        body.append(k, data[k]);
                    });
                }

                var resp = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: body
                });

                var json = await resp.json();

                // Update resource bar if state included
                if (json.state) {
                    ResourceBar.update(json.state, !opts.noAnimate);
                }

                // Show toast or report box
                if (json.message && !opts.silent) {
                    var shown = Toast.show(json.message, json.success ? 'success' : 'error', opts.toastDuration || 6000);
                    // If toast was suppressed (muted), show in report box
                    if (!shown && json.success && TurnReport._box) {
                        TurnReport.show(json.message);
                    }
                }

                // Update turn count and game date
                if (json.state && json.state.turns_free !== undefined) {
                    TurnTimer.setTurns(json.state.turns_free);
                }
                if (json.state && json.state.game_date) {
                    var dateEl = document.querySelector('.game-date');
                    if (dateEl) dateEl.textContent = json.state.game_date;
                }

                // Trigger sound hook
                if (json.success && opts.sound && window.GameSounds) {
                    window.GameSounds.play(opts.sound);
                }

                return json;
            } catch (err) {
                Toast.show('Network error. Please try again.', 'error');
                throw err;
            }
        },

        async get(url) {
            try {
                var resp = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                return await resp.json();
            } catch (err) {
                Toast.show('Network error.', 'error');
                throw err;
            }
        }
    };

    // ─── Resource Bar ─────────────────────────────────────────────
    const ResourceBar = {
        _el: {},

        init() {
            var els = document.querySelectorAll('[data-resource]');
            for (var i = 0; i < els.length; i++) {
                var key = els[i].getAttribute('data-resource');
                this._el[key] = els[i];
            }
        },

        /**
         * Update displayed resource values from a state object.
         * @param {Object} state - {score, gold, wood, iron, food, tools, people, mland, fland, pland, turns_free}
         * @param {boolean} animate - whether to animate the number change
         */
        update(state, animate) {
            var self = this;
            Object.keys(state).forEach(function (key) {
                var el = self._el[key];
                if (!el) return;

                var newVal = state[key];
                var formatted = self._format(newVal);

                if (animate) {
                    var oldText = el.textContent.replace(/,/g, '').trim();
                    var oldVal = parseInt(oldText, 10);
                    if (!isNaN(oldVal) && oldVal !== newVal) {
                        self._animateValue(el, oldVal, newVal, 600);
                        // Flash effect
                        el.classList.add('resource-changed');
                        setTimeout(function () { el.classList.remove('resource-changed'); }, 800);
                        return;
                    }
                }

                el.textContent = formatted;
            });
        },

        _format(n) {
            return Number(n).toLocaleString('en-US');
        },

        _animateValue(el, from, to, duration) {
            var start = performance.now();
            var diff = to - from;

            function step(now) {
                var progress = Math.min((now - start) / duration, 1);
                // Ease out cubic
                var eased = 1 - Math.pow(1 - progress, 3);
                var current = Math.round(from + diff * eased);
                el.textContent = Number(current).toLocaleString('en-US');
                if (progress < 1) requestAnimationFrame(step);
            }

            requestAnimationFrame(step);
        }
    };

    // ─── Turn Timer ───────────────────────────────────────────────
    const TurnTimer = {
        _seconds: 0,
        _turns: 0,
        _maxTurns: 0,
        _minutesPerTurn: 5,
        _interval: null,
        _timerEl: null,
        _countEl: null,
        _fullEl: null,
        _fullSepEl: null,

        /**
         * Initialize the live countdown timer.
         */
        init(seconds, turns, maxTurns, minutesPerTurn) {
            this._seconds = Math.floor(seconds);
            this._turns = turns;
            this._maxTurns = maxTurns;
            this._minutesPerTurn = minutesPerTurn || 5;
            this._timerEl = document.getElementById('turn-timer');
            this._countEl = document.getElementById('turn-count');
            this._fullEl = document.getElementById('turn-full');
            this._fullSepEl = document.getElementById('turn-full-sep');

            if (this._interval) clearInterval(this._interval);
            this._interval = setInterval(function () { TurnTimer._tick(); }, 1000);
            this._render();
        },

        setTurns(n) {
            this._turns = n;
            if (this._countEl) this._countEl.textContent = n;
            // Reset timer for next turn
            this._seconds = this._minutesPerTurn * 60;
            this._render();
        },

        _tick() {
            if (this._turns >= this._maxTurns) {
                this._render();
                return;
            }

            this._seconds--;

            if (this._seconds <= 0) {
                // New turn available!
                this._turns++;
                if (this._countEl) {
                    this._countEl.textContent = this._turns;
                    this._countEl.classList.add('turn-available');
                    setTimeout(function () {
                        TurnTimer._countEl.classList.remove('turn-available');
                    }, 2000);
                }
                this._seconds = this._minutesPerTurn * 60;

                // Play sound if available
                if (window.GameSounds) window.GameSounds.play('turnReady');
            }

            this._render();
        },

        _render() {
            if (!this._timerEl) return;

            if (this._turns >= this._maxTurns) {
                this._timerEl.textContent = 'Max stored';
                // Hide "Full in" when at max
                if (this._fullEl) this._fullEl.style.display = 'none';
                if (this._fullSepEl) this._fullSepEl.style.display = 'none';
                return;
            }

            var m = Math.floor(this._seconds / 60);
            var s = this._seconds % 60;
            var pad = s < 10 ? '0' : '';
            this._timerEl.textContent = 'Next in ' + m + ':' + pad + s;

            // Calculate and display time to full
            if (this._fullEl) {
                this._fullEl.style.display = '';
                if (this._fullSepEl) this._fullSepEl.style.display = '';

                var turnsNeeded = this._maxTurns - this._turns;
                var totalSecondsToFull = this._seconds + (turnsNeeded - 1) * this._minutesPerTurn * 60;
                var totalMinutes = Math.ceil(totalSecondsToFull / 60);
                var h = Math.floor(totalMinutes / 60);
                var rm = totalMinutes % 60;

                if (h > 0) {
                    this._fullEl.textContent = 'Full in ' + h + 'h ' + rm + 'm';
                } else {
                    this._fullEl.textContent = 'Full in ' + rm + 'm';
                }
            }
        }
    };

    // ─── Confirmation Modal ───────────────────────────────────────
    const Modal = {
        _overlay: null,

        /**
         * Show a confirmation modal.
         * @param {string} title
         * @param {string} message
         * @param {Function} onConfirm - called when user clicks Confirm
         * @param {string} confirmText - button text (default "Confirm")
         */
        confirm(title, message, onConfirm, confirmText) {
            this.close(); // close any existing

            confirmText = confirmText || 'Confirm';

            var overlay = document.createElement('div');
            overlay.className = 'modal-overlay';
            overlay.innerHTML =
                '<div class="modal-box">' +
                    '<div class="modal-header">' + title + '</div>' +
                    '<div class="modal-body">' + message + '</div>' +
                    '<div class="modal-footer">' +
                        '<button class="modal-btn modal-btn-confirm">' + confirmText + '</button>' +
                        '<button class="modal-btn modal-btn-cancel">Cancel</button>' +
                    '</div>' +
                '</div>';

            overlay.querySelector('.modal-btn-confirm').addEventListener('click', function () {
                Modal.close();
                if (onConfirm) onConfirm();
            });

            overlay.querySelector('.modal-btn-cancel').addEventListener('click', function () {
                Modal.close();
            });

            // Click outside to close
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) Modal.close();
            });

            // Escape key to close
            this._escHandler = function (e) {
                if (e.key === 'Escape') Modal.close();
            };
            document.addEventListener('keydown', this._escHandler);

            document.body.appendChild(overlay);
            this._overlay = overlay;

            // Focus the cancel button for accessibility
            overlay.querySelector('.modal-btn-cancel').focus();
        },

        close() {
            if (this._overlay) {
                this._overlay.remove();
                this._overlay = null;
            }
            if (this._escHandler) {
                document.removeEventListener('keydown', this._escHandler);
                this._escHandler = null;
            }
        }
    };

    // ─── Turn Presets (Custom Counter) ─────────────────────────────
    const TurnPresets = {
        _container: null,
        _busy: false,
        _value: 1,
        _min: 1,
        _max: 12,
        _valueEl: null,
        _goBtn: null,

        init() {
            this._container = document.getElementById('turn-presets');
            if (!this._container) return;

            this._valueEl = document.getElementById('turn-value');
            this._goBtn = document.getElementById('turn-go');
            var minusBtn = document.getElementById('turn-minus');
            var plusBtn = document.getElementById('turn-plus');

            // Restore saved value
            this._value = Prefs.get('customTurns', 1);
            if (this._value < this._min) this._value = this._min;
            if (this._value > this._max) this._value = this._max;
            if (this._valueEl) this._valueEl.textContent = this._value;

            if (minusBtn) {
                minusBtn.addEventListener('click', function () {
                    TurnPresets._adjust(-1);
                });
            }
            if (plusBtn) {
                plusBtn.addEventListener('click', function () {
                    TurnPresets._adjust(1);
                });
            }
            if (this._goBtn) {
                this._goBtn.addEventListener('click', function () {
                    TurnPresets._endTurns(TurnPresets._value);
                });
            }

            // Also support legacy preset buttons if they exist
            var btns = this._container.querySelectorAll('.turn-btn[data-turns]');
            for (var i = 0; i < btns.length; i++) {
                btns[i].addEventListener('click', function (e) {
                    var turns = parseInt(e.target.getAttribute('data-turns'), 10);
                    TurnPresets._endTurns(turns);
                });
            }
        },

        _adjust(delta) {
            this._value += delta;
            if (this._value < this._min) this._value = this._min;
            if (this._value > this._max) this._value = this._max;
            if (this._valueEl) this._valueEl.textContent = this._value;
            Prefs.set('customTurns', this._value);
        },

        async _endTurns(qty) {
            if (this._busy) return;
            this._busy = true;

            // Highlight the Go button
            if (this._goBtn) this._goBtn.classList.add('active');

            try {
                await Ajax.post('/game/end-turns', { turns: qty }, {
                    sound: 'endTurn',
                    toastDuration: 8000
                });
                // Refresh page content so queues, timers, etc. reflect new state
                this._refreshContent();
            } catch (e) {
                // error already toasted
            }

            this._busy = false;

            // Remove active state after brief delay
            setTimeout(function () {
                if (TurnPresets._goBtn) TurnPresets._goBtn.classList.remove('active');
            }, 1000);
        },

        /**
         * Fetch the current page and replace .panel-body content
         * so queues, counters, and status values stay up to date.
         */
        async _refreshContent() {
            try {
                var resp = await fetch(window.location.href, {
                    headers: { 'X-Requested-With': 'Fetch' }
                });
                if (!resp.ok) return;
                var html = await resp.text();
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var newBody = doc.querySelector('.right-panel > .panel > .panel-body');
                var curBody = document.querySelector('.right-panel > .panel > .panel-body');
                if (newBody && curBody) {
                    curBody.innerHTML = newBody.innerHTML;
                    // Re-run any inline scripts in the new content
                    curBody.querySelectorAll('script').forEach(function (old) {
                        var s = document.createElement('script');
                        s.textContent = old.textContent;
                        old.parentNode.replaceChild(s, old);
                    });
                }
                // Also refresh explore status
                var newExplore = doc.getElementById('explore-status');
                var curExplore = document.getElementById('explore-status');
                if (newExplore && curExplore) {
                    curExplore.innerHTML = newExplore.innerHTML;
                    curExplore.className = newExplore.className;
                }
            } catch (e) {
                // Silent fail — resource bar already updated via AJAX
            }
        }
    };

    // ─── Quick Explore ─────────────────────────────────────────────
    const QuickExplore = {
        _busy: false,

        init() {
            var maxBtn = document.getElementById('explore-max');
            var safeBtn = document.getElementById('explore-safe');
            var horseSelect = document.getElementById('explore-horses');
            var landSelect = document.getElementById('explore-land');

            // Restore horse preference
            if (horseSelect) {
                var saved = Prefs.get('exploreHorses', 0);
                horseSelect.value = saved;
                horseSelect.addEventListener('change', function () {
                    Prefs.set('exploreHorses', parseInt(horseSelect.value, 10));
                });
            }

            // Restore land preference
            if (landSelect) {
                var savedLand = Prefs.get('exploreLand', 0);
                landSelect.value = savedLand;
                landSelect.addEventListener('change', function () {
                    Prefs.set('exploreLand', parseInt(landSelect.value, 10));
                });
            }

            if (maxBtn) {
                maxBtn.addEventListener('click', function () {
                    QuickExplore.send(9999);
                });
            }
            if (safeBtn) {
                safeBtn.addEventListener('click', function () {
                    QuickExplore.sendSafe();
                });
            }
        },

        /**
         * Calculate a safe number of explorers that won't starve the empire.
         * Considers current food, seasonal production, and upcoming cold months.
         */
        sendSafe() {
            var bar = document.getElementById('quick-explore');
            if (!bar) return;

            var food = parseInt(bar.dataset.food, 10) || 0;
            var month = parseInt(bar.dataset.month, 10) || 1;
            var foodPerExplorer = parseInt(bar.dataset.foodPerExplorer, 10) || 1;
            var netSummer = parseInt(bar.dataset.foodNetSummer, 10) || 0;
            var netWinter = parseInt(bar.dataset.foodNetWinter, 10) || 0;

            // Account for horse multiplier on trip length
            var horseSelect = document.getElementById('explore-horses');
            var withHorses = horseSelect ? parseInt(horseSelect.value, 10) : 0;
            var tripLength = 6 + (withHorses >= 1 && withHorses <= 3 ? withHorses * 2 : 0);

            // Project food over the trip to find the minimum point
            var projectedFood = food;
            var minFood = food;
            var m = month;
            for (var i = 0; i < tripLength; i++) {
                m = (m % 12) + 1;
                var isCold = (m >= 11 || m <= 3);
                projectedFood += isCold ? netWinter : netSummer;
                if (projectedFood < minFood) minFood = projectedFood;
            }

            // Safety buffer: 3 turns of worst-case consumption
            var worstNet = Math.min(netSummer, netWinter);
            var safetyBuffer = worstNet < 0 ? Math.abs(worstNet) * 3 : 0;

            // Available food = current food minus what we need to survive the trip
            // Use the minimum projected point to gauge how tight things get
            var foodDeficit = food - minFood; // how much food drops during the trip
            var availableFood = food - foodDeficit - safetyBuffer;
            if (availableFood < 0) availableFood = 0;

            var safeQty = Math.floor(availableFood / Math.max(1, foodPerExplorer));

            // Game requires minimum 4 explorers
            if (safeQty < 4) {
                Toast.show('Not safe to explore right now \u2014 food reserves too low for the upcoming months.', 'warning', 5000);
                return;
            }

            // Cap by people/capacity limits (pre-computed server-side)
            var maxSend = parseInt(bar.dataset.maxSend, 10) || 0;
            safeQty = Math.min(safeQty, maxSend);

            // Cap by horse availability
            if (withHorses >= 1 && withHorses <= 3) {
                var horses = parseInt(bar.dataset.horses, 10) || 0;
                var horseLimit = Math.floor(horses / withHorses);
                safeQty = Math.min(safeQty, horseLimit);
            }

            if (safeQty < 4) {
                Toast.show('Not enough resources to safely explore right now.', 'warning', 5000);
                return;
            }

            this.send(safeQty);
        },

        async send(qty) {
            if (this._busy) return;
            this._busy = true;

            var horseSelect = document.getElementById('explore-horses');
            var landSelect = document.getElementById('explore-land');
            var withHorses = horseSelect ? parseInt(horseSelect.value, 10) : 0;
            var seekLand = landSelect ? parseInt(landSelect.value, 10) : 0;

            try {
                await Ajax.post('/game/explore/send', {
                    eflag: 'send_explorers',
                    qty: qty,
                    withHorses: withHorses,
                    seekLand: seekLand
                }, { toastDuration: 6000 });
                // Refresh page content to update explore status
                TurnPresets._refreshContent();
            } catch (e) {
                // error already toasted
            }

            this._busy = false;
        }
    };

    // ─── Royal Adviser Toggle ──────────────────────────────────────
    const Advisor = {
        init() {
            // Restore collapsed state from preferences
            if (Prefs.get('advisorOpen', true) === false) {
                var body = document.getElementById('advisor-body');
                var toggle = document.getElementById('advisor-toggle');
                if (body) body.style.display = 'none';
                if (toggle) toggle.innerHTML = '&#9654;';
            }
        }
    };

    // ─── Sticky Resource Bar ──────────────────────────────────────
    const StickyBar = {
        _bar: null,
        _sentinel: null,

        init() {
            this._bar = document.querySelector('.resource-bar');
            if (!this._bar) return;

            // Create a sentinel element before the resource bar
            this._sentinel = document.createElement('div');
            this._sentinel.className = 'sticky-sentinel';
            this._sentinel.style.height = '1px';
            this._bar.parentNode.insertBefore(this._sentinel, this._bar);

            // Use IntersectionObserver if available
            if ('IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        StickyBar._bar.classList.toggle('is-sticky', !entry.isIntersecting);
                    });
                }, { threshold: 0 });

                observer.observe(this._sentinel);
            }
        }
    };

    // ─── Favicon Badge ────────────────────────────────────────────
    const FaviconBadge = {
        _original: null,
        _canvas: null,
        _link: null,

        init() {
            this._link = document.querySelector('link[rel="icon"]') ||
                          document.querySelector('link[rel="shortcut icon"]');
            if (this._link) {
                this._original = this._link.href;
            }
            this._canvas = document.createElement('canvas');
            this._canvas.width = 32;
            this._canvas.height = 32;
        },

        setBadge(count) {
            if (!this._link || !this._canvas.getContext) return;

            var img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = function () {
                var ctx = FaviconBadge._canvas.getContext('2d');
                ctx.clearRect(0, 0, 32, 32);
                ctx.drawImage(img, 0, 0, 32, 32);

                if (count > 0) {
                    // Draw red circle
                    ctx.fillStyle = '#cc3333';
                    ctx.beginPath();
                    ctx.arc(24, 8, 8, 0, Math.PI * 2);
                    ctx.fill();

                    // Draw number
                    ctx.fillStyle = '#ffffff';
                    ctx.font = 'bold 11px sans-serif';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText(count > 9 ? '9+' : String(count), 24, 8);
                }

                FaviconBadge._link.href = FaviconBadge._canvas.toDataURL('image/png');
            };
            img.src = this._original || '/images/icons/icon-192.png';
        },

        clear() {
            if (this._link && this._original) {
                this._link.href = this._original;
            }
        }
    };

    // ─── AJAX Form Enhancement ────────────────────────────────────
    // Intercept forms with data-ajax attribute and submit via AJAX
    function initAjaxForms() {
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form.hasAttribute('data-ajax')) return;

            e.preventDefault();

            var url = form.action;
            var data = {};
            var formData = new FormData(form);
            formData.forEach(function (value, key) {
                if (key !== '_token') data[key] = value;
            });

            var sound = form.getAttribute('data-sound') || null;
            Ajax.post(url, data, { sound: sound }).then(function (json) {
                // If the form has a data-reload attribute, reload the page section
                if (form.hasAttribute('data-reload')) {
                    window.location.reload();
                }
            });
        });
    }

    // ─── Turn Report Box (when toasts are muted) ──────────────────
    const TurnReport = {
        _box: null,
        _content: null,
        _title: null,
        _arrow: null,
        _toggle: null,

        init() {
            this._box = document.getElementById('turn-report-box');
            this._content = document.getElementById('turn-report-content');
            this._title = document.getElementById('turn-report-title');
            this._arrow = document.getElementById('turn-report-arrow');
            this._toggle = document.getElementById('turn-report-toggle');

            if (!this._toggle) return;

            var self = this;
            this._toggle.addEventListener('click', function () {
                var open = self._content.style.display !== 'none';
                self._content.style.display = open ? 'none' : 'block';
                self._arrow.classList.toggle('is-open', !open);
            });
        },

        /**
         * Show the turn report in the collapsible box.
         * @param {string} html - report HTML content
         */
        show(html) {
            if (!this._box) return;
            this._content.innerHTML = html;
            this._content.style.display = 'block';
            this._arrow.classList.add('is-open');
            this._box.style.display = 'block';
        },

        hide() {
            if (!this._box) return;
            this._box.style.display = 'none';
        }
    };

    // ─── Toast Toggle ────────────────────────────────────────────
    function initToastToggle() {
        var btn = document.getElementById('toast-toggle');
        if (!btn) return;

        function applyState() {
            btn.textContent = Toast.muted ? '\u{1F515}' : '\u{1F514}';
            btn.title = Toast.muted ? 'Notifications off' : 'Notifications on';
        }

        applyState();

        btn.addEventListener('click', function () {
            Toast.muted = !Toast.muted;
            Prefs.set('toastsMuted', Toast.muted);
            applyState();
            // Temporarily unmute to show the confirmation toast
            var wasMuted = Toast.muted;
            Toast.muted = false;
            Toast.show(wasMuted ? 'Notifications muted — reports shown in header' : 'Notifications enabled', 'info', 2000);
            Toast.muted = wasMuted;
            // Hide report box when unmuting
            if (!wasMuted) TurnReport.hide();
        });
    }

    // ─── Expose Global ────────────────────────────────────────────
    window.Game = {
        Prefs: Prefs,
        Toast: Toast,
        Ajax: Ajax,
        ResourceBar: ResourceBar,
        TurnTimer: TurnTimer,
        Modal: Modal,
        TurnPresets: TurnPresets,
        TurnReport: TurnReport,
        StickyBar: StickyBar,
        FaviconBadge: FaviconBadge,
        QuickExplore: QuickExplore,
        Advisor: Advisor
    };

    // Global toggleAdvisor function (called from Blade component onclick)
    window.toggleAdvisor = function () {
        var body = document.getElementById('advisor-body');
        var toggle = document.getElementById('advisor-toggle');
        if (!body) return;
        if (body.style.display === 'none') {
            body.style.display = '';
            if (toggle) toggle.innerHTML = '&#9660;';
            Prefs.set('advisorOpen', true);
        } else {
            body.style.display = 'none';
            if (toggle) toggle.innerHTML = '&#9654;';
            Prefs.set('advisorOpen', false);
        }
    };

    // ─── Auto-Init on DOM Ready ───────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        Toast.init();
        var mutedDefault = window.innerWidth <= 600 ? true : false;
        Toast.muted = Prefs.get('toastsMuted', mutedDefault);
        ResourceBar.init();
        TurnPresets.init();
        QuickExplore.init();
        Advisor.init();
        StickyBar.init();
        FaviconBadge.init();
        TurnReport.init();
        initAjaxForms();
        initToastToggle();

        // Convert server-side flash message to toast or report box
        var flashEl = document.querySelector('.eflag-message');
        if (flashEl) {
            var html = flashEl.innerHTML;
            flashEl.remove();
            if (Toast.muted) {
                // When muted, show in the report box instead
                TurnReport.show(html);
            } else {
                Toast.show(html, 'info', 8000);
            }
        }

        // Set favicon badge from server data
        var badgeCount = parseInt(document.body.getAttribute('data-badge-count') || '0', 10);
        if (badgeCount > 0) {
            FaviconBadge.setBadge(badgeCount);
        }
    });

})();
