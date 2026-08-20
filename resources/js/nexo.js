tailwind.config = {
    theme: {
        extend: {
            fontFamily: {
                outfit: ['Outfit', 'sans-serif'],
                sans: ['Outfit', 'sans-serif']
            },
            colors: {
                main: '#FF4D00',
                'main-light': '#FFAC88',
                'main-l': '#FFAC88',
                black: '#171717',
                elblack: '#171717',
                charcoal: '#242424',
                gray1: '#3B3B3B',
                gray2: '#5B5B5B',
                gray3: '#ADADAD',
                elgray: '#8F8F8F',
                fill: '#FDFDFD',
                elfill: '#FDFDFD',
                bg: '#F6F5F5',
                green: '#2AAF2F',
                elgreen: '#2AAF2F',
                red: '#DE1709',
                purple: '#8A38F5',
                blue: '#002B97'
            }
        }
    }
};


// ------------------- index.js

(function () {
    const body = document.body;
    const menuBtn = document.getElementById("menuBtn");

    function openSideMenu() {
        const m = document.getElementById("sideMenu");
        if (!m) return;
        m.classList.remove("hidden");
        requestAnimationFrame(() => m.classList.add("open"));
        document.body.style.overflow = "hidden";
        menuBtn.classList.add("opened");
    }

    function closeSideMenu() {
        const m = document.getElementById("sideMenu");
        if (!m) return;
        m.classList.remove("open");
        setTimeout(() => {
            m.classList.add("hidden");
            document.body.style.overflow = "";
            menuBtn.classList.remove("opened");
        }, 300);
    }

    if (menuBtn) {
        menuBtn.addEventListener("click", openSideMenu);
    }

    function switchCat(el) {
        document.querySelectorAll(".cat-pill").forEach((p) => {
            p.classList.remove("active");
            p.style.background = "";
            p.style.color = "";
            p.style.borderColor = "";
        });
        el.classList.add("active");

        const targetId = el.dataset.target;
        const filter = el.dataset.filter || "all";
        if (!targetId) return;

        const target = document.getElementById(targetId);
        if (!target) return;

        target.querySelectorAll("[data-home-filter-card]").forEach((card) => {
            const tags = (card.dataset.tags || "")
                .split(",")
                .map((tag) => tag.trim())
                .filter(Boolean);
            const show = filter === "all" || tags.includes(filter);
            card.style.display = show ? "" : "none";
        });
    }

    const countdownRoot = document.querySelector("[data-countdown-end]");
    const hoursEl = document.getElementById("hours");
    const minutesEl = document.getElementById("minutes");
    const secondsEl = document.getElementById("seconds");
    const pad = (n) => String(n).padStart(2, "0");
    let totalSeconds = 2 * 3600 + 15 * 60 + 10;

    if (countdownRoot) {
        const countdownEnd = Number(countdownRoot.dataset.countdownEnd || 0);
        if (countdownEnd > 0) {
            totalSeconds = Math.max(
                0,
                Math.floor(countdownEnd - Date.now() / 1000),
            );
        }
    }

    function renderCountdown() {
        if (!hoursEl || !minutesEl || !secondsEl) return;

        const h = Math.floor(totalSeconds / 3600);
        const m = Math.floor((totalSeconds % 3600) / 60);
        const s = totalSeconds % 60;
        hoursEl.textContent = pad(h) + "h";
        minutesEl.textContent = pad(m) + "m";
        secondsEl.textContent = pad(s) + "s";
    }

    if (hoursEl && minutesEl && secondsEl) {
        renderCountdown();
        setInterval(() => {
            if (totalSeconds <= 0) {
                totalSeconds = 0;
                renderCountdown();
                return;
            }

            totalSeconds--;
            renderCountdown();
        }, 1000);
    }

    let cur = 0;
    const slides = document.querySelectorAll(".hero-slide");
    const dots = document.querySelectorAll(".hero-dot");

    function renderSlide(index) {
        if (!slides.length) return;

        slides[cur].classList.remove("active");
        if (dots[cur]) {
            dots[cur].classList.remove("active");
        }

        cur = (index + slides.length) % slides.length;
        slides[cur].classList.add("active");
        if (dots[cur]) {
            dots[cur].classList.add("active");
        }
    }

    function changeSlide(d) {
        if (!slides.length) return;
        renderSlide(cur + d);
    }

    function goSlide(index) {
        renderSlide(index);
    }

    document.querySelectorAll(".heart-btn").forEach((b) =>
        b.addEventListener("click", function () {
            this.classList.toggle("active");
        }),
    );

    document.querySelectorAll(".card-cart-btn").forEach((b) => {
        b.addEventListener("click", function (e) {
            e.stopPropagation();
            const orig = this.innerHTML;
            this.style.background = "#FF4D00";
            this.innerHTML =
                '<svg style="width:16px;height:16px" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>';
            setTimeout(() => {
                this.style.background = "";
                this.innerHTML = orig;
            }, 1500);
        });
    });

    window.openSideMenu = openSideMenu;
    window.closeSideMenu = closeSideMenu;
    window.switchCat = switchCat;
    window.changeSlide = changeSlide;
    window.goSlide = goSlide;
})();

