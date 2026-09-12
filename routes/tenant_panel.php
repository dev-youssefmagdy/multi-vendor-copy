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
use App\Http\Controllers\Tenant\Panel\Finance\BillingController;
use App\Http\Controllers\Tenant\Panel\Finance\PayoutsController;
use App\Http\Controllers\Tenant\Panel\Finance\SettlementPaymentsController;
use App\Http\Controllers\Tenant\Panel\Finance\VendorPurchaseController;
use App\Http\Controllers\Tenant\Panel\Finance\VendorSettleOrderController;
use App\Livewire\Tenant\Finance\VendorPurchasePage;
use App\Http\Controllers\Tenant\Panel\Finance\WalletController;
use App\Http\Controllers\Tenant\Panel\Finance\BuyLanguageController;
use App\Livewire\Tenant\Order\OrdersList;
use App\Livewire\Tenant\Order\OrderDetailPage as TenantOrderDetailPage;
use App\Livewire\Tenant\Return\ReturnsList as TenantReturnsList;
use App\Livewire\Tenant\Return\ReturnDetailPage as TenantReturnDetailPage;
use App\Livewire\Tenant\Help\DocsPage;
use App\Livewire\Tenant\Storefront\RequestReturnForm;
use App\Livewire\Tenant\Storefront\ReturnDetailPage as StorefrontReturnDetailPage;
use App\Livewire\Tenant\Product\AddEditProduct;
use App\Livewire\Tenant\Product\ProductsList;
use App\Http\Controllers\Tenant\Panel\Catalog\OwnProductController;
use App\Http\Controllers\Tenant\Panel\Catalog\OwnProductsListController;
use App\Http\Controllers\Tenant\Panel\Store\ThemesController;
use App\Http\Controllers\Tenant\Panel\Store\PagesController;
use App\Http\Controllers\Tenant\Panel\Store\PageFormController;
use App\Livewire\Tenant\Manufacturing\ManufacturingRequestsList as TenantManufacturingRequestsList;
use App\Livewire\Tenant\Manufacturing\AddManufacturingRequest;
use App\Livewire\Tenant\Manufacturing\ManufacturingRequestDetail as TenantManufacturingRequestDetail;
use App\Http\Controllers\Tenant\ManufacturingPaymentController;
use App\Http\Controllers\Tenant\TenantImpersonateController;
use App\Livewire\Tenant\Notifications\NotificationsPage;
use App\Http\Controllers\Tenant\Panel\Support\TicketController;
use App\Http\Controllers\Tenant\Panel\Requests\ProductRequestController;
use App\Livewire\Tenant\Onboarding\OnboardingPage;
use App\Http\Controllers\Tenant\Panel\Settings\AccountSettingsController;
use App\Http\Controllers\Tenant\Panel\Settings\ComplianceCenterController;
use App\Http\Controllers\Tenant\Panel\Settings\AdminsController;
use App\Http\Controllers\Tenant\Panel\Settings\AiTranslationController;
use App\Http\Controllers\Tenant\Panel\Settings\CurrenciesController;
use App\Http\Controllers\Tenant\Panel\Settings\DomainsController;
use App\Http\Controllers\Tenant\Panel\Settings\EmailTemplateController;
use App\Http\Controllers\Tenant\Panel\Settings\GeneralSettingsController;
use App\Http\Controllers\Tenant\Panel\Settings\LanguagesController;
use App\Http\Controllers\Tenant\Panel\Settings\LanguagesManageController;
use App\Http\Controllers\Tenant\Panel\Settings\MailConfigurationsController;
use App\Http\Controllers\Tenant\Panel\Settings\PaymentGatewaysController;
use App\Http\Controllers\Tenant\Panel\Settings\PaymentReadinessController;
use App\Http\Controllers\Tenant\Panel\Settings\ReturnPolicyController;
use App\Http\Controllers\Tenant\Panel\Settings\RolesPermissionsController;
use App\Http\Controllers\Tenant\Panel\Settings\SubscribersController;
use App\Http\Controllers\Tenant\Panel\Settings\TrackingSettingsController;
use App\Http\Controllers\Tenant\Panel\Settings\TranslationsController;
use App\Http\Controllers\Tenant\Panel\Store\BannerController;
use App\Http\Controllers\Tenant\Panel\Store\CouponController;
use App\Http\Controllers\Tenant\Panel\Store\FlashSaleController;
use App\Http\Controllers\Tenant\Panel\Store\AppearanceController;
use App\Http\Controllers\Tenant\Panel\Store\SocialLinkController;
use App\Livewire\Tenant\Store\BladeThemePage;
use App\Livewire\Tenant\Store\HomeVariantsPage;
use App\Livewire\Tenant\Store\PageBuilderPage;
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


        Route::get('/', function () {
            return redirect()->route(
                Auth::guard('tenant')->check() ? 'tenant.dashboard' : 'tenant.login'
            );
        });

        Route::middleware('guest:tenant')->group(function () {
            Route::get('/login', [\App\Http\Controllers\Tenant\Panel\Auth\LoginController::class, 'show'])->name('tenant.login');
            Route::post('/login', [\App\Http\Controllers\Tenant\Panel\Auth\LoginController::class, 'login'])
                ->middleware('throttle:6,1')
                ->name('tenant.login.attempt');
            Route::post('/login/validate', [\App\Http\Controllers\Tenant\Panel\Auth\LoginController::class, 'validateLogin'])
                ->middleware('throttle:tenant-validate')
                ->name('tenant.login.validate');
        });

        Route::post('/logout', [\App\Http\Controllers\Tenant\Panel\Auth\LoginController::class, 'logout'])
            ->middleware('auth:tenant')
            ->name('tenant.logout');

        // Central-admin → Tenant impersonation (no auth guard yet – token IS the auth)
        Route::get('/impersonate/{token}', [TenantImpersonateController::class, 'accept'])
            ->name('tenant.impersonate.accept');

        // Email verification — the signed link itself doesn't require a session
        // (it may be opened from a different browser than the one logged in).
        Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
            ->middleware(['signed', 'throttle:6,1'])
            ->name('tenant.verification.verify');

        Route::middleware(['auth:tenant', 'tenant.setup.enforce', 'tenant.tour'])->group(function () {

            Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
                ->middleware('throttle:6,1')
                ->withoutMiddleware('tenant.setup.enforce')
                ->name('tenant.verification.send');

            Route::post('/compliance/accept', [\App\Http\Controllers\Tenant\Panel\Shell\ComplianceController::class, 'accept'])
                ->withoutMiddleware('tenant.setup.enforce')
                ->name('tenant.compliance.accept');
            Route::post('/compliance/accept/validate', [\App\Http\Controllers\Tenant\Panel\Shell\ComplianceController::class, 'validateAccept'])
                ->middleware('throttle:tenant-validate')
                ->withoutMiddleware('tenant.setup.enforce')
                ->name('tenant.compliance.accept.validate');

            Route::get('/widgets/setup-progress', [\App\Http\Controllers\Tenant\Panel\Shell\SetupProgressController::class, 'show'])
                ->withoutMiddleware('tenant.setup.enforce')
                ->name('tenant.widgets.setup-progress');
            Route::post('/widgets/setup-progress/pages-reviewed', [\App\Http\Controllers\Tenant\Panel\Shell\SetupProgressController::class, 'pagesReviewed'])
                ->withoutMiddleware('tenant.setup.enforce')
                ->name('tenant.widgets.setup-progress.pages-reviewed');

            Route::get('/dashboard', [\App\Http\Controllers\Tenant\Panel\Insights\DashboardController::class, 'index'])
                ->middleware('tenant.permission:dashboard.view')
                ->name('tenant.dashboard');

            Route::get('/onboarding/{tab?}', OnboardingPage::class)
                ->where('tab', 'tour|setup')
                ->name('tenant.onboarding');

            if (app()->environment('local')) {
                Route::prefix('_ui')->name('tenant.ui-kit')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Tenant\Panel\UiKitController::class, '__invoke'])->name('');
                    Route::get('/data', [\App\Http\Controllers\Tenant\Panel\UiKitController::class, 'data'])->name('.data');
                    Route::post('/validate', [\App\Http\Controllers\Tenant\Panel\UiKitController::class, 'validateForm'])->name('.validate')->middleware('throttle:tenant-validate');
                    Route::post('/', [\App\Http\Controllers\Tenant\Panel\UiKitController::class, 'store'])->name('.store');
                });
            }

            Route::prefix('products')->name('tenant.products.')->middleware('tenant.permission:catalog.products.manage')->group(function () {
                Route::get('/', [\App\Http\Controllers\Tenant\Panel\Catalog\ProductsListController::class, 'index'])->middleware('tenant.setup:theme')->name('index');
                Route::get('/data', [\App\Http\Controllers\Tenant\Panel\Catalog\ProductsListController::class, 'data'])->name('data');
                Route::get('/sort', [\App\Http\Controllers\Tenant\Panel\Catalog\ProductSortController::class, 'index'])->name('sort');
                Route::post('/sort', [\App\Http\Controllers\Tenant\Panel\Catalog\ProductSortController::class, 'update'])->name('sort.save');
                Route::get('/edit-requests', [\App\Http\Controllers\Tenant\Panel\Catalog\EditRequestsController::class, 'index'])->name('edit-requests');
                Route::get('/edit-requests/data', [\App\Http\Controllers\Tenant\Panel\Catalog\EditRequestsController::class, 'data'])->name('edit-requests.data');
                Route::get('/central-search', [\App\Http\Controllers\Tenant\Panel\Catalog\ProductController::class, 'centralSearch'])->name('central-search');
                Route::get('/central-snapshot/{centralProduct}', [\App\Http\Controllers\Tenant\Panel\Catalog\ProductController::class, 'centralSnapshot'])->name('central-snapshot');
                Route::get('/create', [\App\Http\Controllers\Tenant\Panel\Catalog\ProductController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Tenant\Panel\Catalog\ProductController::class, 'store'])->name('store');
                Route::post('/validate', [\App\Http\Controllers\Tenant\Panel\Catalog\ProductController::class, 'validateStore'])->name('validate')->middleware('throttle:tenant-validate');
                Route::patch('/{product}/active', [\App\Http\Controllers\Tenant\Panel\Catalog\ProductsListController::class, 'toggleActive'])->name('toggle-active');
                Route::patch('/{product}/featured', [\App\Http\Controllers\Tenant\Panel\Catalog\ProductsListController::class, 'toggleFeatured'])->name('toggle-featured');
                Route::get('/{product}/social', [\App\Http\Controllers\Tenant\Panel\Catalog\ProductModalsController::class, 'social'])->name('social');
                Route::post('/{product}/social/generate', [\App\Http\Controllers\Tenant\Panel\Catalog\ProductModalsController::class, 'generateSocial'])->name('social.generate');
                Route::get('/{product}/ai-price', [\App\Http\Controllers\Tenant\Panel\Catalog\ProductModalsController::class, 'aiPrice'])->name('ai-price');
                Route::post('/{product}/ai-price', [\App\Http\Controllers\Tenant\Panel\Catalog\ProductModalsController::class, 'fetchAiPrice'])->name('ai-price.fetch');
                Route::get('/{product}/share', [\App\Http\Controllers\Tenant\Panel\Catalog\ProductModalsController::class, 'share'])->name('share');
                Route::get('/{product}/price-list', [\App\Http\Controllers\Tenant\Panel\Catalog\ProductModalsController::class, 'priceListShow'])->name('price-list');
                Route::put('/{product}/price-list', [\App\Http\Controllers\Tenant\Panel\Catalog\ProductModalsController::class, 'priceListSave'])->name('price-list.save');
                Route::post('/{product}/price-list/preview', [\App\Http\Controllers\Tenant\Panel\Catalog\ProductModalsController::class, 'priceListPreview'])->name('price-list.preview');
                Route::get('/{product}/edit', [\App\Http\Controllers\Tenant\Panel\Catalog\ProductController::class, 'edit'])->name('edit');
                Route::put('/{product}', [\App\Http\Controllers\Tenant\Panel\Catalog\ProductController::class, 'update'])->name('update');
                Route::post('/{product}/validate', [\App\Http\Controllers\Tenant\Panel\Catalog\ProductController::class, 'validateUpdate'])->name('validate.update')->middleware('throttle:tenant-validate');
                Route::post('/image-search', [\App\Http\Controllers\ImageSearchController::class, 'tenantPanel'])->name('image-search');
            });

            Route::prefix('own-products')->name('tenant.own-products.')->middleware('tenant.permission:catalog.products.manage')->group(function () {
                Route::get('/', [OwnProductsListController::class, 'index'])->name('index');
                Route::get('/data', [OwnProductsListController::class, 'data'])->name('data');
                Route::get('/create', [OwnProductController::class, 'create'])->name('create');
                Route::post('/', [OwnProductController::class, 'store'])->name('store');
                Route::post('/validate', [OwnProductController::class, 'validateStore'])->name('validate');
                Route::get('/{product}/edit', [OwnProductController::class, 'edit'])->name('edit');
                Route::put('/{product}', [OwnProductController::class, 'update'])->name('update');
                Route::post('/{product}/validate', [OwnProductController::class, 'validateUpdate'])->name('validate.update');
                Route::delete('/{product}', [OwnProductsListController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('manufacturing')->name('tenant.manufacturing.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Tenant\Panel\Requests\ManufacturingController::class, 'index'])->name('index');
                Route::get('/data', [\App\Http\Controllers\Tenant\Panel\Requests\ManufacturingController::class, 'data'])->name('data');
                Route::get('/export', [\App\Http\Controllers\Tenant\Panel\Requests\ManufacturingController::class, 'export'])->name('export');
                Route::get('/products/search', [\App\Http\Controllers\Tenant\Panel\Requests\ManufacturingController::class, 'searchProducts'])->name('products.search');
                Route::get('/create', [\App\Http\Controllers\Tenant\Panel\Requests\ManufacturingController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Tenant\Panel\Requests\ManufacturingController::class, 'store'])->name('store');
                Route::post('/validate', [\App\Http\Controllers\Tenant\Panel\Requests\ManufacturingController::class, 'validateStore'])->name('validate')->middleware('throttle:tenant-validate');
                Route::get('/{id}', [\App\Http\Controllers\Tenant\Panel\Requests\ManufacturingController::class, 'show'])->whereNumber('id')->name('show');
                Route::post('/{id}/cancel', [\App\Http\Controllers\Tenant\Panel\Requests\ManufacturingController::class, 'cancel'])->whereNumber('id')->name('cancel');
                Route::post('/{id}/messages', [\App\Http\Controllers\Tenant\Panel\Requests\ManufacturingController::class, 'sendMessage'])->whereNumber('id')->name('messages');
                Route::post('/{id}/messages/validate', [\App\Http\Controllers\Tenant\Panel\Requests\ManufacturingController::class, 'validateMessage'])->whereNumber('id')->name('messages.validate')->middleware('throttle:tenant-validate');
                Route::post('/{id}/pay', [\App\Http\Controllers\Tenant\Panel\Requests\ManufacturingController::class, 'pay'])->whereNumber('id')->name('pay');
                Route::post('/{id}/pay/validate', [\App\Http\Controllers\Tenant\Panel\Requests\ManufacturingController::class, 'validatePay'])->whereNumber('id')->name('pay.validate')->middleware('throttle:tenant-validate');
            });

            Route::prefix('brand-requests')->name('tenant.brand-requests.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Tenant\Panel\Requests\BrandRequestController::class, 'index'])->name('index');
                Route::get('/data', [\App\Http\Controllers\Tenant\Panel\Requests\BrandRequestController::class, 'data'])->name('data');
                Route::get('/create', [\App\Http\Controllers\Tenant\Panel\Requests\BrandRequestController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Tenant\Panel\Requests\BrandRequestController::class, 'store'])->name('store');
                Route::post('/validate', [\App\Http\Controllers\Tenant\Panel\Requests\BrandRequestController::class, 'validateStore'])->name('validate')->middleware('throttle:tenant-validate');
                Route::get('/{id}', [\App\Http\Controllers\Tenant\Panel\Requests\BrandRequestController::class, 'show'])->whereNumber('id')->name('show');
                Route::post('/{id}/messages', [\App\Http\Controllers\Tenant\Panel\Requests\BrandRequestController::class, 'sendMessage'])->whereNumber('id')->name('messages');
                Route::post('/{id}/messages/validate', [\App\Http\Controllers\Tenant\Panel\Requests\BrandRequestController::class, 'validateMessage'])->whereNumber('id')->name('messages.validate')->middleware('throttle:tenant-validate');
                Route::post('/{id}/pay', [\App\Http\Controllers\Tenant\Panel\Requests\BrandRequestController::class, 'pay'])->whereNumber('id')->name('pay');
                Route::post('/{id}/pay/validate', [\App\Http\Controllers\Tenant\Panel\Requests\BrandRequestController::class, 'validatePay'])->whereNumber('id')->name('pay.validate')->middleware('throttle:tenant-validate');
            });

            Route::prefix('notifications')->name('tenant.notifications.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Tenant\Panel\Support\NotificationsController::class, 'index'])->name('index');
                Route::get('/feed', [\App\Http\Controllers\Tenant\Panel\Support\NotificationsController::class, 'feed'])->name('feed');
                Route::post('/read-all', [\App\Http\Controllers\Tenant\Panel\Support\NotificationsController::class, 'markAllRead'])->name('read-all');
                Route::patch('/{id}/read', [\App\Http\Controllers\Tenant\Panel\Support\NotificationsController::class, 'markRead'])->whereNumber('id')->name('mark-read');
            });

            Route::prefix('support')->name('tenant.support.')->group(function () {
                Route::get('/', [TicketController::class, 'index'])->name('index');
                Route::get('/data', [TicketController::class, 'data'])->name('data');
                Route::get('/new', [TicketController::class, 'create'])->name('create');
                Route::post('/', [TicketController::class, 'store'])->name('store');
                Route::post('/validate', [TicketController::class, 'validateStore'])->name('validate')->middleware('throttle:tenant-validate');
                Route::get('/{ticketId}', [TicketController::class, 'show'])->whereNumber('ticketId')->name('show');
                Route::post('/{ticketId}/replies', [TicketController::class, 'reply'])->whereNumber('ticketId')->name('replies');
                Route::post('/{ticketId}/replies/validate', [TicketController::class, 'validateReply'])->whereNumber('ticketId')->name('replies.validate')->middleware('throttle:tenant-validate');
                Route::get('/{ticketId}/thread', [TicketController::class, 'thread'])->whereNumber('ticketId')->name('thread');
            });

            Route::prefix('product-requests')->name('tenant.product-requests.')->middleware('tenant.permission:catalog.products.manage')->group(function () {
                Route::get('/', [ProductRequestController::class, 'index'])->name('index');
                Route::get('/data', [ProductRequestController::class, 'data'])->name('data');
                Route::get('/new', [ProductRequestController::class, 'create'])->name('create');
                Route::post('/', [ProductRequestController::class, 'store'])->name('store');
                Route::post('/validate', [ProductRequestController::class, 'validateStore'])->name('validate')->middleware('throttle:tenant-validate');
                Route::get('/{requestId}', [ProductRequestController::class, 'show'])->whereNumber('requestId')->name('show');
                Route::post('/{requestId}/replies', [ProductRequestController::class, 'reply'])->whereNumber('requestId')->name('replies');
                Route::post('/{requestId}/replies/validate', [ProductRequestController::class, 'validateReply'])->whereNumber('requestId')->name('replies.validate')->middleware('throttle:tenant-validate');
            });

            Route::prefix('categories')->name('tenant.categories.')->middleware('tenant.permission:catalog.categories.manage')->group(function () {
                Route::get('/', [\App\Http\Controllers\Tenant\Panel\Catalog\CategoryController::class, 'index'])->name('index');
                Route::get('/data', [\App\Http\Controllers\Tenant\Panel\Catalog\CategoryController::class, 'data'])->name('data');
                Route::get('/sort', [\App\Http\Controllers\Tenant\Panel\Catalog\CategorySortController::class, 'index'])->name('sort');
                Route::post('/sort', [\App\Http\Controllers\Tenant\Panel\Catalog\CategorySortController::class, 'update'])->name('sort.save');
                Route::get('/create', [\App\Http\Controllers\Tenant\Panel\Catalog\CategoryController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Tenant\Panel\Catalog\CategoryController::class, 'store'])->name('store');
                Route::post('/validate', [\App\Http\Controllers\Tenant\Panel\Catalog\CategoryController::class, 'validateStore'])->name('validate')->middleware('throttle:tenant-validate');
                Route::get('/{category}/edit', [\App\Http\Controllers\Tenant\Panel\Catalog\CategoryController::class, 'edit'])->name('edit');
                Route::put('/{category}', [\App\Http\Controllers\Tenant\Panel\Catalog\CategoryController::class, 'update'])->name('update');
                Route::post('/{category}/validate', [\App\Http\Controllers\Tenant\Panel\Catalog\CategoryController::class, 'validateUpdate'])->name('validate.update')->middleware('throttle:tenant-validate');
                Route::patch('/{category}/active', [\App\Http\Controllers\Tenant\Panel\Catalog\CategoryController::class, 'toggleActive'])->name('toggle-active');
                Route::patch('/{category}/featured', [\App\Http\Controllers\Tenant\Panel\Catalog\CategoryController::class, 'toggleFeatured'])->name('toggle-featured');
                Route::delete('/{category}', [\App\Http\Controllers\Tenant\Panel\Catalog\CategoryController::class, 'destroy'])->name('destroy');
                Route::get('/{category}/products', [\App\Http\Controllers\Tenant\Panel\Catalog\CategoryProductsController::class, 'index'])->name('products');
                Route::post('/{category}/products/sort', [\App\Http\Controllers\Tenant\Panel\Catalog\CategoryProductsController::class, 'update'])->name('products.sort');
            });

            Route::prefix('badges')->name('tenant.badges.')->middleware('tenant.permission:catalog.badges.manage')->group(function () {
                Route::get('/', function () {
                    return redirect()->route('tenant.badges.show', ['badge' => 'new-in']);
                })->name('index');
                Route::get('/{badge}', [\App\Http\Controllers\Tenant\Panel\Catalog\BadgeController::class, 'show'])->name('show');
                Route::get('/{badge}/search', [\App\Http\Controllers\Tenant\Panel\Catalog\BadgeController::class, 'searchProducts'])->name('search');
                Route::get('/{badge}/sort', [\App\Http\Controllers\Tenant\Panel\Catalog\BadgeSortController::class, 'index'])->name('sort');
                Route::post('/{badge}/sort', [\App\Http\Controllers\Tenant\Panel\Catalog\BadgeSortController::class, 'update'])->name('sort');
                Route::post('/{badge}/assign-category', [\App\Http\Controllers\Tenant\Panel\Catalog\BadgeController::class, 'assignCategory'])->name('assign-category');
                Route::post('/{badge}/save', [\App\Http\Controllers\Tenant\Panel\Catalog\BadgeController::class, 'save'])->name('save');
            });

            Route::get('/orders', [\App\Http\Controllers\Tenant\Panel\Sales\OrdersController::class, 'index'])
                ->middleware(['tenant.permission:sales.orders.view', 'tenant.setup:payment_gateway'])
                ->name('tenant.orders.index');

            Route::get('/orders/data', [\App\Http\Controllers\Tenant\Panel\Sales\OrdersController::class, 'data'])
                ->middleware(['tenant.permission:sales.orders.view', 'tenant.setup:payment_gateway'])
                ->name('tenant.orders.data');

            Route::get('/orders/export', [\App\Http\Controllers\Tenant\Panel\Sales\OrdersController::class, 'export'])
                ->middleware(['tenant.permission:sales.orders.view', 'tenant.setup:payment_gateway'])
                ->name('tenant.orders.export');

            Route::get('/orders/{orderId}', [\App\Http\Controllers\Tenant\Panel\Sales\OrdersController::class, 'show'])
                ->whereNumber('orderId')
                ->middleware('tenant.permission:sales.orders.view')
                ->name('tenant.orders.show');

            Route::patch('/orders/{orderId}/shipping-status', [\App\Http\Controllers\Tenant\Panel\Sales\OrdersController::class, 'updateShippingStatus'])
                ->whereNumber('orderId')
                ->middleware('tenant.permission:sales.orders.view')
                ->name('tenant.orders.shipping-status');

            Route::post('/orders/{orderId}/shipping-status/validate', [\App\Http\Controllers\Tenant\Panel\Sales\OrdersController::class, 'validateUpdateShippingStatus'])
                ->whereNumber('orderId')
                ->middleware(['tenant.permission:sales.orders.view', 'throttle:tenant-validate'])
                ->name('tenant.orders.shipping-status.validate');

            Route::prefix('returns')->name('tenant.returns.')->middleware('tenant.permission:sales.returns.manage')->group(function () {
                Route::get('/', [\App\Http\Controllers\Tenant\Panel\Sales\ReturnsController::class, 'index'])->name('index');
                Route::get('/data', [\App\Http\Controllers\Tenant\Panel\Sales\ReturnsController::class, 'data'])->name('data');

                Route::get('/analytics', [\App\Http\Controllers\Tenant\Panel\Sales\ReturnAnalyticsController::class, 'index'])->name('analytics');
                Route::get('/analytics/data', [\App\Http\Controllers\Tenant\Panel\Sales\ReturnAnalyticsController::class, 'data'])->name('analytics.data');

                Route::get('/{id}', [\App\Http\Controllers\Tenant\Panel\Sales\ReturnController::class, 'show'])->whereNumber('id')->name('show');

                Route::post('/{id}/approve', [\App\Http\Controllers\Tenant\Panel\Sales\ReturnController::class, 'approve'])->whereNumber('id')->name('approve');

                Route::post('/{id}/reject', [\App\Http\Controllers\Tenant\Panel\Sales\ReturnController::class, 'reject'])->whereNumber('id')->name('reject');
                Route::post('/{id}/reject/validate', [\App\Http\Controllers\Tenant\Panel\Sales\ReturnController::class, 'validateReject'])->whereNumber('id')->name('reject.validate');

                Route::post('/{id}/request-info', [\App\Http\Controllers\Tenant\Panel\Sales\ReturnController::class, 'requestMoreInfo'])->whereNumber('id')->name('request-info');
                Route::post('/{id}/request-info/validate', [\App\Http\Controllers\Tenant\Panel\Sales\ReturnController::class, 'validateRequestInfo'])->whereNumber('id')->name('request-info.validate');

                Route::post('/{id}/received', [\App\Http\Controllers\Tenant\Panel\Sales\ReturnController::class, 'markItemReceived'])->whereNumber('id')->name('received');

                Route::post('/{id}/refunded', [\App\Http\Controllers\Tenant\Panel\Sales\ReturnController::class, 'markRefunded'])->whereNumber('id')->name('refunded');
                Route::post('/{id}/refunded/validate', [\App\Http\Controllers\Tenant\Panel\Sales\ReturnController::class, 'validateRefunded'])->whereNumber('id')->name('refunded.validate');

                Route::post('/{id}/notes', [\App\Http\Controllers\Tenant\Panel\Sales\ReturnController::class, 'addNote'])->whereNumber('id')->name('notes');
                Route::post('/{id}/notes/validate', [\App\Http\Controllers\Tenant\Panel\Sales\ReturnController::class, 'validateNote'])->whereNumber('id')->name('notes.validate');
            });

            Route::prefix('customers')->name('tenant.customers.')->middleware('tenant.permission:sales.customers.manage')->group(function () {
                Route::get('/', [\App\Http\Controllers\Tenant\Panel\Sales\CustomersController::class, 'index'])->name('index');
                Route::get('/data', [\App\Http\Controllers\Tenant\Panel\Sales\CustomersController::class, 'data'])->name('data');
                Route::get('/export', [\App\Http\Controllers\Tenant\Panel\Sales\CustomersController::class, 'export'])->name('export');
                Route::get('/create', [\App\Http\Controllers\Tenant\Panel\Sales\CustomerCreateController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Tenant\Panel\Sales\CustomerCreateController::class, 'store'])->name('store');
                Route::post('/validate', [\App\Http\Controllers\Tenant\Panel\Sales\CustomerCreateController::class, 'validateStore'])->name('validate')->middleware('throttle:tenant-validate');

                Route::get('/{customerId}', [\App\Http\Controllers\Tenant\Panel\Sales\CustomerDetailController::class, 'show'])->whereNumber('customerId')->name('show');
                Route::put('/{customerId}', [\App\Http\Controllers\Tenant\Panel\Sales\CustomerDetailController::class, 'updateProfile'])->whereNumber('customerId')->name('update');
                Route::post('/{customerId}/validate', [\App\Http\Controllers\Tenant\Panel\Sales\CustomerDetailController::class, 'validateProfile'])->whereNumber('customerId')->name('validate.update')->middleware('throttle:tenant-validate');
                Route::patch('/{customerId}/active', [\App\Http\Controllers\Tenant\Panel\Sales\CustomerDetailController::class, 'toggleActive'])->whereNumber('customerId')->name('toggle-active');
                Route::delete('/{customerId}', [\App\Http\Controllers\Tenant\Panel\Sales\CustomersController::class, 'destroy'])->whereNumber('customerId')->name('destroy');
                Route::get('/{customerId}/payments/data', [\App\Http\Controllers\Tenant\Panel\Sales\CustomerDetailController::class, 'paymentsData'])->whereNumber('customerId')->name('payments.data');

                Route::post('/{customerId}/addresses', [\App\Http\Controllers\Tenant\Panel\Sales\CustomerDetailController::class, 'storeAddress'])->whereNumber('customerId')->name('addresses.store');
                Route::post('/{customerId}/addresses/validate', [\App\Http\Controllers\Tenant\Panel\Sales\CustomerDetailController::class, 'validateAddress'])->whereNumber('customerId')->name('addresses.validate')->middleware('throttle:tenant-validate');
                Route::get('/{customerId}/addresses/{addressId}', [\App\Http\Controllers\Tenant\Panel\Sales\CustomerDetailController::class, 'showAddress'])->whereNumber('customerId')->whereNumber('addressId')->name('addresses.show');
                Route::put('/{customerId}/addresses/{addressId}', [\App\Http\Controllers\Tenant\Panel\Sales\CustomerDetailController::class, 'updateAddress'])->whereNumber('customerId')->whereNumber('addressId')->name('addresses.update');
                Route::delete('/{customerId}/addresses/{addressId}', [\App\Http\Controllers\Tenant\Panel\Sales\CustomerDetailController::class, 'destroyAddress'])->whereNumber('customerId')->whereNumber('addressId')->name('addresses.destroy');
            });

            Route::get('/cities-by-country/{countryId?}', [\App\Http\Controllers\Tenant\Panel\Sales\CustomerDetailController::class, 'citiesByCountry'])
                ->middleware('tenant.permission:sales.customers.manage')
                ->name('tenant.cities.by-country');

            Route::prefix('analytics')->name('tenant.analytics.')->middleware('tenant.permission:analytics.view')->group(function () {
                Route::get('/orders', [\App\Http\Controllers\Tenant\Panel\Insights\OrderAnalyticsController::class, 'index'])->name('orders');
                Route::get('/orders/data/monthly', [\App\Http\Controllers\Tenant\Panel\Insights\OrderAnalyticsController::class, 'dataMonthly'])->name('orders.data.monthly');
                Route::get('/orders/data/status', [\App\Http\Controllers\Tenant\Panel\Insights\OrderAnalyticsController::class, 'dataStatus'])->name('orders.data.status');

                Route::get('/customer-lifetime-value', [\App\Http\Controllers\Tenant\Panel\Insights\CustomerLifetimeValueController::class, 'index'])->name('clv');
                Route::get('/customer-lifetime-value/data', [\App\Http\Controllers\Tenant\Panel\Insights\CustomerLifetimeValueController::class, 'data'])->name('clv.data');

                Route::get('/shipping', [\App\Http\Controllers\Tenant\Panel\Insights\ShippingAnalyticsController::class, 'index'])->name('shipping');
                Route::get('/shipping/data/monthly', [\App\Http\Controllers\Tenant\Panel\Insights\ShippingAnalyticsController::class, 'dataMonthly'])->name('shipping.data.monthly');

                Route::get('/profitability', [\App\Http\Controllers\Tenant\Panel\Insights\ProductProfitabilityController::class, 'index'])->name('profitability');
                Route::get('/profitability/data', [\App\Http\Controllers\Tenant\Panel\Insights\ProductProfitabilityController::class, 'data'])->name('profitability.data');
            });

            Route::prefix('finance')->name('tenant.finance.')->group(function () {
                Route::get('/wallet', [WalletController::class, 'index'])
                    ->middleware('tenant.permission:finance.wallet.view')
                    ->name('wallet');
                Route::get('/wallet/subscriptions/data', [WalletController::class, 'subscriptionsData'])
                    ->middleware('tenant.permission:finance.wallet.view')
                    ->name('wallet.subscriptions.data');
                Route::get('/wallet/transactions/data', [WalletController::class, 'transactionsData'])
                    ->middleware('tenant.permission:finance.wallet.view')
                    ->name('wallet.transactions.data');
                Route::post('/wallet/subscription', [WalletController::class, 'subscribe'])
                    ->middleware('tenant.permission:finance.wallet.view')
                    ->name('wallet.subscribe');
                Route::post('/wallet/subscription/validate', [WalletController::class, 'validateSubscribe'])
                    ->middleware('tenant.permission:finance.wallet.view')
                    ->name('wallet.subscribe.validate');
                Route::get('/billing', [BillingController::class, 'index'])
                    ->middleware('tenant.permission:finance.billing.view')
                    ->name('billing');
                Route::get('/billing/data', [BillingController::class, 'data'])
                    ->middleware('tenant.permission:finance.billing.view')
                    ->name('billing.data');
                Route::get('/billing/{orderId}', [BillingController::class, 'show'])
                    ->whereNumber('orderId')
                    ->middleware('tenant.permission:finance.billing.view')
                    ->name('billing.detail');
                Route::get('/vendor-purchases', [VendorPurchaseController::class, 'index'])
                    ->middleware('tenant.permission:finance.vendor-purchases.view')
                    ->name('vendor-purchases');
                Route::get('/vendor-purchases/data', [VendorPurchaseController::class, 'data'])
                    ->middleware('tenant.permission:finance.vendor-purchases.view')
                    ->name('vendor-purchases.data');
                Route::get('/vendor-purchases/export', [VendorPurchaseController::class, 'export'])
                    ->middleware('tenant.permission:finance.vendor-purchases.view')
                    ->name('vendor-purchases.export');
                Route::get('/vendor-purchases/{orderId}/settle', [VendorSettleOrderController::class, 'show'])
                    ->whereNumber('orderId')
                    ->middleware('tenant.permission:finance.vendor-purchases.view')
                    ->name('vendor-purchase-settle');
                Route::get('/vendor-purchases/{orderId}/settle/breakdown', [VendorSettleOrderController::class, 'breakdown'])
                    ->whereNumber('orderId')
                    ->middleware('tenant.permission:finance.vendor-purchases.view')
                    ->name('vendor-purchase-settle.breakdown');
                Route::post('/vendor-purchases/{orderId}/settle', [VendorSettleOrderController::class, 'settle'])
                    ->whereNumber('orderId')
                    ->middleware('tenant.permission:finance.vendor-purchases.view')
                    ->name('vendor-purchase-settle.pay');
                Route::post('/vendor-purchases/{orderId}/settle/validate', [VendorSettleOrderController::class, 'validateSettle'])
                    ->whereNumber('orderId')
                    ->middleware('tenant.permission:finance.vendor-purchases.view')
                    ->name('vendor-purchase-settle.pay.validate');
                Route::get('/settlement-payments', [SettlementPaymentsController::class, 'index'])
                    ->middleware('tenant.permission:finance.vendor-purchases.view')
                    ->name('settlement-payments');
                Route::get('/settlement-payments/data', [SettlementPaymentsController::class, 'data'])
                    ->middleware('tenant.permission:finance.vendor-purchases.view')
                    ->name('settlement-payments.data');
                Route::get('/payouts-received', [PayoutsController::class, 'index'])
                    ->middleware('tenant.permission:finance.wallet.view')
                    ->name('payouts');
                Route::get('/payouts-received/data', [PayoutsController::class, 'data'])
                    ->middleware('tenant.permission:finance.wallet.view')
                    ->name('payouts.data');
                Route::get('/buy-languages', [BuyLanguageController::class, 'index'])
                    ->middleware('tenant.permission:settings.languages.purchase')
                    ->name('buy-languages');
                Route::get('/buy-languages/data', [BuyLanguageController::class, 'data'])
                    ->middleware('tenant.permission:settings.languages.purchase')
                    ->name('buy-languages.data');
                Route::post('/buy-languages', [BuyLanguageController::class, 'purchase'])
                    ->middleware('tenant.permission:settings.languages.purchase')
                    ->name('buy-languages.purchase');
                Route::post('/buy-languages/validate', [BuyLanguageController::class, 'validatePurchase'])
                    ->middleware('tenant.permission:settings.languages.purchase')
                    ->name('buy-languages.purchase.validate');
            });

            // Language purchase payment flow (no extra permission middleware – controller guards the logic)
            // Subscription renewal / upgrade payment flow
            Route::prefix('subscription-payment')->name('tenant.subscription-payment.')->group(function () {
                Route::get('{gateway}/{packageId}/{type}', [SubscriptionPaymentController::class, 'charge'])->name('charge');
                Route::match(['get', 'post'], '{gateway}/success', [SubscriptionPaymentController::class, 'success'])->name('success');
                Route::get('{gateway}/cancel', [SubscriptionPaymentController::class, 'cancel'])->name('cancel');
            });

            Route::prefix('language-purchase')->name('tenant.language-purchase.')->group(function () {
                Route::get('{gateway}/{languageId}', [LanguagePaymentController::class, 'charge'])->name('charge');
                Route::match(['get', 'post'], '{gateway}/success', [LanguagePaymentController::class, 'success'])->name('success');
                Route::get('{gateway}/cancel', [LanguagePaymentController::class, 'cancel'])->name('cancel');
            });

            Route::prefix('ai-translation-purchase')->name('tenant.ai-translation-purchase.')->group(function () {
                Route::get('{gateway}/{languageId}', [AiTranslationPaymentController::class, 'charge'])->name('charge');
                Route::match(['get', 'post'], '{gateway}/success', [AiTranslationPaymentController::class, 'success'])->name('success');
                Route::get('{gateway}/cancel', [AiTranslationPaymentController::class, 'cancel'])->name('cancel');
            });

            Route::prefix('manufacturing-payment')->name('tenant.manufacturing-payment.')->group(function () {
                Route::get('{gateway}/{paymentRequestId}', [ManufacturingPaymentController::class, 'charge'])->name('charge');
                Route::match(['get', 'post'], '{gateway}/success', [ManufacturingPaymentController::class, 'success'])->name('success');
                Route::get('{gateway}/cancel', [ManufacturingPaymentController::class, 'cancel'])->name('cancel');
            });

            Route::prefix('brand-request-payment')->name('tenant.brand-request-payment.')->group(function () {
                Route::get('{gateway}/{paymentRequestId}', [\App\Http\Controllers\Tenant\BrandRequestPaymentController::class, 'charge'])->name('charge');
                Route::match(['get', 'post'], '{gateway}/success', [\App\Http\Controllers\Tenant\BrandRequestPaymentController::class, 'success'])->name('success');
                Route::get('{gateway}/cancel', [\App\Http\Controllers\Tenant\BrandRequestPaymentController::class, 'cancel'])->name('cancel');
            });

            // Vendor-to-central settlement payment flow
            Route::prefix('vendor-settlement')->name('tenant.vendor-settlement.')->group(function () {
                Route::get('{gateway}/{orderId}', [VendorSettlementPaymentController::class, 'charge'])->name('charge');
                Route::match(['get', 'post'], '{gateway}/success', [VendorSettlementPaymentController::class, 'success'])->name('success');
                Route::get('{gateway}/cancel', [VendorSettlementPaymentController::class, 'cancel'])->name('cancel');
            });

            Route::prefix('store')->name('tenant.store.')->group(function () {
                Route::middleware('tenant.permission:store.themes.manage')->group(function () {
                    Route::get('/themes', [ThemesController::class, 'index'])->name('themes');
                    Route::post('/themes/{theme}/activate', [ThemesController::class, 'activate'])
                        ->whereNumber('theme')
                        ->name('themes.activate');
                    Route::post('/themes/{theme}/variants/{variant}/activate', [ThemesController::class, 'activateVariant'])
                        ->whereNumber('theme')
                        ->whereNumber('variant')
                        ->name('themes.variants.activate');
                    Route::post('/themes/{theme}/deactivate', [ThemesController::class, 'deactivate'])
                        ->whereNumber('theme')
                        ->name('themes.deactivate');
                    Route::get('/themes/{theme}/countries', [ThemesController::class, 'countries'])
                        ->whereNumber('theme')
                        ->name('themes.countries');
                    Route::put('/themes/{theme}/countries', [ThemesController::class, 'updateCountries'])
                        ->whereNumber('theme')
                        ->name('themes.countries.update');
                });

                Route::middleware('tenant.permission:store.pages.manage')->group(function () {
                    Route::get('/pages', [PagesController::class, 'index'])->name('pages');
                    Route::get('/pages/data', [PagesController::class, 'data'])->name('pages.data');
                    Route::get('/pages/create', [PageFormController::class, 'create'])->name('pages.create');
                    Route::post('/pages', [PageFormController::class, 'store'])->name('pages.store');
                    Route::post('/pages/validate', [PageFormController::class, 'validateStore'])->name('pages.validate');
                    Route::get('/pages/{page}/edit', [PageFormController::class, 'edit'])->whereNumber('page')->name('pages.edit');
                    Route::put('/pages/{page}', [PageFormController::class, 'update'])->whereNumber('page')->name('pages.update');
                    Route::post('/pages/{page}/validate', [PageFormController::class, 'validateUpdate'])->whereNumber('page')->name('pages.validate.update');
                    Route::delete('/pages/{page}', [PagesController::class, 'destroy'])->whereNumber('page')->name('pages.destroy');
                });
                Route::middleware('tenant.permission:store.coupons.manage')->group(function () {
                    Route::get('/coupons', [CouponController::class, 'index'])->name('coupons.index');
                    Route::get('/coupons/data/{countryId?}', [CouponController::class, 'data'])->whereNumber('countryId')->name('coupons.data');
                    Route::get('/coupons/item/{coupon}', [CouponController::class, 'show'])->whereNumber('coupon')->name('coupons.show');
                    Route::post('/coupons', [CouponController::class, 'store'])->name('coupons.store');
                    Route::post('/coupons/validate', [CouponController::class, 'validateStore'])->name('coupons.validate');
                    Route::put('/coupons/item/{coupon}', [CouponController::class, 'update'])->whereNumber('coupon')->name('coupons.update');
                    Route::post('/coupons/item/{coupon}/validate', [CouponController::class, 'validateUpdate'])->whereNumber('coupon')->name('coupons.validate.update');
                    Route::delete('/coupons/item/{coupon}', [CouponController::class, 'destroy'])->whereNumber('coupon')->name('coupons.destroy');
                    Route::get('/coupons/list/{countryId?}', [CouponController::class, 'list'])->whereNumber('countryId')->name('coupons.list');
                });

                Route::middleware('tenant.permission:store.flash-sales.manage')->group(function () {
                    Route::get('/flash-sales', [FlashSaleController::class, 'index'])->name('flash-sales.index');
                    Route::get('/flash-sales/data/{countryId?}', [FlashSaleController::class, 'data'])->whereNumber('countryId')->name('flash-sales.data');
                    Route::get('/flash-sales/products/search', [FlashSaleController::class, 'searchProducts'])->name('flash-sales.products.search');
                    Route::get('/flash-sales/item/{flashSale}', [FlashSaleController::class, 'show'])->whereNumber('flashSale')->name('flash-sales.show');
                    Route::post('/flash-sales', [FlashSaleController::class, 'store'])->name('flash-sales.store');
                    Route::post('/flash-sales/validate', [FlashSaleController::class, 'validateStore'])->name('flash-sales.validate');
                    Route::put('/flash-sales/item/{flashSale}', [FlashSaleController::class, 'update'])->whereNumber('flashSale')->name('flash-sales.update');
                    Route::post('/flash-sales/item/{flashSale}/validate', [FlashSaleController::class, 'validateUpdate'])->whereNumber('flashSale')->name('flash-sales.validate.update');
                    Route::delete('/flash-sales/item/{flashSale}', [FlashSaleController::class, 'destroy'])->whereNumber('flashSale')->name('flash-sales.destroy');
                    Route::get('/flash-sales/list/{countryId?}', [FlashSaleController::class, 'list'])->whereNumber('countryId')->name('flash-sales');
                });

                Route::middleware('tenant.permission:store.appearance.manage')->group(function () {
                    Route::get('/appearance', [AppearanceController::class, 'index'])->name('appearance');

                    Route::put('/appearance/general', [AppearanceController::class, 'saveGeneral'])->name('appearance.general');
                    Route::post('/appearance/general/validate', [AppearanceController::class, 'validateGeneral'])->name('appearance.general.validate');

                    Route::put('/appearance/colors/{theme}/{variant}', [AppearanceController::class, 'saveColors'])->whereNumber(['theme', 'variant'])->name('appearance.colors');
                    Route::post('/appearance/colors/{theme}/{variant}/validate', [AppearanceController::class, 'validateColors'])->whereNumber(['theme', 'variant'])->name('appearance.colors.validate');
                    Route::post('/appearance/colors/{theme}/{variant}/reset', [AppearanceController::class, 'resetColors'])->whereNumber(['theme', 'variant'])->name('appearance.colors.reset');

                    Route::get('/appearance/social', [SocialLinkController::class, 'index'])->name('appearance.social.index');
                    Route::get('/appearance/social/{link}', [SocialLinkController::class, 'show'])->whereNumber('link')->name('appearance.social.show');
                    Route::post('/appearance/social', [SocialLinkController::class, 'store'])->name('appearance.social.store');
                    Route::post('/appearance/social/validate', [SocialLinkController::class, 'validateStore'])->name('appearance.social.validate');
                    Route::put('/appearance/social/{link}', [SocialLinkController::class, 'update'])->whereNumber('link')->name('appearance.social.update');
                    Route::post('/appearance/social/{link}/validate', [SocialLinkController::class, 'validateUpdate'])->whereNumber('link')->name('appearance.social.validate.update');
                    Route::delete('/appearance/social/{link}', [SocialLinkController::class, 'destroy'])->whereNumber('link')->name('appearance.social.destroy');

                    Route::put('/appearance/promo-banner', [AppearanceController::class, 'savePromoBanner'])->name('appearance.promo-banner');
                    Route::post('/appearance/promo-banner/validate', [AppearanceController::class, 'validatePromoBanner'])->name('appearance.promo-banner.validate');

                    Route::put('/appearance/footer', [AppearanceController::class, 'saveFooter'])->name('appearance.footer');
                    Route::post('/appearance/footer/validate', [AppearanceController::class, 'validateFooter'])->name('appearance.footer.validate');
                });

                Route::middleware('tenant.permission:store.appearance.manage')->group(function () {
                    Route::get('/banners', [BannerController::class, 'index'])->name('banners.index');
                    Route::get('/banners/item/{banner}', [BannerController::class, 'show'])->whereNumber('banner')->name('banners.show');
                    Route::post('/banners', [BannerController::class, 'store'])->name('banners.store');
                    Route::post('/banners/validate', [BannerController::class, 'validateStore'])->name('banners.validate');
                    Route::put('/banners/item/{banner}', [BannerController::class, 'update'])->whereNumber('banner')->name('banners.update');
                    Route::post('/banners/item/{banner}/validate', [BannerController::class, 'validateUpdate'])->whereNumber('banner')->name('banners.validate.update');
                    Route::delete('/banners/item/{banner}', [BannerController::class, 'destroy'])->whereNumber('banner')->name('banners.destroy');
                    Route::post('/banners/{countryId}/order', [BannerController::class, 'updateOrder'])->whereNumber('countryId')->name('banners.order');
                    Route::get('/banners/list/{countryId?}', [BannerController::class, 'list'])->whereNumber('countryId')->name('banners');
                });
                Route::middleware('tenant.permission:store.blade-theme.manage')->group(function () {
                    Route::get('/blade-theme', [\App\Http\Controllers\Tenant\Panel\Store\BladeThemeController::class, 'index'])->name('blade-theme');
                    Route::get('/blade-theme/starter-kit', [\App\Http\Controllers\Tenant\BladeThemeStarterKitController::class, 'download'])->name('blade-theme.starter-kit');
                    Route::post('/blade-theme', [\App\Http\Controllers\Tenant\Panel\Store\BladeThemeController::class, 'upload'])->name('blade-theme.upload');
                    Route::post('/blade-theme/validate', [\App\Http\Controllers\Tenant\Panel\Store\BladeThemeController::class, 'validateUpload'])->name('blade-theme.upload.validate');
                    Route::post('/blade-theme/deactivate', [\App\Http\Controllers\Tenant\Panel\Store\BladeThemeController::class, 'deactivate'])->name('blade-theme.deactivate');
                    Route::delete('/blade-theme/{upload}', [\App\Http\Controllers\Tenant\Panel\Store\BladeThemeController::class, 'destroy'])->whereNumber('upload')->name('blade-theme.destroy');
                });

                Route::middleware('tenant.permission:store.page-builder.manage')->group(function () {
                    Route::get('/page-builder', [\App\Http\Controllers\Tenant\Panel\Store\PageBuilderController::class, 'index'])->name('page-builder');
                    Route::post('/page-builder/order', [\App\Http\Controllers\Tenant\Panel\Store\PageBuilderController::class, 'updateOrder'])->name('page-builder.order');
                    Route::patch('/page-builder/sections/{section}/visibility', [\App\Http\Controllers\Tenant\Panel\Store\PageBuilderController::class, 'toggleVisibility'])->name('page-builder.sections.visibility');
                });

                Route::middleware('tenant.permission:store.home-variants.manage')->group(function () {
                    Route::get('/home-variants', [\App\Http\Controllers\Tenant\Panel\Store\HomeVariantsController::class, 'index'])->name('home-variants');
                    Route::post('/home-variants', [\App\Http\Controllers\Tenant\Panel\Store\HomeVariantsController::class, 'selectVariant'])->name('home-variants.select');
                });
            });

            Route::prefix('help')->name('tenant.help.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Tenant\Panel\Support\HelpController::class, 'index'])->name('index');
                Route::get('/articles/{slug}', [\App\Http\Controllers\Tenant\Panel\Support\HelpController::class, 'article'])->name('article');
            });

            Route::prefix('settings')->name('tenant.settings.')->group(function () {
                Route::middleware('tenant.permission:settings.tracking.manage')->group(function () {
                    Route::get('/tracking', [TrackingSettingsController::class, 'index'])->name('tracking');
                    Route::put('/tracking', [TrackingSettingsController::class, 'update'])->name('tracking.update');
                    Route::post('/tracking/validate', [TrackingSettingsController::class, 'validateUpdate'])->name('tracking.validate');
                });

                Route::middleware('tenant.permission:store.subscribers.manage')->group(function () {
                    Route::get('/subscribers', [SubscribersController::class, 'index'])->name('subscribers');
                    Route::get('/subscribers/data', [SubscribersController::class, 'data'])->name('subscribers.data');
                    Route::get('/subscribers/export', [SubscribersController::class, 'export'])->name('subscribers.export');
                    Route::delete('/subscribers/{subscriber}', [SubscribersController::class, 'destroy'])->name('subscribers.destroy');
                });

                Route::middleware('tenant.permission:settings.regional.manage')->group(function () {
                    Route::get('/currencies', [CurrenciesController::class, 'index'])->name('currencies');
                    Route::get('/currencies/data', [CurrenciesController::class, 'data'])->name('currencies.data');
                    Route::patch('/currencies/{currency}/active', [CurrenciesController::class, 'toggleActive'])->name('currencies.toggle-active');
                    Route::post('/currencies/{currency}/default', [CurrenciesController::class, 'makeDefault'])->name('currencies.default');

                    Route::get('/languages', [LanguagesController::class, 'index'])->name('languages');
                    Route::get('/languages/data', [LanguagesController::class, 'data'])->name('languages.data');
                    Route::patch('/languages/{language}/active', [LanguagesController::class, 'toggleActive'])->name('languages.toggle-active');
                    Route::post('/languages/{language}/default', [LanguagesController::class, 'makeDefault'])->name('languages.default');

                    Route::get('/languages-manage', [LanguagesManageController::class, 'index'])->name('languages-manage');
                    Route::get('/languages-manage/data', [LanguagesManageController::class, 'data'])->name('languages-manage.data');
                    Route::get('/languages-manage/available', [LanguagesManageController::class, 'available'])->name('languages-manage.available.data');
                    Route::post('/languages-manage/purchase', [LanguagesManageController::class, 'purchase'])->name('languages-manage.purchase');
                    Route::post('/languages-manage/purchase/validate', [LanguagesManageController::class, 'validatePurchase'])->name('languages-manage.purchase.validate');
                    Route::patch('/languages-manage/{language}/active', [LanguagesManageController::class, 'toggleActive'])->name('languages-manage.toggle-active');
                    Route::post('/languages-manage/{language}/default', [LanguagesManageController::class, 'makeDefault'])->name('languages-manage.default');
                });

                Route::middleware('tenant.permission:settings.domains.manage')->group(function () {
                    Route::get('/domains', [DomainsController::class, 'index'])->name('domains');
                    Route::post('/domains', [DomainsController::class, 'store'])->name('domains.store');
                    Route::post('/domains/validate', [DomainsController::class, 'validateStore'])->name('domains.validate');
                    Route::get('/domains/{domainRequest}', [DomainsController::class, 'show'])->name('domains.show');
                    Route::put('/domains/{domainRequest}', [DomainsController::class, 'update'])->name('domains.update');
                    Route::post('/domains/{domainRequest}/validate', [DomainsController::class, 'validateUpdate'])->name('domains.validate.update');
                    Route::post('/domains/{domainRequest}/check-dns', [DomainsController::class, 'checkDns'])->name('domains.check-dns');
                    Route::delete('/domains/{domainRequest}', [DomainsController::class, 'destroy'])->name('domains.destroy');
                });

                Route::middleware('tenant.permission:settings.translations.manage')->group(function () {
                    Route::get('/ai-translation', [AiTranslationController::class, 'index'])->name('ai-translation');
                    Route::get('/ai-translation/history/data', [AiTranslationController::class, 'historyData'])->name('ai-translation.history.data');
                    Route::get('/ai-translation/status', [AiTranslationController::class, 'status'])->name('ai-translation.status');
                    Route::post('/ai-translation/purchase/validate', [AiTranslationController::class, 'validatePurchase'])->name('ai-translation.purchase.validate');
                    Route::post('/ai-translation/{language}/run', [AiTranslationController::class, 'run'])->whereNumber('language')->name('ai-translation.run');
                    Route::post('/ai-translation/{language}/purchase', [AiTranslationController::class, 'purchase'])->whereNumber('language')->name('ai-translation.purchase');

                    Route::get('/translations', [TranslationsController::class, 'index'])->name('translations');
                    Route::get('/translations/data', [TranslationsController::class, 'data'])->name('translations.data');
                    Route::post('/translations/{language}/keys', [TranslationsController::class, 'saveKey'])->name('translations.keys.update');
                    Route::post('/translations/{language}/keys/ai', [TranslationsController::class, 'translateKeyWithAi'])->name('translations.keys.ai');
                    Route::post('/translations/{language}/keys/ai-batch', [TranslationsController::class, 'translateSelectedWithAi'])->name('translations.keys.ai-bulk');
                    Route::post('/translations/{language}/translate-store', [TranslationsController::class, 'translateStore'])->name('translations.store-ai');
                    Route::get('/translations/{language}/status', [TranslationsController::class, 'status'])->name('translations.status');
                });

                Route::middleware('tenant.permission:settings.admins.manage')->group(function () {
                    Route::get('/admins', [AdminsController::class, 'index'])->name('admins');
                    Route::get('/admins/data', [AdminsController::class, 'data'])->name('admins.data');
                    Route::post('/admins/validate', [AdminsController::class, 'validateStore'])->name('admins.validate');
                    Route::post('/admins', [AdminsController::class, 'store'])->name('admins.store');
                    Route::get('/admins/{admin}', [AdminsController::class, 'show'])->name('admins.show');
                    Route::put('/admins/{admin}', [AdminsController::class, 'update'])->name('admins.update');
                    Route::post('/admins/{admin}/validate', [AdminsController::class, 'validateUpdate'])->name('admins.validate.update');
                    Route::post('/admins/{admin}/activate', [AdminsController::class, 'activate'])->name('admins.activate');
                    Route::post('/admins/{admin}/deactivate', [AdminsController::class, 'deactivate'])->name('admins.deactivate');
                    Route::delete('/admins/{admin}', [AdminsController::class, 'destroy'])->name('admins.destroy');
                });

                Route::middleware('tenant.permission:settings.roles.manage')->group(function () {
                    Route::get('/roles-permissions', [RolesPermissionsController::class, 'index'])->name('roles-permissions');
                    Route::get('/roles-permissions/data', [RolesPermissionsController::class, 'data'])->name('roles-permissions.data');
                    Route::post('/roles-permissions/validate', [RolesPermissionsController::class, 'validateStore'])->name('roles-permissions.validate');
                    Route::post('/roles-permissions', [RolesPermissionsController::class, 'store'])->name('roles-permissions.store');
                    Route::get('/roles-permissions/{role}', [RolesPermissionsController::class, 'show'])->name('roles-permissions.show');
                    Route::put('/roles-permissions/{role}', [RolesPermissionsController::class, 'update'])->name('roles-permissions.update');
                    Route::post('/roles-permissions/{role}/validate', [RolesPermissionsController::class, 'validateUpdate'])->name('roles-permissions.validate.update');
                    Route::delete('/roles-permissions/{role}', [RolesPermissionsController::class, 'destroy'])->name('roles-permissions.destroy');
                });

                Route::middleware('tenant.permission:settings.payment-gateways.manage')->group(function () {
                    Route::get('/payment-gateways', [PaymentGatewaysController::class, 'index'])->name('payment-gateways');
                    Route::get('/payment-gateways/data', [PaymentGatewaysController::class, 'data'])->name('payment-gateways.data');
                    Route::get('/payment-gateways/{gateway}', [PaymentGatewaysController::class, 'show'])->name('payment-gateways.show');
                    Route::put('/payment-gateways/{gateway}', [PaymentGatewaysController::class, 'update'])->name('payment-gateways.update');
                    Route::post('/payment-gateways/{gateway}/validate', [PaymentGatewaysController::class, 'validateUpdate'])->name('payment-gateways.validate.update');
                    Route::post('/payment-gateways/{gateway}/primary', [PaymentGatewaysController::class, 'setPrimary'])->name('payment-gateways.primary');
                    Route::post('/payment-gateways/{gateway}/check-connection', [PaymentGatewaysController::class, 'checkConnection'])->name('payment-gateways.check');

                    Route::get('/payment-readiness', [PaymentReadinessController::class, 'index'])->name('payment-readiness');
                });

                Route::middleware('tenant.permission:settings.mail.manage')->group(function () {
                    Route::get('/email-templates', [EmailTemplateController::class, 'index'])->name('email-templates');
                    Route::get('/email-templates/data', [EmailTemplateController::class, 'data'])->name('email-templates.data');
                    Route::get('/email-templates/{emailTemplate}/edit', [EmailTemplateController::class, 'edit'])->name('email-templates.edit');
                    Route::put('/email-templates/{emailTemplate}', [EmailTemplateController::class, 'update'])->name('email-templates.update');
                    Route::post('/email-templates/{emailTemplate}/validate', [EmailTemplateController::class, 'validateUpdate'])->name('email-templates.validate');
                    Route::post('/email-templates/{emailTemplate}/resync', [EmailTemplateController::class, 'resync'])->name('email-templates.resync');

                    Route::get('/mail', [MailConfigurationsController::class, 'index'])->name('mail');
                    Route::put('/mail', [MailConfigurationsController::class, 'update'])->name('mail.update');
                    Route::post('/mail/validate', [MailConfigurationsController::class, 'validateUpdate'])->name('mail.validate');
                    Route::post('/mail/test', [MailConfigurationsController::class, 'sendTest'])->name('mail.test');
                    Route::post('/mail/test/validate', [MailConfigurationsController::class, 'validateSendTest'])->name('mail.test.validate');
                });

                Route::middleware('tenant.permission:settings.account.manage')->group(function () {
                    Route::get('/account', [AccountSettingsController::class, 'show'])->name('account');
                    Route::put('/account', [AccountSettingsController::class, 'update'])->name('account.update');
                    Route::post('/account/validate', [AccountSettingsController::class, 'validateUpdate'])->name('account.validate');

                    Route::get('/general', [GeneralSettingsController::class, 'index'])->name('general');
                    Route::put('/general', [GeneralSettingsController::class, 'update'])->name('general.update');
                    Route::post('/general/validate', [GeneralSettingsController::class, 'validateUpdate'])->name('general.validate');
                    Route::post('/general/country-request', [GeneralSettingsController::class, 'submitCountryRequest'])->name('general.country-request');
                    Route::post('/general/country-request/validate', [GeneralSettingsController::class, 'validateCountryRequest'])->name('general.country-request.validate');
                    Route::post('/general/category-request', [GeneralSettingsController::class, 'submitCategoryRequest'])->name('general.category-request');
                    Route::post('/general/category-request/validate', [GeneralSettingsController::class, 'validateCategoryRequest'])->name('general.category-request.validate');

                    Route::get('/compliance', [ComplianceCenterController::class, 'show'])->name('compliance');
                    Route::post('/compliance', [ComplianceCenterController::class, 'update'])->name('compliance.update');
                    Route::post('/compliance/validate', [ComplianceCenterController::class, 'validateUpdate'])->name('compliance.validate');
                    Route::get('/compliance/cities-by-country/{countryId}', [ComplianceCenterController::class, 'citiesByCountry'])->name('compliance.cities-by-country');
                });

                Route::middleware('tenant.permission:sales.returns.manage')->group(function () {
                    Route::get('/return-policy', [ReturnPolicyController::class, 'index'])->name('return-policy');
                    Route::put('/return-policy', [ReturnPolicyController::class, 'update'])->name('return-policy.update');
                    Route::post('/return-policy/validate', [ReturnPolicyController::class, 'validateUpdate'])->name('return-policy.validate');
                });
            });
        });
