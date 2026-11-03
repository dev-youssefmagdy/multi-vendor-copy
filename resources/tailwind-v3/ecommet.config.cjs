// Same config the page used to set at runtime (themes/ecommet/layout/scripts.blade.php).
module.exports = {
    content: require('./shared-content.cjs')('ecommet'),
    theme: {
        container: { center: true, padding: '1rem', screens: { '2xl': '1440px' } },
        extend: {
            colors: {
                primary: '#de1709', secondary: '#ffaf09', success: '#2aaf2e', purple: '#61167f',
                'gray-darkest': '#232323', 'gray-dark': '#292d32', 'gray-medium': '#7f7f7f', 'gray-light': '#c4c4c4',
                'gray-lighter': '#e7e7e7', 'gray-lightest': '#f5f5f5', 'sale-light': '#ffecea', 'yellow-light': '#fdff9f',
            },
            fontFamily: { main: "'Outfit', sans-serif" },
        },
    },
};
