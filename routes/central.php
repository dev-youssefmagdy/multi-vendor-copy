<?php

use App\Http\Middleware\AdminAuth;
use App\Livewire\Admin\Auth\LoginPage;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Admin\AdminsList;
use App\Livewire\Admin\Admin\RolesPermissionsList;
use App\Livewire\Admin\Blog\AddEditBlogCategory;
use App\Livewire\Admin\Blog\AddEditBlogPost;
use App\Livewire\Admin\Blog\BlogCategoriesList;
use App\Livewire\Admin\Blog\BlogPostsList;
use App\Livewire\Admin\Cache\MainCachePage;
use App\Livewire\Admin\Cache\TenantsCachePage;
use App\Livewire\Admin\Category\AddEditCategory;
use App\Livewire\Admin\Category\CategoriesList;
use App\Livewire\Admin\Category\CategoryProducts;
use App\Livewire\Admin\Category\SortCategories;
use App\Livewire\Admin\Domain\DnsRecordsList;
use App\Livewire\Admin\Domain\DomainRequestsList;
use App\Livewire\Admin\Faq\AddEditFaq;
use App\Livewire\Admin\Faq\FaqsList;
use App\Livewire\Admin\Order\InDeliveryOrdersList;
use App\Livewire\Admin\Order\OrderReturnDetailPage;
use App\Livewire\Admin\Order\OrderReturnsList;
use App\Livewire\Admin\Order\OrdersList;
use App\Livewire\Admin\Order\OrdersReportPage;
use App\Livewire\Admin\Page\AddEditStaticPage;
use App\Livewire\Admin\Page\StaticPagesList;
use App\Livewire\Admin\Customer\CentralCustomersList;
use App\Livewire\Admin\PaymentLog\PaymentLogsList;
use App\Livewire\Admin\Plan\PlansList;
use App\Livewire\Admin\Plan\RegisteredUsersList;
use App\Livewire\Admin\Plan\PendingRegistrationsList;
use App\Livewire\Admin\Plan\AddEditPackage;
use App\Http\Controllers\Admin\TenantEditorController;
use App\Http\Controllers\Admin\OrderReceiptController;
use App\Http\Controllers\Admin\TenantImpersonateController;
use App\Http\Controllers\Admin\BadgeProductsController;
use App\Http\Controllers\Admin\ProductController;
use App\Livewire\Admin\Product\AddEditProduct;
use App\Livewire\Admin\Product\ProductEditRequestsList;
use App\Livewire\Admin\Product\ProductsList;
use App\Livewire\Admin\Product\SortProducts;
use App\Livewire\Admin\Product\SortBadgeProducts;
use App\Http\Controllers\Admin\LanguageController;
use App\Livewire\Admin\Setting\AddEditCurrency;
use App\Livewire\Admin\Setting\AddEditEmailTemplate;
use App\Livewire\Admin\Setting\CurrenciesPage;
use App\Livewire\Admin\Setting\EmailConfigurationPage;
use App\Livewire\Admin\Setting\EmailTemplatesPage;
use App\Livewire\Admin\Setting\GeneralSettingsPage;
use App\Livewire\Admin\Setting\HomeVariantsPage;
use App\Livewire\Admin\Setting\CountriesPage;
use App\Livewire\Admin\Setting\LanguagesPage;
use App\Livewire\Admin\Setting\MaintenanceModePage;
use App\Livewire\Admin\Setting\AddEditPaymentGateway;
use App\Livewire\Admin\Setting\PaymentGatewayLimitsPage;
use App\Livewire\Admin\Setting\AiLogoLimitPage;
use App\Livewire\Admin\Setting\TranslationKeyAccessPage;
use App\Livewire\Admin\Setting\PaymentGatewaysPage;
use App\Livewire\Admin\Setting\AddEditTemplatePart;
use App\Livewire\Admin\Setting\TemplateControlPage;
use App\Livewire\Admin\Setting\BladeThemeQueuePage;
use App\Livewire\Admin\Setting\TemplatePartsPage;
use App\Livewire\Admin\Setting\TranslationsPage;
use App\Livewire\Admin\Setting\LanguagePurchasesPage;
use App\Livewire\Admin\Setting\DefaultBannersIndexPage;
use App\Livewire\Admin\Setting\DefaultBannersListPage;
use App\Livewire\Admin\Shipping\DeliveryChargesList;
use App\Livewire\Admin\Shipping\AddEditShippingZone;
use App\Livewire\Admin\Shipping\FixedShippingCostsList;
use App\Livewire\Admin\Shipping\AddEditFixedShippingCost;
use App\Livewire\Admin\Shipping\DeliveryPopupPage;
use App\Livewire\Admin\Shipping\ShippingDaysPage;
use App\Livewire\Admin\Shipping\ShippingSettingsPage;
use App\Livewire\Admin\Store\CouponsPage as AdminCouponsPage;
use App\Livewire\Admin\Store\FlashSalesIndexPage as AdminFlashSalesIndexPage;
use App\Livewire\Admin\Store\FlashSalesPage as AdminFlashSalesPage;
use App\Livewire\Admin\Store\TenantSyncPage as AdminTenantSyncPage;
use App\Livewire\Admin\Branch\BranchesList;
use App\Http\Controllers\Admin\AddEditBranchController;
use App\Livewire\Admin\Manufacturing\ManufacturingRequestsList;
use App\Livewire\Admin\Manufacturing\ManufacturingRequestDetail;
use App\Livewire\Admin\Variation\AddEditVariation;
use App\Livewire\Admin\Variation\VariationsList;
use App\Livewire\Admin\Compliance\ComplianceOverviewList;
use App\Livewire\Admin\Compliance\ComplianceDetailPage;
use App\Livewire\Admin\Wallet\InvoicesList;
use App\Livewire\Admin\Wallet\InvoiceDetailPage;
use App\Livewire\Admin\Wallet\WalletDetailPage;
use App\Livewire\Admin\PaymentLog\PaymentLogDetailPage;
use App\Livewire\Admin\Order\OrderDetailPage;
use App\Livewire\Admin\Order\InDeliveryOrderDetailPage;
use App\Livewire\Admin\Docs\DocsPage;
use App\Livewire\Admin\Finance\FinanceReportsPage;
use App\Livewire\Admin\Finance\PayoutsIndexPage;
use App\Livewire\Admin\Finance\TenantPayoutEditPage;
use App\Livewire\Admin\Finance\TenantPayoutPage;
use App\Livewire\Admin\Finance\VendorSettlementsPage;
use App\Livewire\Admin\Wallet\TransactionsList;
use App\Livewire\Admin\Wallet\WalletsList;
use App\Livewire\Admin\Newsletter\NewsletterSubscribersList;
use App\Livewire\Admin\Notifications\NotificationsPage;
use App\Livewire\Admin\Support\TicketsList as AdminTicketsList;
use App\Livewire\Admin\Support\TicketDetail as AdminTicketDetail;
use App\Livewire\Website\AboutPage;
use App\Livewire\Website\BlogDetailPage;
use App\Livewire\Website\BlogListPage;
use App\Livewire\Website\ContactPage;
use App\Livewire\Website\FaqsPage;
use App\Livewire\Website\HowItWorksPage;
use App\Livewire\Website\LandingPage;
use App\Livewire\Website\TemplatesPage;
use App\Livewire\Website\PricingPage;
use App\Livewire\Website\RegisterPage;
use App\Livewire\Website\CompleteRegistrationPage;
use App\Livewire\Website\StoreOnboardingWizard;
use App\Livewire\Website\TermsPage;
use App\Livewire\Website\PrivacyPage;
use App\Livewire\Website\StaticPageView;
use App\Livewire\Website\MaintenancePage;
use App\Livewire\Website\NotFoundPage;
use App\Livewire\Website\TenantLoginPage;
use App\Http\Controllers\Website\RegistrationPaymentController;
use App\Http\Controllers\Website\CentralSocialAuthController;
use App\Http\Middleware\TenantOwnerAuth;
use App\Livewire\TenantOwner\Auth\LoginPage as OwnerLoginPage;
use App\Livewire\TenantOwner\Auth\SelectTenantPage as OwnerSelectTenantPage;
use App\Livewire\TenantOwner\DomainsList as OwnerDomainsList;
use App\Livewire\TenantOwner\SubscriptionsList as OwnerSubscriptionsList;
use App\Http\Controllers\Website\SitemapController;
use App\Jobs\ApplyTenantProfitPercentageJob;
use App\Jobs\SyncFixedShippingCostsToTenantsJob;
use App\Jobs\SyncProductFixedShippingCosts;
use App\Models\Tenant;
use App\Models\Tenant\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// ── Locale switcher ───────────────────────────────────────────────────────────
Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'ar'])) {
        session(['locale' => $locale]);
    }
    return redirect()->back(fallback: route('website.home'));
})->name('locale.switch');

