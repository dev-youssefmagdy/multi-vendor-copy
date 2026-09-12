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
                Route::get('/', ProductsList::class)->middleware('tenant.setup:theme')->name('index');
                Route::get('/sort', TenantSortProducts::class)->name('sort');
                Route::get('/edit-requests', \App\Livewire\Tenant\Product\EditRequestsPage::class)->name('edit-requests');
                Route::get('/create', AddEditProduct::class)->name('create');
                Route::get('/{product}/edit', AddEditProduct::class)->name('edit');
                Route::post('/image-search', [\App\Http\Controllers\ImageSearchController::class, 'tenantPanel'])->name('image-search');
            });

            Route::prefix('own-products')->name('tenant.own-products.')->middleware('tenant.permission:catalog.products.manage')->group(function () {
                Route::get('/', OwnProductsList::class)->name('index');
                Route::get('/create', [OwnProductController::class, 'create'])->name('create');
                Route::post('/', [OwnProductController::class, 'store'])->name('store');
                Route::post('/validate', [OwnProductController::class, 'validateForm'])->name('validate');
                Route::get('/{product}/edit', [OwnProductController::class, 'edit'])->name('edit');
                Route::put('/{product}', [OwnProductController::class, 'update'])->name('update');
                Route::post('/{product}/validate', [OwnProductController::class, 'validateForm'])->name('validate.update');
            });

            Route::prefix('manufacturing')->name('tenant.manufacturing.')->group(function () {
                Route::get('/', TenantManufacturingRequestsList::class)->name('index');
                Route::get('/create', AddManufacturingRequest::class)->name('create');
                Route::get('/{id}', TenantManufacturingRequestDetail::class)->name('show');
            });

            Route::prefix('brand-requests')->name('tenant.brand-requests.')->group(function () {
                Route::get('/', \App\Livewire\Tenant\BrandRequest\BrandRequestsList::class)->name('index');
                Route::get('/create', \App\Livewire\Tenant\BrandRequest\CreateBrandRequest::class)->name('create');
                Route::get('/{id}', \App\Livewire\Tenant\BrandRequest\BrandRequestDetail::class)->name('show');
            });

            Route::get('/notifications', NotificationsPage::class)->name('tenant.notifications.index');

            Route::prefix('support')->name('tenant.support.')->group(function () {
                Route::get('/', TicketsList::class)->name('index');
                Route::get('/new', CreateTicket::class)->name('create');
                Route::get('/{ticketId}', TicketDetail::class)->name('show');
            });

            Route::prefix('product-requests')->name('tenant.product-requests.')->middleware('tenant.permission:catalog.products.manage')->group(function () {
                Route::get('/', \App\Livewire\Tenant\ProductRequest\RequestsList::class)->name('index');
                Route::get('/new', \App\Livewire\Tenant\ProductRequest\CreateRequest::class)->name('create');
                Route::get('/{requestId}', \App\Livewire\Tenant\ProductRequest\RequestDetail::class)->name('show');
            });

            Route::prefix('categories')->name('tenant.categories.')->middleware('tenant.permission:catalog.categories.manage')->group(function () {
                Route::get('/', CategoriesList::class)->name('index');
                Route::get('/sort', SortCategories::class)->name('sort');
                Route::get('/create', AddEditCategory::class)->name('create');
                Route::get('/{category}/edit', AddEditCategory::class)->name('edit');
                Route::get('/{category}/products', CategoryProducts::class)->name('products');
            });

            Route::prefix('badges')->name('tenant.badges.')->middleware('tenant.permission:catalog.badges.manage')->group(function () {
                Route::get('/', function () {
                    return redirect()->route('tenant.badges.show', ['badge' => 'new-in']);
                })->name('index');
                Route::get('/{badge}', [TenantBadgeProductsController::class, 'show'])->name('show');
                Route::get('/{badge}/search', [TenantBadgeProductsController::class, 'searchProducts'])->name('search');
                Route::get('/{badge}/sort', TenantSortBadgeProducts::class)->name('sort');
                Route::post('/{badge}/assign-category', [TenantBadgeProductsController::class, 'assignCategory'])->name('assign-category');
                Route::post('/{badge}/save', [TenantBadgeProductsController::class, 'save'])->name('save');
            });

            Route::get('/orders', OrdersList::class)
                ->middleware(['tenant.permission:sales.orders.view', 'tenant.setup:payment_gateway'])
                ->name('tenant.orders.index');

            Route::get('/orders/{orderId}', TenantOrderDetailPage::class)
                ->middleware('tenant.permission:sales.orders.view')
                ->name('tenant.orders.show');

            Route::get('/returns', TenantReturnsList::class)
                ->middleware('tenant.permission:sales.returns.manage')
                ->name('tenant.returns.index');

            Route::get('/returns/analytics', \App\Livewire\Tenant\Return\ReturnAnalyticsPage::class)
                ->middleware('tenant.permission:sales.returns.manage')
                ->name('tenant.returns.analytics');

            Route::get('/returns/{id}', TenantReturnDetailPage::class)
                ->middleware('tenant.permission:sales.returns.manage')
                ->name('tenant.returns.show');

            Route::get('/customers', CustomersList::class)
                ->middleware('tenant.permission:sales.customers.manage')
                ->name('tenant.customers.index');

            Route::get('/customers/create', [CustomerCreateController::class, 'create'])
                ->middleware('tenant.permission:sales.customers.manage')
                ->name('tenant.customers.create');

            Route::post('/customers', [CustomerCreateController::class, 'store'])
                ->middleware('tenant.permission:sales.customers.manage')
                ->name('tenant.customers.store');

            Route::get('/customers/{customerId}', [CustomerDetailController::class, 'show'])
                ->middleware('tenant.permission:sales.customers.manage')
                ->name('tenant.customers.show');

            Route::put('/customers/{customerId}', [CustomerDetailController::class, 'updateProfile'])
                ->middleware('tenant.permission:sales.customers.manage')
                ->name('tenant.customers.update');

            Route::post('/customers/{customerId}/addresses', [CustomerDetailController::class, 'storeAddress'])
                ->middleware('tenant.permission:sales.customers.manage')
                ->name('tenant.customers.addresses.store');

            Route::put('/customers/{customerId}/addresses/{addressId}', [CustomerDetailController::class, 'updateAddress'])
                ->middleware('tenant.permission:sales.customers.manage')
                ->name('tenant.customers.addresses.update');

            Route::delete('/customers/{customerId}/addresses/{addressId}', [CustomerDetailController::class, 'destroyAddress'])
                ->middleware('tenant.permission:sales.customers.manage')
                ->name('tenant.customers.addresses.destroy');

            Route::get('/cities-by-country/{countryId}', [CustomerDetailController::class, 'citiesByCountry'])
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
                Route::get('/wallet', WalletPage::class)
                    ->middleware('tenant.permission:finance.wallet.view')
                    ->name('wallet');
                Route::get('/billing', BillingPage::class)
                    ->middleware('tenant.permission:finance.billing.view')
                    ->name('billing');
                Route::get('/billing/{orderId}', BillingDetailPage::class)
                    ->middleware('tenant.permission:finance.billing.view')
                    ->name('billing.detail');
                Route::get('/vendor-purchases', VendorPurchasePage::class)
                    ->middleware('tenant.permission:finance.vendor-purchases.view')
                    ->name('vendor-purchases');
                Route::get('/vendor-purchases/{orderId}/settle', VendorSettleOrderPage::class)
                    ->middleware('tenant.permission:finance.vendor-purchases.view')
                    ->name('vendor-purchase-settle');
                Route::get('/settlement-payments', SettlementPaymentsPage::class)
                    ->middleware('tenant.permission:finance.vendor-purchases.view')
                    ->name('settlement-payments');
                Route::get('/payouts-received', PayoutsReceivedPage::class)
                    ->middleware('tenant.permission:finance.wallet.view')
                    ->name('payouts');
                Route::get('/buy-languages', BuyLanguagePage::class)
                    ->middleware('tenant.permission:settings.languages.purchase')
                    ->name('buy-languages');
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
                Route::get('/themes', ThemesPage::class)
                    ->middleware('tenant.permission:store.themes.manage')
                    ->name('themes');
                Route::get('/pages', PagesList::class)
                    ->middleware('tenant.permission:store.pages.manage')
                    ->name('pages');
                Route::get('/pages/create', AddEditPage::class)
                    ->middleware('tenant.permission:store.pages.manage')
                    ->name('pages.create');
                Route::get('/pages/{page}/edit', AddEditPage::class)
                    ->middleware('tenant.permission:store.pages.manage')
                    ->name('pages.edit');
                Route::get('/coupons', CouponsIndexPage::class)
                    ->middleware('tenant.permission:store.coupons.manage')
                    ->name('coupons.index');
                Route::get('/coupons/list/{countryId?}', CouponsPage::class)
                    ->middleware('tenant.permission:store.coupons.manage')
                    ->name('coupons.list');
                Route::get('/flash-sales', FlashSalesIndexPage::class)
                    ->middleware('tenant.permission:store.flash-sales.manage')
                    ->name('flash-sales.index');
                Route::get('/flash-sales/list/{countryId?}', FlashSalesPage::class)
                    ->middleware('tenant.permission:store.flash-sales.manage')
                    ->name('flash-sales');
                Route::get('/appearance', AppearancePage::class)
                    ->middleware('tenant.permission:store.appearance.manage')
                    ->name('appearance');
                Route::get('/banners', BannersIndexPage::class)
                    ->middleware('tenant.permission:store.appearance.manage')
                    ->name('banners.index');
                Route::get('/banners/list/{countryId?}', BannersPage::class)
                    ->middleware('tenant.permission:store.appearance.manage')
                    ->name('banners');
                Route::get('/blade-theme', BladeThemePage::class)
                    ->middleware('tenant.permission:store.blade-theme.manage')
                    ->name('blade-theme');
                Route::get('/blade-theme/starter-kit', [\App\Http\Controllers\Tenant\BladeThemeStarterKitController::class, 'download'])
                    ->middleware('tenant.permission:store.blade-theme.manage')
                    ->name('blade-theme.starter-kit');
                Route::get('/page-builder', PageBuilderPage::class)
                    ->middleware('tenant.permission:store.page-builder.manage')
                    ->name('page-builder');
                Route::get('/home-variants', HomeVariantsPage::class)
                    ->middleware('tenant.permission:store.home-variants.manage')
                    ->name('home-variants');
            });

            Route::get('/help', DocsPage::class)->name('tenant.help.index');

            Route::prefix('settings')->name('tenant.settings.')->group(function () {
                Route::get('/tracking', TrackingSettingsPage::class)
                    ->middleware('tenant.permission:settings.tracking.manage')
                    ->name('tracking');
                Route::get('/subscribers', SubscribersPage::class)
                    ->middleware('tenant.permission:store.subscribers.manage')
                    ->name('subscribers');
                Route::get('/currencies', CurrenciesPage::class)
                    ->middleware('tenant.permission:settings.regional.manage')
                    ->name('currencies');
                Route::get('/domains', DomainsList::class)
                    ->middleware('tenant.permission:settings.domains.manage')
                    ->name('domains');
                Route::get('/languages', LanguagesPage::class)
                    ->middleware('tenant.permission:settings.regional.manage')
                    ->name('languages');
                Route::get('/languages-manage', LanguagesManagePage::class)
                    ->middleware('tenant.permission:settings.regional.manage')
                    ->name('languages-manage');
                Route::get('/ai-translation', AiTranslationPage::class)
                    ->middleware('tenant.permission:settings.translations.manage')
                    ->name('ai-translation');
                Route::get('/translations', TranslationsPage::class)
                    ->middleware('tenant.permission:settings.translations.manage')
                    ->name('translations');
                Route::get('/admins', AdminsList::class)
                    ->middleware('tenant.permission:settings.admins.manage')
                    ->name('admins');
                Route::get('/roles-permissions', RolesPermissionsList::class)
                    ->middleware('tenant.permission:settings.roles.manage')
                    ->name('roles-permissions');
                Route::get('/payment-gateways', PaymentGatewaysPage::class)
                    ->middleware('tenant.permission:settings.payment-gateways.manage')
                    ->name('payment-gateways');
                Route::get('/payment-readiness', \App\Livewire\Tenant\Setting\PaymentReadinessPage::class)
                    ->middleware('tenant.permission:settings.payment-gateways.manage')
                    ->name('payment-readiness');
                Route::get('/email-templates', EmailTemplatesPage::class)
                    ->middleware('tenant.permission:settings.mail.manage')
                    ->name('email-templates');
                Route::get('/email-templates/{emailTemplate}/edit', AddEditEmailTemplate::class)
                    ->middleware('tenant.permission:settings.mail.manage')
                    ->name('email-templates.edit');
                Route::get('/mail', MailConfigurationsPage::class)
                    ->middleware('tenant.permission:settings.mail.manage')
                    ->name('mail');
                Route::get('/account', [AccountSettingsController::class, 'show'])
                    ->middleware('tenant.permission:settings.account.manage')
                    ->name('account');
                Route::put('/account', [AccountSettingsController::class, 'update'])
                    ->middleware('tenant.permission:settings.account.manage')
                    ->name('account.update');
                Route::get('/general', GeneralSettingsPage::class)
                    ->middleware('tenant.permission:settings.account.manage')
                    ->name('general');
                Route::get('/compliance', [ComplianceCenterController::class, 'show'])
                    ->middleware('tenant.permission:settings.account.manage')
                    ->name('compliance');
                Route::post('/compliance', [ComplianceCenterController::class, 'update'])
                    ->middleware('tenant.permission:settings.account.manage')
                    ->name('compliance.update');
                Route::get('/compliance/cities-by-country/{countryId}', [ComplianceCenterController::class, 'citiesByCountry'])
                    ->middleware('tenant.permission:settings.account.manage')
                    ->name('compliance.cities-by-country');
                Route::get('/return-policy', \App\Livewire\Tenant\Setting\ReturnPolicyPage::class)
                    ->middleware('tenant.permission:sales.returns.manage')
                    ->name('return-policy');
            });
        });
