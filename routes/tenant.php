<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\{
    BroadcastAuthController,
    CartController,
    FavoriteController,
    HomePageController,
    PaymentController,
    PaymentWebhookController,
    RobotsController,
    SitemapController,
    StorefrontInvoiceController,
    StorefrontSearchController,
    StorefrontSocialAuthController,
};
use App\Livewire\Tenant\Storefront\{
    AuthPage,
    BestSellingPage,
    CartPage,
    CategoryPage,
    CheckoutPage,
    FavoritesPage,
    FullStarPage,
    HomePage,
    NewInPage,
    NotFoundPage,
    OffersPage,
    OrderStatusPage,
    OrderTrackingPage,
    PageView,
    ProductPage,
    ProfilePage,
    RequestReturnForm,
    ReturnDetailPage as StorefrontReturnDetailPage,
};
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    // Tenancy-aware broadcasting auth endpoint. The default /broadcasting/auth
    // route (registered on the central router) never initializes tenancy, so
    // private channels scoped to a tenant subdomain authorize here instead.
    Route::post('/tenant/broadcasting/auth', BroadcastAuthController::class)->middleware('auth:tenant')->name('tenant.broadcasting.auth');

    // ─── Storefront (public) ─────────────────────────────────────────────────

    Route::get('/robots.txt', RobotsController::class)->middleware('tenant.storefront.context')->name('tenant.robots');
    Route::get('/sitemap.xml', SitemapController::class)->middleware('tenant.storefront.context')->name('tenant.sitemap');

    Route::middleware(['tenant.storefront.context', 'identify.tenant.theme', 'tenant.gateway.blocked', 'preview.template', 'blade.theme.home', 'store.launch.gate'])->group(function () {
        Route::get('/', HomePage::class)->name('tenant.home');
        Route::get('/best-selling', BestSellingPage::class)->name('tenant.storefront.best-selling');
        Route::get('/full-star', FullStarPage::class)->name('tenant.storefront.full-star');
        Route::get('/new-in', NewInPage::class)->name('tenant.storefront.new-in');
        Route::get('/offers', OffersPage::class)->name('tenant.storefront.offers');
        Route::get('/search', BestSellingPage::class)->name('tenant.storefront.search');
        // Image search v1 — expandable to vector DB (pgvector, Pinecone, etc.).
        Route::post('/search/image', [\App\Http\Controllers\ImageSearchController::class, 'storefront'])->name('tenant.storefront.search.image');
        Route::get('/search/autocomplete', [StorefrontSearchController::class, 'autocomplete'])->name('tenant.storefront.search.autocomplete');
        Route::get('/api/products', [StorefrontSearchController::class, 'products'])->name('tenant.storefront.products.json');
        Route::get('/api/home/tabbed-products', HomePageController::class)->name('tenant.storefront.home.tabbed-products');
        Route::get('/categories/{slug?}', CategoryPage::class)->name('tenant.storefront.category');
        Route::get('/categories-products/{slug?}', [StorefrontSearchController::class, 'categoryProducts'])->name('tenant.storefront.category.products.json');
        Route::get('/products/{slug}', ProductPage::class)->name('tenant.storefront.product');
        Route::post('/cart/add', [CartController::class, 'add'])->name('tenant.storefront.cart.add');
        Route::post('/cart/remove', [CartController::class, 'remove'])->name('tenant.storefront.cart.remove');
        Route::post('/cart/update', [CartController::class, 'update'])->name('tenant.storefront.cart.update');
        Route::get('/cart', CartPage::class)->name('tenant.storefront.cart');
        Route::get('/favorites', FavoritesPage::class)->name('tenant.storefront.favorites')->middleware('auth:storefront');
        Route::middleware('auth:storefront')->group(function () {
            Route::post('/favorites/toggle', [FavoriteController::class, 'toggle'])->name('tenant.storefront.favorites.toggle');
            Route::get('/favorites/list', [FavoriteController::class, 'list'])->name('tenant.storefront.favorites.list');
            Route::get('/favorites/ids', [FavoriteController::class, 'ids'])->name('tenant.storefront.favorites.ids');
        });
        Route::get('/checkout', CheckoutPage::class)
            ->name('tenant.storefront.checkout');
        Route::get('/orders/{uuid}/status', OrderStatusPage::class)->name('tenant.storefront.order-status');
        Route::get('/orders/{uuid}/tracking', OrderTrackingPage::class)->name('tenant.storefront.order-tracking');
        Route::get('/orders/{uuid}/return', RequestReturnForm::class)
            ->middleware('auth:storefront')
            ->name('tenant.storefront.order-return');
        Route::get('/account/returns/{id}', StorefrontReturnDetailPage::class)
            ->middleware('auth:storefront')
            ->name('tenant.storefront.return-detail');
        Route::get('/orders/{uuid}/invoice', [StorefrontInvoiceController::class, 'show'])
            ->middleware('auth:storefront')
            ->name('tenant.storefront.order-invoice');

        // Customer auth
        Route::middleware('guest:storefront')->group(function () {
            Route::get('/account/login', AuthPage::class)->name('tenant.storefront.login');

            Route::get('/auth/google', [StorefrontSocialAuthController::class, 'redirectToGoogle'])
                ->name('tenant.storefront.social.google');
            Route::get('/auth/google/callback', [StorefrontSocialAuthController::class, 'handleGoogleCallback'])
                ->name('tenant.storefront.social.google.callback');
            Route::get('/auth/apple', [StorefrontSocialAuthController::class, 'redirectToApple'])
                ->name('tenant.storefront.social.apple');
            Route::post('/auth/apple/callback', [StorefrontSocialAuthController::class, 'handleAppleCallback'])
                ->name('tenant.storefront.social.apple.callback');
        });

        Route::get('/profile', ProfilePage::class)
            ->middleware('auth:storefront')
            ->name('tenant.storefront.profile');

        Route::get('/pages/{slug}', PageView::class)->name('tenant.storefront.page');

        // Named 404 page — safe to call with route() (no dynamic parameters)
        Route::get('/not-found', NotFoundPage::class)->name('tenant.storefront.not-found');

        // Catch-all for unknown storefront paths → themed 404 page
        Route::fallback(NotFoundPage::class)->name('tenant.storefront.404');

        // ─── Payment callbacks ───────────────────────────────────────────────
        //
        // GET      /checkout/payment/{gateway}/{orderUuid}  — initiate charge
        // GET|POST /checkout/payment/{gateway}/success       — gateway callback
        // GET      /checkout/payment/{gateway}/cancel        — user cancelled
        //
        // Flow (from CheckoutPage::placeOrder):
        //   1. Order is created (paid=false, status=Pending)
        //   2. Livewire redirects to tenant.payment.charge
        //   3. PaymentController::charge() calls the gateway and follows the redirect
        //   4. Gateway redirects back to tenant.payment.success  →  order marked paid
        //
        // Payment is always charged in USD. Currency shown on the frontend is
        // display-only (conversion rate applied client-side / in the view layer).
        //
        Route::prefix('checkout/payment')->name('tenant.payment.')->middleware('auth:storefront')->group(function () {
            Route::get('{gateway}/{orderUuid}', [PaymentController::class, 'charge'])->name('charge');
            Route::match(['get', 'post'], '{gateway}/success', [PaymentController::class, 'success'])->name('success');
            Route::get('{gateway}/cancel', [PaymentController::class, 'cancel'])->name('cancel');
        });

        // Inbound gateway webhook — no customer session, called by the gateway's
        // own server. CSRF-exempt (see bootstrap/app.php validateCsrfTokens).
        Route::prefix('checkout/payment')->name('tenant.payment.')->group(function () {
            Route::post('{gateway}/webhook', [PaymentWebhookController::class, 'handle'])->name('webhook');
        });

        Route::post('/account/logout', [StorefrontSocialAuthController::class, 'logout'])->middleware('auth:storefront')->name('tenant.storefront.logout');
    });

    // ─── Admin panel (all under /admin) ─────────────────────────────────────

    Route::prefix('admin')->group(base_path('routes/tenant_panel.php'));
});