// ── Public website ────────────────────────────────────────────────────────────
Route::middleware('track.affiliate')->group(function () {
    Route::get('/', LandingPage::class)->name('website.home');
    Route::get('/about', AboutPage::class)->name('website.about');
    Route::get('/contact', ContactPage::class)->name('website.contact');
    Route::get('/register', RegisterPage::class)->name('website.register');
    Route::get('/register/complete', CompleteRegistrationPage::class)->name('website.register.complete');
    Route::get('/register/setup/{tenantId}', StoreOnboardingWizard::class)->name('website.store.onboarding');
    Route::prefix('register/payment')->name('website.register.payment.')->group(function () {
        Route::get('{gateway}/{registration}/charge', [RegistrationPaymentController::class, 'charge'])->name('charge');
        Route::match(['get', 'post'], '{gateway}/{registration}/success', [RegistrationPaymentController::class, 'success'])->name('success');
        Route::get('{gateway}/{registration}/cancel', [RegistrationPaymentController::class, 'cancel'])->name('cancel');
    });
    Route::get('/templates', TemplatesPage::class)->name('website.templates');
    Route::get('/pricing', PricingPage::class)->name('website.pricing');
    Route::get('/how-it-works', HowItWorksPage::class)->name('website.how-it-works');
    Route::get('/faqs', FaqsPage::class)->name('website.faqs');
    Route::get('/terms', TermsPage::class)->name('website.terms');
    Route::get('/privacy', PrivacyPage::class)->name('website.privacy');
    Route::get('/blog', BlogListPage::class)->name('website.blog');
    Route::get('/blog/{slug}', BlogDetailPage::class)->name('website.blog.show');
    Route::get('/pages/{slug}', StaticPageView::class)->name('website.page');
    Route::get('/maintenance', MaintenancePage::class)->name('website.maintenance');
});

