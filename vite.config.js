import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/elora.css',
                'resources/css/elora/category.css',
                'resources/css/elora/home.css',
                'resources/css/ecommet.css',
                'resources/css/souqify.css',
                'resources/css/nexo.css',
                'resources/css/nexo/category.css',
                'resources/css/nexo/home.css',
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
                'resources/js/nexo.js',
                'resources/js/nexo/cart.js',
                'resources/js/nexo/category.js',
                'resources/js/nexo/home.js',
                'resources/js/nexo/product.js',
                'resources/js/website.js',
                'resources/css/website.css',
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
