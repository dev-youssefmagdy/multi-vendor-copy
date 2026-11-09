<script>
    window.__i18n = @json(\App\Support\StorefrontJsTranslations::forCurrentLocale());
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
