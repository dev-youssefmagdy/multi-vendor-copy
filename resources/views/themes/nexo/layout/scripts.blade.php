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
<script src="{{ asset('tailwindcss.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('js/lazyload.js') }}" defer></script>

@vite('resources/js/storefront-cart.js')
@vite('resources/js/nexo.js')