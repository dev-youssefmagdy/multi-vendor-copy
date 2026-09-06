@push('body-attrs')data-page="index" data-variant="v4"@endpush

@php
$currency = $currentCurrency ?? null;
$symbol = data_get($currency, 'symbol', '$');
$rate = (float) data_get($currency, 'conversion_rate', 1.0);
$selectedCountryId = session('storefront_country_id');
$selectedCountry = $selectedCountryId ? \App\Models\Country::find($selectedCountryId) : null;
@endphp


{{-- ============================================================
     SINGLE ROOT WRAPPER (Livewire requires one root element)
     ============================================================ --}}
<div class="elora-v4-root">
    @php
        $__homeSections = $homeSections ?? \App\Services\Tenant\PageBuilder\SectionRegistry::defaultsFor('elora', 'home');
    @endphp
    @foreach ($__homeSections as $__section)
        @includeIf("themes.elora.pages.home-v4.sections.{$__section}")
    @endforeach

</div>

@push('scripts')
@once
<script>
    (function () {
        var FAV_KEY = 'elora_favorites';

        function readFavorites() {
            try { return JSON.parse(localStorage.getItem(FAV_KEY) || '[]'); } catch (e) { return []; }
        }
        function writeFavorites(list) {
            localStorage.setItem(FAV_KEY, JSON.stringify(list));
        }
        function setHeartActive(btn, active) {
            btn.classList.toggle('is-favorited', active);
            btn.style.background = active ? '#FF522C' : '';
            var img = btn.querySelector('img');
            if (img) img.style.filter = active ? 'brightness(0) invert(1)' : '';
        }

        window.eloraV4ToggleFavorite = function (btn) {
            var data;
            try { data = JSON.parse(btn.getAttribute('data-fav') || '{}'); } catch (e) { return; }
            if (!data.slug) return;
            var list = readFavorites();
            var idx = list.findIndex(function (item) { return item.slug === data.slug; });
            var nowActive = idx < 0;
            if (nowActive) {
                data.added = Math.floor(Date.now() / 1000);
                list.unshift(data);
            } else {
                list.splice(idx, 1);
            }
            writeFavorites(list);
            document.querySelectorAll('.elora-v4-heart-btn[data-fav]').forEach(function (el) {
                try {
                    if (JSON.parse(el.getAttribute('data-fav') || '{}').slug === data.slug) setHeartActive(el, nowActive);
                } catch (e) {}
            });
        };

        function markFavorited() {
            var slugs = readFavorites().map(function (item) { return item.slug; });
            document.querySelectorAll('.elora-v4-heart-btn[data-fav]').forEach(function (el) {
                try {
                    var data = JSON.parse(el.getAttribute('data-fav') || '{}');
                    setHeartActive(el, slugs.indexOf(data.slug) !== -1);
                } catch (e) {}
            });
        }

        document.addEventListener('DOMContentLoaded', markFavorited);
        document.addEventListener('livewire:navigated', markFavorited);

        document.addEventListener('livewire:init', function () {
            Livewire.on('storefront-cart-added', function () {
                document.querySelectorAll('#elora-v4-cart-badge, #elora-v4-cart-badge-mobile').forEach(function (badge) {
                    badge.textContent = (parseInt(badge.textContent, 10) || 0) + 1;
                    badge.classList.remove('hidden');
                });
            });
        });
    })();
</script>
@endonce
@endpush
