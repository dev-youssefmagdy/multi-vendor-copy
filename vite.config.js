import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/elora.css',
                'resources/css/elora-v2.css',
                'resources/css/elora/category.css',
                'resources/css/elora/home.css',
                'resources/css/elora/home-v2.css',
                'resources/css/ecommet.css',
                'resources/css/souqify.css',
                'resources/js/app.js',
                'resources/js/storefront-cart.js',
                'resources/js/image-search.js',
                'resources/js/elora.js',
                'resources/js/elora/cart.js',
                'resources/js/elora/category.js',
                'resources/js/elora/home.js',
                'resources/js/elora/product.js',
                'resources/js/ecommet.js',
                'resources/js/ecommet/cart.js',
                'resources/js/ecommet/home.js',
                'resources/js/ecommet/product.js',
                'resources/js/souqify.js',
                'resources/js/website.js',
                'resources/css/website.css',
                'resources/js/elora-v2-interactions.js',
                'resources/js/elora-v2-carousels.js'
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
