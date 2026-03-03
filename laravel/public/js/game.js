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

                // Update turn count
                if (json.state && json.state.turns_free !== undefined) {
                    TurnTimer.setTurns(json.state.turns_free);
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
                this._timerEl.textContent = 'maximum turns stored)';
                return;
            }

            var m = Math.floor(this._seconds / 60);
            var s = this._seconds % 60;
            this._timerEl.textContent =
                'next free month in ' + m + ' minute' + (m !== 1 ? 's' : '') +
                ' and ' + s + ' second' + (s !== 1 ? 's' : '') + ')';
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

    // ─── Turn Presets ─────────────────────────────────────────────
    const TurnPresets = {
        _container: null,
        _busy: false,

        init() {
            this._container = document.getElementById('turn-presets');
            if (!this._container) return;

            var btns = this._container.querySelectorAll('.turn-btn');
            for (var i = 0; i < btns.length; i++) {
                btns[i].addEventListener('click', function (e) {
                    var turns = parseInt(e.target.getAttribute('data-turns'), 10);
                    TurnPresets._endTurns(turns);
                });
            }
        },

        async _endTurns(qty) {
            if (this._busy) return;
            this._busy = true;

            // Highlight the clicked button
            var btns = this._container.querySelectorAll('.turn-btn');
            for (var i = 0; i < btns.length; i++) {
                btns[i].classList.remove('active');
                if (parseInt(btns[i].getAttribute('data-turns'), 10) === qty) {
                    btns[i].classList.add('active');
                }
            }

            try {
                await Ajax.post('/game/end-turns', { turns: qty }, {
                    sound: 'endTurn',
                    toastDuration: 8000
                });
            } catch (e) {
                // error already toasted
            }

            this._busy = false;

            // Remove active state after brief delay
            setTimeout(function () {
                var btns = TurnPresets._container.querySelectorAll('.turn-btn');
                for (var i = 0; i < btns.length; i++) btns[i].classList.remove('active');
            }, 1000);
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
        FaviconBadge: FaviconBadge
    };

    // ─── Auto-Init on DOM Ready ───────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        Toast.init();
        Toast.muted = Prefs.get('toastsMuted', false);
        ResourceBar.init();
        TurnPresets.init();
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
