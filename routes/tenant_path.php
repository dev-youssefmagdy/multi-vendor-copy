<?php

declare(strict_types=1);

/**
 * Path-based tenant storefront routes.
 *
 * These routes expose every tenant's public storefront on the central domain
 * using the tenant slug as the first path segment:
 *
 *   nogrgr.com/{slug}/                → store home page
 *   nogrgr.com/{slug}/products/{slug} → product page
 *   … etc.
 *
 * The admin panel is intentionally NOT exposed here – it is only accessible
 * via the subdomain or custom domain (e.g. store.nogrgr.com/admin).
 *
 * A dedicated Livewire update endpoint is registered here as well so that
 * Livewire component updates from path-based pages are routed through
 * InitializeTenancyBySlug:
 *
 *   POST nogrgr.com/s/{slug}/livewire/update
 *
 * The theme layout injects a hidden element with that URI so Livewire's JS
 * picks it up as the update endpoint (see theme layout app.blade.php files).
 */

use App\Http\Controllers\Tenant\CartController;
use App\Http\Controllers\Tenant\HomePageController;
use App\Http\Controllers\Tenant\RobotsController;
use App\Http\Controllers\Tenant\SitemapController;
use App\Http\Controllers\Tenant\StorefrontInvoiceController;
use App\Http\Controllers\Tenant\PaymentController;
use App\Http\Middleware\InitializeTenancyBySlug;
use App\Http\Middleware\InitializeTenancyForPreview;
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
use App\Livewire\Tenant\Storefront\PageView;
use App\Livewire\Tenant\Storefront\ProfilePage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ─── Path-based Livewire update endpoint ────────────────────────────────────
//
// Receives Livewire subsequent-request POSTs for components that were rendered
// on a path-based page.  The theme layout overrides Livewire's update URI via
// a hidden DOM element that points to this endpoint.
//
Route::post('/s/{tenant}/livewire/update', function () {
    // Handled by the Livewire mechanism, which is invoked by the router
    // automatically once the route matches.  We just need the middleware to
    // initialise tenancy before that happens.
    abort(404); // Should never reach here – Livewire intercepts the route.
})->middleware(['web', InitializeTenancyBySlug::class])
    ->name('tenant.path.livewire.update');

