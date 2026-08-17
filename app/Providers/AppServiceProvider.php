<?php

namespace App\Providers;

use App\Http\Middleware\ApplyStorefrontSessionContext;
use App\Http\Middleware\InitializeTenancyBySlug;
use App\Models\Category;
use App\Models\Currency;
use App\Models\EmailTemplate;
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
use App\Observers\TenantOrderObserver;
use App\Observers\TenantTransactionObserver;
use App\Eloquent\Relations\CachedBelongsTo;
use App\Services\Tenant\TemplateRegistryService;
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

        Event::listen(SocialiteWasCalled::class, [AppleExtendSocialite::class, 'handle']);

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
