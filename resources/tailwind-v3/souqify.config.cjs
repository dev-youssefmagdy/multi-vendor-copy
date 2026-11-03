// Same config themes/souqify/layout/styles.blade.php used to set at runtime.
module.exports = {
    content: [...require('./shared-content.cjs')('souqify'), './public/souqify-*/*.js'],
    theme: {
        extend: {
            fontFamily: { sans: ['Outfit', 'system-ui', 'sans-serif'], manrope: ['Manrope', 'sans-serif'] },
            colors: { brand: { blue: '#1d4ed8', amber: '#f59e0b', orange: '#ea580c' } },
        },
    },
};