// ── Affiliate Panel ────────────────────────────────────────────────────────
Route::prefix('affiliate')->name('affiliate.')->group(function () {

    Route::redirect('/', '/affiliate/dashboard');

    // Auth (guest only)
    Route::middleware('guest:affiliate')->group(function () {
        Route::get('/login',    \App\Livewire\Affiliate\Auth\LoginPage::class)->name('login');
        Route::get('/register', \App\Livewire\Affiliate\Auth\RegisterPage::class)->name('register');
    });

    // Authenticated affiliate panel
    Route::middleware('auth:affiliate')->group(function () {
        Route::get('/dashboard',     \App\Livewire\Affiliate\DashboardPage::class)->name('dashboard');
        Route::get('/links',         \App\Livewire\Affiliate\LinksPage::class)->name('links');
        Route::get('/conversions',   \App\Livewire\Affiliate\ConversionsPage::class)->name('conversions');
        Route::get('/payouts',       \App\Livewire\Affiliate\PayoutsPage::class)->name('payouts');
        Route::get('/profile',       \App\Livewire\Affiliate\ProfilePage::class)->name('profile');
        Route::post('/logout',       \App\Http\Controllers\Affiliate\AuthController::class . '@logout')->name('logout');
    });
});

Route::get('/sitemap.xml', SitemapController::class)->name('website.sitemap');
Route::get('/login', OwnerLoginPage::class)->middleware('guest:tenant_owner')->name('owner.login');

// ── Google / Apple sign-in (tenant-owner login & new-tenant registration) ─────
Route::get('/auth/google/{intent}', [CentralSocialAuthController::class, 'redirectToGoogle'])
    ->whereIn('intent', ['login', 'register', 'register-popup'])
    ->name('website.social.google');
Route::get('/auth/google/callback', [CentralSocialAuthController::class, 'handleGoogleCallback'])
    ->name('website.social.google.callback');
Route::get('/auth/apple/{intent}', [CentralSocialAuthController::class, 'redirectToApple'])
    ->whereIn('intent', ['login', 'register', 'register-popup'])
    ->name('website.social.apple');
Route::post('/auth/apple/callback', [CentralSocialAuthController::class, 'handleAppleCallback'])
    ->name('website.social.apple.callback');
Route::get('/my-account/select-tenant', OwnerSelectTenantPage::class)->middleware('auth:tenant_owner')->name('owner.select-tenant');

// ── Tenant Owner Panel ────────────────────────────────────────────────────────
Route::group([
    'prefix' => 'my-account',
    'as' => 'owner.',
    'middleware' => TenantOwnerAuth::class,
], function () {
    Route::redirect('/', '/my-account/domains')->name('dashboard');
    Route::get('/domains', OwnerDomainsList::class)->name('domains');
    Route::get('/subscriptions', OwnerSubscriptionsList::class)->name('subscriptions');
});

Route::post('/my-account/logout', function (Request $request) {
    Auth::guard('tenant_owner')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('owner.login');
})->name('owner.logout');

Route::middleware('guest:admin')->group(function () {
    Route::get('/admin/login', LoginPage::class)->name('admin.login');
});

// Default /broadcasting/auth endpoint, used by the admin panel's Echo client
// (see resources/js/bootstrap.js) to authorize private/presence channels
// under the 'admin' guard.
Route::post('/broadcasting/auth', function (Request $request) {
    return \Illuminate\Support\Facades\Broadcast::auth($request);
})->middleware(AdminAuth::class)->name('broadcasting.auth');