// ------------------- main.js

(function () {
    function toastPosition() {
        return document.documentElement.getAttribute('dir') === 'rtl' ? 'top-start' : 'top-end';
    }

    function normalizeToastType(type) {
        var allowed = ['success', 'error', 'warning', 'info', 'question'];
        return allowed.indexOf(type) !== -1 ? type : 'success';
    }

    function toastInstance() {
        return Swal.mixin({
            showCloseButton: true,
            toast: true,
            position: toastPosition(),
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true,
            background: '#2AAF2F',
            color: 'white',
            customClass: {
                popup: 'shadow-xl border border-[#e8e8e8] rounded-xl',
                title: 'text-[13px] font-medium',
            },
        });
    }

    var _toastIcons = {
        success: '<polyline points="20 6 9 17 4 12"/>',
        error: '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        warning: '<path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>',
        info: '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
    };

    window.showStorefrontToast = function (message, type) {
        if (!message) return;
        var t = normalizeToastType(type || 'success');
        var iconPath = _toastIcons[t] || _toastIcons.success;
        toastInstance().fire({
            html: '<div style="display:flex;align-items:center;gap:10px">' +
                  '<svg style="flex-shrink:0;width:18px;height:18px" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">' + iconPath + '</svg>' +
                  '<span style="color:white;font-size:13px;font-weight:500;line-height:1.3">' + (message + '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span></div>',
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
        var firstInStock = variants.find(function (v) { return v.inStock !== false; });
        _selectedVariantId = firstInStock ? firstInStock.id : null;

        var modal = document.getElementById('variant-select-modal');
        var title = document.getElementById('variant-modal-title');
        var list = document.getElementById('variant-modal-list');
        if (!modal) return;

        title.textContent = productName;
        list.innerHTML = '';

        variants.forEach(function (v) {
            var outOfStock = v.inStock === false;
            var isSelected = v.id === _selectedVariantId;

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.dataset.variantId = v.id;
            btn.disabled = outOfStock;

            btn.className = 'variant-option w-full text-left px-4 py-3 rounded-lg border text-[14px] transition flex items-center justify-between gap-2 ' +
                (outOfStock
                    ? 'border-[#e5e5e5] opacity-50 cursor-not-allowed'
                    : isSelected
                        ? 'border-[#242424] bg-gray-50 font-medium'
                        : 'border-[#e5e5e5] hover:border-[#aaa]');

            var label = document.createElement('span');
            label.textContent = v.label + (v.price ? '  ' + v.price : '');
            btn.appendChild(label);

            if (outOfStock) {
                var badge = document.createElement('span');
                badge.className = 'shrink-0 text-[11px] uppercase tracking-wide px-2 py-0.5 rounded bg-gray-200 text-gray-600';
                badge.textContent = 'Out of stock';
                btn.appendChild(badge);
            } else {
                btn.addEventListener('click', function () {
                    _selectedVariantId = v.id;
                    list.querySelectorAll('.variant-option').forEach(function (el) {
                        el.classList.remove('border-[#242424]', 'bg-gray-50', 'font-medium');
                        el.classList.add('border-[#e5e5e5]');
                    });
                    this.classList.remove('border-[#e5e5e5]');
                    this.classList.add('border-[#242424]', 'bg-gray-50', 'font-medium');
                });
            }

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

    document.addEventListener('click', function (e) {
        if (e.target.id === 'variant-modal-confirm') {
            if (!_modalData || _selectedVariantId === null) return;
            Livewire.dispatch('add-to-cart-variant', {
                productId: _modalData.productId,
                variantId: _selectedVariantId,
            });
            closeVariantModal();
            return;
        }

        if (e.target.id === 'variant-select-modal') {
            closeVariantModal();
        }
    });
})();

// ── Favorites: heart toggle ──────────────────────────────────────────────────
(function () {
    const FAV_KEY = 'nexo_favorites';

    function getFavs() {
        try { return JSON.parse(localStorage.getItem(FAV_KEY) || '[]'); } catch { return []; }
    }
    function saveFavs(favs) {
        localStorage.setItem(FAV_KEY, JSON.stringify(favs));
    }

    function applyHeartState(btn, active) {
        var svg = btn.querySelector('svg');
        var path = svg && svg.querySelector('path');
        if (active) {
            btn.classList.add('active');
            if (svg) { svg.setAttribute('fill', '#FF4D00'); svg.setAttribute('stroke', '#FF4D00'); }
            if (path) path.setAttribute('fill', '#FF4D00');
        } else {
            btn.classList.remove('active');
            if (svg) { svg.setAttribute('fill', 'none'); svg.setAttribute('stroke', '#171717'); }
            if (path) path.setAttribute('fill', 'none');
        }
    }

    window.nexoHeartToggle = function (btn) {
        var data;
        try { data = JSON.parse(btn.dataset.fav || '{}'); } catch { return; }
        if (!data.slug) return;

        var favs = getFavs();
        var idx = favs.findIndex(function (f) { return f.slug === data.slug; });
        var adding = idx < 0;

        if (adding) {
            data.added = Date.now();
            favs.push(data);
        } else {
            favs.splice(idx, 1);
        }
        saveFavs(favs);

        // Sync all buttons for this product on the page
        document.querySelectorAll('.heart-btn[data-fav]').forEach(function (b) {
            try {
                var d = JSON.parse(b.dataset.fav || '{}');
                if (d.slug === data.slug) applyHeartState(b, adding);
            } catch { }
        });
    };

    function markFavoritedProducts() {
        var favs = getFavs();
        var slugs = new Set(favs.map(function (f) { return f.slug; }));
        document.querySelectorAll('.heart-btn[data-fav]').forEach(function (btn) {
            try {
                var d = JSON.parse(btn.dataset.fav || '{}');
                applyHeartState(btn, slugs.has(d.slug));
            } catch { }
        });
    }

    document.addEventListener('DOMContentLoaded', markFavoritedProducts);
    document.addEventListener('livewire:navigated', markFavoritedProducts);
    document.addEventListener('livewire:updated', markFavoritedProducts);

    window.markFavoritedProducts = markFavoritedProducts;
})();

// ── Search Autocomplete + History ────────────────────────────────────────────
(function initSearchHistory() {
    const HISTORY_KEY = 'nexo_search_history';
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
            if (input.__nexoACBound) return;
            input.__nexoACBound = true;

            const form = input.closest('form');
            const autocompleteUrl = form.dataset.autocompleteUrl;
            const container = input.closest('.nexo-search-inner') || input.parentElement;
            if (!container) return;
            container.style.position = 'relative';

            const dropdown = document.createElement('div');
            dropdown.className = 'nexo-ac-dropdown absolute left-[-12px] right-[-12px] top-[calc(100%+8px)] bg-white rounded-2xl shadow-2xl border border-gray-100 z-[300] hidden overflow-hidden';
            container.appendChild(dropdown);

            let timer = null, abortCtrl = null;

            function show(html) { dropdown.innerHTML = html; dropdown.classList.remove('hidden'); }
            function hide() { dropdown.classList.add('hidden'); dropdown.innerHTML = ''; }

            function bindHistoryEvents() {
                dropdown.querySelector('.nexo-clear-hist')?.addEventListener('click', function (e) {
                    e.stopPropagation(); clearHistory(); hide();
                });
                dropdown.querySelectorAll('.nexo-sh-item').forEach(function (el) {
                    el.addEventListener('mousedown', function (e) {
                        e.preventDefault(); input.value = el.dataset.term; saveToHistory(el.dataset.term); hide(); form.submit();
                    });
                });
            }

            function bindResultEvents(kw) {
                dropdown.querySelectorAll('a[data-term]').forEach(function (a) {
                    a.addEventListener('mousedown', function (e) { e.preventDefault(); saveToHistory(kw); window.location.href = a.href; });
                });
                dropdown.querySelector('.nexo-see-all')?.addEventListener('mousedown', function (e) {
                    e.preventDefault(); saveToHistory(kw); window.location.href = e.currentTarget.href;
                });
            }

            function renderHistory() {
                const hist = getHistory();
                if (hist.length === 0) { hide(); return; }
                const items = hist.map(function (t) {
                    return '<div class="nexo-sh-item flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 cursor-pointer" data-term="' + safeText(t) + '">' +
                        '<svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' +
                        '<span class="text-[13px] text-[#333] flex-1 truncate">' + safeText(t) + '</span></div>';
                }).join('');
                show('<div class="flex items-center justify-between px-4 py-2 border-b border-gray-100">' +
                    '<span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Recent searches</span>' +
                    '<button class="nexo-clear-hist text-[12px] text-main hover:underline">Clear</button></div>' + items);
                bindHistoryEvents();
            }

            function renderLoading() {
                show('<div class="flex items-center justify-center gap-2 px-4 py-5 text-[13px] text-gray-400">' +
                    '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>' +
                    'Searching…</div>');
            }

            function renderResults(products, kw) {
                if (products.length === 0) {
                    show('<div class="px-4 py-5 text-center text-[13px] text-gray-400">No products found for "<strong>' + safeText(kw) + '</strong>"</div>');
                    return;
                }
                const placeholder = '<svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';
                const rows = products.map(function (p) {
                    const priceHtml = p.has_discount
                        ? '<span class="text-[12px] text-main font-semibold">' + safeText(String(p.price)) + '</span> <span class="text-[11px] text-gray-400 line-through">' + safeText(String(p.original_price)) + '</span>'
                        : '<span class="text-[12px] text-main font-semibold">' + safeText(String(p.price)) + '</span>';
                    return '<a href="' + p.url + '" data-term="' + safeText(kw) + '" class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 transition">' +
                        '<div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center shrink-0 overflow-hidden">' +
                        (p.image ? '<img src="' + p.image + '" alt="' + safeText(p.name) + '" class="w-full h-full object-contain">' : placeholder) + '</div>' +
                        '<div class="flex-1 min-w-0"><div class="text-[13px] font-medium text-[#222] truncate">' + safeText(p.name) + '</div>' +
                        '<div class="flex items-center gap-1.5">' + priceHtml + '</div></div></a>';
                }).join('');
                const seeAll = form.action + '?q=' + encodeURIComponent(kw);
                show('<div class="px-4 py-2 border-b border-gray-100"><span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Products</span></div>' +
                    rows +
                    '<a href="' + seeAll + '" class="nexo-see-all flex items-center justify-center gap-1 px-4 py-2.5 text-[12px] font-semibold text-main border-t border-gray-100 hover:bg-gray-50 transition">' +
                    'See all results for"<strong>' + safeText(kw) + '</strong>"' +
                    '</a>');
                    // '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>');
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

        // Close on outside click
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.nexo-ac-dropdown') && !e.target.closest('form[data-autocomplete-url]')) {
                document.querySelectorAll('.nexo-ac-dropdown').forEach(function (d) { d.classList.add('hidden'); });
            }
        });
    }

    document.addEventListener('DOMContentLoaded', bindForms);
    document.addEventListener('livewire:navigated', function () {
        // Reset bound flag so re-mounted forms are rebound
        document.querySelectorAll('form[data-autocomplete-url] input[name="q"]').forEach(function (i) { i.__nexoACBound = false; });
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
            ? (qty > 1 ? qty + ' × ' + itemName : itemName) + ' added to your cart.'
            : 'Item added to your cart successfully.';

        showStorefrontToast(message, 'success');

        var mobBadge = document.getElementById('nexo-mob-cart-badge');
        if (mobBadge) {
            var current = parseInt(mobBadge.textContent, 10) || 0;
            var next = current + qty;
            mobBadge.textContent = next;
            mobBadge.classList.remove('hidden');
        }

        var desktopBadge = document.getElementById('nexo-cart-badge');
        if (desktopBadge) {
            var currentD = parseInt(desktopBadge.textContent, 10) || 0;
            var nextD = currentD + qty;
            desktopBadge.textContent = nextD;
            desktopBadge.classList.remove('hidden');
        }
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
