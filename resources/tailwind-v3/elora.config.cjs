// Same config resources/js/elora.js used to set at runtime.
const v = require('./css-var-color.cjs');

module.exports = {
    content: [...require('./shared-content.cjs')('elora'), './public/elora-*/*.js'],
    theme: {
        extend: {
            fontFamily: { outfit: ['Outfit', 'sans-serif'], sans: ['Outfit', 'sans-serif'] },
            colors: {
                main: v('--color-main', '#FF4D00'), 'main-light': v('--color-main-light', '#FFAC88'), 'main-l': v('--color-main-light', '#FFAC88'),
                black: v('--color-ink', '#171717'), elblack: v('--color-ink', '#171717'),
                charcoal: v('--color-charcoal', '#242424'),
                gray1: v('--color-gray-1', '#3B3B3B'), gray2: v('--color-gray-2', '#5B5B5B'), gray3: v('--color-gray-3', '#ADADAD'),
                elgray: v('--color-gray-4', '#8F8F8F'),
                fill: v('--color-fill', '#FDFDFD'), elfill: v('--color-fill', '#FDFDFD'),
                bg: v('--color-bg', '#F6F5F5'),
                green: v('--color-green', '#2AAF2F'), elgreen: v('--color-green', '#2AAF2F'),
                red: v('--color-red', '#DE1709'), purple: v('--color-purple', '#8A38F5'), blue: v('--color-blue', '#002B97'),
            },
        },
    },
};
