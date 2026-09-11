<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\CartController;
use App\Http\Controllers\Tenant\FavoriteController;
use App\Http\Controllers\Tenant\EmailVerificationController;
use App\Http\Controllers\Tenant\StorefrontInvoiceController;
use App\Http\Controllers\Tenant\StorefrontSocialAuthController;
use App\Http\Controllers\Tenant\BadgeProductsController as TenantBadgeProductsController;
use App\Http\Controllers\Tenant\HomePageController;
use App\Http\Controllers\Tenant\PaymentController;
use App\Http\Controllers\Tenant\PaymentWebhookController;
use App\Http\Controllers\Tenant\AiTranslationPaymentController;
use App\Http\Controllers\Tenant\LanguagePaymentController;
use App\Http\Controllers\Tenant\SubscriptionPaymentController;
use App\Http\Controllers\Tenant\VendorSettlementPaymentController;
use App\Livewire\Tenant\Analytics\CustomerLifetimeValuePage;
use App\Livewire\Tenant\Analytics\OrderAnalyticsPage;
use App\Livewire\Tenant\Analytics\ProductProfitabilityPage;
use App\Livewire\Tenant\Analytics\ShippingAnalyticsPage;
use App\Livewire\Tenant\Auth\LoginPage;
use App\Livewire\Tenant\Category\AddEditCategory;
use App\Livewire\Tenant\Category\CategoriesList;
use App\Livewire\Tenant\Category\CategoryProducts;
use App\Livewire\Tenant\Category\SortCategories;
use App\Livewire\Tenant\Product\SortProducts as TenantSortProducts;
use App\Livewire\Tenant\Badge\SortBadgeProducts as TenantSortBadgeProducts;
use App\Livewire\Tenant\Customer\CustomersList;
use App\Http\Controllers\Tenant\CustomerCreateController;
use App\Http\Controllers\Tenant\CustomerDetailController;
use App\Livewire\Tenant\Dashboard;
use App\Livewire\Tenant\Finance\BillingPage;
use App\Livewire\Tenant\Finance\BillingDetailPage;
use App\Livewire\Tenant\Finance\BuyLanguagePage;
use App\Livewire\Tenant\Finance\PayoutsReceivedPage;
use App\Livewire\Tenant\Finance\SettlementPaymentsPage;
use App\Livewire\Tenant\Finance\VendorPurchasePage;
use App\Livewire\Tenant\Finance\VendorSettleOrderPage;
use App\Livewire\Tenant\Finance\WalletPage;
use App\Livewire\Tenant\Order\OrdersList;
use App\Livewire\Tenant\Order\OrderDetailPage as TenantOrderDetailPage;
use App\Livewire\Tenant\Return\ReturnsList as TenantReturnsList;
use App\Livewire\Tenant\Return\ReturnDetailPage as TenantReturnDetailPage;
use App\Livewire\Tenant\Help\DocsPage;
use App\Livewire\Tenant\Storefront\RequestReturnForm;
use App\Livewire\Tenant\Storefront\ReturnDetailPage as StorefrontReturnDetailPage;
use App\Livewire\Tenant\Product\AddEditProduct;
use App\Livewire\Tenant\Product\ProductsList;
use App\Livewire\Tenant\Product\OwnProductsList;
use App\Http\Controllers\Tenant\OwnProductController;
use App\Livewire\Tenant\Manufacturing\ManufacturingRequestsList as TenantManufacturingRequestsList;
use App\Livewire\Tenant\Manufacturing\AddManufacturingRequest;
use App\Livewire\Tenant\Manufacturing\ManufacturingRequestDetail as TenantManufacturingRequestDetail;
use App\Http\Controllers\Tenant\ManufacturingPaymentController;
use App\Http\Controllers\Tenant\TenantImpersonateController;
use App\Livewire\Tenant\Notifications\NotificationsPage;
use App\Livewire\Tenant\Support\TicketsList;
use App\Livewire\Tenant\Support\CreateTicket;
use App\Livewire\Tenant\Support\TicketDetail;
use App\Livewire\Tenant\Onboarding\OnboardingPage;
use App\Http\Controllers\Tenant\AccountSettingsController;
use App\Http\Controllers\Tenant\ComplianceCenterController;
use App\Livewire\Tenant\Setting\AddEditEmailTemplate;
use App\Livewire\Tenant\Setting\AdminsList;
use App\Livewire\Tenant\Setting\CurrenciesPage;
use App\Livewire\Tenant\Setting\DomainsList;
use App\Livewire\Tenant\Setting\EmailTemplatesPage;
use App\Livewire\Tenant\Setting\GeneralSettingsPage;
use App\Livewire\Tenant\Setting\LanguagesManagePage;
use App\Livewire\Tenant\Setting\AiTranslationPage;
use App\Livewire\Tenant\Setting\TranslationsPage;
use App\Livewire\Tenant\Setting\LanguagesPage;
use App\Livewire\Tenant\Setting\MailConfigurationsPage;
use App\Livewire\Tenant\Setting\PaymentGatewaysPage;
use App\Livewire\Tenant\Setting\RolesPermissionsList;
use App\Livewire\Tenant\Setting\SubscribersPage;
use App\Livewire\Tenant\Store\AppearancePage;
use App\Livewire\Tenant\Store\BannersIndexPage;
use App\Livewire\Tenant\Store\BannersPage;
use App\Livewire\Tenant\Store\BladeThemePage;
use App\Livewire\Tenant\Setting\TrackingSettingsPage;
use App\Livewire\Tenant\Store\HomeVariantsPage;
use App\Livewire\Tenant\Store\PageBuilderPage;
use App\Livewire\Tenant\Store\CouponsIndexPage;
use App\Livewire\Tenant\Store\CouponsPage;
use App\Livewire\Tenant\Store\FlashSalesIndexPage;
use App\Livewire\Tenant\Store\FlashSalesPage;
use App\Livewire\Tenant\Store\AddEditPage;
use App\Livewire\Tenant\Store\PagesList;
use App\Livewire\Tenant\Store\ThemesPage;
use App\Livewire\Tenant\Storefront\AuthPage;
use App\Livewire\Tenant\Storefront\BestSellingPage;
use App\Livewire\Tenant\Storefront\CartPage;
use App\Livewire\Tenant\Storefront\CategoryPage;
use App\Livewire\Tenant\Storefront\FavoritesPage;
use App\Livewire\Tenant\Storefront\CheckoutPage;
use App\Livewire\Tenant\Storefront\FullStarPage;
use App\Livewire\Tenant\Storefront\HomePage;
use App\Livewire\Tenant\Storefront\NewInPage;
use App\Livewire\Tenant\Storefront\OffersPage;
use App\Livewire\Tenant\Storefront\OrderStatusPage;
use App\Livewire\Tenant\Storefront\OrderTrackingPage;
use App\Livewire\Tenant\Storefront\ProductPage;
use App\Http\Controllers\Tenant\RobotsController;
use App\Http\Controllers\Tenant\SitemapController;
use App\Livewire\Tenant\Storefront\NotFoundPage;
use App\Livewire\Tenant\Storefront\PageView;
use App\Livewire\Tenant\Storefront\ProfilePage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomainOrSubdomain;
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
    Route::post('/tenant/broadcasting/auth', function (Request $request) {
        return \Illuminate\Support\Facades\Broadcast::auth($request);
    })->middleware('auth:tenant')->name('tenant.broadcasting.auth');

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
        Route::get('/search/autocomplete', function () {
            $keyword = trim((string) request('q', ''));
            if (strlen($keyword) < 2) {
                return response()->json(['products' => []]);
            }
            $repo = app(\App\Repositories\Tenant\StorefrontRepository::class);
            $products = $repo->autocompleteProducts($keyword);
            $currentCurrency = request()->attributes->get('storefrontCurrentCurrency') ?: $repo->currentCurrency();
            $symbol = data_get($currentCurrency, 'symbol', '$');
            $rate = (float) data_get($currentCurrency, 'conversion_rate', 1.0);

            return response()->json([
                'products' => $products->map(function ($p) use ($symbol, $rate) {
                    $pricing = $p->storefrontPricing();

                    return [
                        'name' => $p->translationValue('name') ?? $p->slug,
                        'slug' => $p->slug,
                        'url' => route('tenant.storefront.product', $p->slug),
                        'image' => $p->primary_image_url,
                        'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
                        'original_price' => $pricing['original_price'] !== null
                            ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2)
                            : null,
                        'has_discount' => $pricing['has_discount'],
                        'discount_percentage' => $pricing['discount_percentage'],
                    ];
                })->values(),
            ]);
        })->name('tenant.storefront.search.autocomplete');
        Route::get('/api/products', function () {
            $repo = app(\App\Repositories\Tenant\StorefrontRepository::class);
            $currentCurrency = request()->attributes->get('storefrontCurrentCurrency')
                ?: $repo->currentCurrency();
            $paginator = $repo->paginatedProducts([], 20);

            // Resolve which product-card partial to use based on the active theme.
            $theme = request()->attributes->get('storefrontCurrentTheme');
            $themeSlug = $theme?->slug ?? 'elora';
            $cardView = "themes.{$themeSlug}.pages._product-card";
            if (!view()->exists($cardView)) {
                $cardView = 'themes.elora.pages._product-card';
            }

            $cards = collect($paginator->items())->map(function ($product) use ($currentCurrency, $cardView) {
                return view($cardView, [
                    'product' => $product,
                    'badge' => null,
                    'currentCurrency' => $currentCurrency,
                ])->render();
            })->values()->all();

            return response()->json([
                'has_more' => $paginator->hasMorePages(),
                'next_page' => $paginator->currentPage() + 1,
                'cards' => $cards,
            ]);
        })->name('tenant.storefront.products.json');
        Route::get('/api/home/tabbed-products', HomePageController::class)->name('tenant.storefront.home.tabbed-products');
        Route::get('/categories/{slug?}', CategoryPage::class)->name('tenant.storefront.category');
        Route::get('/categories-products/{slug?}', function (string $slug = null) {
            $repo = app(\App\Repositories\Tenant\StorefrontRepository::class);
            $category = $repo->categoryBySlug($slug);
            // if (!$category) {
            //     return response()->json(['has_more' => false, 'cards' => []]);
            // }
            $filters = [
                'keyword' => trim((string) request('keyword', '')),
                'sort' => request('sort', 'latest'),
                'availability' => request('availability', ''),
                'product_flag' => request('product_flag', ''),
                'on_sale' => request('on_sale', ''),
                'ratings' => request('ratings', ''),
                'min' => request('min', ''),
                'max' => request('max', ''),
            ];
            $paginator = $repo->paginatedProductsByCategory($slug ? $category : null, $filters, 15);
            $currentCurrency = $repo->currentCurrency();
            $cards = collect($paginator->items())->map(fn($p) => view('themes.elora.pages._product-card', [
                'product' => $p,
                'badge' => null,
                'currentCurrency' => $currentCurrency,
            ])->render())->values()->all();
            return response()->json([
                'has_more' => $paginator->hasMorePages(),
                'cards' => $cards,
            ]);
        })->name('tenant.storefront.category.products.json');
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

        Route::post('/account/logout', function (Request $request) {
            Auth::guard('storefront')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('tenant.home');
        })->middleware('auth:storefront')->name('tenant.storefront.logout');
    });

    // ─── Admin panel (all under /admin) ─────────────────────────────────────

    Route::prefix('admin')->group(base_path('routes/tenant_panel.php'));
});
