@once
    <script>
        (function () {
            if (window.__ecommetFavInit) return;
            window.__ecommetFavInit = true;
            var ACTIVE = '#de1709';
            var t = function (s) { return typeof window.trans === 'function' ? window.trans(s) : s; };

            function paint() {
                var slugs = new Set(window.StorefrontFavorites.get().map(function (f) { return f.slug; }));
                document.querySelectorAll('[data-fav-slug]').forEach(function (b) {
                    var active = slugs.has(b.getAttribute('data-fav-slug'));
                    b.setAttribute('aria-pressed', active ? 'true' : 'false');
                    var svg = b.querySelector('svg'), path = svg && svg.querySelector('path');
                    if (svg) svg.setAttribute('stroke', active ? ACTIVE : '#242424');
                    if (path) path.setAttribute('fill', active ? ACTIVE : 'none');
                });
            }

            // Shared store (resources/js/storefront-favorites.js) - same key/function as every theme.
            window.ecommetToggleFavorite = function (btn) {
                var data;
                try { data = JSON.parse(btn.getAttribute('data-fav') || '{}'); } catch (e) { return; }
                if (!data.slug) return;
                var adding = window.StorefrontFavorites.toggle(data);
                paint();
                if (typeof window.showStorefrontToast === 'function') {
                    window.showStorefrontToast(t(adding ? 'Added to favorites!' : 'Removed from favorites.'), adding ? 'success' : 'info');
                }
            };

            document.addEventListener('DOMContentLoaded', paint);
            document.addEventListener('livewire:navigated', paint);
            document.addEventListener('livewire:updated', paint);
            window.addEventListener('storefront-favorites-changed', paint);
            if (document.readyState !== 'loading') paint();
        })();
    </script>
@endonce
