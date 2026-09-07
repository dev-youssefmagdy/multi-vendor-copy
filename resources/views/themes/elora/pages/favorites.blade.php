@section('pageData', 'favorite')

<div class="bg-white">
    {{-- Breadcrumb --}}
    <div class="bg-white">
        <div
            class="max-w-[1390px] mx-auto px-4 sm:px-6 lg:px-10 py-2.5 flex items-center gap-1 text-sm text-[#808080] flex-wrap">
            <a href="{{ route('tenant.home') }}"
                class="hover:text-main transition-colors tracking-[0.5px]">{{ __('Home') }}</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="m9 18 6-6-6-6" />
            </svg>
            <span class="text-[#1B1B1B] font-medium tracking-[0.5px]">{{ __('Favorite') }}</span>
        </div>
    </div>

    <div class="max-w-[1390px] mx-auto px-4 sm:px-6 lg:px-10 py-6 pb-16 bg-white">

        {{-- Header row: title + count + search --}}
        <div class="flex items-center justify-between gap-4 mb-3 sm:mb-6 flex-wrap gap-y-3">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-medium text-[#171717]">{{ __('My Favorite') }}</h1>
                <span id="favCount" class="text-sm text-[#ADADAD] font-normal mt-1">(<span id="favNum">0</span>
                    {{ __('items') }})</span>
            </div>
            <div
                class="flex items-center gap-2 border border-[#E0E0E0] rounded-full px-3 py-1 bg-white w-full sm:w-auto">
                <input type="text" placeholder="{{ __('Search in Favorites...') }}" id="favSearch"
                    class="outline-none text-sm text-[#555] min-w-48 min-h-10 flex-1" oninput="filterFavorites()" />
                <button>
                    <svg class="w-4 h-4 text-[#ADADAD]" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Sort / filter bar --}}
        <div class="flex items-center gap-1 gap-y-3 sm:gap-3 mb-6 flex-wrap">
            <button onclick="sortFavorites('recent')" id="sort-recent"
                class="sort-btn px-2 sm:px-4 py-2 rounded-full border border-main text-xs sm:text-sm text-white bg-main font-medium">
                {{ __('Recently added') }}
            </button>
            <button onclick="sortFavorites('price-asc')" id="sort-price-asc"
                class="sort-btn px-2 sm:px-4 py-2 rounded-full border border-[#E0E0E0] text-xs sm:text-sm text-[#555] hover:border-main hover:text-main transition-colors bg-white">
                {{ __('Price: Low to High') }}
            </button>
            <button onclick="sortFavorites('price-desc')" id="sort-price-desc"
                class="sort-btn px-2 sm:px-4 py-2 rounded-full border border-[#E0E0E0] text-sm text-[#555] hover:border-main hover:text-main transition-colors bg-white">
                {{ __('Price: High to Low') }}
            </button>
            <button onclick="sortFavorites('name')" id="sort-name"
                class="sort-btn px-2 sm:px-4 py-2 rounded-full border border-[#E0E0E0] text-xs sm:text-sm text-[#555] hover:border-main hover:text-main transition-colors bg-white">
                {{ __('Name A–Z') }}
            </button>
            <button onclick="addAllToCart()"
                class="ml-auto flex items-center gap-2 px-5 py-2 bg-[#171717] text-white text-sm font-medium rounded-full hover:bg-black transition-colors">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="9" cy="21" r="1" />
                    <circle cx="20" cy="21" r="1" />
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                </svg>
                {{ __('Add all to cart') }}
            </button>
        </div>

        {{-- Favorites grid --}}
        <div class="fav-grid flex flex-wrap gap-6" id="favGrid"></div>

        {{-- Empty state --}}
        <div id="emptyState" class="hidden flex flex-col items-center justify-center py-24 text-center">
            <img loading="lazy" src="{{ asset('elora/assets/images/empty-favorite.svg') }}" alt="{{ __('broken heart') }}">
            <h2 class=" text-xl font-semibold text-[#333] mb-2">{{ __('Your favorites list is empty') }}</h2>
            <p class="text-sm text-[#888] mb-6">
                {{ __('Browse products and tap the heart icon to save your favorites here.') }}
            </p>
            <a href="{{ route('tenant.home') }}"
                class="px-8 py-3 bg-[#171717] text-white text-sm font-medium rounded-full hover:bg-black transition-colors">
                {{ __('Start Shopping') }}
            </a>
        </div>

    </div>
</div>