// ─── Path-prefixed storefront ────────────────────────────────────────────────
Route::prefix('vendor/{tenant}')
    ->middleware([InitializeTenancyBySlug::class, 'web', 'tenant.storefront.context', 'tenant.gateway.blocked', 'preview.template'])
    ->group(function () {

        Route::get('/', HomePage::class)->middleware('custom.template.home')->name('tenant.path.home');

        Route::get('/custom-template-assets/{file}', [\App\Http\Controllers\Tenant\CustomTemplateController::class, 'live'])
            ->where('file', '.*')
            ->name('tenant.path.custom-template.asset');

        Route::get('/robots.txt', RobotsController::class)->name('tenant.path.robots');
        Route::get('/sitemap.xml', SitemapController::class)->name('tenant.path.sitemap');

        Route::get('/best-selling', BestSellingPage::class)->name('tenant.path.storefront.best-selling');
        Route::get('/full-star', FullStarPage::class)->name('tenant.path.storefront.full-star');
        Route::get('/new-in', NewInPage::class)->name('tenant.path.storefront.new-in');
        Route::get('/offers', OffersPage::class)->name('tenant.path.storefront.offers');
        Route::get('/search', BestSellingPage::class)->name('tenant.path.storefront.search');

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
                        'name' => $p->name ?? $p->slug,
                        'slug' => $p->slug,
                        'url' => route('tenant.path.storefront.product', [request()->route('tenant'), $p->slug]),
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
        })->name('tenant.path.storefront.search.autocomplete');

        Route::get('/api/products', function () {
            $repo = app(\App\Repositories\Tenant\StorefrontRepository::class);
            $currentCurrency = request()->attributes->get('storefrontCurrentCurrency')
                ?: $repo->currentCurrency();
            $paginator = $repo->paginatedProducts([], 20);

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
        })->name('tenant.path.storefront.products.json');

        Route::get('/api/home/tabbed-products', HomePageController::class)->name('tenant.path.storefront.home.tabbed-products');

        Route::get('/categories/{slug?}', CategoryPage::class)->name('tenant.path.storefront.category');
        Route::get('/categories-products/{slug?}', function (string $slug = null) {
            $repo = app(\App\Repositories\Tenant\StorefrontRepository::class);
            $category = $repo->categoryBySlug($slug);
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
        })->name('tenant.path.storefront.category.products.json');

        Route::get('/products/{slug}', ProductPage::class)->name('tenant.path.storefront.product');
        Route::post('/cart/add', [CartController::class, 'add'])->name('tenant.path.storefront.cart.add');
        Route::get('/cart', CartPage::class)->name('tenant.path.storefront.cart');

        Route::get('/favorites', FavoritesPage::class)
            ->middleware('auth:storefront')
            ->name('tenant.path.storefront.favorites');

        Route::get('/checkout', CheckoutPage::class)
            ->middleware('auth:storefront')
            ->name('tenant.path.storefront.checkout');

        Route::get('/orders/{uuid}/status', OrderStatusPage::class)->name('tenant.path.storefront.order-status');
        Route::get('/orders/{uuid}/tracking', OrderTrackingPage::class)->name('tenant.path.storefront.order-tracking');

        Route::get('/orders/{uuid}/invoice', [StorefrontInvoiceController::class, 'download'])
            ->middleware('auth:storefront')
            ->name('tenant.path.storefront.order-invoice');

        Route::middleware('guest:storefront')->group(function () {
            Route::get('/account/login', AuthPage::class)->name('tenant.path.storefront.login');
        });

        Route::get('/profile', ProfilePage::class)
            ->middleware('auth:storefront')
            ->name('tenant.path.storefront.profile');

        Route::get('/pages/{slug}', PageView::class)->name('tenant.path.storefront.page');

        // ─── Payment callbacks ───────────────────────────────────────────────
        Route::prefix('checkout/payment')->name('tenant.path.payment.')->middleware('auth:storefront')->group(function () {
            Route::get('{gateway}/{orderUuid}', [PaymentController::class, 'charge'])->name('charge');
            Route::match(['get', 'post'], '{gateway}/success', [PaymentController::class, 'success'])->name('success');
            Route::get('{gateway}/cancel', [PaymentController::class, 'cancel'])->name('cancel');
        });

        Route::post('/account/logout', function (Request $request) {
            Auth::guard('storefront')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('tenant.path.home', ['tenant' => request()->route('tenant')]);
        })->middleware('auth:storefront')->name('tenant.path.storefront.logout');

        // ─── Admin panel redirect ────────────────────────────────────────────
        // The full admin panel is only available on the subdomain/custom domain.
        // Redirect path-based /admin requests to the canonical tenant URL.
        Route::get('/admin{any}', function (string $any = '') {
            $tenantSlug = request()->route('tenant');
            /** @var \App\Models\Tenant|null $tenant */
            $tenant = tenancy()->tenant;
            $domain = $tenant?->domains->first()?->domain;
            if ($domain) {
                $scheme = request()->isSecure() ? 'https' : 'http';
                return redirect("{$scheme}://{$domain}/admin{$any}", 301);
            }
            abort(404);
        })->where('any', '.*')->name('tenant.path.admin.redirect');
    });

// ─── Theme preview (central domain) ────────────────────────────────────────
// Renders the dedicated "preview" tenant's storefront with URL-supplied
// theme/color/homepage-variant overrides. Never touches real tenant data.
//
//   /preview?theme=elora&colors[color-primary]=%23ff0000&homepage_variant=v2
Route::get('/preview', HomePage::class)
    ->middleware(['web', InitializeTenancyForPreview::class, 'tenant.storefront.context', 'preview.template'])
    ->name('preview');
