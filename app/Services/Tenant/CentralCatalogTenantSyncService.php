<?php

namespace App\Services\Tenant;

use App\Jobs\SyncCentralProductToTenantJob;
use App\Jobs\SyncTenantSectionsJob;
use App\Models\AppSetting;
use App\Models\Category;
use App\Models\CentralCoupon;
use App\Models\CentralFlashSale;
use App\Models\Currency;
use App\Models\EmailTemplate;
use App\Models\Language;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StaticPage;
use App\Models\Tenant;
use App\Models\Template;
use App\Models\Variation;
use App\Repositories\Tenant\Catalog\BadgeCatalog;
use App\Repositories\Tenant\Catalog\LabelCatalog;
use App\Support\CacheVersion;

class CentralCatalogTenantSyncService
{
    public function __construct(protected TenantCatalogSyncService $tenantCatalogSyncService)
    {
    }

    protected function dispatchProductToTenants(Product $product, iterable $tenants): void
    {
        foreach ($tenants as $tenant) {
            // Dedup plan §2b: tenants reading products live from central get no copy.
            if ($this->tenantCatalogSyncService->skipsCentralSection($tenant, 'products')) {
                continue;
            }

            SyncCentralProductToTenantJob::dispatch($product->id, $tenant->id);
        }
    }

    public function syncAllTenants(array $sections): void
    {
        $this->dispatchToTenants(Tenant::query()->orderBy('id')->get(), $sections);
    }

    public function syncCategory(Category $category): void
    {
        $this->syncAllTenants(['categories', 'products']);
    }

    /**
     * Push a single product's own changes (price, stock, weight, details) to the
     * tenants that carry it, without re-syncing their entire product catalog.
     */
    public function syncProduct(Product $product): void
    {
        $this->dispatchProductToTenants($product, Tenant::query()->orderBy('id')->get());
    }

    /**
     * Dispatch a products-only sync to every tenant in $tenantIds.
     *
     * Call this after updating ProductTenantAssignment rows so that:
     *  - newly assigned tenants receive the product (is_tenant_owned = true)
     *  - already-assigned tenants get updated product details
     *  - removed tenants have the product cleaned up from their database
     *
     * @param string[] $tenantIds
     */
    public function syncProductToAssignedTenants(Product $product, array $tenantIds): void
    {
        $tenantIds = array_values(array_unique(array_filter($tenantIds)));

        if ($tenantIds === []) {
            return;
        }

        $tenants = Tenant::query()->whereIn('id', $tenantIds)->get();

        $this->dispatchProductToTenants($product, $tenants);
    }

    public function syncProductVariant(ProductVariant $variant): void
    {
        $product = $variant->product;

        if (!$product) {
            return;
        }

        $this->dispatchProductToTenants($product, Tenant::query()->orderBy('id')->get());
    }

    public function syncVariation(Variation $variation): void
    {
        $this->syncAllTenants(['products']);
    }

    public function syncSettings(): void
    {
        $this->syncAllTenants(['settings']);
    }

    public function syncLabels(): void
    {
        $this->syncAllTenants(['labels']);
    }

    public function syncFlashSales(): void
    {
        $this->syncAllTenants(['flash-sales']);
    }

    public function syncCoupons(): void
    {
        $this->syncAllTenants(['coupons']);
    }

    public function syncSharedResource(AppSetting|Currency|Language|PaymentGateway|Template|EmailTemplate|StaticPage $model): void
    {
        $sections = match (true) {
            $model instanceof AppSetting => ['settings'],
            $model instanceof Currency => ['currencies'],
            $model instanceof Language => ['languages'],
            $model instanceof PaymentGateway => ['payment-gateways'],
            $model instanceof Template => ['themes'],
            $model instanceof EmailTemplate => ['email-templates'],
            $model instanceof StaticPage => ['pages'],
        };

        $this->syncAllTenants($sections);
    }

    protected function dispatchToTenants(iterable $tenants, array $sections): void
    {
        $sections = array_values(array_unique(array_filter($sections)));

        // Dedup plan phase 2 / invariant 8: tenants reading badges / labels live from central get no sync job, so a
        // central change must invalidate their caches through the global version tags instead.
        $globalTags = array_values(array_intersect_key(
            [
                'badges' => BadgeCatalog::CACHE_TAG,
                'labels' => LabelCatalog::CACHE_TAG,
                'coupons' => \App\Repositories\Tenant\Catalog\CouponCatalog::CACHE_TAG,
                'flash-sales' => \App\Repositories\Tenant\Catalog\FlashSaleCatalog::CACHE_TAG,
            ],
            array_flip($sections),
        ));
        if ($globalTags !== []) {
            CacheVersion::bumpMany($globalTags);
        }

        foreach ($tenants as $tenant) {
            // Dedup plan §2b: drop the sections this tenant reads live from central (all on `sync` = unchanged).
            $tenantSections = array_values(array_filter(
                $sections,
                fn (string $section) => ! $this->tenantCatalogSyncService->skipsCentralSection($tenant, $section),
            ));

            if ($sections !== [] && $tenantSections === []) {
                continue;
            }

            SyncTenantSectionsJob::dispatch($tenant->id, $tenantSections);
        }
    }
}
