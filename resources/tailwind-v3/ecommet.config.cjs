// Same config the page used to set at runtime (themes/ecommet/layout/scripts.blade.php).
const v = require('./css-var-color.cjs');

module.exports = {
    content: require('./shared-content.cjs')('ecommet'),
    theme: {
        container: { center: true, padding: '1rem', screens: { '2xl': '1440px' } },
        extend: {
            colors: {
                primary: v('--color-brand', '#de1709'), secondary: v('--color-brand-alt', '#ffaf09'),
                success: v('--color-success', '#2aaf2e'), purple: v('--color-purple', '#61167f'),
                'gray-darkest': v('--color-gray-darkest', '#232323'), 'gray-dark': v('--color-gray-dark', '#292d32'),
                'gray-medium': v('--color-gray-medium', '#7f7f7f'), 'gray-light': v('--color-gray-light', '#c4c4c4'),
                'gray-lighter': v('--color-gray-lighter', '#e7e7e7'), 'gray-lightest': v('--color-gray-lightest', '#f5f5f5'),
                'sale-light': v('--color-sale-light', '#ffecea'), 'yellow-light': v('--color-yellow-light', '#fdff9f'),
            },
            fontFamily: { main: "'Outfit', sans-serif" },
        },
    },
};