@push('scripts')
    <script>
        (function () {
            const STORAGE_KEY = 'elora_favorites';

            // ── helpers ──────────────────────────────────────────
            function getFavs() {
                try {
                    return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
                } catch {
                    return [];
                }
            }

            function saveFavs(favs) {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(favs));
            }

            // ── build card HTML ───────────────────────────────────
            function buildCard(item, idx) {
                const badge = item.badge ?
                    `<span class="absolute top-0 right-0 text-xs px-2 py-1.5 font-normal tracking-wide" style="background:#FF4D00;color:#fff;border-radius:0 8px 0 8px">${item.badge}</span>` :
                    '';
                const img = item.image ?
                    `<img loading="lazy" src="${item.image}" alt="" class="w-full h-full object-cover"/>` :
                    `<div class="w-full h-full bg-gray-100 flex items-center justify-center text-5xl">🛍️</div>`;
                const stars = buildStars(item.rating || 0);
                const oldPrice = item.old_price ?
                    `<span class="text-sm text-[#ADADAD] line-through font-light">$${parseFloat(item.old_price).toFixed(2)}</span>` :
                    '';
                const discount = item.discount ?
                    `<span class="text-xs text-[#FF522C] tracking-[0.5px]">${item.discount}</span>` :
                    '';

                return `<div class="fav-card group relative flex flex-col bg-[#FDFDFD] rounded-xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300" data-index="${idx}" data-name="${(item.name || '').toLowerCase()}" data-price="${item.price || 0}" data-added="${item.added || 0}">
              <div class="relative overflow-hidden" style="height:183px">
                ${img}
                ${badge}
                <button class="fav-btn absolute top-2.5 left-2.5 w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-md hover:scale-110 transition-transform z-10" onclick="removeFavoriteByIndex(${idx})" title="{{ __('Remove from favorites') }}">
                  <svg class="w-4 h-4" fill="#FF4D00" stroke="#FF4D00" stroke-width="1" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                </button>
                <button class="add-cart-btn absolute bottom-3 right-3 w-10 h-10 bg-[#FDFDFD] rounded-2xl flex items-center justify-center shadow-md hover:bg-main transition-colors z-10" onclick="addToCartItem(${idx})">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                </button>
              </div>
              <div class="p-3 flex flex-col gap-1.5">
                <div class="flex items-center justify-between">
                  <span class="text-base font-medium text-[#171717] tracking-[0.5px] truncate max-w-[140px]">${item.name || ''}</span>
                  ${item.weight ? `<span class="text-sm text-main font-normal tracking-[0.5px] flex-shrink-0 ml-1">${item.weight}</span>` : ''}
                </div>
                ${item.description ? `<p class="text-sm text-[#ADADAD] tracking-[0.5px] truncate">${item.description}</p>` : ''}
                <div class="flex items-center gap-1">${stars}<span class="text-xs text-[#ADADAD] ml-1 tracking-[0.5px]">${item.rating || ''} ${item.reviews ? '(+' + item.reviews + ')' : ''}</span></div>
                <div class="flex items-end gap-1.5 flex-wrap">
                  <span class="text-lg font-medium text-[#171717]">$${parseFloat(item.price || 0).toFixed(2)}</span>
                  ${oldPrice} ${discount}
                </div>
              </div>
            </div>`;
            }

            function buildStars(rating) {
                let html = '';
                for (let i = 1; i <= 5; i++) {
                    const color = i <= Math.round(rating) ? '#FFE100' : '#D5D5D5';
                    html +=
                        `<div class="w-2.5 h-2.5" style="background:${color};clip-path:polygon(50% 0%,61% 35%,98% 35%,68% 57%,79% 91%,50% 70%,21% 91%,32% 57%,2% 35%,39% 35%)"></div>`;
                }
                return html;
            }

            // ── render ────────────────────────────────────────────
            let currentSort = 'recent';
            let currentSearch = '';

            function render() {
                let favs = getFavs();
                const grid = document.getElementById('favGrid');
                const empty = document.getElementById('emptyState');
                const favNum = document.getElementById('favNum');

                // filter
                if (currentSearch) {
                    favs = favs.filter(f => (f.name || '').toLowerCase().includes(currentSearch));
                }

                // sort
                if (currentSort === 'price-asc') favs = [...favs].sort((a, b) => (a.price || 0) - (b.price || 0));
                if (currentSort === 'price-desc') favs = [...favs].sort((a, b) => (b.price || 0) - (a.price || 0));
                if (currentSort === 'name') favs = [...favs].sort((a, b) => (a.name || '').localeCompare(b.name || ''));

                favNum.textContent = favs.length;

                if (favs.length === 0) {
                    grid.innerHTML = '';
                    empty.classList.remove('hidden');
                    empty.classList.add('flex');
                } else {
                    grid.innerHTML = favs.map((item, i) => buildCard(item, i)).join('');
                    empty.classList.add('hidden');
                    empty.classList.remove('flex');
                }
            }

            // ── public functions ──────────────────────────────────
            window.removeFavoriteByIndex = function (idx) {
                const favs = getFavs();
                favs.splice(idx, 1);
                saveFavs(favs);
                render();
            };

            window.addToCartItem = function (idx) {
                const favs = getFavs();
                const item = favs[idx];
                if (!item) return;
                // Dispatch a Livewire-compatible addToCart event if available
                if (window.Livewire) {
                    window.Livewire.dispatch('addToCart', {
                        productId: item.id,
                        qty: 1
                    });
                }
                // Show brief feedback on button
                const btn = document.querySelectorAll('.add-cart-btn')[idx];
                if (btn) {
                    btn.style.background = '#FF4D00';
                    setTimeout(() => {
                        btn.style.background = '';
                    }, 600);
                }
            };

            window.addAllToCart = function () {
                const favs = getFavs();
                if (window.Livewire) {
                    favs.forEach(item => {
                        if (item.id) window.Livewire.dispatch('addToCart', {
                            productId: item.id,
                            qty: 1
                        });
                    });
                }
            };

            window.sortFavorites = function (type) {
                currentSort = type;
                document.querySelectorAll('.sort-btn').forEach(btn => {
                    btn.classList.remove('bg-main', 'text-white', 'border-main', 'font-medium');
                    btn.classList.add('text-[#555]', 'bg-white', 'border-[#E0E0E0]');
                });
                const active = document.getElementById('sort-' + type);
                if (active) {
                    active.classList.add('bg-main', 'text-white', 'border-main', 'font-medium');
                    active.classList.remove('text-[#555]', 'bg-white', 'border-[#E0E0E0]');
                }
                render();
            };

            window.filterFavorites = function () {
                currentSearch = (document.getElementById('favSearch').value || '').toLowerCase();
                render();
            };

            // Initial render
            render();

            // Listen for favorites changes from other tabs
            window.addEventListener('storage', function (e) {
                if (e.key === STORAGE_KEY) render();
            });
        })();
    </script>
@endpush