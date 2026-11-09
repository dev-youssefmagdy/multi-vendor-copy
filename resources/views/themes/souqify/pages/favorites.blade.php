<div>
    @unless (in_array($storefrontThemeVariant?->key, ['v2', 'v3', 'v4', 'v5', 'v6'], true))
        @include('themes.souqify.partials.mobile-home-header', ['tight' => true])
    @endunless
    <main class="bg-zinc-100 pt-6 pb-16 w-full">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="text-xs text-neutral-500 flex items-center gap-2 mb-4">
                <a
                    href="{{ route('tenant.home') }}"
                    class="hover:text-[color:var(--sq-primary)]"
                    >{{ __("Home") }}</a
                >
                <span>/</span>
                <span class="text-slate-900 font-medium">{{
                    __("My Wishlist")
                }}</span>
            </nav>

            <div class="flex items-end justify-between flex-wrap gap-3 mb-6">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">
                        {{ __("My Wishlist") }}
                    </h1>
                    <p class="text-sm text-neutral-500 mt-1">
                        <span id="souqify-fav-count">0</span>
                        {{ __("items saved") }}
                    </p>
                </div>
                <div class="hidden lg:flex items-center gap-2">
                    <input
                        type="text"
                        id="souqify-fav-search"
                        oninput="souqifyFavSearch(this.value)"
                        placeholder="{{ __('Search wishlist') }}"
                        class="h-10 px-4 rounded-full border border-neutral-200 text-sm bg-white focus:outline-none focus:border-[color:var(--sq-primary)]"
                    />
                    <select
                        id="souqify-fav-sort"
                        onchange="souqifyFavSort(this.value)"
                        class="h-10 px-4 rounded-full border border-neutral-200 text-sm bg-white focus:outline-none focus:border-[color:var(--sq-primary)]"
                    >
                        <option value="recent">
                            {{ __("Recently added") }}
                        </option>
                        <option value="price-asc">
                            {{ __("Price: Low to High") }}
                        </option>
                        <option value="price-desc">
                            {{ __("Price: High to Low") }}
                        </option>
                        <option value="name">{{ __("Name A–Z") }}</option>
                    </select>
                </div>
            </div>

            <div
                id="souqify-fav-grid"
                class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 md:gap-4"
            ></div>
            <div id="souqify-fav-pager"></div>

            <div
                id="souqify-fav-empty"
                class="hidden bg-white border border-neutral-200 rounded-2xl py-16 text-center"
            >
                <svg
                    class="w-20 h-20 mx-auto mb-4 text-neutral-300"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"
                    />
                </svg>
                <h2 class="text-xl font-bold text-slate-900 mb-2">
                    {{ __("Your wishlist is empty") }}
                </h2>
                <p class="text-sm text-neutral-500 mb-5">
                    {{
                        __(
                            "Browse products and tap the heart icon to save your favorites."
                        )
                    }}
                </p>
                <a
                    href="{{ route('tenant.home') }}"
                    class="inline-block px-7 py-3 rounded-full bg-[color:var(--sq-primary)] hover:bg-[color:var(--sq-primary-hover)] text-white text-sm font-semibold transition"
                >
                    {{ __("Start Shopping") }}
                </a>
            </div>
        </div>
    </main>

    @push('scripts')
    <script>
        (function () {
@include('components.storefront.favorites-pager-js')
@include('components.storefront.favorites-display-js')
            const display = window.StorefrontFavoritesDisplay;

            let currentSort = 'recent';
            let currentSearch = '';
            let currentPage = 1;

            function getFavs() { return window.StorefrontFavorites.get(); }
            function saveFavs(list) { window.StorefrontFavorites.save(list); }

            function buildCard(item, idx) {
                const img = item.image
                    ? display.imgTag(item.image, 'w-full h-full object-cover')
                    : `<div class="w-full h-full flex items-center justify-center text-5xl text-neutral-400">🛍️</div>`;
                const oldPrice = display.num(item.old_price);
                const old = oldPrice
                    ? `<span class="text-xs text-neutral-400 line-through">$${oldPrice.toFixed(2)}</span>`
                    : '';
                const stars = (() => {
                    let h = '';
                    for (let i = 1; i <= 5; i++) {
                        h += `<svg class="w-3 h-3" fill="${i <= Math.round(display.num(item.rating)) ? '#FFE100' : '#E5E5E5'}" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>`;
                    }
                    return h;
                })();
                return `<div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden hover:shadow-lg transition group flex flex-col">
                                <a href="${display.esc(display.link(item.url))}" class="block relative aspect-square bg-zinc-50 overflow-hidden">${img}
                                    <button type="button" onclick="event.preventDefault(); souqifyRemoveFav(${idx})"
                                        class="absolute top-3 right-3 w-9 h-9 rounded-full bg-white shadow flex items-center justify-center hover:bg-red-50 transition" title="{{ __('Remove') }}">
                                        <svg class="w-4 h-4" stroke-width="1.8" viewBox="0 0 24 24" style="fill: var(--sq-primary); stroke: var(--sq-primary)"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                                    </button>
                                </a>
                                <div class="p-3 flex flex-col gap-1.5">
                                    <a href="${display.esc(display.link(item.url))}" class="text-sm font-semibold text-slate-900 line-clamp-2 hover:text-[color:var(--sq-primary)]">${display.esc(item.name)}</a>
                                    <div class="flex items-center gap-0.5">${stars}</div>
                                    <div class="flex items-baseline gap-2 mt-1">
                                        <span class="text-base font-bold text-[color:var(--sq-primary)]">$${display.num(item.price).toFixed(2)}</span>
                                        ${old}
                                    </div>
                                </div>
                            </div>`;
            }

            function render() {
                const stored = getFavs();
                let favs = [...stored];
                if (currentSearch) {
                    favs = favs.filter(f => (f.name || '').toLowerCase().includes(currentSearch));
                }
                if (currentSort === 'price-asc') favs.sort((a, b) => (a.price || 0) - (b.price || 0));
                if (currentSort === 'price-desc') favs.sort((a, b) => (b.price || 0) - (a.price || 0));
                if (currentSort === 'name') favs.sort((a, b) => (a.name || '').localeCompare(b.name || ''));

                const grid = document.getElementById('souqify-fav-grid');
                const empty = document.getElementById('souqify-fav-empty');
                const count = document.getElementById('souqify-fav-count');
                const pager = document.getElementById('souqify-fav-pager');
                if (!grid) return;
                count.textContent = favs.length;
                if (favs.length === 0) {
                    grid.innerHTML = '';
                    pager.innerHTML = '';
                    empty.classList.remove('hidden');
                } else {
                    const paged = window.StorefrontPager.slice(favs, currentPage);
                    currentPage = paged.page;
                    // Remove acts on the stored list, so index each card by its stored position
                    grid.innerHTML = paged.items.map(item => buildCard(item, stored.indexOf(item))).join('');
                    window.StorefrontPager.render(pager, paged, goToPage, 'var(--sq-primary)');
                    empty.classList.add('hidden');
                }
            }

            function goToPage(page) {
                currentPage = page;
                render();
                document.getElementById('souqify-fav-grid').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            window.souqifyRemoveFav = function (idx) {
                const favs = getFavs();
                favs.splice(idx, 1);
                saveFavs(favs);
                render();
                window.dispatchEvent(new CustomEvent('storefront-toast', { detail: { type: 'info', message: '{{ __('Removed from favorites') }}' } }));
            };
            window.souqifyFavSearch = function (val) { currentSearch = (val || '').toLowerCase(); currentPage = 1; render(); };
            window.souqifyFavSort = function (val) { currentSort = val; currentPage = 1; render(); };

            document.addEventListener('DOMContentLoaded', render);
            document.addEventListener('livewire:navigated', render);
            window.addEventListener('storefront-favorites-changed', render);
            if (document.readyState !== 'loading') render();
            // The store's change event repaints the grid after a refresh.
            display.refresh((slugs, ids) => @this.favoriteDisplayData(slugs, ids));
        })();
    </script>
    @endpush
</div>
