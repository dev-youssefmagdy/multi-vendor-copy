import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/elora.css',
                'resources/css/elora-v2.css',
                'resources/css/elora-v3.css',
                'resources/css/elora-v4.css',
                'resources/css/elora-v5.css',
                'resources/css/elora-v6.css',
                'resources/css/elora/category.css',
                'resources/css/elora/footer.css',
                'resources/css/elora/footer-v2.css',
                'resources/css/elora/footer-v3.css',
                'resources/css/elora/footer-v4.css',
                'resources/css/elora/footer-v5.css',
                'resources/css/elora/footer-v6.css',
                'resources/css/elora/header.css',
                'resources/css/elora/header-v2.css',
                'resources/css/elora/header-v3.css',
                'resources/css/elora/header-v4.css',
                'resources/css/elora/header-v5.css',
                'resources/css/elora/header-v6.css',
                'resources/css/elora/home.css',
                'resources/css/elora/home-v2.css',
                'resources/css/elora/home-v3.css',
                'resources/css/elora/home-v4.css',
                'resources/css/elora/home-v5.css',
                'resources/css/elora/home-v6.css',
                'resources/css/ecommet.css',
                'resources/css/souqify.css',
                'resources/css/souqify-v2.css',
                'resources/js/app.js',
                'resources/js/storefront-cart.js',
                'resources/js/image-search.js',
                'resources/js/elora.js',
                'resources/js/elora/cart.js',
                'resources/js/elora/category.js',
                'resources/js/elora/footer.js',
                'resources/js/elora/footer-v2.js',
                'resources/js/elora/footer-v3.js',
                'resources/js/elora/footer-v4.js',
                'resources/js/elora/footer-v5.js',
                'resources/js/elora/footer-v6.js',
                'resources/js/elora/header.js',
                'resources/js/elora/header-v2.js',
                'resources/js/elora/header-v3.js',
                'resources/js/elora/header-v4.js',
                'resources/js/elora/header-v5.js',
                'resources/js/elora/header-v6.js',
                'resources/js/elora/home.js',
                'resources/js/elora/product.js',
                'resources/js/ecommet.js',
                'resources/js/ecommet/cart.js',
                'resources/js/ecommet/home.js',
                'resources/js/ecommet/product.js',
                'resources/js/souqify.js',
                'resources/js/souqify/header-v2.js',
                'resources/js/souqify-v2-interactions.js',
                'resources/js/souqify-v2-carousels.js',
                'resources/js/website.js',
                'resources/css/website.css',
                'resources/js/elora-v2-interactions.js',
                'resources/js/elora-v2-carousels.js',
                'resources/js/elora-v3-interactions.js',
                'resources/js/elora-v3-carousels.js',
                'resources/js/elora-v4-interactions.js',
                'resources/js/elora-v4-carousels.js',
                'resources/js/elora-v5-interactions.js',
                'resources/js/elora-v5-carousels.js',
                'resources/js/elora-v6-interactions.js',
                'resources/js/elora-v6-carousels.js',

                // ── TENANT PANEL (/admin on tenant domain) ─────────────────────────────
                'resources/css/tenant/app.css',
                'resources/js/tenant/app.js',
                'resources/js/tenant/pages/ui-kit.js',
                'resources/js/tenant/pages/auth/login.js',
                'resources/js/tenant/pages/dashboard/index.js',
                'resources/js/tenant/pages/insights/index.js',
                'resources/js/tenant/pages/catalog/edit-requests.js',
                'resources/js/tenant/pages/catalog/sortable.js',
                'resources/js/tenant/pages/catalog/categories-index.js',
                'resources/js/tenant/pages/catalog/category-form.js',
                'resources/js/tenant/pages/catalog/badge-show.js',
                'resources/js/tenant/pages/catalog/products-index.js',
                'resources/js/tenant/pages/catalog/products-price-list.js',
                'resources/js/tenant/pages/catalog/product-form.js',
                'resources/js/tenant/pages/catalog/own-products-index.js',
                'resources/js/tenant/pages/catalog/own-product-form.js',
                'resources/js/tenant/pages/sales/orders-index.js',
                'resources/js/tenant/pages/sales/order-show.js',
                'resources/js/tenant/pages/sales/returns-index.js',
                'resources/js/tenant/pages/sales/return-show.js',
                'resources/js/tenant/pages/sales/customers-index.js',
                'resources/js/tenant/pages/sales/customer-create.js',
                'resources/js/tenant/pages/sales/customer-detail.js',
                // page entries — one line per page; added by prompts 03–11, listed explicitly
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@tenant': path.resolve(__dirname, 'resources/js/tenant'),
            '@tenant-css': path.resolve(__dirname, 'resources/css/tenant'),
        },
    },
    build: {
        chunkSizeWarningLimit: 900,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) return undefined;
                    if (/node_modules\/(jquery|select2|datatables\.net|toastr)/.test(id)) return 'vendor-jquery';
                    if (id.includes('node_modules/flatpickr')) return 'vendor-flatpickr';
                    if (id.includes('node_modules/chart.js')) return 'vendor-charts';
                    if (id.includes('node_modules/tinymce')) return 'vendor-tinymce';
                    if (id.includes('node_modules/intl-tel-input')) return 'vendor-phone';
                    if (id.includes('node_modules/sweetalert2')) return 'vendor-swal';
                    if (id.includes('node_modules/sortablejs')) return 'vendor-sortable';
                    return undefined;
                },
            },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
