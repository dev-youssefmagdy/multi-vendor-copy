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
<script src="{{ asset('tailwindcss.js') }}"></script>
<script src="{{ asset('js/lazyload.js') }}" defer></script>

<script>
    tailwind.config = {
        theme: {
            container: {
                center: true,
                padding: '1rem',
                screens: {
                    '2xl': '1440px',
                },
            },
            extend: {
                colors: {
                    primary: '#de1709',
                    secondary: '#ffaf09',
                    success: '#2aaf2e',
                    purple: '#61167f',
                    'gray-darkest': '#232323',
                    'gray-dark': '#292d32',
                    'gray-medium': '#7f7f7f',
                    'gray-light': '#c4c4c4',
                    'gray-lighter': '#e7e7e7',
                    'gray-lightest': '#f5f5f5',
                    'sale-light': '#ffecea',
                    'yellow-light': '#fdff9f'
                },
                fontFamily: {
                    main: "'Outfit', sans-serif"
                }
            }
        }
    }
</script>

@vite('resources/js/storefront-cart.js')
@vite('resources/js/ecommet.js')
