<?php

namespace App\Providers;

use App\Http\Middleware\ApplyStorefrontSessionContext;
use App\Http\Middleware\InitializeTenancyBySlug;
use App\Models\Category;
use App\Models\Currency;
use App\Models\EmailTemplate;
use App\Models\File;
use App\Models\Language;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StaticPage;
use App\Models\Template;
use App\Models\CentralFlashSale;
use App\Models\CentralCoupon;
use App\Models\TemplatePart;
use App\Models\Tenant\Order as TenantOrder;
use App\Models\Tenant\Transaction as TenantTransaction;
use App\Models\Variation;
use App\Observers\CentralCatalogSyncObserver;
use App\Observers\CentralCouponObserver;
use App\Observers\CentralFlashSaleObserver;
use App\Observers\CentralSharedTenantSyncObserver;
use App\Observers\CentralTemplatePartObserver;
use App\Observers\ProductFileObserver;
use App\Observers\TenantOrderObserver;
use App\Observers\TenantTransactionObserver;
use App\Eloquent\Relations\CachedBelongsTo;
use App\Services\Tenant\TemplateRegistryService;
use App\Services\Tenant\Templates\UploadedBladeTemplateStrategy;
use App\Translation\TenantTranslator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Livewire;
use SocialiteProviders\Apple\AppleExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TemplateRegistryService::class);

        // Swap Laravel's default translator for one that resolves tenant DB
        // overrides (Settings > Translations) before falling back to the lang
        // files, so every __()/@lang() call across the storefront and tenant
        // panel picks up AI/manual overrides with no per-call-site changes.
        $this->app->extend('translator', function ($translator, $app) {
            $tenantTranslator = new TenantTranslator($translator->getLoader(), $translator->getLocale());
            $tenantTranslator->setFallback($translator->getFallback());

            return $tenantTranslator;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        $this->configureSessionDomain();

        TemplateRegistryService::register('custom', UploadedBladeTemplateStrategy::class);

        Event::listen(SocialiteWasCalled::class, [AppleExtendSocialite::class, 'handle']);

        Livewire::component('storefront.add-to-cart-button', \App\Livewire\Tenant\Storefront\ThemeKit\AddToCartButton::class);
        Livewire::component('storefront.cart-icon', \App\Livewire\Tenant\Storefront\ThemeKit\CartIcon::class);

        // The central `/preview` route (tenant panel "Preview" button, admin
        // Templates page) renders the dedicated "preview" tenant's storefront
        // on the central domain by design (see InitializeTenancyForPreview).
        // Livewire's persistent middleware below runs before that route's own
        // middleware, so without this exception PreventAccessFromCentralDomains
        // aborts(404) on every /preview hit; that 404 is then redirected by the
        // global exception handler to the (also Livewire-persistent-middleware-
        // guarded) not-found route, aborting again and looping forever.
        PreventAccessFromCentralDomains::$abortRequest = function ($request, $next) {
            if ($request->is('preview')) {
                return $next($request);
            }

            abort(404);
        };

        Livewire::addPersistentMiddleware([
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
            ApplyStorefrontSessionContext::class,
                // Path-based tenancy (nogrgr.com/{slug}/…) – skips gracefully when
                // the {tenant} route parameter is absent (subdomain/domain requests).
            InitializeTenancyBySlug::class,
        ]);

        Category::observe(CentralCatalogSyncObserver::class);
        CentralCoupon::observe(CentralCouponObserver::class);
        CentralFlashSale::observe(CentralFlashSaleObserver::class);
        Currency::observe(CentralSharedTenantSyncObserver::class);
        EmailTemplate::observe(CentralSharedTenantSyncObserver::class);
        File::observe(ProductFileObserver::class);
        Language::observe(CentralSharedTenantSyncObserver::class);
        PaymentGateway::observe(CentralSharedTenantSyncObserver::class);
        Product::observe(CentralCatalogSyncObserver::class);
        ProductVariant::observe(CentralCatalogSyncObserver::class);
        StaticPage::observe(CentralSharedTenantSyncObserver::class);
        Template::observe(CentralSharedTenantSyncObserver::class);
        TemplatePart::observe(CentralTemplatePartObserver::class);
        TenantOrder::observe(TenantOrderObserver::class);
        TenantTransaction::observe(TenantTransactionObserver::class);
        Variation::observe(CentralCatalogSyncObserver::class);

        $this->app->terminating(fn() => CachedBelongsTo::flushCache());
    }

    protected function configureSessionDomain(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        $host = request()->getHost();

        if (!is_string($host) || $host === '') {
            return;
        }

        $baseCookie = (string) config('session.cookie', Str::slug((string) config('app.name', 'laravel')) . '-session');
        $hostCookie = Str::slug($host);

        config([
            'session.domain' => null,
            'session.cookie' => $baseCookie . '-' . $hostCookie,
        ]);
    }
}
