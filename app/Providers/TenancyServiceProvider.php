<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Middleware\InitializeTenancyByDomainForLivewire;
use App\Http\Middleware\InitializeTenancyBySlug;
use App\Repositories\Tenant\StorefrontRepository;
use App\View\Composers\StorefrontComposer;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Features\SupportFileUploads\FilePreviewController;
use Livewire\Livewire;
use Stancl\JobPipeline\JobPipeline;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Jobs;
use Stancl\Tenancy\Listeners;
use Stancl\Tenancy\Middleware;

class TenancyServiceProvider extends ServiceProvider
{
    // By default, no namespace is used to support the callable array syntax.
    public static string $controllerNamespace = '';

    public function events()
    {
        return [
                // Tenant events
            Events\CreatingTenant::class => [],
            Events\TenantCreated::class => [
                JobPipeline::make([
                    Jobs\CreateDatabase::class,
                    Jobs\MigrateDatabase::class,
                    // Jobs\SeedDatabase::class,

                    // Your own jobs to prepare the tenant.
                    // Provision API keys, create S3 buckets, anything you want!

                ])->send(function (Events\TenantCreated $event) {
                    return $event->tenant;
                })->shouldBeQueued(false), // `false` by default, but you probably want to make this `true` for production.
            ],
            Events\SavingTenant::class => [],
            Events\TenantSaved::class => [],
            Events\UpdatingTenant::class => [],
            Events\TenantUpdated::class => [],
            Events\DeletingTenant::class => [],
            Events\TenantDeleted::class => [
                JobPipeline::make([
                    Jobs\DeleteDatabase::class,
                ])->send(function (Events\TenantDeleted $event) {
                    return $event->tenant;
                })->shouldBeQueued(false), // `false` by default, but you probably want to make this `true` for production.
            ],

                // Domain events
            Events\CreatingDomain::class => [],
            Events\DomainCreated::class => [],
            Events\SavingDomain::class => [],
            Events\DomainSaved::class => [],
            Events\UpdatingDomain::class => [],
            Events\DomainUpdated::class => [],
            Events\DeletingDomain::class => [],
            Events\DomainDeleted::class => [],

                // Database events
            Events\DatabaseCreated::class => [],
            Events\DatabaseMigrated::class => [],
            Events\DatabaseSeeded::class => [],
            Events\DatabaseRolledBack::class => [],
            Events\DatabaseDeleted::class => [],

                // Tenancy events
            Events\InitializingTenancy::class => [],
            Events\TenancyInitialized::class => [
                Listeners\BootstrapTenancy::class,
            ],

            Events\EndingTenancy::class => [],
            Events\TenancyEnded::class => [
                Listeners\RevertToCentralContext::class,
            ],

            Events\BootstrappingTenancy::class => [],
            Events\TenancyBootstrapped::class => [],
            Events\RevertingToCentralContext::class => [],
            Events\RevertedToCentralContext::class => [],

                // Resource syncing
            Events\SyncedResourceSaved::class => [
                Listeners\UpdateSyncedResource::class,
            ],

                // Fired only when a synced resource is changed in a different DB than the origin DB (to avoid infinite loops)
            Events\SyncedResourceChangedInForeignDatabase::class => [],
        ];
    }

    public function register()
    {
        //
    }

    public function boot()
    {
        $this->livewireRoutes();
        $this->bootEvents();
        $this->mapRoutes();
        $this->registerStorefrontComposer();

        $this->makeTenancyMiddlewareHighestPriority();
    }

    /**
     * Register the view composer for all theme views after tenancy boots
     * (so the tenant DB connection is available when the composer runs).
     */
    protected function registerStorefrontComposer(): void
    {
        Event::listen(Events\TenancyBootstrapped::class, function () {
            // Re-bind as a fresh singleton each time tenancy initialises (once per request).
            // This ensures the in-request $memo cache is isolated per request while the
            // same instance is shared across all themes.* view-composer calls on that request.
            app()->singleton(StorefrontRepository::class);

            View::composer('themes.*', StorefrontComposer::class);
        });
    }

    protected function livewireRoutes(): void
    {
        Livewire::setUpdateRoute(function ($handle, string $path) {
            // ── Path-based Livewire update (nogrgr.com/s/{slug}/livewire/update) ──
            // Registered FIRST so the route name ends with 'livewire.update' and
            // Livewire's findUpdateRoute() treats it as a custom update route.
            // The theme layout overrides data-update-uri to point here for path-
            // based pages; for subdomain/domain pages the default route below is used.
            Route::post('/s/{tenant}' . $path, $handle)
                ->middleware(['web', InitializeTenancyBySlug::class])
                ->name('tenant.path.livewire.update');

            // ── Domain/Subdomain-based Livewire update (store.nogrgr.com/livewire/update) ──
            return Route::post($path, $handle)
                ->middleware([
                    'web',
                    'universal',
                    InitializeTenancyByDomainForLivewire::class,
                ]);
        });

        FilePreviewController::$middleware = ['web', InitializeTenancyByDomainForLivewire::class, 'universal'];
    }

    protected function bootEvents()
    {
        foreach ($this->events() as $event => $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof JobPipeline) {
                    $listener = $listener->toListener();
                }

                Event::listen($event, $listener);
            }
        }
    }

    protected function mapRoutes()
    {
        $this->app->booted(function () {
            if (file_exists(base_path('routes/tenant.php'))) {
                Route::namespace(static::$controllerNamespace)
                    ->group(base_path('routes/tenant.php'));
            }

            // Path-based storefront routes (central domain: nogrgr.com/{slug}/…)
            if (file_exists(base_path('routes/tenant_path.php'))) {
                Route::namespace(static::$controllerNamespace)
                    ->group(base_path('routes/tenant_path.php'));
            }
        });
    }

    protected function makeTenancyMiddlewareHighestPriority()
    {
        $tenancyMiddleware = [
                // Even higher priority than the initialization middleware
            Middleware\PreventAccessFromCentralDomains::class,

            Middleware\InitializeTenancyByDomain::class,
            Middleware\InitializeTenancyBySubdomain::class,
            Middleware\InitializeTenancyByDomainOrSubdomain::class,
            Middleware\InitializeTenancyByPath::class,
            Middleware\InitializeTenancyByRequestData::class,
        ];

        foreach (array_reverse($tenancyMiddleware) as $middleware) {
            $this->app[\Illuminate\Contracts\Http\Kernel::class]->prependToMiddlewarePriority($middleware);
        }
    }
}
