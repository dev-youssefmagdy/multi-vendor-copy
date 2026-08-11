<script>
    window.__i18n = @json(json_decode(file_get_contents(lang_path(app()->getLocale() . '.json')), true) ?? []);
    window.trans = function (key, replacements) {
        var str = (window.__i18n && Object.prototype.hasOwnProperty.call(window.__i18n, key)) ? window.__i18n[key] : key;
        if (replacements) {
            Object.keys(replacements).forEach(function (k) {
                str = str.split(':' + k).join(replacements[k]);
            });
        }
        return str;
    };
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('js/lazyload.js') }}" defer></script>
@vite('resources/js/storefront-cart.js')

<script>
    // ── Storefront Toasts (Souqify palette: navy + blue + gold) ────────────────
    (function () {
        function toastPosition() {
            return document.documentElement.getAttribute('dir') === 'rtl' ? 'top-start' : 'top-end';
        }

        function normalizeToastType(type) {
            var allowed = ['success', 'error', 'warning', 'info', 'question'];
            return allowed.indexOf(type) !== -1 ? type : 'success';
        }

        // Souqify toast palette per icon
        // success → blue, error → red, warning → gold, info → navy
        function toastTheme(type) {
            switch (type) {
                case 'success':
                    return { bg: '#0159ED', color: '#FFFFFF', accent: '#FFE100' };
                case 'error':
                    return { bg: '#DC2626', color: '#FFFFFF', accent: '#FFFFFF' };
                case 'warning':
                    return { bg: '#FFE100', color: '#001537', accent: '#001537' };
                case 'info':
                    return { bg: '#001537', color: '#FFFFFF', accent: '#FFE100' };
                default:
                    return { bg: '#0159ED', color: '#FFFFFF', accent: '#FFE100' };
            }
        }

        window.showStorefrontToast = function (message, type) {
            if (!message) return;
            var theme = toastTheme(type || 'success');
            Swal.mixin({
                toast: true,
                position: toastPosition(),
                showConfirmButton: false,
                showCloseButton: true,
                timer: 3500,
                timerProgressBar: true,
                background: theme.bg,
                color: theme.color,
                iconColor: theme.accent,
                customClass: {
                    popup: 'souqify-toast shadow-2xl rounded-xl border-0',
                    title: 'text-[13px] font-medium font-[Outfit]',
                    closeButton: 'souqify-toast-close',
                    timerProgressBar: 'souqify-toast-progress',
                },
            }).fire({
                icon: normalizeToastType(type || 'success'),
                title: message,
            });
        };

        function showFlashMessages() {
            var payload = document.getElementById('storefront-flash-messages');
            if (!payload) return;
            try {
                var messages = JSON.parse(payload.textContent || '[]');
                payload.remove();
                messages.forEach(function (entry, index) {
                    setTimeout(function () {
                        window.showStorefrontToast(entry.message, entry.type || 'success');
                    }, index * 250);
                });
            } catch (error) {
                console.error('Failed to parse storefront flash messages.', error);
            }
        }

        document.addEventListener('DOMContentLoaded', showFlashMessages);
        document.addEventListener('livewire:navigated', showFlashMessages);
    })();

    // ── Variant Selection Modal ──────────────────────────────────────────────────
    (function () {
        var _modalData = null;
        var _selectedVariantId = null;

        window.openVariantModal = function (productId, productName, variants) {
            _modalData = { productId: productId, productName: productName, variants: variants };
            _selectedVariantId = variants.length > 0 ? variants[0].id : null;

            var modal = document.getElementById('variant-select-modal');
            var title = document.getElementById('variant-modal-title');
            var list = document.getElementById('variant-modal-list');
            if (!modal) return;

            title.textContent = productName;
            list.innerHTML = '';

            variants.forEach(function (v, i) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.dataset.variantId = v.id;

                var isFirst = (i === 0);
                btn.className = 'variant-option w-full text-left px-4 py-3 rounded-lg border text-[14px] transition ' +
                    (isFirst
                        ? 'border-blue-700 bg-blue-50 font-medium'
                        : 'border-neutral-200 hover:border-blue-700');

                btn.textContent = v.label + (v.price ? '  ' + v.price : '');

                btn.addEventListener('click', function () {
                    _selectedVariantId = v.id;
                    list.querySelectorAll('.variant-option').forEach(function (el) {
                        el.classList.remove('border-blue-700', 'bg-blue-50', 'font-medium');
                        el.classList.add('border-neutral-200');
                    });
                    this.classList.remove('border-neutral-200');
                    this.classList.add('border-blue-700', 'bg-blue-50', 'font-medium');
                });

                list.appendChild(btn);
            });

            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        };

        window.closeVariantModal = function () {
            var modal = document.getElementById('variant-select-modal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
            _modalData = null;
            _selectedVariantId = null;
        };

        document.addEventListener('DOMContentLoaded', function () {
            var confirmBtn = document.getElementById('variant-modal-confirm');
            if (confirmBtn) {
                confirmBtn.addEventListener('click', function () {
                    if (!_modalData || _selectedVariantId === null) return;
                    Livewire.dispatch('add-to-cart-variant', {
                        productId: _modalData.productId,
                        variantId: _selectedVariantId,
                    });
                    closeVariantModal();
                });
            }

            var modal = document.getElementById('variant-select-modal');
            if (modal) {
                modal.addEventListener('click', function (e) {
                    if (e.target === modal) closeVariantModal();
                });
            }
        });
    })();

    // ── Mobile menu (souqify) ───────────────────────────────────────────────────
    window.openMobileMenu = function () {
        var el = document.getElementById('mobileMenu');
        if (el) el.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    };
    window.closeMobileMenu = function () {
        var el = document.getElementById('mobileMenu');
        if (el) el.classList.add('hidden');
        document.body.style.overflow = '';
    };

    // ── Favorites (localStorage) ─────────────────────────────────────────────────
    (function initFavorites() {
        const FAV_KEY = 'souqify_favorites';

        function getFavs() {
            try { return JSON.parse(localStorage.getItem(FAV_KEY) || '[]'); } catch { return []; }
        }
        function saveFavs(list) { localStorage.setItem(FAV_KEY, JSON.stringify(list)); }

        function isFavored(slug) { return getFavs().some(f => f.slug === slug); }

        function paintHearts() {
            document.querySelectorAll('.souqify-fav-btn').forEach(btn => {
                const slug = btn.getAttribute('data-slug');
                const icon = btn.querySelector('.fav-icon');
                if (!icon) return;
                if (isFavored(slug)) {
                    icon.setAttribute('fill', '#0159ED');
                    icon.setAttribute('stroke', '#0159ED');
                } else {
                    icon.setAttribute('fill', 'none');
                    icon.setAttribute('stroke', '#475569');
                }
            });
        }

        window.souqifyToggleFavorite = function (btn) {
            const slug = btn.getAttribute('data-slug');
            let item = null;
            try { item = JSON.parse(btn.getAttribute('data-fav') || '{}'); } catch { item = { slug }; }
            const favs = getFavs();
            const idx = favs.findIndex(f => f.slug === slug);
            if (idx >= 0) {
                favs.splice(idx, 1);
                window.dispatchEvent(new CustomEvent('storefront-toast', { detail: { type: 'info', message: '{{ __('Removed from favorites') }}' } }));
            } else {
                favs.push(Object.assign({}, item, { added: Date.now() }));
                window.dispatchEvent(new CustomEvent('storefront-toast', { detail: { type: 'success', message: '{{ __('Added to favorites') }}' } }));
            }
            saveFavs(favs);
            paintHearts();
            window.dispatchEvent(new CustomEvent('souqify-favorites-changed'));
        };

        document.addEventListener('DOMContentLoaded', paintHearts);
        document.addEventListener('livewire:navigated', paintHearts);
    })();

    // ── Search Autocomplete + History ────────────────────────────────────────────
    (function initSearchHistory() {
        const HISTORY_KEY = 'souqify_search_history';
        const MAX_HISTORY = 8;

        function getHistory() {
            try { return JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]'); } catch { return []; }
        }
        function saveToHistory(term) {
            if (!term || term.length < 2) return;
            const h = getHistory().filter(x => x !== term);
            h.unshift(term);
            localStorage.setItem(HISTORY_KEY, JSON.stringify(h.slice(0, MAX_HISTORY)));
        }
        function clearHistory() { localStorage.removeItem(HISTORY_KEY); }
        function safeText(str) { const d = document.createElement('div'); d.textContent = str ?? ''; return d.innerHTML; }

        function bindForms() {
            document.querySelectorAll('form[data-autocomplete-url] input[name="q"]').forEach(function (input) {
                if (input.__souqifyACBound) return;
                input.__souqifyACBound = true;

                const form = input.closest('form');
                const autocompleteUrl = form.dataset.autocompleteUrl;
                const container = input.closest('.souqify-search-inner') || input.parentElement;
                if (!container) return;
                container.style.position = 'relative';

                const dropdown = document.createElement('div');
                dropdown.className = 'souqify-ac-dropdown absolute left-0 right-0 top-[calc(100%+8px)] bg-white rounded-xl shadow-2xl border border-neutral-100 z-[300] hidden overflow-hidden';
                container.appendChild(dropdown);

                let timer = null, abortCtrl = null;

                function show(html) { dropdown.innerHTML = html; dropdown.classList.remove('hidden'); }
                function hide() { dropdown.classList.add('hidden'); dropdown.innerHTML = ''; }

                function bindHistoryEvents() {
                    dropdown.querySelector('.souqify-clear-hist')?.addEventListener('click', function (e) {
                        e.stopPropagation(); clearHistory(); hide();
                    });
                    dropdown.querySelectorAll('.souqify-sh-item').forEach(function (el) {
                        el.addEventListener('mousedown', function (e) {
                            e.preventDefault(); input.value = el.dataset.term; saveToHistory(el.dataset.term); hide(); form.submit();
                        });
                    });
                }

                function bindResultEvents(kw) {
                    dropdown.querySelectorAll('a[data-term]').forEach(function (a) {
                        a.addEventListener('mousedown', function (e) { e.preventDefault(); saveToHistory(kw); window.location.href = a.href; });
                    });
                    dropdown.querySelector('.souqify-see-all')?.addEventListener('mousedown', function (e) {
                        e.preventDefault(); saveToHistory(kw); window.location.href = e.currentTarget.href;
                    });
                }

                function renderHistory() {
                    const hist = getHistory();
                    if (hist.length === 0) { hide(); return; }
                    const items = hist.map(function (t) {
                        return '<div class="souqify-sh-item flex items-center gap-3 px-4 py-2.5 hover:bg-neutral-50 cursor-pointer" data-term="' + safeText(t) + '">' +
                            '<svg class="w-4 h-4 text-neutral-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' +
                            '<span class="text-[13px] text-neutral-700 flex-1 truncate">' + safeText(t) + '</span></div>';
                    }).join('');
                    show('<div class="flex items-center justify-between px-4 py-2 border-b border-neutral-100">' +
                        '<span class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wide">{{ __("Recent searches") }}</span>' +
                        '<button class="souqify-clear-hist text-[12px] text-blue-700 hover:underline">{{ __("Clear") }}</button></div>' + items);
                    bindHistoryEvents();
                }

                function renderLoading() {
                    show('<div class="flex items-center justify-center gap-2 px-4 py-5 text-[13px] text-neutral-400">' +
                        '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>' +
                        '{{ __("Searching…") }}</div>');
                }

                function renderResults(products, kw) {
                    if (products.length === 0) {
                        show('<div class="px-4 py-5 text-center text-[13px] text-neutral-400">{{ __("No products found for") }} "<strong>' + safeText(kw) + '</strong>"</div>');
                        return;
                    }
                    const placeholder = '<svg class="w-5 h-5 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';
                    const rows = products.map(function (p) {
                        const priceHtml = p.has_discount
                            ? '<span class="text-[12px] text-blue-700 font-semibold">' + safeText(String(p.price)) + '</span> <span class="text-[11px] text-neutral-400 line-through">' + safeText(String(p.original_price)) + '</span>'
                            : '<span class="text-[12px] text-blue-700 font-semibold">' + safeText(String(p.price)) + '</span>';
                        return '<a href="' + p.url + '" data-term="' + safeText(kw) + '" class="flex items-center gap-3 px-4 py-2.5 hover:bg-neutral-50 transition">' +
                            '<div class="w-10 h-10 rounded-lg bg-neutral-100 flex items-center justify-center shrink-0 overflow-hidden">' +
                            (p.image ? '<img loading="lazy" src="' + p.image + '" alt="' + safeText(p.name) + '" class="w-full h-full object-contain">' : placeholder) + '</div>' +
                            '<div class="flex-1 min-w-0"><div class="text-[13px] font-medium text-slate-900 truncate">' + safeText(p.name) + '</div>' +
                            '<div class="flex items-center gap-1.5">' + priceHtml + '</div></div></a>';
                    }).join('');
                    const seeAll = form.action + '?q=' + encodeURIComponent(kw);
                    show('<div class="px-4 py-2 border-b border-neutral-100"><span class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wide">{{ __("Products") }}</span></div>' +
                        rows +
                        '<a href="' + seeAll + '" class="souqify-see-all flex items-center justify-center gap-1 px-4 py-2.5 text-[12px] font-semibold text-blue-700 border-t border-neutral-100 hover:bg-neutral-50 transition">' +
                        '{{ __("See all results for") }} "<strong>' + safeText(kw) + '</strong>"' +
                        '<svg class="w-3.5 h-3.5" style="' + (document.documentElement.dir === 'rtl' ? 'transform:rotate(180deg)' : '') + '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>');
                    bindResultEvents(kw);
                }

                function fetchResults(kw) {
                    if (abortCtrl) abortCtrl.abort();
                    abortCtrl = new AbortController();
                    renderLoading();
                    fetch(autocompleteUrl + '?q=' + encodeURIComponent(kw), {
                        signal: abortCtrl.signal,
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    }).then(function (r) { return r.ok ? r.json() : Promise.reject(); })
                        .then(function (data) { renderResults(data.products || [], kw); })
                        .catch(function () { });
                }

                input.addEventListener('focus', function () {
                    input.value.trim().length >= 2 ? fetchResults(input.value.trim()) : renderHistory();
                });
                input.addEventListener('input', function () {
                    clearTimeout(timer);
                    const val = input.value.trim();
                    if (val.length < 2) { renderHistory(); return; }
                    timer = setTimeout(function () { fetchResults(val); }, 300);
                });
                input.addEventListener('blur', function () { setTimeout(hide, 200); });
                form.addEventListener('submit', function () {
                    const val = input.value.trim();
                    if (val.length >= 2) saveToHistory(val);
                    hide();
                });
            });

            document.addEventListener('click', function (e) {
                if (!e.target.closest('.souqify-ac-dropdown') && !e.target.closest('form[data-autocomplete-url]')) {
                    document.querySelectorAll('.souqify-ac-dropdown').forEach(function (d) { d.classList.add('hidden'); });
                }
            });
        }

        document.addEventListener('DOMContentLoaded', bindForms);
        document.addEventListener('livewire:navigated', function () {
            document.querySelectorAll('form[data-autocomplete-url] input[name="q"]').forEach(function (i) { i.__souqifyACBound = false; });
            bindForms();
        });
    })();

    // ── Livewire storefront toast listeners ─────────────────────────────────────
    document.addEventListener('livewire:init', function () {
        Livewire.on('storefront-cart-added', function (event) {
            var payload = event && event[0] ? event[0] : {};
            var itemName = payload.itemName;
            var qty = Number(payload.qty || 1);
            var message = itemName
                ? qty > 1
                    ? @js(__(':count × :item added to your cart.', ['count' => '__COUNT__', 'item' => '__ITEM__']))
                        .replace('__COUNT__', qty)
                        .replace('__ITEM__', itemName)
                    : @js(__(':item added to your cart.', ['item' => '__ITEM__'])).replace('__ITEM__', itemName)
                : @js(__('Item added to your cart successfully.'));

            showStorefrontToast(message, 'success');

            document.querySelectorAll('.souqify-cart-badge').forEach(function (badge) {
                var current = parseInt(badge.textContent, 10) || 0;
                badge.textContent = current + qty;
                badge.classList.remove('hidden');
                badge.closest('div')?.classList.remove('hidden');
            });
        });

        Livewire.on('storefront-toast', function (event) {
            var payload = event && event[0] ? event[0] : {};
            showStorefrontToast(payload.message, payload.type || 'success');
        });

        Livewire.on('admin-toast', function (event) {
            var payload = event && event[0] ? event[0] : {};
            showStorefrontToast(payload.message, payload.type || 'success');
        });
    });

    // ── Preloader: hide spinner when all assets are loaded ───────────────────
    (function () {
        function revealPage() {
            var el = document.getElementById('page-preloader');
            if (el) { el.style.opacity = '0'; setTimeout(function () { el.style.display = 'none'; }, 320); }
        }
        if (document.readyState === 'complete') {
            revealPage();
        } else {
            window.addEventListener('load', revealPage);
            setTimeout(revealPage, 6000);
        }
    })();