Route::post('/admin/logout', function (Request $request) {
    Auth::guard('admin')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('admin.login');
})->middleware(AdminAuth::class)->name('admin.logout');

Route::group([
    'prefix' => 'admin',
    'as' => 'admin.',
    'middleware' => AdminAuth::class,
], function () {
    Route::get('/', Dashboard::class)->middleware('admin.permission:dashboard.view')->name('dashboard');

    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', ProductsList::class)->middleware('admin.permission:catalog.products.view,catalog.products.manage')->name('index');
        Route::get('/sort', SortProducts::class)->middleware('admin.permission:catalog.products.manage')->name('sort');
        Route::get('/create', [ProductController::class, 'create'])->middleware('admin.permission:catalog.products.manage')->name('create');
        Route::post('/create', [ProductController::class, 'store'])->middleware('admin.permission:catalog.products.manage')->name('store');
        Route::post('/validate', [ProductController::class, 'validateForm'])->middleware('admin.permission:catalog.products.manage')->name('validate');
        Route::get('/edit-requests', ProductEditRequestsList::class)->middleware('admin.permission:catalog.product-edit-requests.manage')->name('edit-requests');
        Route::get('/{product}/edit', [ProductController::class, 'edit'])->middleware('admin.permission:catalog.products.manage')->name('edit');
        Route::put('/{product}/edit', [ProductController::class, 'update'])->middleware('admin.permission:catalog.products.manage')->name('update');
        Route::post('/{product}/validate', [ProductController::class, 'validateForm'])->middleware('admin.permission:catalog.products.manage')->name('validate.update');
        Route::post('/image-search', [\App\Http\Controllers\ImageSearchController::class, 'adminPanel'])
            ->middleware('admin.permission:catalog.products.view,catalog.products.manage')
            ->name('image-search');
    });

    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', CategoriesList::class)->middleware('admin.permission:catalog.categories.view,catalog.categories.manage')->name('index');
        Route::get('/create', AddEditCategory::class)->middleware('admin.permission:catalog.categories.manage')->name('create');
        Route::get('/sort', SortCategories::class)->middleware('admin.permission:catalog.categories.manage')->name('sort');
        Route::get('/{category}/edit', AddEditCategory::class)->middleware('admin.permission:catalog.categories.manage')->name('edit');
        Route::get('/{category}/products', CategoryProducts::class)->middleware('admin.permission:catalog.categories.manage')->name('products');
    });

    Route::prefix('variations')->name('variations.')->group(function () {
        Route::get('/', VariationsList::class)->middleware('admin.permission:catalog.variations.view,catalog.variations.manage')->name('index');
        Route::get('/create', AddEditVariation::class)->middleware('admin.permission:catalog.variations.manage')->name('create');
        Route::get('/{variation}/edit', AddEditVariation::class)->middleware('admin.permission:catalog.variations.manage')->name('edit');
    });

    Route::prefix('badges')->name('badges.')->middleware('admin.permission:catalog.badges.manage')->group(function () {
        Route::get('/{badge}', [BadgeProductsController::class, 'show'])->name('show');
        Route::get('/new-in', [BadgeProductsController::class, 'show'])->name('new-in');
        Route::get('/best-selling', [BadgeProductsController::class, 'show'])->name('best-selling');
        Route::get('/featured', [BadgeProductsController::class, 'show'])->name('featured');
        Route::get('/recommended', [BadgeProductsController::class, 'show'])->name('recommended');
        Route::get('/{badge}/search', [BadgeProductsController::class, 'searchProducts'])->name('search');
        Route::get('/{badge}/sort', SortBadgeProducts::class)->name('sort');
        Route::post('/{badge}/assign-category', [BadgeProductsController::class, 'assignCategory'])->name('assign-category');
        Route::post('/{badge}/save', [BadgeProductsController::class, 'save'])->name('save');
    });

    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', OrdersList::class)->middleware('admin.permission:sales.orders.view,sales.orders.manage')->name('index');
        Route::get('/report', OrdersReportPage::class)->middleware('admin.permission:sales.orders.report.view,sales.orders.manage')->name('report');
        Route::get('/returns', OrderReturnsList::class)->middleware('admin.permission:sales.orders.view,sales.orders.manage')->name('returns.index');
        Route::get('/returns/analytics', \App\Livewire\Admin\Order\ReturnAnalyticsPage::class)->middleware('admin.permission:sales.orders.view,sales.orders.manage')->name('returns.analytics');
        Route::get('/returns/{id}', OrderReturnDetailPage::class)->middleware('admin.permission:sales.orders.manage')->name('returns.show');
        Route::get('/{tenantId}/{orderNumber}', OrderDetailPage::class)->middleware('admin.permission:sales.orders.view,sales.orders.manage')->name('show');
        Route::get('/{tenantId}/{orderNumber}/receipt', [OrderReceiptController::class, 'show'])->middleware('admin.permission:sales.orders.view,sales.orders.manage')->name('receipt');
        Route::get('/{tenantId}/{orderNumber}/shipping-label', [OrderReceiptController::class, 'shippingLabel'])->middleware('admin.permission:sales.orders.view,sales.orders.manage')->name('shipping-label');
    });

    Route::prefix('shipping')->name('shipping.')->group(function () {
        Route::get('/in-delivery', InDeliveryOrdersList::class)->middleware('admin.permission:shipping.delivery.view,shipping.delivery.manage')->name('in-delivery');
        Route::get('/in-delivery/{tenantId}/{orderNumber}', InDeliveryOrderDetailPage::class)->middleware('admin.permission:shipping.delivery.view,shipping.delivery.manage')->name('in-delivery.show');
        Route::get('/delivery-charges', DeliveryChargesList::class)->middleware('admin.permission:shipping.delivery.view,shipping.delivery.manage')->name('delivery-charges');
        Route::get('/delivery-charges/create', AddEditShippingZone::class)->middleware('admin.permission:shipping.delivery.manage')->name('delivery-charges.create');
        Route::get('/delivery-charges/{shippingZone}/edit', AddEditShippingZone::class)->middleware('admin.permission:shipping.delivery.manage')->name('delivery-charges.edit');
        Route::get('/settings', ShippingSettingsPage::class)->middleware('admin.permission:shipping.settings.view,shipping.settings.manage')->name('settings');
        Route::get('/shipping-days', ShippingDaysPage::class)->middleware('admin.permission:shipping.settings.view,shipping.settings.manage')->name('shipping-days.index');
        Route::get('/delivery-popup', DeliveryPopupPage::class)->middleware('admin.permission:shipping.settings.view,shipping.settings.manage')->name('delivery-popup.index');
        Route::get('/fixed-costs', FixedShippingCostsList::class)->middleware('admin.permission:shipping.delivery.view,shipping.delivery.manage')->name('fixed-costs');
        Route::get('/fixed-costs/create', AddEditFixedShippingCost::class)->middleware('admin.permission:shipping.delivery.manage')->name('fixed-costs.create');
        Route::get('/fixed-costs/{fixedShippingCost}/edit', AddEditFixedShippingCost::class)->middleware('admin.permission:shipping.delivery.manage')->name('fixed-costs.edit');
    });
    Route::get('products-run', function () {
        SyncProductFixedShippingCosts::dispatch();
    });

    Route::prefix('store')->name('store.')->group(function () {
        Route::get('/flash-sales', AdminFlashSalesIndexPage::class)->middleware('admin.permission:store.flash-sales.manage')->name('flash-sales.index');
        Route::get('/flash-sales/list/{countryId?}', AdminFlashSalesPage::class)->middleware('admin.permission:store.flash-sales.manage')->name('flash-sales.list');
        Route::get('/coupons', AdminCouponsPage::class)->middleware('admin.permission:store.coupons.manage')->name('coupons.index');
        Route::get('/tenant-sync', AdminTenantSyncPage::class)->middleware('admin.permission:store.sync.manage')->name('tenant-sync.index');
    });

    Route::prefix('branches')->name('branches.')->group(function () {
        Route::get('/', BranchesList::class)->middleware('admin.permission:branches.view,branches.manage')->name('index');

        Route::get('/create', [AddEditBranchController::class, 'create'])->middleware('admin.permission:branches.manage')->name('create');
        Route::post('/create', [AddEditBranchController::class, 'store'])->middleware('admin.permission:branches.manage')->name('store');

        Route::get('/{branch}/edit', [AddEditBranchController::class, 'edit'])->middleware('admin.permission:branches.manage')->name('edit');
        Route::put('/{branch}/edit', [AddEditBranchController::class, 'update'])->middleware('admin.permission:branches.manage')->name('update');
    });

    Route::prefix('manufacturing')->name('manufacturing.')->group(function () {
        Route::get('/', ManufacturingRequestsList::class)->middleware('admin.permission:manufacturing.view,manufacturing.manage')->name('index');
        Route::get('/{id}', ManufacturingRequestDetail::class)->middleware('admin.permission:manufacturing.view')->name('show');
    });

    Route::prefix('payment-logs')->name('payment-logs.')->group(function () {
        Route::get('/', PaymentLogsList::class)->middleware('admin.permission:billing.payment-logs.view')->name('index');
        Route::get('/{tenantId}/{orderNumber}', PaymentLogDetailPage::class)->middleware('admin.permission:billing.payment-logs.view')->name('show');
    });

    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', CentralCustomersList::class)->middleware('admin.permission:sales.customers.view')->name('index');
    });

    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/registered-users', RegisteredUsersList::class)->middleware('admin.permission:plans.tenants.view,plans.tenants.manage')->name('users');
        Route::get('/registered-users/create', [TenantEditorController::class, 'create'])->middleware('admin.permission:plans.tenants.manage')->name('users.create');
        Route::post('/registered-users', [TenantEditorController::class, 'store'])->middleware('admin.permission:plans.tenants.manage')->name('users.store');
        Route::get('/registered-users/{tenant}/edit', [TenantEditorController::class, 'edit'])->middleware('admin.permission:plans.tenants.manage')->name('users.edit');
        Route::put('/registered-users/{tenant}', [TenantEditorController::class, 'update'])->middleware('admin.permission:plans.tenants.manage')->name('users.update');
        // Central admin → Tenant admin panel (token-based impersonation login)
        Route::get('/registered-users/{tenantId}/impersonate', [TenantImpersonateController::class, 'generate'])
            ->middleware('admin.permission:plans.tenants.manage')
            ->name('users.impersonate');
        Route::get('/pending-registrations', PendingRegistrationsList::class)->middleware('admin.permission:plans.pending-registrations.view,plans.pending-registrations.manage')->name('pending-registrations');
        Route::get('/', PlansList::class)->middleware('admin.permission:plans.packages.view,plans.packages.manage')->name('index');
        Route::get('/create', AddEditPackage::class)->middleware('admin.permission:plans.packages.manage')->name('create');
        Route::get('/{package}/edit', AddEditPackage::class)->middleware('admin.permission:plans.packages.manage')->name('edit');
    });

    Route::prefix('wallets')->name('wallets.')->group(function () {
        Route::get('/', WalletsList::class)->middleware('admin.permission:billing.wallets.view')->name('index');
        Route::get('/{tenantId}', WalletDetailPage::class)->middleware('admin.permission:billing.wallets.view')->name('show');
    });

    Route::prefix('compliance')->name('compliance.')->group(function () {
        Route::get('/', ComplianceOverviewList::class)->middleware('admin.permission:compliance.tenants.view')->name('index');
        Route::get('/{tenant}', ComplianceDetailPage::class)->middleware('admin.permission:compliance.tenants.view')->name('show');
    });

    Route::prefix('wallet-transactions')->name('wallet-transactions.')->group(function () {
        Route::get('/', TransactionsList::class)->middleware('admin.permission:billing.transactions.view')->name('index');
    });

    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', InvoicesList::class)->middleware('admin.permission:billing.invoices.view')->name('index');
        Route::get('/{invoiceId}', InvoiceDetailPage::class)->middleware('admin.permission:billing.invoices.view')->name('show');
    });

    Route::prefix('vendor-settlements')->name('vendor-settlements.')->group(function () {
        Route::get('/', VendorSettlementsPage::class)->middleware('admin.permission:billing.vendor-settlements.view')->name('index');
    });

    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/reports', FinanceReportsPage::class)->middleware('admin.permission:billing.reports.view')->name('reports');
        Route::get('/payouts', PayoutsIndexPage::class)->middleware('admin.permission:billing.payouts.manage')->name('payouts.index');
        Route::get('/payouts/create/{tenant}', TenantPayoutPage::class)->middleware('admin.permission:billing.payouts.manage')->name('payouts.create');
        Route::get('/payouts/{payout}/edit', TenantPayoutEditPage::class)->middleware('admin.permission:billing.payouts.manage')->name('payouts.edit');
    });

    Route::prefix('dev-docs')->name('dev-docs.')->group(function () {
        Route::get('/', DocsPage::class)->middleware('admin.permission:system.dev-docs.view')->name('index');
        Route::get('/{topic}', DocsPage::class)->middleware('admin.permission:system.dev-docs.view')->name('show');
    });

    Route::prefix('blog')->name('blog.')->group(function () {
        Route::get('/categories', BlogCategoriesList::class)->middleware('admin.permission:content.blog-categories.view,content.blog-categories.manage')->name('categories');
        Route::get('/categories/create', AddEditBlogCategory::class)->middleware('admin.permission:content.blog-categories.manage')->name('categories.create');
        Route::get('/categories/{blogCategory}/edit', AddEditBlogCategory::class)->middleware('admin.permission:content.blog-categories.manage')->name('categories.edit');
        Route::get('/posts', BlogPostsList::class)->middleware('admin.permission:content.blog-posts.view,content.blog-posts.manage')->name('posts');
        Route::get('/posts/create', AddEditBlogPost::class)->middleware('admin.permission:content.blog-posts.manage')->name('posts.create');
        Route::get('/posts/{post}/edit', AddEditBlogPost::class)->middleware('admin.permission:content.blog-posts.manage')->name('posts.edit');
    });

    Route::prefix('domains')->name('domains.')->group(function () {
        Route::get('/requests', DomainRequestsList::class)->middleware('admin.permission:domains.requests.view,domains.requests.manage')->name('requests');
        Route::get('/dns-records', DnsRecordsList::class)->middleware('admin.permission:domains.dns.view,domains.dns.manage')->name('dns-records');
    });

    Route::prefix('pages')->name('pages.')->group(function () {
        Route::get('/', StaticPagesList::class)->middleware('admin.permission:content.pages.view,content.pages.manage')->name('index');
        Route::get('/create', AddEditStaticPage::class)->middleware('admin.permission:content.pages.manage')->name('create');
        Route::get('/{staticPage}/edit', AddEditStaticPage::class)->middleware('admin.permission:content.pages.manage')->name('edit');
    });

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/general', GeneralSettingsPage::class)->middleware('admin.permission:settings.general.manage')->name('general');
        Route::get('/home-variants', HomeVariantsPage::class)->middleware('admin.permission:settings.home-variants.manage')->name('home-variants');
        Route::get('/templates', TemplateControlPage::class)->middleware('admin.permission:settings.templates.manage')->name('templates');
        Route::get('/blade-themes', BladeThemeQueuePage::class)->middleware('admin.permission:settings.templates.manage')->name('blade-themes');
        Route::get('/template-parts', TemplatePartsPage::class)->middleware('admin.permission:settings.templates.manage')->name('template-parts');
        Route::get('/template-parts/create/{templateId?}', AddEditTemplatePart::class)->middleware('admin.permission:settings.templates.manage')->name('template-parts.create');
        Route::get('/template-parts/{partId}/edit', AddEditTemplatePart::class)->middleware('admin.permission:settings.templates.manage')->name('template-parts.edit');
        Route::get('/template-parts/{templateId}', TemplatePartsPage::class)->middleware('admin.permission:settings.templates.manage')->name('template-parts.show');
        Route::get('/payment-gateways', PaymentGatewaysPage::class)->middleware('admin.permission:settings.payment-gateways.manage')->name('payment-gateways');
        Route::get('/payment-gateways/{gateway}/edit', AddEditPaymentGateway::class)->middleware('admin.permission:settings.payment-gateways.manage')->name('payment-gateways.edit');
        Route::get('/payment-gateway-limits', PaymentGatewayLimitsPage::class)->middleware('admin.permission:settings.payment-gateway-limits.manage')->name('payment-gateway-limits');
        Route::get('/email-templates', EmailTemplatesPage::class)->middleware('admin.permission:settings.email-templates.manage')->name('email-templates');
        Route::get('/email-templates/create', AddEditEmailTemplate::class)->middleware('admin.permission:settings.email-templates.manage')->name('email-templates.create');
        Route::get('/email-templates/{emailTemplate}/edit', AddEditEmailTemplate::class)->middleware('admin.permission:settings.email-templates.manage')->name('email-templates.edit');
        Route::get('/email-configuration', EmailConfigurationPage::class)->middleware('admin.permission:settings.email-configuration.manage')->name('email-configuration');
        Route::get('/currencies', CurrenciesPage::class)->middleware('admin.permission:settings.currencies.view,settings.currencies.manage')->name('currencies');
        Route::get('/currencies/create', AddEditCurrency::class)->middleware('admin.permission:settings.currencies.manage')->name('currencies.create');
        Route::get('/currencies/{currency}/edit', AddEditCurrency::class)->middleware('admin.permission:settings.currencies.manage')->name('currencies.edit');
        Route::get('/languages', LanguagesPage::class)->middleware('admin.permission:settings.languages.view,settings.languages.manage')->name('languages');
        Route::get('/language-purchases', LanguagePurchasesPage::class)->middleware('admin.permission:billing.payment-logs.view,settings.languages.purchases.view')->name('language-purchases');
        Route::get('/languages/create', [LanguageController::class, 'create'])->middleware('admin.permission:settings.languages.manage')->name('languages.create');
        Route::post('/languages', [LanguageController::class, 'store'])->middleware('admin.permission:settings.languages.manage')->name('languages.store');
        Route::get('/languages/{language}/edit', [LanguageController::class, 'edit'])->middleware('admin.permission:settings.languages.manage')->name('languages.edit');
        Route::put('/languages/{language}', [LanguageController::class, 'update'])->middleware('admin.permission:settings.languages.manage')->name('languages.update');
        Route::get('/translations', TranslationsPage::class)->middleware('admin.permission:settings.languages.view,settings.languages.manage')->name('translations');
        Route::get('/countries', CountriesPage::class)->middleware('admin.permission:settings.countries.view,settings.countries.manage')->name('countries');
        Route::get('/maintenance', MaintenanceModePage::class)->middleware('admin.permission:settings.maintenance.manage')->name('maintenance');
        Route::get('/default-banners', DefaultBannersIndexPage::class)->middleware('admin.permission:settings.default-banners.manage')->name('default-banners');
        Route::get('/default-banners/list/{countryId?}', DefaultBannersListPage::class)->middleware('admin.permission:settings.default-banners.manage')->name('default-banners.list');
        Route::get('/ai-logo-limit', AiLogoLimitPage::class)->middleware('admin.permission:settings.ai-logo-limits.manage')->name('ai-logo-limit');
        Route::get('/translation-key-access', TranslationKeyAccessPage::class)->middleware('admin.permission:settings.languages.manage')->name('translation-key-access');
        Route::get('/return-policy', \App\Livewire\Admin\Setting\ReturnPolicySettingsPage::class)->middleware('admin.permission:settings.general.manage')->name('return-policy');
    });

    Route::prefix('admins')->name('admins.')->group(function () {
        Route::get('/', AdminsList::class)->middleware('admin.permission:admins.users.view,admins.users.manage')->name('index');
        Route::get('/roles-permissions', RolesPermissionsList::class)->middleware('admin.permission:admins.roles.view,admins.roles.manage')->name('roles-permissions');
    });

    Route::prefix('faqs')->name('faqs.')->group(function () {
        Route::get('/', FaqsList::class)->middleware('admin.permission:content.faqs.view,content.faqs.manage')->name('index');
        Route::get('/create', AddEditFaq::class)->middleware('admin.permission:content.faqs.manage')->name('create');
        Route::get('/{faq}/edit', AddEditFaq::class)->middleware('admin.permission:content.faqs.manage')->name('edit');
    });

    Route::prefix('newsletter')->name('newsletter.')->group(function () {
        Route::get('/', NewsletterSubscribersList::class)->middleware('admin.permission:content.newsletter.view,content.newsletter.manage')->name('index');
    });

    Route::prefix('cache')->name('cache.')->group(function () {
        Route::get('/tenants', TenantsCachePage::class)->middleware('admin.permission:system.cache.manage')->name('tenants');
        Route::get('/main', MainCachePage::class)->middleware('admin.permission:system.cache.manage')->name('main');
    });

    Route::get('/notifications', NotificationsPage::class)->name('notifications.index');

    Route::prefix('support')->name('support.')->group(function () {
        Route::get('/', AdminTicketsList::class)->middleware('admin.permission:support.tickets.view,support.tickets.manage')->name('index');
        Route::get('/{ticketId}', AdminTicketDetail::class)->middleware('admin.permission:support.tickets.view,support.tickets.manage')->name('show');
    });

    Route::prefix('product-requests')->name('product-requests.')->group(function () {
        Route::get('/', \App\Livewire\Admin\ProductRequest\RequestsList::class)
             ->middleware('admin.permission:catalog.product-requests.view,catalog.product-requests.manage')
             ->name('index');
        Route::get('/{requestId}', \App\Livewire\Admin\ProductRequest\RequestDetail::class)
             ->middleware('admin.permission:catalog.product-requests.view,catalog.product-requests.manage')
             ->name('show');
    });

    Route::prefix('affiliates')->name('affiliates.')->middleware('admin.permission:affiliates.manage')->group(function () {
        Route::get('/',            \App\Livewire\Admin\Affiliate\AffiliatesListPage::class)->name('index');
        Route::get('/conversions', \App\Livewire\Admin\Affiliate\ConversionsListPage::class)->name('conversions');
        Route::get('/payouts',     \App\Livewire\Admin\Affiliate\PayoutsListPage::class)->name('payouts');
        Route::get('/reports',     \App\Livewire\Admin\Affiliate\AffiliateReportsPage::class)->name('reports');
        Route::get('/coupons',     \App\Livewire\Admin\Affiliate\AffiliateCouponsPage::class)->name('coupons');
    });
});

Route::fallback(NotFoundPage::class)->name('website.not-found');

Route::post('upload-files', function (Request $request) {
    $request->validate([
        'file' => ['required'],
    ]);

    $path = $request->file('file')->storeAs('uploads', $request->file('file')->getClientOriginalName(), 'public');

    return response()->json([
        'path' => $path,
        'file' => url(Storage::url($path))
    ]);
})->name('upload-files');

Route::get('prod', function () {
    for ($i = 0; $i <= 50; $i++) {
        Artisan::call('neozena:import', ['--page' => $i, '--only-page' => true]);
    }
});

// Route::get('apply-tenant-profit', function () {
//     ApplyTenantProfitPercentageJob::dispatch(Tenant::first()->id);
// });