</script>

<style>
    /* Souqify SweetAlert2 toast theme */
    .souqify-toast {
        font-family: 'Outfit', system-ui, sans-serif !important;
        padding: 0.85rem 1rem !important;
        border-left: 4px solid #FFE100 !important;
    }

    .souqify-toast.swal2-icon-error {
        border-left-color: #FFFFFF !important;
    }

    .souqify-toast.swal2-icon-warning {
        border-left-color: #001537 !important;
    }

    .souqify-toast.swal2-icon-info {
        border-left-color: #FFE100 !important;
    }

    .souqify-toast .swal2-title {
        margin: 0 !important;
        padding: 0 0.5rem !important;
    }

    .souqify-toast .swal2-icon {
        margin: 0 0.5rem 0 0 !important;
    }

    .souqify-toast-close {
        font-size: 1.25rem !important;
        margin-inline-start: 0.5rem !important;
        color: rgba(255, 255, 255, 0.85) !important;
        opacity: 0.9 !important;
        transition: opacity .15s ease;
    }

    .souqify-toast-close:hover {
        opacity: 1 !important;
    }

    .souqify-toast.swal2-icon-warning .souqify-toast-close {
        color: #001537 !important;
    }

    .souqify-toast-progress {
        background: rgba(255, 225, 0, 0.85) !important;
    }

    .souqify-toast.swal2-icon-warning .souqify-toast-progress {
        background: #001537 !important;
    }
</style>